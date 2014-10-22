<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in, otherwise return home
if(!Me::$loggedIn)
{
	// Get the current URL
	$parseURL = URL::parse($_SERVER['REQUEST_URI']);
	
	Me::redirectLogin('/transaction/amount?' . $parseURL['query']);
}

// Prepare Values
$runTransaction = true;
$returnURL = "";
$trans = array();

// Make sure the necessary bundle of data was sent
if(!isset($_GET['d']))
{
	$runTransaction = false;
}
else
{
	// Extract the data
	$trans = json_decode(Decrypt::run(Credits::$transEncKey, $_GET['d']), true);
	
	// Make sure the extracted data is complete
	$required = array("uni_id", "title", "site", "api", "return_url");
	
	foreach($required as $req)
	{
		// If the required data is not sent, the transaction cannot be run
		if(!isset($trans[$req]))
		{
			Alert::error("Requires More Data", "Some data required for this transaction was not provided.");
			$runTransaction = false;
			
			break;
		}
	}
	
	// Set the Return URL
	if(isset($trans["return_url"]))
	{
		$returnURL = Sanitize::url($trans['return_url']);
	}
}

// Run Global Script
require(APP_PATH . "/includes/global.php");


// Get credit balance of the user
// $balance = AppCredits::getBalance(Me::$vals['auth_id']);
// Note: we have $balance from the global script

// Prepare Values
$defaultAmount = 0.00;
$creditNote = "";
$fixedAmount = 0;
$applyFee = (isset($trans['no_fee']) ? false : true);

// Display the Transaction
if($runTransaction)
{
	// Make sure the user is allowed to load this transaction
	if($trans['uni_id'] and Me::$id != $trans['uni_id'])
	{
		$runTransaction = false;
		
		Alert::error("Invalid User", "You do not have permissions to view this transaction.", 10);
	}
	
	// Get the default amount
	if(isset($trans['def_amount']))
	{
		$defaultAmount = (float) $trans['def_amount'];
	}
	
	// If there is a minimum or maximum amount / range, provide a note about it
	if(isset($trans['min_amount']))
	{
		if(isset($trans['max_amount']))
		{
			// Check if there is a fixed amount (min and max costs are identical)
			if($trans['min_amount'] == $trans['max_amount'])
			{
				$fixedAmount = $trans['min_amount'];
				$defaultAmount = number_format($fixedAmount, 2);
			}
			
			$creditNote = '<div style="font-size:0.9em; margin-top:4px;">Note: Must process between ' . number_format($trans['min_amount']) . ' and ' . number_format($trans['max_amount']) . ' UniJoule.</div>';
		}
		else
		{
			$creditNote = '<div style="font-size:0.9em; margin-top:4px;">Note: Minimum of ' . number_format($trans['min_amount']) . ' UniJoule.</div>';
		}
	}
}

// Get the amount submitted, if applicable
if(!$fixedAmount and isset($_POST['amount']))
{
	$defaultAmount = (float) $_POST['amount'];
}

