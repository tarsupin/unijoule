<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); } 

// Provide custom login details
// $loginResponse is provided here
/*
function custom_login($loginResponse)
{
	
}
*/

// Run the universal login script
require(SYS_PATH . "/controller/login.php");

// Redirect to the home page
header("Location: /");