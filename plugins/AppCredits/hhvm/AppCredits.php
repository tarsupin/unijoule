<?hh if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

-----------------------------------------
------ About the AppCredits Plugin ------
-----------------------------------------

This plugin tracks a user's credits. Credits are the virtual unit of money within the Uni system. It can be used as a means of exchange, such as if the user provides products or services through a site or trades it.


-------------------------------
------ Methods Available ------
-------------------------------

// Get the current number of UniJoule of a User
$balance = AppCredits::getBalance($authID);

// Get the current number of UniJoule available in a gift card
$amount = AppCredits::getGiftCardBalance($giftcardCode);

*/

abstract class AppCredits {
	
	
/****** Plugin Variables ******/
	
	// Transaction Values
	public static string $error = "";				// <str> The error associated with this credit update, if applicable.
	public static float $userBalance = 0.00;		// <float> The balance of the user.
	public static int $transactionID = 0;		// <int> The transaction ID of the last exchange.
	
	
/****** Check how many UniJoule a user has ******/
	public static function getBalance
	(
		int $authID		// <int> The Auth ID of the user to check the balance of.
	): mixed				// RETURNS <mixed> The amount of UniJoule the user has, FALSE on error.
	
	// $amount = AppCredits::getBalance(Me::$vals['auth_id']);
	{
		// Make sure the Auth ID is set
		if($authID == 0) { return false; }
		
		// Gather the credit data
		if(!$fetchCredits = Database::selectOne("SELECT amount FROM credits WHERE auth_id=? LIMIT 1", array($authID)))
		{
			// If nothing was recovered, create the user's credits row
			if(!$success = Database::query("INSERT IGNORE INTO credits (auth_id, amount) VALUES (?, ?)", array($authID, 0)))
			{
				return false;
			}
			
			$fetchCredits = array("amount" => 0.00);
		}
		
		return (float) $fetchCredits['amount'];
	}
	
	
/****** Check how many UniJoule a Gift Card has ******/
	public static function getGiftCardBalance
	(
		string $giftcardCode		// <str> The code assigned to the gift card.
	): mixed						// RETURNS <mixed> The amount of UniJoule the gift card has, FALSE on error.
	
	// $amount = AppCredits::getGiftCardBalance($giftcardCode);
	{
		$results = Database::selectOne("SELECT giftcard_code, credits FROM credits_giftcards WHERE giftcard_code=? LIMIT 1", array($giftcardCode));
		
		if(isset($results['giftcard_code']))
		{
			return (float) $results['credits'];
		}
		
		return false;
	}
	
	
/****** Retrieve Gift Card Data ******/
	public static function getGiftCardData
	(
		string $giftcardCode		// <str> The code assigned to the gift card.
	): array <str, mixed>						// RETURNS <str:mixed> The data from a gift card.
	
	// $giftcardData = AppCredits::getGiftCardData($giftcardCode);
	{
		return Database::selectOne("SELECT * FROM credits_giftcards WHERE giftcard_code=? LIMIT 1", array($giftcardCode));
	}
}