<?hh if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

----------------------------
------ About this API ------
----------------------------

This API allows users to purchase items on other sites using UniJoule.


------------------------------
------ Calling this API ------
------------------------------
	
	$packet = array(
		"uni_id"			=> $user['uni_id']		// UniID of the person purchasing something
	,	"amount"			=> 12.34				// Amount of unijoules to exchange
	,	"desc"				=> "Tip"				// Description of the exchange
	,	"refund_duration"	=> 600					// Duration during which you can refund (0 is no refunds)
	,	"apply_fee"			=> true					// TRUE to apply a purchase fee, FALSE if not
	);
	
	Connect::to("unijoule", "PurchaseAPI", $packet);
	
	
[ Possible Responses ]
	TRUE if the amount was successfully purchased.
	FALSE on failure.
	
*/

class PurchaseAPI extends API {
	
	
/****** API Variables ******/
	public bool $isPrivate = true;			// <bool> TRUE if this API is private (requires an API Key), FALSE if not.
	public string $encryptType = "";			// <str> The encryption algorithm to use for response, or "" for no encryption.
	public array <int, str> $allowedSites = array();		// <int:str> the sites to allow the API to connect with. Default is all sites.
	public int $microCredits = 0;			// <int> The cost in microcredits (1/10000 of a credit) to access this API.
	public int $minClearance = 6;			// <int> The minimum clearance level required to use this API.
	
	
/****** Run the API ******/
	public function runAPI (
	): bool					// RETURNS <bool> TRUE if the exchange passed successfully, FALSE on failure.
	
	// $this->runAPI()
	{
		// Make sure the appropriate values were provided
		if(!isset($this->data['uni_id']) or !isset($this->data['amount']))
		{
			return false;
		}
		
		// Prepare and Sanitize Values
		$uniID = (int) $this->data['uni_id'];
		$applyFee = (isset($this->data['apply_fee']) ? (bool) $this->data['apply_fee'] : false);
		
		$this->data['desc'] = isset($this->data['desc']) ? Sanitize::safeword($this->data['desc']) : '';
		
		//$this->data['refund_duration'] = isset($this->data['allow_refund']) ? (int) $this->data['allow_refund'] : 0;
		
		// Check if the user is registered on this site
		if(!$check = Database::selectValue("SELECT uni_id FROM users WHERE uni_id=? LIMIT 1", array($uniID)))
		{
			if(!User::silentRegister($uniID))
			{
				$this->alert = "The user could not be not located.";
				return false;
			}
			
			if(!$check = Database::selectValue("SELECT uni_id FROM users WHERE uni_id=? LIMIT 1", array($uniID)))
			{
				$this->alert = "The user could not be not located.";
				return false;
			}
		}
		
		// Run the Exchange
		$transactionID = AppTransactions::subtract($uniID, (float) $this->data['amount'], $this->data['desc'], $this->apiHandle, $applyFee);
		
		// Determine if there was an error or not - if so, set an alert
		if(AppTransactions::$error !== "")
		{
			$this->alert = AppTransactions::$error;
		}
		
		return $transactionID ? true : false;
	}
	
}