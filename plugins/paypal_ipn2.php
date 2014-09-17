<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Send an empty HTTP 200 OK response to acknowledge receipt of the notification 
header('HTTP/1.1 200 OK');

// Assign payment notification values to local variables
$item_name			= $_POST['item_name'];
$item_number		= $_POST['item_number'];
$payment_status		= $_POST['payment_status'];
$payment_amount		= $_POST['mc_gross'];
$payment_currency	= $_POST['mc_currency'];
$txn_id				= $_POST['txn_id'];
$receiver_email		= $_POST['receiver_email'];
$payer_email		= $_POST['payer_email'];
$uniID				= $_POST['custom'];

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

while (!feof($fp))
{
	// While not EOF
	$res = fgets($fp, 1024);	// Get the acknowledgement response
	
	// Response was valid, proceed with payment processing successful
	if (strcmp ($res, "VERIFIED") == 0)
	{
		// Possible processing steps for a payment include the following:
		
		// Check that the payment_status is Completed
		// Check that txn_id has not been previously processed
		// Check that receiver_email is your Primary PayPal email
		// Check that payment_amount/payment_currency are correct
		// Process payment
		
		// check whether the payment_status is Completed
		if($payment_status == "Completed")
		{
			// Credits Worth
			$credits = 0;
			
			if($payment_amount >= 20) { $credits = floor($payment_amount * 1.1); }
			elseif($payment_amount >= 5) { $credits = floor($payment_amount * 1); }
			
			// Get the Auth ID of the Uni-ID
			if($chkAuthID = (int) Database::selectValue("SELECT auth_id FROM users WHERE uni_id=? LIMIT 1", array($uniID)))
			{
				// Add the database result
				Database::query("INSERT INTO `credit_purchases` (auth_id, uni_id, txn_id, payment_status, email, amount_paid, date_paid, credits_provided) VALUES (?, ?, ?, ?, ?, ?, ?)", array($chkAuthID, $uniID, $txn_id, $payment_status, $payer_email, $payment_amount, time(), $credits));
				
				// Add to the user's credits (if applicable)
				if($credits > 0)
				{
					$transactionID = AppTransactions::add($chkAuthID, $uniID, $credits, "Purchased Credits.");
					$balance = AppTransactions::$recipientBalance;
					
					if($balance != false)
					{
						Database::query("UPDATE credit_purchases SET user_received=1 WHERE user_id=? LIMIT 1", array($uniID));
					}
				}
			}
		}
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
