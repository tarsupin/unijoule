<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

/*
	// Prepare Values
	$amount = 0.55;
	$desc = "Purchasing a rare item.";
	
	// Attempt to Purchase Item (one-click purchase)
	$response = Credits::chargeInstant($uniID, $amount, $desc);
	
	if($response && isset($response['transactionID']))
	{
		echo "You have successfully purchased the item!";
	}
	
	var_dump($response);
*/

// If the proper information wasn't sent, exit the page
if(!isset($_GET['api']) or !$key = Network::key($_GET['site'])) { exit; }

// Interpret the data sent
$apiData = API::interpret($_GET['api'], $key, $_GET['salt'], $_GET['conf']);

// Prepare the site-specific record keeping
$api = array("site_handle" => $_GET['site']);

// Get the Auth ID
if(!$userData = User::get($apiData['uni_id'], "auth_id"))
{
	if(!$userData = UserAuth::silentRegister($apiData['uni_id']))
	{
		echo API::respond("An error has occurred with the account trying to process this transaction."); exit;
	}
}

// Update the User's Balance
$balance = AppCredits::subtract($userData['auth_id'], $apiData['uni_id'], $apiData['amount'], $apiData['desc'], $errorStr, $api);

if($errorStr)
{
	echo API::respond($errorStr); exit;
}

// Transaction ID
$apiData['balance'] = $balance;
$apiData['transactionID'] = $api['transactionID'];

// The API Succeeded, respond accordingly
echo API::respond($apiData);
