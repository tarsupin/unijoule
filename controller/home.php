<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Send logged in users to their account
if(Me::$loggedIn)
{
	header("Location: /my-account"); exit;
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
<h2>Welcome to UniJoule</h2>
<p>
	<strong>UniJoule is a virtual currency</strong>
	<br />It simplifies purchases throughout the entire UniFaction system; from purchasing ad space to tipping a user for a helpful comment.</p>

<p>
	People can convert other forms of money into UniJoule. It is a 1:1 exchange from USD to UniJoule, meaning $1.00 USD is equal to 1.00 UniJoule.
</p>

<p>
	<strong>Gift Cards</strong>
	<br />You can purchase gift cards with UniJoule. These gift cards can be shared with other people or used without the need for an account on UniFaction.
</p>';

echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");