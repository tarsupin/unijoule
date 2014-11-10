<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

$balance = "0.00";

// Get credit balance of the user
if(Me::$loggedIn)
{
	$balance = AppCredits::getBalance(Me::$id);
}

// Build Panel Navigation
if($url[0] != "")
{
	// Main Navigation
	$html = '
	<div class="panel-box">
		<ul class="panel-slots">
			<li class="nav-slot nav-back">' . (Me::$loggedIn ? '<a href="/my-account">My Account' : '<a href="/">Home') . '<span class="icon-arrow-left nav-arrow"></span></a></li>
		</ul>
	</div>';
	
	WidgetLoader::add("SidePanel", 10, $html);
}

// Standard
$html = '	
<div class="panel-box">
	<div style="padding:22px;">
		<div style="font-size:16px;color:#57c2c1;">Balance:</div>
		<div style="font-size:24px;color:#57c2c1; font-weight:bold; margin-top:8px; margin-bottom:20px;">' . number_format($balance, 2) . ' UniJoule</div>';
		
		if(Me::$loggedIn)
		{
			$html .= '
			<a class="button" href="/get-unijoules">Buy More UniJoule</a><br />';
		}
		
		$html .= '
		<a class="button" href="/get-giftcard">Get a Gift Card</a>
	</div>
</div>

<div class="panel-box">
	<div style="padding:22px;">
		<div style="font-size:16px;color:#475055;padding-right:22px;">Sending UniJoule to someone?</div>
		<div style="font-size:16px;text-decoration:underline;font-weight:bold;margin-top:18px;padding-right:22px;"><a href="#" style="color:#475055;" onclick="showCreditForm(); return false;">Send UniJoule</a></div>';
		
		// Prepare Form Values
		$_POST['handle'] = (isset($_POST['handle']) ? Sanitize::variable($_POST['handle']) : "");
		$_POST['joules'] = (isset($_POST['joules']) ? Sanitize::number($_POST['joules'], 0.01, 9999.99, true) : "0.00");
		$_POST['desc'] = (isset($_POST['desc']) ? Sanitize::safeword($_POST['desc']) : "");
		
		$html .= '
		<form id="send-credit-form" class="uniform" action="/my-account" method="post">' . Form::prepare("send-credits-frmcrd") . '
			<input type="text" name="handle" value="' . $_POST['handle'] . '" placeholder="Their @handle" maxlength="22" />
			<input type="text" name="joules" value="' . $_POST['joules'] . '" placeholder="Number of UniJoule" maxlength="8" />
			<input type="text" name="desc" value="' . $_POST['desc'] . '" placeholder="Brief message . . ." maxlength="64" />
			
			<input class="button" type="submit" value="Send UniJoule" style="width:100%;" />
		</form>
	</div>
</div>

<style>
.uniform { margin-top:14px; }
.uniform>input { margin-top:10px; }
</style>

<script>
var hideForm = document.getElementById("send-credit-form");
hideForm.style.display = "none";

function showCreditForm()
{
	hideForm.style.display = "block";
}
</script>';

WidgetLoader::add("SidePanel", 15, $html);
