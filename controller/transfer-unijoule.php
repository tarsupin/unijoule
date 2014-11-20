<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/transfer-unijoule");
}

// Run Action to Transfer
if(Form::submitted("transfer"))
{
	$_POST['account'] = Sanitize::variable($_POST['account'], ".");
	$_POST['password'] = trim($_POST['password']);
	
	if(!$userData = Database::selectOne("SELECT account, password FROM s4u_accounts WHERE account=? AND uni6_id=? LIMIT 1", array($_POST['account'], 0)))
	{
		Alert::error("Wrong Username", "The user " . $_POST['account'] . " does not exist on Uni5, or you have already transferred.");
	}
	else
	{
		// check password
		if($userData['password'] == sha1($_POST['password']))
		{
			// Check if the credits account has already been used
			if($check = Database::selectValue("SELECT used FROM s4u_account_credits WHERE account=? LIMIT 1", array($userData['account'])))
			{
				Alert::error("Already Transferred", "You have already transferred your UniJoule.");
			}
			else
			{
				Database::startTransaction();
				
				// Update the value as being set
				if($pass = Database::query("UPDATE s4u_accounts SET uni6_id=? WHERE account=? LIMIT 1", array(Me::$id, $userData['account'])))
				{
					// Update the credits as being taken
					if($pass = Database::query("UPDATE s4u_account_credits SET used=? WHERE account=? LIMIT 1", array(1, $userData['account'])))
					{
						$credits = (int) Database::selectValue("SELECT credits FROM s4u_account_credits WHERE account=? LIMIT 1", array($userData['account']));
						
						// Check if your credit value exists yet
						if($exists = Database::selectValue("SELECT uni_id FROM credits WHERE uni_id=? LIMIT 1", array(Me::$id)))
						{
							$pass = Database::query("UPDATE credits SET amount=amount+? WHERE uni_id=? LIMIT 1", array($credits, Me::$id));
						}
						else
						{
							$pass = Database::query("REPLACE INTO credits (uni_id, amount) VALUES (?, ?)", array(Me::$id, $credits));
						}
					}
				}
				
				if(Database::endTransaction($pass))
				{
					Alert::success("Transfer Success", "You have successfully transferred " . $credits . " Credits into UniJoule!");
				}
				else
				{
					Alert::success("Transfer Failed", "The transfer shut down unexpectedly. Contact a moderator for assistance.");
				}
			}
		}
		else
		{
			Alert::error("Wrong Password", "The password does not match.");
		}
	}
}

// Set page title
$config['pageTitle'] = "Transfer from Uni5";

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Run Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

echo '
<div id="panel-right"></div>
<div id="content">' . Alert::display();

echo '
	<h2>Transfer from Uni5</h2>
	<p>This will transfer your credits (from Uni5) into UniJoule.</p>
	
	<form class="uniform" method="post">' . Form::prepare("transfer") . '
		<h4>Uni5 Account Name</h4>
		<p><input type="text" name="account"/></p>
		<h4>Uni5 Password</h4>
		<p><input type="password" name="password"/></p>
		<input type="submit" name="submit" value="Transfer" />
	</form>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
