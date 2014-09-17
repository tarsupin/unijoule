<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

$balance = "0.00";

if(Me::$loggedIn)
{
	WidgetLoader::add("SidePanel", 1, Notifications::sideWidget());
	
	// Get credit balance of the user
	$balance = AppCredits::getBalance(Me::$vals['auth_id']);
}

// Build Panel Navigation
if(Me::$loggedIn)
{
	$html = '	
	<div class="panel-box">
		<div style="padding:22px;">
			<div style="font-size:16px;color:#57c2c1;">Balance:</div>
			<div style="font-size:24px;color:#57c2c1; font-weight:bold; margin-top:8px; margin-bottom:20px;">' . number_format($balance, 2) . ' UniJoules</div>
			<a class="button" href="/get-unijoules">Buy More UniJoules</a>' .
			(ENVIRONMENT != "production" ? '<br /><a class="button" href="/get-unijoules?alpha=free">500 Free UniJoules</a>' : '') . '
		</div>
	</div>
	
	<div class="panel-box">
		<div style="padding:22px;">
			<div style="font-size:16px;color:#475055;padding-right:22px;">Sending UniJoules to someone?</div>
			<div style="font-size:16px;text-decoration:underline;font-weight:bold;margin-top:18px;padding-right:22px;"><a href="/send-unijoules" style="color:#475055;" onclick="showCreditForm(); return false;">Send UniJoules</a></div>';
			
			// Prepare Form Values
			$_POST['handle'] = (isset($_POST['handle']) ? Sanitize::variable($_POST['handle']) : "");
			$_POST['joules'] = (isset($_POST['joules']) ? Sanitize::number($_POST['joules'], 0.01, 9999.99, true) : "0.00");
			$_POST['desc'] = (isset($_POST['desc']) ? Sanitize::safeword($_POST['desc']) : "");
			
			$html .= '
			<form id="send-credit-form" class="uniform" action="/" method="post">' . Form::prepare("send-credits-frmcrd") . '
				<input type="text" name="handle" value="' . $_POST['handle'] . '" placeholder="their @handle" maxlength="22" />
				<input type="text" name="joules" value="' . $_POST['joules'] . '" placeholder="Number of unijoules" maxlength="8" />
				<input type="text" name="desc" value="' . $_POST['desc'] . '" placeholder="brief message" maxlength="64" />
				
				<input class="button" type="submit" value="Send UniJoules" style="width:100%;" />
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
	
	WidgetLoader::add("SidePanel", 5, $html);
}