// Run the Form
if(Form::submitted("trans-amount-unijoule") and $runTransaction)
{
	// Prepare Values
	$amt = 0.00;
	
	if($fixedAmount)
	{
		$amt = $fixedAmount;
	}
	else if(isset($_POST['amount']))
	{
		$amt = (float) $_POST['amount'];
	}
	
	// Make sure the amount is in range
	if(!$amt)
	{
		Alert::error("Invalid Amount", "You must provide UniJoule for this value.");
	}
	else if(isset($trans['min_amount']) and $amt < $trans['min_amount'])
	{
		Alert::error("Invalid Amount", "The number of UniJoule provided is too low for this transaction.");
	}
	else if(isset($trans['max_amount']) and $amt > $trans['max_amount'])
	{
		Alert::error("Invalid Amount", "You have provided more UniJoule than can be sent for this transaction.");
	}
	
	// Prepare gift card usage
	$giftcardCode = $_POST['giftcard_code'] ? $_POST['giftcard_code'] : "";
	
	if($giftcardCode)
	{
		// Check if the gift card exists and has sufficient UniJoule
		$cardAmount = AppCredits::getGiftCardBalance($giftcardCode);
		
		if($cardAmount === false)
		{
			$giftcardCode = "";
			Alert::warning("Invalid Card", "That gift card code is invalid.");
		}
		else if($cardAmount < $amt)
		{
			$giftcardCode = "";
			Alert::warning("Low Funds on Card", "You do not have enough UniJoule on this gift card.");
		}
	}
	
	// Make sure you have enough UniJoule for this transaction (unless the giftcard didn't have enough)
	if(!$giftcardCode and $amt > $balance)
	{
		Alert::error("Not Enough UniJoule", "You do not have enough UniJoule to complete this transaction.");
	}
	
	// If all checks passed, you can submit the update
	if(FormValidate::pass())
	{
		// We now need to perform this transaction, and then send the data to the site through the designated API
		
		// We must confirm / deny that the transaction was successful.
		// If the transaction fails, it needs to reattempt the API again at a later time.
		
		// Prepare the Transaction Packet
		$packet = array(
			"uni_id"			=> Me::$id
		,	"amount"			=> (float) $amt
		);
		
		// Include any custom data that was sent
		if(isset($trans['custom']))
		{
			$packet['custom'] = $trans['custom'];
		}
		
		// Check if the transaction API was successful
		if($response = (bool) Connect::to($trans['site'], $trans['api'], $packet))
		{
			if($giftcardCode)
			{
				$transactionID = AppTransactions::subtractFromGiftCard($giftcardCode, $amt, Me::$vals['auth_id'], Me::$id, "Gift Card Transaction", $trans['site'], $applyFee);
			}
			else
			{
				// If the transaction API ran successfully, we can run the transaction on this site
				$transactionID = AppTransactions::subtract(Me::$vals['auth_id'], Me::$id, $amt, "Transaction", $trans['site'], $applyFee);
			}
			
			// Redirect back to the return URL
			header("Location: " . $trans['return_url']); exit;
		}
		else
		{
			Alert::error("Transaction Failure", "There was an error with the recipient site - the site may be offline.");
		}
	}
}

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

// Display Content
echo '
<div id="panel-right"></div>
<div id="content">'	. Alert::display();

// Display the Transaction
if($runTransaction)
{
	// Display the Transaction Info
	echo '
	<h3>' . $trans['title'] . '</h3>';
	
	if(isset($trans['desc']))
	{
		echo '
		<p>' . $trans['desc'] . '</p>';
	}
	
	// Prepare the Form
	echo '
	<form class="uniform" action="/transaction/amount?d=' . urlencode($_GET['d']) . '" method="post">' . Form::prepare("trans-amount-unijoule");
	
	// Display dropdown options if they are available
	if(isset($trans['amount_opts']) and is_array($trans['amount_opts']))
	{
		// Display the Dropdown
		echo '
		<div>
		<select name="amount">';
		
		foreach($trans['amount_opts'] as $opt)
		{
			echo '
			<option value="' . $opt . '"' . ($defaultAmount == $opt ? ' selected' : '') . '>' . number_format($opt, 2) . ' Credits</option>';
		}
		
		echo '
		</select>
		' . ($creditNote ? $creditNote : '') . '
		</div>';
	}
	
	// Display an amount input box if the dropdowns aren't set
	else
	{
		// If there is a fixed amount, require it
		if($fixedAmount)
		{
			echo '
			<div style="font-weight:bold; font-size:1.2em;">' . number_format($defaultAmount, 2) . ' UniJoule will be deducted from your account</div>';
		}
		else
		{
			echo '
			<div>
				<strong>Enter the number of UniJoule you wish to process:</strong><br />
				<input type="text" name="amount" value="' . ($defaultAmount ? number_format($defaultAmount, 2) : '') . '" maxlength="12" />
				' . ($creditNote ? $creditNote : '') . '
			</div>';
		}
	}
	
	echo '
		<p style="margin-top:22px;">
			<strong>[Optional] Pay with a UniJoule Gift Card</strong>
			<input type="text" name="giftcard_code" value="" placeholder="Enter your gift card code here . . ." style="width:95%;" maxlength="20" />
		</p>
		<p style="margin-top:22px;"><input type="submit" name="submit" value="Approve Purchase" /></p>
	</form>';
}

// If the transaction cannot be displayed, announce it
else
{
	echo '
	<h3>Transaction Unavailable</h3>
	<p>The proper data was not available for this transaction to run.</p>';
	
	if($returnURL)
	{
		echo '
		<p><a href="' . $returnURL . '">Return to ' . $returnURL . '</a></p>';
	}
}

echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");