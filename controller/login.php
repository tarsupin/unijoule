<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); } 

// Provide custom login details
// $loginResponse is provided here, which includes Auth's auth_id if this site requires it
function custom_login($loginResponse)
{
	// On the first login, set the Auth ID
	if(Me::$vals['auth_id'] == 0)
	{
		Database::startTransaction();
		
		if($pass = Database::query("UPDATE users SET auth_id=? WHERE uni_id=? LIMIT 1", array($loginResponse['auth_id'], Me::$id)))
		{
			$pass = UserAuth::addUser($loginResponse['auth_id'], $loginResponse['uni_id'], $loginResponse['handle']);
		}
		
		Database::endTransaction($pass);
	}
}

// Run the universal login script
require(SYS_PATH . "/controller/login.php");

// Redirect to the home page
header("Location: /");