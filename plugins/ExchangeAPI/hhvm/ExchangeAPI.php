<?hh if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

----------------------------
------ About this API ------
----------------------------

This API allows a user to automatically exchange credits with another user, such as for tips.


------------------------------
------ Calling this API ------
------------------------------
	
	$packet = array(
		"sender_id"			=> $sender['uni_id']		// UniID of the sender
	,	"recipient_id"		=> $recipient['uni_id']		// UniID of the recipient
	,	"unijoule"			=> 12.34					// Amount of credits to exchange
	,	"desc"				=> "Tip"					// Description of the exchange
	);
	
	Connect::to("unijoule", "ExchangeAPI", $packet);
	
	
[ Possible Responses ]
	TRUE if the exchange was successful
	FALSE if the exchange failed (such as not enough credits)
	
*/

class ExchangeAPI extends API {
	
	
/****** API Variables ******/
	public bool $isPrivate = true;			// <bool> TRUE if this API is private (requires an API Key), FALSE if not.
	public string $encryptType = "";			// <str> The encryption algorithm to use for response, or "" for no encryption.
	public array <int, str> $allowedSites = array();		// <int:str> the sites to allow the API to connect with. Default is all sites.
	public int $microCredits = 0;			// <int> The cost in microcredits (1/10000 of a credit) to access this API.
	public int $minClearance = 6;			// <int> The minimum clearance level required to use this API.
	
	
/****** Run the API ******/
	public function runAPI (
	): bool					// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// $this->runAPI()
	{
		// Make sure the appropriate values were provided
		if(!isset($this->data['sender_id']) or !isset($this->data['recipient_id']) or !isset($this->data['unijoule']))
		{
			return false;
		}
		
		// Sanitize Values
		$senderID = (int) $this->data['sender_id'];
		$recipientID = (int) $this->data['recipient_id'];
		
		$this->data['desc'] = isset($this->data['desc']) ? Sanitize::safeword($this->data['desc']) : '';
		
		// Check if the sender is registered on this site
		if(!$check = Database::selectValue("SELECT uni_id FROM users WHERE uni_id=? LIMIT 1", array($senderID)))
		{
			if(!User::silentRegister($senderID))
			{
				$this->alert = "The sender could not be not located.";
				return false;
			}
			
			if(!$check = Database::selectValue("SELECT uni_id FROM users WHERE uni_id=? LIMIT 1", array($senderID)))
			{
				$this->alert = "The sender could not be not located.";
				return false;
			}
		}
		
		// Check if the recipient is registered on this site
		if(!$check = Database::selectValue("SELECT uni_id FROM users WHERE uni_id=? LIMIT 1", array($recipientID)))
		{
			if(!User::silentRegister($recipientID))
			{
				$this->alert = "The recipient could not be not located.";
				return false;
			}
			
			if(!$check = Database::selectValue("SELECT uni_id FROM users WHERE uni_id=? LIMIT 1", array($recipientID)))
			{
				$this->alert = "The recipient could not be not located.";
				return false;
			}
		}
		
		// Run the Exchange
		$transactionID = AppTransactions::exchange($senderID, $recipientID, (float) $this->data['unijoule'], $this->data['desc'], $this->apiHandle);
		
		// Determine if there was an error or not - if so, set an alert
		if(AppTransactions::$error !== "")
		{
			$this->alert = AppTransactions::$error;
		}
		
		return $transactionID ? true : false;
	}
	
}