<?hh if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

----------------------------------------------
------ About the AppTransactions Plugin ------
----------------------------------------------

This plugin manages transactions, or exchanges of credits. It keeps track of transactions that have been made so that they can be referenced for shopping, refunds, or for record keeping purposes.


-------------------------------
------ Methods Available ------
-------------------------------

list($sendBalance, $toBalance) = AppTransactions::exchange($senderUniID, $recipientUniID, $amount, $desc, [$siteHandle], [$senderAuthID], [$recipientAuthID])

*/

abstract class AppTransactions {
	
	
/****** Plugin Variables ******/
	
	// Plugin Values
	public static float $feeBase = 0.0034;	// <float> The base fee (1/3rd of a penny)
	public static float $feeMult = 0.017;		// <float> The multiplier (percentage) to apply to fees for each transaction.
	public static float $feeMax = 0.34;		// <float> The maximum fee (34 cents)
	
	// Transaction Values
	public static string $error = "";				// <str> The error associated with this transaction, if applicable.
	public static float $senderBalance = 0.00;	// <float> The balance of the sender (after the transaction is complete)
	public static float $recipientBalance = 0.00;	// <float> The balance of the recipient (after the transaction is complete)
	
	
/****** Exchange credits between two users ******/
	public static function exchange
	(
		int $senderUniID			// <int> The Uni-Profile of the user sending credits.
	,	int $recipientUniID			// <int> The Uni-Profile of the user receiving credits.
	,	float $amount					// <float> Sets how many credits to exchange.
	,	string $desc = ""				// <str> The description of the transaction, if applicable.
	,	string $siteHandle = ""		// <str> The site handle of the site responsible for this transaction.
	): int							// RETURNS <int> The Transaction ID, or 0 on failure.
	
