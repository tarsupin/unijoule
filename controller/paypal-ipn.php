<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Send an empty HTTP 200 OK response to acknowledge receipt of the notification 
header('HTTP/1.1 200 OK');

// Assign payment notification values to local variables
$payment_status		= Sanitize::variable($_POST['payment_status']);
$payment_amount		= (float) $_POST['mc_gross'];
$payment_currency	= Sanitize::variable($_POST['mc_currency']);
$txn_id				= Sanitize::variable($_POST['txn_id']);
$receiver_email		= Sanitize::variable($_POST['receiver_email'], "+-@.");
$payer_email		= Sanitize::variable($_POST['payer_email'], "+-@.");
$custom				= json_decode($_POST['custom'], true);

// Prepare Values
$uniID = (isset($custom['uni_id']) ? (int) $custom['uni_id'] : 0);	// {"uni_id":1}
$giftcardCode = "";

$debugValue = mt_rand(0, 50);

// Prepare the Gift Card
if(isset($custom['type']) and isset($custom['code']) and $custom['type'] == "giftcard")
{
	$giftcardCode = $custom['code'];
}

// Build the required acknowledgement message out of the notification just received
$req = 'cmd=_notify-validate';    // Add 'cmd=_notify-validate' to beginning of the acknowledgement

// Loop through the notification NV pairs
foreach ($_POST as $key => $value)
{
	$value = urlencode(stripslashes($value));  // Encode these values
	$req  .= "&$key=$value";                   // Add the NV pairs to the acknowledgement
}

// Set up the acknowledgement request headers
$header  = "POST /cgi-bin/webscr HTTP/1.1\r\n";                    // HTTP POST request
$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";

// Open a socket for the acknowledgement request
$fp = fsockopen('ssl://www.sandbox.paypal.com', 443, $errno, $errstr, 30);

// Send the HTTP POST request back to PayPal for validation
fputs($fp, $header . $req);
Debug::file($debugValue . "a", json_encode($_POST));
while (!feof($fp))
{
	// While not EOF
	$res = fgets($fp, 1024);	// Get the acknowledgement response
	Debug::file($debugValue . "res" . mt_rand(0, 10), json_encode(array($res)));
	// Response was valid, proceed with payment processing successful
	if(strcmp ($res, "VERIFIED") == 0)
	{
		// Can check for txn_type here.
		// https://developer.paypal.com/docs/classic/ipn/integration-guide/IPNandPDTVariables/
		Debug::file($debugValue . "b", json_encode(array($uniID, $giftcardCode, $txn_id, $payment_status, $payment_amount)));
		// Check that the payment_status is Completed
		if($payment_status == "Completed")
		{
			// Check that txn_id has not been previously processed
			//
			
			// Check that receiver_email is your Primary PayPal email
			//
			
			// Check that payment_amount/payment_currency are correct
			//
			
			// Credits Worth
			$credits = 0;
			
			if($payment_amount >= 50)
			{
				$credits = (float) $payment_amount * 1.10;
			}
			else if($payment_amount >= 20)
			{
				$credits = (float) $payment_amount * 1.05;
			}
			else if($payment_amount >= 5)
			{
				$credits = (float) $payment_amount * 1;
			}
			
			// If the user is purchasing a gift card
			if($giftcardCode)
			{
				Database::startTransaction();
				
				// Record the purchase
				if($pass = Database::query("INSERT INTO `credits_purchases` (uni_id, txn_id, payment_status, email, amount_paid, date_paid, credits_provided) VALUES (?, ?, ?, ?, ?, ?, ?)", array($uniID, $txn_id, $payment_status, $payer_email, $payment_amount, time(), $credits)))
				{
					// Make sure no other gift card has the same code
					if(!Database::selectValue("SELECT giftcard_code FROM credits_giftcards WHERE giftcard_code=? LIMIT 1", array($giftcardCode)))
					{
						// Add a Gift Card
						$pass = Database::query("INSERT INTO credits_giftcards (giftcard_code, uni_id, credits, email, date_purchased) VALUES (?, ?, ?, ?, ?)", array($giftcardCode, $uniID, $credits, $payer_email, time()));
					}
					else
					{
						$pass = false;
					}
				}
				
				Database::endTransaction($pass);
			}
			
			// If the user is adding UniJoule to their account
			else if($uniID)
			{
				Database::startTransaction();
				$track1 = "not here";
				// Record the purchase
				if($pass = Database::query("INSERT INTO `credits_purchases` (uni_id, txn_id, payment_status, email, amount_paid, date_paid, credits_provided) VALUES (?, ?, ?, ?, ?, ?, ?)", array($uniID, $txn_id, $payment_status, $payer_email, $payment_amount, time(), $credits)))
				{
					$track1 = (string) $pass;
					// Add to the user's credits
					$transactionID = AppTransactions::add($uniID, $credits, "Purchased Credits.");
					
					if(AppTransactions::$recipientBalance == false)
					{
						$pass = false;
					}
				}
				Debug::file($debugValue . "c", json_encode(array($uniID, $track1, $transactionID)));
				Database::endTransaction($pass);
			}
		}
		
		/*
			Other payment statuses possible:
			
			Canceled_Reversal
			Denied
			Expired
			Failed
			In-Progress
			Partially_Refunded
			Pending
			Processed
			Refunded
			Reversed
			Voided
		*/
	}
	
	// The request was invalid
	else if (strcmp ($res, "INVALID") == 0)
	{
		// Authentication protocol is complete - begin error handling
		
		/*
			$mail_From    = "IPN@example.com";
			$mail_To      = "Your-eMail-UniqueID";
			$mail_Subject = "INVALID IPN";
			$mail_Body    = $req;
			
			mail($mail_To, $mail_Subject, $mail_Body, $mail_From);
		*/
	}
}

fclose($fp);  // Close the file
