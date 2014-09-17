<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in, otherwise return home
if(!Me::$loggedIn)
{
	Me::redirectLogin("/get-credits");
}

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Provide Free Credits (only available to local and development)
if(isset($_GET['alpha']) and $_GET['alpha'] == "free" and ENVIRONMENT != "production")
{
	if($balance < 9600)
	{
		AppTransactions::add(Me::$vals['auth_id'], Me::$id, 500.00, "Free Credits for Pre-Release");
		
		Alert::saveSuccess("Free Credits", 'Free windfall! Pre-release is great! Enjoy your credits!');
	}
	else
	{
		Alert::saveSuccess("Enough Credits", "Okay, okay, let's not get crazy now.");
	}
	
	header("Location: /"); exit;
}

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

// Content
echo '
<div id="panel-right"></div>
<div id="content">' . Alert::display() . '

<h2 style="font-weight:normal;">Buy Credits &nbsp; &nbsp; <span style="color:#57c2c1;">$1.00 = 1.00 Credits</span></h2>

<p>
	5% free credits with $20 or more<br />
	10% free credits with $50 or more
</p>

<img src="/assets/images/paypal_cards.png" />

</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");