	// $transactionID = AppTransactions::exchange(Me::$id, $recipientUniID, 2.50, "Sent Bob 2.5 credits.", [$siteHandle]);
	{
		self::reset();
		
		// Reject if the amount isn't a positive value
		if($amount <= 0)
		{
			self::$error = "Must send a positive amount"; return 0;
		}
		
		// Retrieve the AuthID of the Sender
		if(!$authSender = User::get($senderUniID, "auth_id"))
		{
			if(!$authSender = UserAuth::silentRegister($senderUniID))
			{
				self::$error = "Unable to identify the sender."; return 0;
			}
		}
		
		// Retrieve the AuthID of the Recipient
		if(!$authRecipient = User::get($recipientUniID, "auth_id"))
		{
			if(!$authRecipient = UserAuth::silentRegister($recipientUniID))
			{
				self::$error = "Unable to identify the recipient."; return 0;
			}
		}
		
		// Prepare Values
		$senderAuthID = $authSender['auth_id'];
		$recipientAuthID = $authRecipient['auth_id'];
		
		// Make sure the user isn't sending to themselves
		if($recipientAuthID == $senderAuthID)
		{
			self::$error = "Cannot send credits to yourself."; return 0;
		}
		
		// Determine the fee for this transaction
		$fee = self::calculateFee($amount);
		
		// Get the current amounts of each user (also confirms the users exist & creates their rows)
		if(!$senderBalance = AppCredits::getBalance($senderAuthID))
		{
			self::$error = "Insufficient credits on sender's account."; return 0;
		}
		
		$recipientBalance = AppCredits::getBalance($recipientAuthID);
		
		if($recipientBalance === false)
		{
			self::$error = "Recipient's balance encountered errors while processing."; return 0;
		}
		
		// Prepare Values
		self::$senderBalance = $senderBalance;
		self::$recipientBalance = $recipientBalance;
		
		// Make sure the sender has the appropriate amount
		if(self::$senderBalance < $amount)
		{
			self::$error = "Sender doesn't have enough credits."; return 0;
		}
		
		// Don't charge a fee if the sender cannot afford it
		if(self::$senderBalance < ($amount + $fee))
		{
			$fee = 0.00;
		}
		
		// Add the credits - if not successful, return false
		Database::startTransaction();
		
		$success1 = Database::query("UPDATE credits SET amount=amount-? WHERE auth_id=? LIMIT 1", array($amount + $fee, $senderAuthID));
		$success2 = Database::query("UPDATE credits SET amount=amount+? WHERE auth_id=? LIMIT 1", array($amount, $recipientAuthID));
		
		if(!$success1 or !$success2)
		{
			self::$error = "Couldn't process the transaction.";
			Database::endTransaction(false);
			return 0;
		}
		
		// Get the new balances
		self::$senderBalance -= ($amount + $fee);
		self::$recipientBalance += $amount;
		
		// Run the transaction
		Database::query("INSERT INTO `credits_transactions` (`sender_auth_id`, `sender_uni_id`, `recipient_auth_id`, `recipient_uni_id`, `amount`, `fee`, `date_exchange`, `site_handle`, `description`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)", array($senderAuthID, $senderUniID, $recipientAuthID, $recipientUniID, $amount, $fee, time(), $siteHandle, Sanitize::safeword($desc)));
		
		// Get the Transaction ID
		if(!$transactionID = Database::$lastID)
		{
			self::$error = "Error trying to interpret the transaction ID.";
			Database::endTransaction(false);
			return 0;
		}
		
		// Record the Transaction for the Sender
		if(!$success = AppRecords::record($senderAuthID, $transactionID, true, self::$senderBalance))
		{
			self::$error = "Recording sender's transaction was unsuccessful.";
			Database::endTransaction(false);
			return 0;
		}
		
		// Record the Transaction for the Recipient
		if(!$success = AppRecords::record($recipientAuthID, $transactionID, false, self::$recipientBalance))
		{
			self::$error = "Recording recipient's transaction was unsuccessful.";
			Database::endTransaction(false);
			return 0;
		}
		
		// Commit the transaction
		Database::endTransaction();
		
		return $transactionID;
	}
	
	
/****** The server grants credits to a user ******/
	public static function add
	(
		int $recipientAuthID	// <int> The Auth ID of the user receiving credits.
	,	int $recipientUniID		// <int> The Uni-Profile of the user receiving credits.
	,	float $amount				// <float> Sets how many credits to exchange.
	,	string $desc = ""			// <str> The description of the transaction, if applicable.
	,	string $siteHandle = ""	// <str> The site handle of the site responsible for this transaction.
	): int						// RETURNS <int> The Transaction ID, or 0 on failure.
	
	// $transactionID = AppTransactions::add($recipientAuthID, $recipientUniID, 2.50, "Received 2.50 from server.", $siteHandle);
	{
		self::reset();
		
		// Get the current amount (also confirms the user exists & creates the row)
		$userBalance = AppCredits::getBalance($recipientAuthID);
		
		if($userBalance === false)
		{
			self::$error = "User's balance encountered errors while processing."; return false;
		}
		
		// Add the credits - if not successful, return false
		Database::startTransaction();
		
		if(!$success = Database::query("UPDATE credits SET amount=amount+? WHERE auth_id=? LIMIT 1", array($amount, $recipientAuthID)))
		{
			self::$error = "Couldn't process the transaction.";
			Database::endTransaction(false);
			return 0;
		}
		
		// Get the new balance
		self::$recipientBalance = $userBalance + $amount;
		
		// Run the transaction
		Database::query("INSERT INTO `credits_transactions` (`recipient_auth_id`, `recipient_uni_id`, `amount`, `date_exchange`, `site_handle`, `description`) VALUES (?, ?, ?, ?, ?, ?)", array($recipientAuthID, $recipientUniID, $amount, time(), $siteHandle, Sanitize::safeword($desc)));
		
		// Get the Transaction ID
		if(!$transactionID = Database::$lastID)
		{
			self::$error = "Error trying to interpret the transaction ID.";
			Database::endTransaction(false);
			return 0;
		}
		
		// Record the Transaction
		if(!$success = AppRecords::record($recipientAuthID, $transactionID, false, self::$recipientBalance))
		{
			self::$error = "Recording transaction was unsuccessful.";
			Database::endTransaction(false);
			return 0;
		}
		
		// Commit the transaction
		Database::endTransaction();
		
		return $transactionID;
	}
	
	
/****** The server takes credits from a user ******/
	public static function subtract
	(
		int $senderAuthID		// <int> The Auth ID of the user spending credits.
	,	int $senderUniID		// <int> The Uni-Profile of the user spending credits.
	,	float $amount				// <float> Sets how many credits to exchange.
	,	string $desc = ""			// <str> The description of the transaction, if applicable.
	,	string $siteHandle = ""	// <str> The site handle of the site responsible for this transaction.
	,	bool $applyFee = true	// <bool> TRUE to apply a fee, FALSE to not apply a fee.
	): int						// RETURNS <int> The Transaction ID, or 0 on failure.
	
