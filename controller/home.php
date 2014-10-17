<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in, otherwise return home
if(!Me::$loggedIn)
{
	Me::redirectLogin("/");
}

// Get credit balance of the user
$balance = AppCredits::getBalance(Me::$vals['auth_id']);

// Prepare Values
$myHandle = Me::$vals['handle'];
$maxRows = 25;		// The number of rows to return in the recorded transaction table
$startPos = (isset($_POST['pos']) ? $_POST['pos'] + 0 : 0);		// The position to start at (for pagination)

if($startPos < 0) { $startPos = 0; }

// Check if form was submitted properly
if(Form::submitted("send-credits-frmcrd"))
{
	// Prepare Values
	$_POST['joules'] = (float) $_POST['joules'];
	
	// Validate the fields submitted
	FormValidate::number_float("UniJoules to Send", $_POST['joules'], 0.01, (float) $balance);
	
	if(!$userData = User::getDataByHandle($_POST['handle'], "auth_id, uni_id, handle"))
	{
		if(!$userData = UserAuth::silentRegister($_POST['handle']))
		{
			Alert::error("Recipient", "That recipient does not exist.");
		}
	}
	
	// Make sure the balance was valid
	if($balance === false)
	{
		Alert::error("Balance", "There was an error with the user's balance.", 2);
	}
	else if($balance <= 0)
	{
		Alert::error("Balance", "You have no UniJoules to send.", 2);
	}
	
	if(FormValidate::pass())
	{
		// Exchange the UniJoule
		AppTransactions::exchange(Me::$id, $userData['uni_id'], $_POST['joules'], "Sent UniJoules to " . $userData['handle']);
		
		if(AppTransactions::$error)
		{
			Alert::error("Transaction", AppTransactions::$error, 4);
		}
		else
		{
			Alert::saveSuccess("UniJoule Sent", "You have successfully sent " . number_format($_POST['joules'], 2) . " UniJoules to " . $userData['handle']);
			
			header("Location: /"); exit;
		}
	}
}

// Gather Transactions
$records = AppRecords::get(Me::$vals['auth_id']);

// Get User Handles from Transactions
$userSQL = "";
$userArray = array();
$userList = array(0 => "");

foreach($records as $record)
{
	if(!isset($userList[$record['recipient_uni_id']]))
	{
		$userList[$record['recipient_uni_id']] = "";
		$userSQL .= ($userSQL == "" ? "" : ", ") . "?";
		$userArray[] = $record['recipient_uni_id'];
	}
	
	if(!isset($userList[$record['sender_uni_id']]))
	{
		$userList[$record['sender_uni_id']] = "";
		$userSQL .= ($userSQL == "" ? "" : ", ") . "?";
		$userArray[] = $record['sender_uni_id'];
	}
}

if($userSQL != "")
{
	$scan = Database::selectMultiple("SELECT uni_id, handle FROM users WHERE uni_id IN (" . $userSQL . ")", $userArray);
	
	foreach($scan as $s)
	{
		$userList[(int) $s['uni_id']] = $s['handle'];
	}
}

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

// Display Content
echo '
<div id="panel-right"></div>
<div id="content">'	. Alert::display();

echo '
<style>
	table { border-right:solid 1px #e2e2e1; width:100%; text-align:left; }
	tr { }
	th { border-left:solid 1px #e2e2e1; color:white; background-color:#57c2c1; padding:6px 10px 6px 12px; }
	td { border-left:solid 1px #e2e2e1; color:#263a54; padding:6px 10px 6px 12px; font-size:0.85em; }
	
	tr:nth-child(odd) { background-color:#f8f8f7; }
	
	.desc { max-width:120px; }
</style>

<h3>Transactions</h3>

<table border="0" cellpadding="0" cellspacing="0">
	<tr>
		<th>Date</th>
		<th>From</th>
		<th>To</th>
		<th>Description</th>
		<th>Amount</th>
		<th>Balance</th>
	</tr>';

// Cycle through the records
$count = 0;
$curTime = time();
$curYear = date('Y', $curTime);

foreach($records as $record)
{
	if($count++ >= $maxRows) { break; }
	
	// Prepare the "From" and "To" Columns
	$from = $userList[$record['sender_uni_id']];
	$to = $userList[$record['recipient_uni_id']];
	
	$from = ($from == $myHandle ? "ME" : ($from == "" ? "SERVER" : '<a href="' . URL::unifaction_social() . '/' . $from . '">@' . $from . "</a>"));
	$to = ($to == $myHandle ? "ME" : ($to == "" ? "SERVER" : '<a href="' . URL::unifaction_social() . '/' . $to . '">@' . $to . "</a>"));
	
	// Display the Transactions
	echo '
	<tr>
		<td>' . date('M jS' . (date('Y', $record['date_exchange']) != $curYear ? ", Y" : ""), $record['date_exchange']) . '</td>
		<td>' . $from . '</td>
		<td>' . $to . '</td>
		<td class="desc">' . $record['description'] . '</td>
		<td>' . number_format($record['amount'], 2) . '</td>
		<td>' . number_format($record['running_total'], 2) . '</td>
	</tr>';
}

// If you have no records present
if(!$records)
{
	echo '
	<tr><td colspan="6">You have no transaction history at this time.</td></tr>';
}

echo '
</table>';

// Extra Pagination
echo '
<div style="margin-top:14px;text-align:right;">';

if($startPos > 0)
{
	echo '
	<a class="button" href="/?pos=' . max(0, $startPos - $maxRows) . '">Earlier Transactions</a>';
}

if(count($records) > $maxRows)
{
	echo '
	<a class="button" href="/?pos=' . ($startPos + $maxRows) . '">Older Transactions</a>';
}

echo '
</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");