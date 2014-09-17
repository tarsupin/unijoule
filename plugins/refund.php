<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

/*
	// Prepare Values
	$desc = "Refund ID-" . $transactionID;
	
	// Attempt to Purchase Item
	$response = Credits::refundInstant($transactionID, $userID, $desc, $errorStr);
	
	if($response && isset($response['transactionID']))
	{
		echo "You have successfully refunded this transaction!";
	}
	
	var_dump($response);
*/

// If the proper information wasn't sent, exit the page
if(!isset($_GET['api']) or !$key = Network::key($_GET['site'])) { exit; }

// Interpret the data sent
$apiData = API::interpret($_GET['api'], $key, $_GET['salt'], $_GET['conf']);

// Recognize Integers
$apiData['uni_id'] = (int) $apiData['uni_id'];
$apiData['transactionID'] = (int) $apiData['transactionID'];

// Get the Auth ID
if(!$auth = User::get($apiData['uni_id'], "auth_id"))
{
	echo API::respond("That user must sign in to the credit system to use credits."); exit;
}

// Recognize Integers
$auth['auth_id'] = (int) $auth['auth_id'];

// Handle the Refund
if(!$getRecord = Database::selectOne("SELECT id, amount, date_exchange, was_refunded FROM credits_records WHERE id=? AND auth_id=? AND site_handle=? LIMIT 1", array($apiData['transactionID'], $auth['auth_id'], $_GET['site'])))
{
	echo API::respond("This transaction could not be recovered."); exit;
}

if($getRecord['was_refunded'] != 0)
{
	echo API::respond("This transaction has already been refunded to the user."); exit;
}

if($getRecord['date_exchange'] < time() - (60 * 15))
{
	echo API::respond("The refund time limit has been passed."); exit;
}

if($getRecord['amount'] > 0)
{
	echo API::respond("The amount cannot be refunded since it was a positive gain."); exit;
}

$getRecord['amount'] = (float) abs($getRecord['amount']);

// Update the Record to reflect that it's being refunded
Database::startTransaction();
Database::query("UPDATE credits_records SET was_refunded=? WHERE id=? LIMIT 1", array(1, $getRecord['id']));

// Prepare the site-specific record keeping
$api = array("site_handle" => $_GET['site']);

// Update the User's Balance
$transactionID = AppTransactions::add($auth['auth_id'], $apiData['uni_id'], $getRecord['amount'], $apiData['desc'], SITE_HANDLE);
$balance = AppTransactions::$recipientBalance;

if($errorStr)
{
	echo API::respond($errorStr); exit;
}

// Check if the user gets a refund fee
$checkRefunds = Database::selectOne("SELECT COUNT(*) as c FROM credits_records WHERE auth_id=? AND date_exchange >= ? AND was_refunded=? LIMIT 1", array($auth['auth_id'], (time() - (3600 * 8)), 1));

if(isset($checkRefunds['c']) && $checkRefunds['c'] > 2)
{
	// Create a micro-fee for doing 3+ refunds in 8 hours.
	$microFee = max(0.08, min(0.45, round($getRecord['amount'] / 35, 2)));
	
	$balance = AppCredits::subtract($auth['auth_id'], $apiData['uni_id'], $microFee, "Micro-Fee: 3+ refunds within 8 hours", $errorStr, $api);
	
	$apiData['refundFee'] = $microFee;
}

$apiData['balance'] = $balance;
$apiData['amount'] = $getRecord['amount'];

Database::endTransaction();

// The API Succeeded, respond accordingly
echo API::respond($apiData);
