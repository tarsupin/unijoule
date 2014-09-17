<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

-----------------------------------------
------ About the AppCredits Plugin ------
-----------------------------------------

This plugin tracks a user's credits. Credits are the virtual unit of money within the Uni system. It can be used as a means of exchange, such as if the user provides products or services through a site or trades it.


-------------------------------
------ Methods Available ------
-------------------------------

// Get the current number of credits of the user
$balance	= AppCredits::getBalance($authID)

*/

abstract class AppCredits {
	
	
/****** Plugin Variables ******/
	
	// Transaction Values
	public static $error = "";				// <str> The error associated with this credit update, if applicable.
	public static $userBalance = 0.00;		// <float> The balance of the user.
	public static $transactionID = 0;		// <int> The transaction ID of the last exchange.
	
	
/****** Check how many credits a user has ******/
	public static function getBalance
	(
		$authID		// <int> The Auth ID of the user to check the balance of.
	)				// RETURNS <mixed> The amount of credits the user has, FALSE on error.
	
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
}