	// $transactionID = AppTransactions::subtract($senderAuthID, $senderUniID, 2.50, "Sent 2.50 to the server.", $siteHandle, [$applyFee]);
	{
		self::reset();
		
		// Get the current amount (also confirms the user exists & creates the row)
		self::$senderBalance = AppCredits::getBalance($senderAuthID);
		
		if(self::$senderBalance === false)
		{
			self::$error = "User's balance encountered errors while processing."; return 0;
		}
		
		// If the user doesn't have that many credits, reject the subtraction
		if(self::$senderBalance < $amount)
		{
			self::$error = "User does not have enough credits available."; return 0;
		}
		
		// Determine the fee for this transaction
		$fee = ($applyFee ? self::calculateFee($amount) : 0.00);
		
		// Don't charge a fee if the sender cannot afford it
		if(self::$senderBalance < ($amount + $fee))
		{
			$fee = 0.00;
		}
		
		// Subtract the credits - if not successful, return false
		Database::startTransaction();
		
		if(!$success = Database::query("UPDATE credits SET amount=amount-? WHERE auth_id=? LIMIT 1", array(($amount + $fee), $senderAuthID)))
		{
			self::$error = "Couldn't process the transaction.";
			Database::endTransaction(false);
			return 0;
		}
		
		// Get the new balances
		self::$senderBalance -= ($amount + $fee);
		
		// Run the transaction
		Database::query("INSERT INTO `credits_transactions` (`sender_auth_id`, `sender_uni_id`, `amount`, `fee`, `date_exchange`, `site_handle`, `description`) VALUES (?, ?, ?, ?, ?, ?, ?)", array($senderAuthID, $senderUniID, $amount, $fee, time(), $siteHandle, Sanitize::safeword($desc)));
		
		// Get the Transaction ID
		if(!$transactionID = Database::$lastID)
		{
			self::$error = "Error trying to interpret the transaction ID.";
			Database::endTransaction(false);
			return 0;
		}
		
		// Record the Transaction
		if(!$success = AppRecords::record($senderAuthID, $transactionID, true, self::$senderBalance))
		{
			self::$error = "Recording transaction was unsuccessful.";
			Database::endTransaction(false);
			return 0;
		}
		
		// Commit the transaction
		Database::endTransaction();
		
		return $transactionID;
	}
	
	
/****** Return a fee associated with a transaction amount ******/
	public static function calculateFee
	(
		float $amount				// <float> The amount of credits to consider a fee for.
							// (adjustments to the fee cost, such as different modes)
	): float						// RETURNS <float> The fee amount.
	
	// $fee = AppTransactions::calculateFee($amount);
	{
		// Calculate the fee for a transaction
		$fee = self::$feeBase + round($amount * self::$feeMult, 4, PHP_ROUND_HALF_UP);
		
		// Enforce Maximum Transaction Fee
		$fee = min($fee, self::$feeMax);
		
		return (float) $fee;
	}
	
	
/****** Reset the transaction variables ******/
	private static function reset (
	): void						// RETURNS <void>
	
	// self::reset();
	{
		// Prepare Values
		self::$error = "";
		self::$senderBalance = 0.00;
		self::$recipientBalance = 0.00;
	}
}