<?php
require_once('res/func.php');

$logoutHeader = "<div class='header'><a class='logout left' href='index.php?logout=true'>LOGOUT</a><div style='clear:both;'></div></div>";  //logout header item;
$out ='';  //variable for output


if (isset($_REQUEST['logout']) && $_REQUEST['logout'] == true) { logout(); }

else if (isset($_SESSION['uid'])) {
	include_once('res/header.php');  //include the header
	$out .= $logoutHeader;
	$out .= "<header><div class='contentheader'>
			<img src='img/logo.png' style='height:100px;'>
		</div>
	</header>";
	$out .= "<div class='content'>
		<div class='sidebar'>
		<ul>
		<li>RFPs / Q&A</li>
		<li>Awarded Projects</li>
		<li>Metrics</li>
		</ul>
		</div>
		<div class='main'>";
		$out .= showCustomerRFPs($_SESSION['channel_partner']);
		$out .= showQuestions($_SESSION['channel_partner']);
		$out .= "</div></div>";
		$out .= "<input type='hidden' id='serverLoadTime' value='" . time() . "'>";

	echo $out;
} else { //not loggded in
	redirect(C_PROJECT_DIRECTORY.'login.php');
}

?>
</body>
</html>