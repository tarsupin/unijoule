<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in, otherwise return home
if(!Me::$loggedIn)
{
	Me::redirectLogin("/get-unijoules");
}

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Provide Free UniJoule (only available to local and development)
if(isset($_GET['alpha']) and $_GET['alpha'] == "free" and ENVIRONMENT != "production")
{
	if($balance < 9600)
	{
		AppTransactions::add(Me::$vals['auth_id'], Me::$id, 500.00, "Free UniJoule for Pre-Release");
		
		Alert::saveSuccess("Free UniJoule", 'Free windfall! Pre-release is great! Enjoy your UniJoule!');
	}
	else
	{
		Alert::saveSuccess("Enough UniJoule", "Okay, okay, let's not get crazy now.");
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

<h2 style="font-weight:normal;">Buy UniJoule &nbsp; &nbsp; <span style="color:#57c2c1;">$1.00 = 1.00 UniJoule</span></h2>

Converting USD to UniJoule is a 1:1 exchange. That is, $1.00 for 1.00 UniJoule.<br /><br />

<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="2J6QZSMJ24EVE">
<table>
<tr><td><input type="hidden" name="on0" value="UniJoule">UniJoule</td></tr><tr><td><select name="os0">
	<option value="5 UniJoule">5 UniJoule</option>
	<option value="10 UniJoule">10 UniJoule</option>
	<option value="15 UniJoule">15 UniJoule</option>
	<option value="20 UniJoule (+1 for free)">20 UniJoule (+1 for free)</option>
	<option value="30 UniJoule (+1.5 for free)">30 UniJoule (+1.5 for free)</option>
	<option value="50 UniJoule (+5 for free)">50 UniJoule (+5 for free)</option>
	<option value="100 UniJoule (+10 for free)">100 UniJoule (+10 for free)</option>
	<option value="200 UniJoule (+20 for free)">200 UniJoule (+20 for free)</option>
</select> </td></tr>
</table>

<br />
<input type="hidden" name="currency_code" value="USD">
<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_buynowCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>

<br /><br />* UniJoule (n): a unit of energy equal to the effort of a group of people working toward a unified goal.

</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");