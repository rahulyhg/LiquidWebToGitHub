<?php
//error_reporting(-1);
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once('constants_root.php');
require_once('constants.php');
include_once('qbFunc.php');
include_once('lib/swift/swift_required.php');
include_once('acknowledgement.php');


//session_start();
//allow_url_fopen=1;

//date_default_timezone_set('America/Denver');
//date_default_timezone_set('America/Los_Angeles');
date_default_timezone_set('UTC');


$qb = false; //quickbase session variable

function logout(){ //end php session
	// remove all session variables
	session_unset(); 
	// destroy the session 
	session_destroy();
	// paranoia 
	setcookie('PHPSESSID',"", time() - 9999);
	unset($_COOKIE['PHPSESSID']);
	redirect('login.php');
}

function isValidEmail($email) { //checks email against domain if needed
	if (C_RESTRICT_EMAIL_DOMAIN) {
		$domain = explode("@", $email);
		if ($domain[1] === C_VALID_EMAIL_DOMAIN) {
			return (true);
		} else {
			return (false);
		}
	} else {
		return (true);
	}
}

function checkForUser($email) {
	if (isValidEmail($email)) { //check if the email is company email
		qbLogin();
		global $qb;
		$response = $qb->DoQuery(C_DBID_USERS, "{'".C_FID_USER_EMAIL."'.EX.'".$email."'}", '3');
		if ($response[0]) { // email in use
			return(2);
		} else { // email not found
			return(0);
		}
	} else {
		return(1); // non company email
	}
}

function userSetTemporaryPassword($email) { // Store a temporary password in QuickBase and email that password to the user
	qbLogin();
	global $qb;
	global $temp_password;
	$response = $qb->DoQuery(C_DBID_USERS, "{'".C_FID_USER_EMAIL."'.EX.'".$email."'}", 'a');
	if (isset($response[0]['3'])) {
		// Generate and encrypt a temporary password
		//$temp_password = random_string(10);
		$temp_password = random_str(10);
		//$temp_password = substr(bin2hex(openssl_random_pseudo_bytes(128)),0,10);
		$enc_temp_password = encrypt($temp_password);
		$fields = array(
			array(
				'fid' => C_FID_USER_TEMPORARY_PASSWORD,
				'value' => $enc_temp_password
			)
		);
		$qb->EditRecord(C_DBID_USERS, $response[0]['3'], $fields);  // Save the temporary password in QuickBase
		sendMail($email, null, 'forgot', $temp_password);  // Send the user their temporary password
	}
}

// Send an email using Swift Mailer
function sendMail($email, $key, $type){
	$from = array(C_MAILED_FROM => C_MAILED_FROM_NAME);
	global $temp_password;

	if ($type == 'validate') {
		$url = C_PROJECT_DIRECTORY."login.php?e=".$email."&k=".$key;
		$subject = C_SIGNUP_SUBJECT;
		$body = C_SIGNUP_BODY.$url;
		$bodyhtml = C_SIGNUP_BODY_HTML.$url;
	}
	else if ($type == 'forgot') {
		$url = C_PROJECT_DIRECTORY."forgot.php?e=".$email;
		$subject = C_FORGOT_PASSWORD_SUBJECT;
		$body = C_FORGOT_PASSWORD_BODY.$temp_password."\n\nPlease copy the link below to reset your password:\n".$url;
		$bodyhtml = C_FORGOT_PASSWORD_BODY_HTML.$temp_password."<br><br>Please copy the link below to reset your password:<br>".$url;
	}
	$transport = Swift_SmtpTransport::newInstance(C_MAILED_SERVER, 465, 'ssl')
		->setUsername(C_MAILED_USERMAIL_LOGIN)
		->setPassword(C_MAILED_USERMAIL_PASSWORD);
	$mailer = Swift_Mailer::newInstance($transport);
	$message = Swift_Message::newInstance()
		->setSubject($subject)
		->setFrom($from)
		->setTo($email)
		->addPart($bodyhtml,'text/html')
		->setBody($body)
		;
	$result = $mailer->send($message);
}

function validateUser($email, $key) {
	qbLogin();
	global $qb;
	$response = $qb->DoQuery('bk6wv3wbm', "{'8'.EX.'".$email."'}AND{'25'.EX.'".$key."'}", 'a', '3'); 
	if ($response[0]) {

		$fields = array(
			array(
				'fid' => 24,
				'value' => 1
			),
			array(
				'fid' => 25,
				'value' => '0'
			)
		);
			$qb->EditRecord('bk6wv3wbm', $response[0]['3'], $fields);
			return (1);		
	} else { return (0); }
}

function userSignup($email, $password, $firstname, $lastname, $company_name, $account_type, $phone_number){
	if(isValidEmail($email)){
		qbLogin();
		global $qb;
		//generate random string for url key
		$key = substr(bin2hex(openssl_random_pseudo_bytes(128)),0,32);
		$fields = array(
			array(
				'fid' => C_FID_USER_EMAIL,
				'value' => $email
			),
			array(
				'fid' => C_FID_USER_PASSWORD,
				'value' => $password
			),
			array(
				'fid' => C_FID_USER_FIRST_NAME,
				'value' => $firstname
			),
			array(
				'fid' => C_FID_USER_LAST_NAME,
				'value' => $lastname
			),
			array(
				'fid' => C_FID_USER_URL_KEY,
				'value' => $key
			),
			array(
				'fid' => C_FID_USER_COMPANY_NAME,
				'value' => $company_name
			),
			array(
				'fid' => C_FID_USER_ACCOUNT_TYPE,
				'value' => $account_type
			),
			array(
				'fid' => C_FID_USER_PHONE_NUMBER,
				'value' => $phone_number
			)
		);
		$qb->AddRecord(C_DBID_USERS, $fields, false);	
		sendMail($email, $key, 'validate', null);
	} else {
		return false;
	}
}

function getRealIpAddr()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
    {
      $ip=$_SERVER['HTTP_CLIENT_IP'];
    }
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
    {
      $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else
    {
      $ip=$_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

// function getLocationLatLon(){
// 	//$ip = $_SERVER['REMOTE_ADDR'];
// 	//$details = json_decode(file_get_contents("http://ipinfo.io/{$ip}/json"));
// 	$location = file_get_contents('http://freegeoip.net/json/'.$_SERVER['REMOTE_ADDR']);
// 	print_r($location);
// 	die();
// 	return $location->latitude . "," . $location->longitude;
// }

function userLogin($email, $pass, $lat_lon = NULL) {
	qbLogin();
	global $qb;
	$response = $qb->DoQuery(C_DBID_USERS, "{'" . C_FID_USER_EMAIL . "'.EX.'" . $email . "'}AND{'" . C_FID_USER_PASSWORD . "'.EX.'" . $pass . "'}", 'a');
	if ($response[0]) {
		if ($response[0][C_FID_USER_APPROVED] =="Approved") {
			if ($response[0][C_FID_USER_VALIDATED]) {
				if ($response[0][58] =='1') { //start blacklisted
					//return ("<span style='background:yellow;font-size:120%;'>Invalid Login.</span>");
					return ("blacklisted");
				} else {
					$_SESSION['userEmail'] = $response[0][C_FID_USER_EMAIL];
					$_SESSION['userFirstName'] = $response[0][C_FID_USER_FIRST_NAME];
					$_SESSION['userLastName'] = $response[0][C_FID_USER_LAST_NAME];
					$_SESSION['uid'] = $response[0][3];
					$_SESSION['channel_partner'] = $response[0][20];
					$_SESSION['channel_partner_name'] = $response[0][27];
					$_SESSION['contact_type'] = $response[0][14];
					$_SESSION['related_customer_rid'] = $response[0][15];
					$_SESSION['customer_name'] = $response[0][16];
					$_SESSION['customer_deactivated'] = $response[0][53];
					$_SESSION['customer_deactivation_date'] = $response[0][57];

					//log activity in QB
					$fields = array(
						array(
							'fid' => 6,//start timestamp
							'value' => date('m/d/Y h:i:s', time())
						),
						array(
							'fid' => 34,//start time
							'value' => date('h:i:s a', time())
						),
						array(
							'fid' => 21,//user rid
							'value' => $response[0][3]
						),
						array(
							'fid' => 10,//page url
							'value' => C_PROJECT_DIRECTORY.'login.php'
						),
						array(
							'fid' => 11,//page name
							'value' => 'Sign in page'
						),
						array(
							'fid' => 13,//geolocation
							'value' => $lat_lon
						),
						array(
							'fid' => 19,//IP address
							'value' => getRealIpAddr()
						),
						array(
							'fid' => 20,//action
							'value' => 'login'
						),
						array(
							'fid' => 29,//action name
							'value' => 'click'
						),
						array(
							'fid' => 22,//target type
							'value' => 'button'
						),
						array(
							'fid' => 23,//name of button
							'value' => 'LOG IN'
						),
						array(
							'fid' => 24,//target id
							'value' => '1'
						)
					);
					$qb->AddRecord(C_DBID_TRACKING, $fields, false);

					//end log activity in qb
					return ("success");
				} //end not blacklisted

			} else { // User is signed up, but not validated. Inform user to validate through email.
				return ("<span style='background:yellow;font-size:120%;'>Error: This account is not validated. Please check your email and use the validation link sent to you.</span>");
			}
		} else { // user is signed up and validated but not yet approved by black bear
			return 'pending_approval';
			} 
	} else { 
		return ("<header><div class='contentheader'>
				<img src='img/logo.png' style='height:100px;'>
			</div>
		</header>Error: Invalid login."); }
}

function encrypt($value) { //holdover from old portal, so bad, especially since php has http://php.net/manual/en/function.password-hash.php
	return hash('sha512', $value.C_SALT);
}

function qbUpdate($dbid, $rid, $fid, $value, $original=null) {
	if($value != $original) {
		global $updates;
		$updates[] = array($dbid, $rid, $fid, $value, $original);
		qbLogin();
		global $qb;
		return($qb->EditRecord($dbid, $rid, array(array('fid' => $fid, 'value'=> $value))));
	}
}

function qbAdd($dbid, $fidValues) {
	qbLogin();
	global $qb;
	foreach ($fidValues as $fid=>$value) { $array[] = array('fid'=>$fid, 'value'=>$value); }
	$result = $qb->AddRecord($dbid, $array, false);
	return( array('result'=>$result, 'rid'=>$result->rid ));
}

function childKeys($key,$array) { //returns an array of arrays where the key for the child array is a value from the passed $key in the child array
	if (is_array($array)) {
		$returnArray = array();
		foreach ($array as $childArray) { $returnArray[$childArray[$key]] = $childArray; }
		return($returnArray);
	}
}

//Bear specific functions
//////////////////////////////////////////////////////////////////////////////////////////////////////
function showCustomerRFPs($channel_partner_id) {
	qbLogin(); //login if not already;
	global $qb;
	global $terms_and_conditions;
	$out = "";
	//start the table
	$out .= "<h3>RFPs</h3>";
			// $out .= "<label class='form-inline'>Show
		 //                            <select id='demo-show-entries' class='form-control input-sm'>
		 //                                <option value='5'>5</option>
		 //                                <option value='10' selected>10</option>
		 //                                <option value='15'>15</option>
		 //                                <option value='20'>20</option>
		 //                            </select> entries </label>";
			$out .= "<table id='demo-foo-row-toggler' style='border-collapse:collapse;' class='tablesaw table-bordered table-hover table toggle-circle' data-tablesaw-mode='stack' data-tablesaw-minimap data-tablesaw-mode-switch data-page-size='100'>
                    <thead>
                        <tr>
                            <th data-toggle='true' scope='col' data-tablesaw-sortable-col data-tablesaw-priority='persist'>RFP Name</th>
                            <th scope='col' data-tablesaw-sortable-col data-tablesaw-sortable-default-col data-tablesaw-priority='3'>Product Type</th>
                            <th scope='col' data-tablesaw-sortable-col data-tablesaw-sortable-default-col data-tablesaw-priority='3'>Status</th>
                            <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='2'>Release Date</th>
                            <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='1'>Due Date</th>
                            <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='4'>Location</th>
                            <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='4'>Opportunity</th>
                            <th scope='col'>Opt In</th>
                            <th scope='col'>Opt Out</th>
                        </tr>
                    </thead><tbody>";
    //search all shortlisted
    if ($_SESSION['customer_deactivated'] =='1'){
    	$query = "{'3'.XEX.'0'}AND{'17'.EX.'".$channel_partner_id."'}AND{'36'.EX.'7.1'}AND{'29'.OBF.'".$_SESSION['customer_deactivation_date']."'}";
    } else {
    	$query = "{'3'.XEX.'0'}AND{'17'.EX.'".$channel_partner_id."'}AND{'36'.EX.'7.1'}";
    }
    $shortlisted = $qb->DoQuery(C_DBID_OPTINS, $query, 'a', '3');
    // $out .= "{'3'.XEX.'0'}AND{'17'.EX.'".$channel_partner_id."'}AND{'36'.EX.'7.1'}";
    // return $out;
	if ($shortlisted[0]){
		$query = "";
		for ($a = 0; $a < count($shortlisted); $a++) {
			$rfp_id = $shortlisted[$a][10];	
			$rfp_rid = $shortlisted[$a][11];
			$rfp_status = $shortlisted[$a][13];
			$rfp_status = explode("-", $rfp_status);
			$rfp_status = $rfp_status[1];
			$rfp_release_date = convertQBDate($shortlisted[$a][29]);
			$rfp_due_date = convertQBDate($shortlisted[$a][65]);
			$rfp_location = $shortlisted[$a][66];
			$rfp_opportunity = $shortlisted[$a][67];
			$rfp_energy_technology = $shortlisted[$a][68];
			$rfp_client = $shortlisted[$a][76];
			
			$rfp_num_of_sites = $shortlisted[$a][71];
			$rfp_type_of_install = $shortlisted[$a][72];
			$rfp_high_level_of_request = $shortlisted[$a][73];
			$rfp_roof_age = $shortlisted[$a][74];
			
			$rfp_building_owner = $shortlisted[$a][69];
			if($rfp_energy_technology=="LED"){
				$rfp_offtaker ="N/A";
				$rfp_outtaker_credit ="N/A";
			} else {
				$rfp_offtaker = $shortlisted[$a][70];
				$rfp_outtaker_credit = $shortlisted[$a][75];
			}

			//search optins
				$query_optins = "{'10'.EX.'".$rfp_id."'}AND{'17'.EX.'".$channel_partner_id."'}";
				$optins = $qb->DoQuery(C_DBID_OPTINS, $query_optins, 'a', '3', 'sortorder-D');
				if ($optins[0]){
					$optin_rid = $optins[0][3];
					//$found="found optin ".$query_optins;
					if($optins[0][6]=="Opt In"){
						$action1 = '0';
						$action2 = 'optout';
						$rfp_opted_in[$rfp_id] = true;
						$button1 = "<span class='custom_label_primary' style='font-weight:400;color:white;background:#DC3796;border-radius:0;width:80px;padding:2px 7px;font-size:12px;line-height:1.5;cursor:default;display:block;text-align:center;white-space:nowrap;vertical-align:middle;'>Opted In</span>";
						$button2 = "<input class=' btn btn-info btn-xs btn-outline' type='button' value='Opt Out' style='width:80px;' data-toggle='modal' data-target='#optoutModal".$optin_rid."' data-whatever='@mdo'>";
						$form1 = $button1;
						$form2 = $button2."
								<div class='modal fade' id='optoutModal".$optin_rid."' tabindex='-1' role='dialog' aria-labelledby='optoutModalLabel'>
		                            <div class='modal-dialog' role='document'>
		                                <div class='modal-content'>
		                                    <div class='modal-header'>
		                                        <button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
		                                        <h4 class='modal-title' id='optoutModalLabel'>Opting Out</h4> 
		                                    </div>
		                                	<form action='res/actions.php?channel_partner_id=".$channel_partner_id."&rfp_id=".$rfp_id."&action=".$action2."&rfp_energy_technology=".$rfp_energy_technology."&optin_rid=".$optin_rid."&redirect=1' method='post'>
		                                		<div class='modal-body'>
			                                        <input type='hidden' name='rfp_rid' value='".$rfp_rid."'>
			                                        <input type='hidden' name='opted_out_by' value='".$_SESSION['userFirstName']." ".$_SESSION['userLastName']."'>
			                                        <div class='form-group'>
			                                            <label for='reason_text' class='control-label'>Reason for opting out</label>
			                                            <textarea class='form-control' name='reason_text' id='reason_text' required></textarea>
			                                        </div>
			                                    </div>
			                                    <div class='modal-footer'>
			                                        <button type='button' class='btn btn-default' data-dismiss='modal'>Close</button>
			                                        <input type='submit' class='btn btn-primary btn_interaction' id='".$rfp_rid."' value='Opt Out'>
			                                    </div>
		                                  	</form>
		                                </div>
		                            </div>
	                        	</div>";
					} elseif ($optins[0][6]=="Opt Out"){
						$action1 = 'optin';
						$action2 = '0';
						$rfp_opted_in[$rfp_id] = false;
						//$button1 = "<input class=' btn btn-primary btn-xs btn-outline btn_interaction' id='".$rfp_rid."' type='submit' value='Opt In' style='width:80px;'>";
						$button1 = "<input class=' btn btn-primary btn-xs btn-outline btn_interaction' type='button' value='Opt In' style='width:80px;' data-toggle='modal' data-target='#optinModal".$optin_rid."' data-whatever='@mdo'>";
						$button2 = "<span class='custom_label_info' style='font-weight:400;color:white;background:#41A0C8;border-radius:0;width:80px;padding:2px 7px;font-size:12px;line-height:1.5;cursor:default;display:block;text-align:center;white-space:nowrap;vertical-align:middle;'>Opted Out</span>";

						//new optin
						//new opt in
						$form1 = $button1."
								<div class='modal fade' id='optinModal".$optin_rid."' tabindex='-1' role='dialog' aria-labelledby='optinModalLabel'>
		                            <div class='modal-dialog' role='document'>
		                                <div class='modal-content'>
		                                    <div class='modal-header'>
		                                        <button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
		                                        <h2 class='modal-title' id='optinModalLabel'>Opting In</h2> 
		                                        <h4 class='modal-title' id='optinModalLabel'>Acknowledgement of Request For Proposal Terms and Conditions</h4>
		                                        <div style='max-height:400px;overflow:scroll;'>".$terms_and_conditions."</div>
		                                    </div>
		                                	<form action='res/actions.php?channel_partner_id=".$channel_partner_id."&rfp_id=".$rfp_id."&action=".$action1."&rfp_energy_technology=".$rfp_energy_technology."&optin_rid=".$optin_rid."&redirect=1' method='post'>
		                                		<div class='modal-body'>
			                                        <input type='hidden' name='rfp_rid' value='".$rfp_rid."'>
			                                        <input type='hidden' name='opted_in_by' value='".$_SESSION['userFirstName']." ".$_SESSION['userLastName']."'>
			                                        <div class='form-group'>
			                                            <label for='acknowlege_checkbox' class='control-label'>I acknowlege and agree to the terms and conditions</label>
			                                            <input style='float:left;margin-right:12px;' id='acknowlege_checkbox' name='acknowlege_checkbox' type='checkbox' required>
			                                        </div>
			                                    </div>
			                                    <div class='modal-footer'>
			                                        <button type='button' class='btn btn-default' data-dismiss='modal'>Close</button>
			                                        <input type='submit' class='btn btn-primary btn_interaction' id='".$rfp_rid."' value='Submit'>
			                                    </div>
		                                  	</form>
		                                </div>
		                            </div>
	                        	</div>";
						//$form1 = "<form style='margin:0 auto;padding:0;' action='res/actions.php?channel_partner_id=".$channel_partner_id."&rfp_id=".$rfp_id."&rfp_rid=".$rfp_rid."&action=".$action1."&rfp_energy_technology=".$rfp_energy_technology."&optin_rid=".$optin_rid."&redirect=1' method='post'>".$button1."</form>";
						$form2 = $button2;
					}
				} else {
					$optin_rid = 0;
					$action1 = 'optin';
					$action2 = 'optout';
					$rfp_opted_in[$rfp_id] = false;
					//$button1 = "<input class=' btn btn-primary btn-xs btn-outline btn_interaction' id='".$rfp_rid."' type='submit' value='Opt In' style='width:80px;'>";

					//new button 1 invoking a modal
					$button1 = "<input class=' btn btn-primary btn-xs btn-outline btn_interaction' type='button' value='Opt In' id='".$rfp_rid." 'style='width:80px;' data-toggle='modal' data-target='#optinModal".$optin_rid."' data-whatever='@mdo'>";

					$button2 = "<input class=' btn btn-info btn-xs btn-outline' type='button' value='Opt Out' style='width:80px;' data-toggle='modal' data-target='#optoutModal' data-whatever='@mdo'>";
					//$form1 = "<form style='margin:0 auto;padding:0;' action='res/actions.php?channel_partner_id=".$channel_partner_id."&rfp_id=".$rfp_id."&rfp_rid=".$rfp_rid."&action=".$action1."&rfp_energy_technology=".$rfp_energy_technology."&optin_rid=".$optin_rid."&redirect=1' method='post'>".$button1."</form>";

					//new opt in
					$form1 = $button1."
							<div class='modal fade' id='optinModal".$optin_rid."' tabindex='-1' role='dialog' aria-labelledby='optinModalLabel'>
	                            <div class='modal-dialog' role='document'>
	                                <div class='modal-content'>
	                                    <div class='modal-header'>
	                                        <button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
	                                        <h2 class='modal-title' id='optinModalLabel'>Opting In</h2> 
	                                        <h4 class='modal-title' id='optinModalLabel'>Acknowledgement of Request For Proposal Terms and Conditions</h4>
	                                        <div style='max-height:400px;overflow:scroll;'>".$terms_and_conditions."</div>
	                                    </div>
	                                	<form action='res/actions.php?channel_partner_id=".$channel_partner_id."&rfp_id=".$rfp_id."&action=".$action1."&rfp_energy_technology=".$rfp_energy_technology."&optin_rid=".$optin_rid."&redirect=1' method='post'>
	                                		<div class='modal-body'>
		                                        <input type='hidden' name='rfp_rid' value='".$rfp_rid."'>
		                                        <input type='hidden' name='opted_in_by' value='".$_SESSION['userFirstName']." ".$_SESSION['userLastName']."'>
		                                        <div class='form-group'>
		                                            <label for='acknowlege_checkbox' class='control-label'>I acknowlege and agree to the terms and conditions</label>
		                                            <input style='float:left;margin-right:12px;' id='acknowlege_checkbox' name='acknowlege_checkbox' type='checkbox' required>
		                                        </div>
		                                    </div>
		                                    <div class='modal-footer'>
		                                        <button type='button' class='btn btn-default' data-dismiss='modal'>Close</button>
		                                        <input type='submit' class='btn btn-primary btn_interaction' id='".$rfp_rid."' value='Submit'>
		                                    </div>
	                                  	</form>
	                                </div>
	                            </div>
	                    	</div>";

					$form2 = "<div class='button-box'>".$button2."</div>
							<div class='modal fade' id='optoutModal' tabindex='-1' role='dialog' aria-labelledby='optoutModalLabel'>
	                            <div class='modal-dialog' role='document'>
	                                <div class='modal-content'>
	                                    <div class='modal-header'>
	                                        <button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
	                                        <h4 class='modal-title' id='optoutModalLabel'>Opting Out</h4> 
	                                    </div>
	                                	<form action='res/actions.php?channel_partner_id=".$channel_partner_id."&rfp_id=".$rfp_id."&action=".$action2."&rfp_energy_technology=".$rfp_energy_technology."&optin_rid=".$optin_rid."&redirect=1' method='post'>
	                                		<div class='modal-body'>
		                                        <input type='hidden' name='rfp_rid' value='".$rfp_rid."'>
		                                        <input type='hidden' name='opted_out_by' value='".$_SESSION['userFirstName']." ".$_SESSION['userLastName']."'>
		                                        <div class='form-group'>
		                                            <label for='reason_text' class='control-label'>Reason for opting out</label>
		                                            <textarea class='form-control' name='reason_text' id='reason_text' required></textarea>
		                                        </div>
		                                    </div>
		                                    <div class='modal-footer'>
		                                        <button type='button' class='btn btn-default' data-dismiss='modal'>Close</button>
		                                        <input type='submit' class='btn btn-primary btn_interaction' id='".$rfp_rid."' value='Opt Out'>
		                                    </div>
	                                  	</form>
	                                </div>
	                            </div>
                        	</div>";
				}

			$out .= "<tr class='' data-href='rfp_detail.php?rfp_rid=".$rfp_rid."&channel_partner_id=".$channel_partner_id."'>";
			//$out .= "<td  class='title'>".$rfp_id."</td>";
			$out .= "<td  class='title'><a href='rfp_detail.php?rfp_rid=".$rfp_rid."&channel_partner_id=".$channel_partner_id."'>".$rfp_id."</a></td>";
			$out .= "<td>".$rfp_energy_technology."</td>";
			$out .= "<td>".$rfp_status."</td>";
			$out .= "<td>".$rfp_release_date."</td>";
			$out .= "<td>".$rfp_due_date."</td>";
			$out .= "<td>".$rfp_location."</td>";
			$out .= "<td>".$rfp_opportunity."</td>";
			$out .= "<td>".$form1."</td>";
			$out .= "<td>".$form2."</td>";
			//$out .= "<td><a href='rfp_detail.php?rfp_rid=".$rfp_rid."&channel_partner_id=".$channel_partner_id."'><b>View -></b></td>";
			$out .= "</tr>";
			// if($rfp_energy_technology=="LED"){
			// 		$out.="<script>
			// 			$('.remove_if_led".$rfp_id."').hide();
			// 		</script>";
			// 	}
			
		}
	}

	$query ="";

	//add custom filters of all extra allowed to the query with and OR
	$query .= "
		({'146'.EX.'".$channel_partner_id."'}AND{'166'.EX.'Show RFP'})OR
		({'148'.EX.'".$channel_partner_id."'}AND{'167'.EX.'Show RFP'})OR
		({'150'.EX.'".$channel_partner_id."'}AND{'168'.EX.'Show RFP'})OR
		({'152'.EX.'".$channel_partner_id."'}AND{'169'.EX.'Show RFP'})OR
		({'154'.EX.'".$channel_partner_id."'}AND{'170'.EX.'Show RFP'})OR
		({'156'.EX.'".$channel_partner_id."'}AND{'171'.EX.'Show RFP'})OR
		({'158'.EX.'".$channel_partner_id."'}AND{'172'.EX.'Show RFP'})OR
		({'160'.EX.'".$channel_partner_id."'}AND{'173'.EX.'Show RFP'})OR
		({'162'.EX.'".$channel_partner_id."'}AND{'174'.EX.'Show RFP'})OR
		({'164'.EX.'".$channel_partner_id."'}AND{'175'.EX.'Show RFP'})
		
	OR";

	//search for all specializations for that channel partner
	$specializations = $qb->DoQuery(C_DBID_SPECIALIZATIONS, "{'3'.XEX.'0'}AND{'12'.EX.'".$channel_partner_id."'}", 'a', '3');
	if ($specializations[0]){
		//$query .= "(";
		//start query of other non shortlisted rfps
		for ($i = 0; $i < count($specializations); $i++) {
			$energy_technology = $specializations[$i][7];
			//for each energy_technology put in query string
			if( $i > 0 ){ $query.="OR"; }
			$query .= "{'44'.EX.'".$energy_technology."'}";
		}
		//$query .= ")";
		if ($_SESSION['customer_deactivated'] =='1'){
	    	$query .="AND({'61'.LTE.'7'}AND{'61'.GTE.'3'}AND{'8'.OBF.'".$_SESSION['customer_deactivation_date']."'})";
	    } else {
	    	$query .="AND({'61'.LTE.'7'}AND{'61'.GTE.'3'})";
	    }
	    
	}//end if specializations

	// //run custom filters of all extra forbidden with an AND
	// $query .= "AND(
	// 	({'146'.XEX.'".$channel_partner_id."'}AND{'166'.XEX.'Hide RFP'})AND
	// 	({'148'.XEX.'".$channel_partner_id."'}AND{'167'.XEX.'Hide RFP'})AND
	// 	({'150'.XEX.'".$channel_partner_id."'}AND{'168'.XEX.'Hide RFP'})AND
	// 	({'152'.XEX.'".$channel_partner_id."'}AND{'169'.XEX.'Hide RFP'})AND
	// 	({'154'.XEX.'".$channel_partner_id."'}AND{'170'.XEX.'Hide RFP'})AND
	// 	({'156'.XEX.'".$channel_partner_id."'}AND{'171'.XEX.'Hide RFP'})AND
	// 	({'158'.XEX.'".$channel_partner_id."'}AND{'172'.XEX.'Hide RFP'})AND
	// 	({'160'.XEX.'".$channel_partner_id."'}AND{'173'.XEX.'Hide RFP'})AND
	// 	({'162'.XEX.'".$channel_partner_id."'}AND{'174'.XEX.'Hide RFP'})AND
	// 	({'164'.XEX.'".$channel_partner_id."'}AND{'175'.XEX.'Hide RFP'}))";

// $out = $query;
// return $out;

	// $out.=$query;
	// return $out;
	//look for energy_technology required for each rfp and only show those that match current users partner energy_technologies.
	$rfps = $qb->DoQuery(C_DBID_CUSTOMER_RFPS, $query, 'a', '3');
	if ($rfps[0]){
		
		for ($j = 0; $j < count($rfps); $j++) {
			$rfp_id = $rfps[$j][6];	
			$rfp_rid = $rfps[$j][3];
			$rfp_status = $rfps[$j][7];
			$rfp_status = explode("-", $rfp_status);
			$rfp_status = $rfp_status[1];
			$rfp_release_date = convertQBDate($rfps[$j][8]);
			$rfp_due_date = convertQBDate($rfps[$j][9]);
			$rfp_location = $rfps[$j][56];
			$rfp_opportunity = $rfps[$j][55];
			$rfp_energy_technology = $rfps[$j][44];
			$rfp_client = $rfps[$j][47];
			
			$rfp_num_of_sites = $rfps[$j][54];
			$rfp_type_of_install = $rfps[$j][66];
			$rfp_high_level_of_request = $rfps[$j][74];
			$rfp_roof_age = $rfps[$j][68];

			$cp1 = $rfps[$j][146];
			$cp2 = $rfps[$j][148];
			$cp3 = $rfps[$j][150];
			$cp4 = $rfps[$j][152];
			$cp5 = $rfps[$j][154];
			$cp6 = $rfps[$j][156];
			$cp7 = $rfps[$j][158];
			$cp8 = $rfps[$j][160];
			$cp9 = $rfps[$j][162];
			$cp10 = $rfps[$j][164];

			$permission1 = $rfps[$j][166];
			$permission2 = $rfps[$j][167];
			$permission3 = $rfps[$j][168];
			$permission4 = $rfps[$j][169];
			$permission5 = $rfps[$j][170];
			$permission6 = $rfps[$j][171];
			$permission7 = $rfps[$j][172];
			$permission8 = $rfps[$j][173];
			$permission9 = $rfps[$j][174];
			$permission10 = $rfps[$j][175];

			if (  ($cp1 == $channel_partner_id && $permission1 == "Hide RFP") 
				||($cp2 == $channel_partner_id && $permission2 == "Hide RFP") 
				||($cp3 == $channel_partner_id && $permission3 == "Hide RFP")
				||($cp4 == $channel_partner_id && $permission4 == "Hide RFP")
				||($cp5 == $channel_partner_id && $permission5 == "Hide RFP")
				||($cp6 == $channel_partner_id && $permission6 == "Hide RFP")
				||($cp7 == $channel_partner_id && $permission7 == "Hide RFP")
				||($cp8 == $channel_partner_id && $permission8 == "Hide RFP")
				||($cp9 == $channel_partner_id && $permission9 == "Hide RFP")
				||($cp10 == $channel_partner_id && $permission10 == "Hide RFP")
			) {
				continue;
			}
			
			$rfp_building_owner = $rfps[$j][65];
			if($rfp_energy_technology=="LED"){
			 	$rfp_offtaker ="N/A";
			 	$rfp_outtaker_credit ="N/A";
			} else {
				$rfp_offtaker = $rfps[$j][11];
				$rfp_outtaker_credit = $rfps[$j][69];
			}

			//search optins
			$query_optins = "{'10'.EX.'".$rfp_id."'}AND{'17'.EX.'".$channel_partner_id."'}";
			$optins = $qb->DoQuery(C_DBID_OPTINS, $query_optins, 'a', '3', 'sortorder-D');
			if ($optins[0]){
				$optin_rid = $optins[0][3];
				//$found="found optin ".$query_optins;
				if($optins[0][6]=="Opt In"){
					$action1 = '0';
					$action2 = 'optout';
					$rfp_opted_in[$rfp_id] = true;
					$button1 = "<span class='custom_label_primary' style='font-weight:400;color:white;background:#DC3796;border-radius:0;width:80px;padding:2px 7px;font-size:12px;line-height:1.5;cursor:default;display:block;text-align:center;white-space:nowrap;vertical-align:middle;'>Opted In</span>";
					$button2 = "<input class=' btn btn-info btn-xs btn-outline' type='button' value='Opt Out' style='width:80px;' data-toggle='modal' data-target='#optoutModal".$optin_rid."' data-whatever='@mdo'>";
					$form1 = $button1;
					$form2 = $button2."
							<div class='modal fade' id='optoutModal".$optin_rid."' tabindex='-1' role='dialog' aria-labelledby='optoutModalLabel'>
	                            <div class='modal-dialog' role='document'>
	                                <div class='modal-content'>
	                                    <div class='modal-header'>
	                                        <button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
	                                        <h4 class='modal-title' id='optoutModalLabel'>Opting Out</h4> 
	                                    </div>
	                                	<form action='res/actions.php?channel_partner_id=".$channel_partner_id."&rfp_id=".$rfp_id."&action=".$action2."&rfp_energy_technology=".$rfp_energy_technology."&optin_rid=".$optin_rid."&redirect=1' method='post'>
	                                		<div class='modal-body'>
		                                        <input type='hidden' name='rfp_rid' value='".$rfp_rid."'>
		                                        <input type='hidden' name='opted_out_by' value='".$_SESSION['userFirstName']." ".$_SESSION['userLastName']."'>
		                                        <div class='form-group'>
		                                            <label for='reason_text' class='control-label'>Reason for opting out</label>
		                                            <textarea class='form-control' name='reason_text' id='reason_text' required></textarea>
		                                        </div>
		                                    </div>
		                                    <div class='modal-footer'>
		                                        <button type='button' class='btn btn-default' data-dismiss='modal'>Close</button>
		                                        <input type='submit' class='btn btn-primary btn_interaction' id='".$rfp_rid."' value='Opt Out'>
		                                    </div>
	                                  	</form>
	                                </div>
	                            </div>
                        	</div>";
				} elseif ($optins[0][6]=="Opt Out"){
					$action1 = 'optin';
					$action2 = '0';
					$rfp_opted_in[$rfp_id] = false;

					//new button 1 envoking a modal
					$button1 = "<input class=' btn btn-primary btn-xs btn-outline btn_interaction' type='button' value='Opt In' style='width:80px;' data-toggle='modal' data-target='#optinModal".$optin_rid."' data-whatever='@mdo'>";
					
					$button2 = "<span class='custom_label_info' style='font-weight:400;color:white;background:#41A0C8;border-radius:0;width:80px;padding:2px 7px;font-size:12px;line-height:1.5;cursor:default;display:block;text-align:center;white-space:nowrap;vertical-align:middle;'>Opted Out</span>";

					//put modal here
					//$form1 = "<form style='margin:0 auto;padding:0;' action='res/actions.php?channel_partner_id=".$channel_partner_id."&rfp_id=".$rfp_id."&rfp_rid=".$rfp_rid."&action=".$action1."&rfp_energy_technology=".$rfp_energy_technology."&optin_rid=".$optin_rid."&redirect=1' method='post'>".$button1."</form>";

					//new opt in
					$form1 = $button1."
							<div class='modal fade' id='optinModal".$optin_rid."' tabindex='-1' role='dialog' aria-labelledby='optinModalLabel'>
	                            <div class='modal-dialog' role='document'>
	                                <div class='modal-content'>
	                                    <div class='modal-header'>
	                                        <button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
	                                        <h2 class='modal-title' id='optinModalLabel'>Opting In</h2> 
	                                        <h4 class='modal-title' id='optinModalLabel'>Acknowledgement of Request For Proposal Terms and Conditions</h4>
	                                        <div style='max-height:400px;overflow:scroll;'>".$terms_and_conditions."</div>
	                                    </div>
	                                	<form action='res/actions.php?channel_partner_id=".$channel_partner_id."&rfp_id=".$rfp_id."&action=".$action1."&rfp_energy_technology=".$rfp_energy_technology."&optin_rid=".$optin_rid."&redirect=1' method='post'>
	                                		<div class='modal-body'>
		                                        <input type='hidden' name='rfp_rid' value='".$rfp_rid."'>
		                                        <input type='hidden' name='opted_in_by' value='".$_SESSION['userFirstName']." ".$_SESSION['userLastName']."'>
		                                        <div class='form-group'>
		                                            <label for='acknowlege_checkbox' class='control-label'>I acknowlege and agree to the terms and conditions</label>
		                                            <input style='float:left;margin-right:12px;' id='acknowlege_checkbox' name='acknowlege_checkbox' type='checkbox' required>
		                                        </div>
		                                    </div>
		                                    <div class='modal-footer'>
		                                        <button type='button' class='btn btn-default' data-dismiss='modal'>Close</button>
		                                        <input type='submit' class='btn btn-primary btn_interaction' id='".$rfp_rid."' value='Submit'>
		                                    </div>
	                                  	</form>
	                                </div>
	                            </div>
                        	</div>";

					$form2 = $button2;
				}
			} else {
				$optin_rid = 0;
				$action1 = 'optin';
				$action2 = 'optout';
				$rfp_opted_in[$rfp_id] = false;
				//$button1 = "<input class=' btn btn-primary btn-xs btn-outline btn_interaction' id='".$rfp_rid."' type='submit' value='Opt In' style='width:80px;'>";
				//new button 1 invoking a modal
				$button1 = "<input class=' btn btn-primary btn-xs btn-outline btn_interaction' type='button' value='Opt In' id='".$rfp_rid." 'style='width:80px;' data-toggle='modal' data-target='#optinModal".$optin_rid."' data-whatever='@mdo'>";

				$button2 = "<input class=' btn btn-info btn-xs btn-outline' type='button' value='Opt Out' style='width:80px;' data-toggle='modal' data-target='#optoutModal' data-whatever='@mdo'>";
				
				//new opt in
				$form1 = $button1."
						<div class='modal fade' id='optinModal".$optin_rid."' tabindex='-1' role='dialog' aria-labelledby='optinModalLabel'>
                            <div class='modal-dialog' role='document'>
                                <div class='modal-content'>
                                    <div class='modal-header'>
                                        <button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                                        <h2 class='modal-title' id='optinModalLabel'>Opting In</h2> 
                                        <h4 class='modal-title' id='optinModalLabel'>Acknowledgement of Request For Proposal Terms and Conditions</h4>
                                        <div style='max-height:400px;overflow:scroll;'>".$terms_and_conditions."</div>
                                    </div>
                                	<form action='res/actions.php?channel_partner_id=".$channel_partner_id."&rfp_id=".$rfp_id."&action=".$action1."&rfp_energy_technology=".$rfp_energy_technology."&optin_rid=".$optin_rid."&redirect=1' method='post'>
                                		<div class='modal-body'>
	                                        <input type='hidden' name='rfp_rid' value='".$rfp_rid."'>
	                                        <input type='hidden' name='opted_in_by' value='".$_SESSION['userFirstName']." ".$_SESSION['userLastName']."'>
	                                        <div class='form-group'>
	                                            <label for='acknowlege_checkbox' class='control-label'>I acknowlege and agree to the terms and conditions</label>
	                                            <input style='float:left;margin-right:12px;' id='acknowlege_checkbox' name='acknowlege_checkbox' type='checkbox' required>
	                                        </div>
	                                    </div>
	                                    <div class='modal-footer'>
	                                        <button type='button' class='btn btn-default' data-dismiss='modal'>Close</button>
	                                        <input type='submit' class='btn btn-primary btn_interaction' id='".$rfp_rid."' value='Submit'>
	                                    </div>
                                  	</form>
                                </div>
                            </div>
                    	</div>";



				//$form1 = "<form style='margin:0 auto;padding:0;' action='res/actions.php?channel_partner_id=".$channel_partner_id."&rfp_id=".$rfp_id."&rfp_rid=".$rfp_rid."&action=".$action1."&rfp_energy_technology=".$rfp_energy_technology."&optin_rid=".$optin_rid."&redirect=1' method='post'>".$button1."</form>";
				$form2 = "<div class='button-box'>".$button2."</div>
						<div class='modal fade' id='optoutModal' tabindex='-1' role='dialog' aria-labelledby='optoutModalLabel'>
                            <div class='modal-dialog' role='document'>
                                <div class='modal-content'>
                                    <div class='modal-header'>
                                        <button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                                        <h4 class='modal-title' id='optoutModalLabel'>Opting Out</h4> 
                                    </div>
                                	<form action='res/actions.php?channel_partner_id=".$channel_partner_id."&rfp_id=".$rfp_id."&action=".$action2."&rfp_energy_technology=".$rfp_energy_technology."&optin_rid=".$optin_rid."&redirect=1' method='post'>
                                		<div class='modal-body'>
	                                        <input type='hidden' name='rfp_rid' value='".$rfp_rid."'>
	                                        <input type='hidden' name='opted_out_by' value='".$_SESSION['userFirstName']." ".$_SESSION['userLastName']."'>
	                                        <div class='form-group'>
	                                            <label for='reason_text' class='control-label'>Reason for opting out</label>
	                                            <textarea class='form-control' name='reason_text' id='reason_text' required></textarea>
	                                        </div>
	                                    </div>
	                                    <div class='modal-footer'>
	                                        <button type='button' class='btn btn-default' data-dismiss='modal'>Close</button>
	                                        <input type='submit' class='btn btn-primary btn_interaction' id='".$rfp_rid."' value='Opt Out'>
	                                    </div>
                                  	</form>
                                </div>
                            </div>
                    	</div>";
			}

			//$out .= "<tr>";
			$out .= "<tr class='' data-href='rfp_detail.php?rfp_rid=".$rfp_rid."&channel_partner_id=".$channel_partner_id."'>";
			$out .= "<td  class='title'><a href='rfp_detail.php?rfp_rid=".$rfp_rid."&channel_partner_id=".$channel_partner_id."'>".$rfp_id."</a></td>";
			$out .= "<td>".$rfp_energy_technology."</td>";
			$out .= "<td>".$rfp_status."</td>";
			$out .= "<td>".$rfp_release_date."</td>";
			$out .= "<td>".$rfp_due_date."</td>";
			$out .= "<td>".$rfp_location."</td>";
			$out .= "<td>".$rfp_opportunity."</td>";
			$out .= "<td>".$form1."</td>";
			$out .= "<td>".$form2."</td>";
			$out .= "</tr>";
			// if($rfp_energy_technology=="LED"){
			// 	$out.="<script>
			// 		$('.remove_if_led".$rfp_id."').hide();
			// 	</script>";
			// }
		}
		$out .= "</tbody></table>";
    // $out .= "</tbody><tfoot><style>.pagination>.active>a, .pagination>.active>span, .pagination>.active>a:hover, .pagination>.active>span:hover, .pagination>.active>a:focus, .pagination>.active>span:focus{background:#a0aec4;border-color:#a0aec4;}</style>
    //     <tr>
    //         <td colspan='12'>
    //             <div class='text-right'>
    //                 <ul class='pagination pagination-split m-t-30'></ul>
    //             </div>
    //         </td>
    //     </tr>
    // </tfoot></table>";

	} else {$out .= "no rfps found";}

	return $out;
}

function showAwardedRFPs($channel_partner_id) {
	qbLogin(); //login if not already;
	global $qb;
	$out = "";
	$thiss="";
	$placeholder_related_customer_rfp="n";
	if($channel_partner_id !=""){
		//search awarded site rfps for this partner
		$query = "{'73'.EX.'".$channel_partner_id."'}";		
		$site_rfps = $qb->DoQuery(C_DBID_SITE_RFPS, $query, 'a', '54');
		if ($site_rfps[0]){
			
			
			$out .= "<h3>Awarded RFPs</h3>";
			$out .= "<table id='demo-foo-row-toggler' style='border-collapse:collapse;' class='tablesaw table-bordered table-hover table toggle-circle' data-tablesaw-mode='stack' data-tablesaw-sortable data-tablesaw-sortable-switch data-tablesaw-minimap data-tablesaw-mode-switch>
	                <thead>
	                    <tr>
	                        <th data-toggle='true' scope='col' data-tablesaw-sortable-col data-tablesaw-priority='persist'>RFP Name</th>
	                        <th scope='col' data-tablesaw-sortable-col data-tablesaw-sortable-default-col data-tablesaw-priority='3'>Product Type</th>
	                        <th scope='col' data-tablesaw-sortable-col data-tablesaw-sortable-default-col data-tablesaw-priority='3'>Status</th>
	                        <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='2'>Release Date</th>
	                        <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='1'>Due Date</th>
	                        <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='4'>Location</th>
	                        <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='4'>Opportunity</th>
	                        <th data-hide='all' scope='col'>More Details</th>
	                        <th data-hide='all' scope='col'>Client</th>
	                        <th data-hide='all' scope='col'>Building Owner</th>
	                        <th data-hide='all' scope='col'>Offtaker</th>
	                        <th data-hide='all' scope='col'>Number of Sites</th>
	                        <th data-hide='all' scope='col'>Type of Install</th>
	                        <th data-hide='all' scope='col'>High Level of Request</th>
	                        <th data-hide='all' scope='col'>Roof Age</th>
	                        <th data-hide='all' scope='col'>Offtaker Credit</th>
	                    </tr>
	                </thead><tbody>";
			for ($j = 0; $j < count($site_rfps); $j++) {
				$site_rfp_rid = $site_rfps[$j][3];
				$rfp_rid = $site_rfps[$j][54];
				if ($placeholder_related_customer_rfp==$rfp_rid)
					continue;
				$rfp_id = $site_rfps[$j][32];
				$rfp_status = $site_rfps[$j][33];
				$rfp_status = explode("-", $rfp_status);
				$rfp_status = $rfp_status[1];
				$rfp_release_date = convertQBDate($site_rfps[$j][74]);
				$rfp_due_date = convertQBDate($site_rfps[$j][75]);
				$rfp_location = $site_rfps[$j][76];
				$rfp_opportunity = $site_rfps[$j][63];
				$rfp_energy_technology = $site_rfps[$j][77];
				$rfp_client = $site_rfps[$j][36];
				$rfp_offtaker = $site_rfps[$j][78];
				$rfp_num_of_sites = $site_rfps[$j][79];
				$rfp_type_of_install = $site_rfps[$j][80];
				$rfp_high_level_of_request = $site_rfps[$j][81];
				$rfp_roof_age = $site_rfps[$j][82];
				$rfp_outtaker_credit = $site_rfps[$j][83];
				$rfp_building_owner = $site_rfps[$j][84];
				$out .= "<tr>";
				$out .= "<td  class='title'>".$rfp_id."</td>";
				$out .= "<td>".$rfp_energy_technology."</td>";
				$out .= "<td>".$rfp_status."</td>";
				$out .= "<td>".$rfp_release_date."</td>";
				$out .= "<td>".$rfp_due_date."</td>";
				$out .= "<td>".$rfp_location."</td>";
				$out .= "<td>".$rfp_opportunity."</td>";
				$out .= "<td><a href='awarded_rfp_detail.php?rfp_rid=".$rfp_rid."&channel_partner_id=".$channel_partner_id."'><b>View -></b></td>";
				
				$out .= "<td>".$rfp_client."</td>";
				$out .= "<td>".$rfp_building_owner."</td>";
				$out .= "<td>".$rfp_offtaker."</td>";
				$out .= "<td>".$rfp_num_of_sites."</td>";
				$out .= "<td>".$rfp_type_of_install."</td>";
				$out .= "<td>".$rfp_high_level_of_request."</td>";
				$out .= "<td>".$rfp_roof_age."</td>";
				$out .= "<td>".$rfp_outtaker_credit."</td>";
				$out .= "</tr>";
				$placeholder_related_customer_rfp=$rfp_rid;
				// if($rfp_energy_technology=="LED"){
				// 	$out.="<script>
				// 		$('.remove_if_led".$rfp_id."').hide();
				// 	</script>";
				// }
			}
			$out .= "</tbody></table>";
		} else {
			$out .= "no awarded rfps found";
		}
	}
	return $out;
}

function showSingleRFP($rfp_id, $channel_partner_id, $category) {
	qbLogin(); //login if not already;
	global $qb;
	global $terms_and_conditions;
	$out = "";
	$show_optin = true;
	$query = "{'3'.EX.'".$rfp_id."'}";
	//look for energy_technology required for each rfp and only show those that match current users partner energy_technologies.
	$rfps = $qb->DoQuery(C_DBID_CUSTOMER_RFPS, $query, 'a', '3');
	if ($rfps[0]){
		$rfp_id = $rfps[0][6];
		$rfp_rid = $rfps[0][3];
		$rfp_related_customer = $rfps[0][47];
		$rfp_status = $rfps[0][7];
		$rfp_status_numeric = $rfps[0][61];
		$rfp_status = explode("-", $rfp_status);
		//determine whether to show or hide the optin/out button
		if (intval ($rfp_status[0]) >= 8 || $category =='awarded') {
			$show_optin = false;
		}
		if ($rfp_status_numeric == '7.1') {
			$shortlisted=true;
		} else {
			$shortlisted=false;
		}
		$rfp_status = $rfp_status[1];
		$rfp_release_date = convertQBDate($rfps[0][8]);
		$rfp_due_date = convertQBDate($rfps[0][9]);
		$rfp_location = $rfps[0][56];
		$rfp_opportunity = $rfps[0][55];
		$rfp_energy_technology = $rfps[0][44];
		if ($rfp_energy_technology == "Solar") {
			$rfps_title = 'Bid Submission Form';
			$show_bid_form_upload = false;
			$activate_accordion = true;
		} elseif ($rfp_energy_technology == "LED") {
			$rfps_title = 'Bid Submission Form - Overview';
			$show_bid_form_upload = true;
			$activate_accordion = true;

		}else {
			$rfps_title = 'Site List';
			$show_bid_form_upload = true;
			$activate_accordion = false;

		}
		$rfp_client = $rfps[0][47];

		$rfp_offtaker = $rfps[0][11];
		$rfp_num_of_sites = $rfps[0][54];
		$rfp_type_of_install = $rfps[0][66];
		$rfp_high_level_of_request = $rfps[0][74];
		if ($rfp_high_level_of_request == "Site Lease Rent"){
			$show_ppa = false;
		} else {
			$show_ppa = true;
		}
		$rfp_roof_age = $rfps[0][68];
		$rfp_outtaker_credit = $rfps[0][69];
		$rfp_building_owner = $rfps[0][65];
		//file attachments
		$rfp_property_site_info = $rfps[0][25];
		$rfp_rfp = $rfps[0][29];
		$rfp_appendix = $rfps[0][30];
		$rfp_language_acceptance_form = $rfps[0][57];
		$rfp_bid_submission_form = $rfps[0][58];
		$rfp_site_lease = $rfps[0][59];
		$rfp_ppa = $rfps[0][60];
		$rfp_site_utility_bills = $rfps[0][71];
		$rfp_led_procurement_contract = $rfps[0][89];
		$rfp_lighting_audit_data = $rfps[0][90];
		$rfp_other_document = $rfps[0][122];
		
		//look for related customer to get Channel Partner Qualification Form file
		$customers = $qb->DoQuery(C_DBID_CUSTOMERS, "{'6'.EX.'".$rfp_related_customer."'}", 'a', '3');
		if ($customers[0]){
			$client_rid = $customers[0][3];
			$rfp_channel_partner_qualification_form = $customers[0][54];
		}
		//if file exists create links for download
		if ($rfp_property_site_info){
			$rfp_property_site_info = "<a class='track_download' id='track_psi_download_date' href='https://blackbearenergy.quickbase.com/up/bk6wv3ukn/a/r".$rfp_rid."/e25/v0'>Property Site Information</a>";
		} else {
			$rfp_property_site_info = "";
		}
		if ($rfp_rfp){
			$rfp_rfp = "<a class='track_download' id='track_rfp_rfp' href='https://blackbearenergy.quickbase.com/up/bk6wv3ukn/a/r".$rfp_rid."/e29/v0'>RFP</a>";
		} else {
			$rfp_rfp = "";
		}
		if ($rfp_appendix){
			$rfp_appendix = "<a class='track_download' id='track_rfp_appendix' href='https://blackbearenergy.quickbase.com/up/bk6wv3ukn/a/r".$rfp_rid."/e30/v0'>Appendix</a>";
		} else {
			$rfp_appendix = "";
		}
		if ($rfp_language_acceptance_form){
			$rfp_language_acceptance_form = "<a class='track_download' id='track_language_acceptance_form' href='https://blackbearenergy.quickbase.com/up/bk6wv3ukn/a/r".$rfp_rid."/e57/v0'>Language Acceptance Form</a>";
		} else {
			$rfp_language_acceptance_form = "";
		}
		if ($rfp_bid_submission_form){
			$rfp_bid_submission_form = "<a class='track_download' id='track_frp_bid_submission_form' href='https://blackbearenergy.quickbase.com/up/bk6wv3ukn/a/r".$rfp_rid."/e58/v0'>Bid Submission Form</a>";
		} else {
			$rfp_bid_submission_form = "";
		}
		if ($rfp_site_lease){
			$rfp_site_lease = "<a class='track_download' id='track_rfp_site_lease' href='https://blackbearenergy.quickbase.com/up/bk6wv3ukn/a/r".$rfp_rid."/e59/v0'>Site Lease</a>";
		} else {
			$rfp_site_lease = "";
		}
		if ($rfp_led_procurement_contract){
			$rfp_led_procurement_contract = "<a class='track_download' id='track_led_procurement_contract' href='https://blackbearenergy.quickbase.com/up/bk6wv3ukn/a/r".$rfp_rid."/e89/v0'>LED Procurement Contract</a>";
		} else {
			$rfp_led_procurement_contract = "";
		}
		if ($rfp_ppa){
			$rfp_ppa = "<a class='track_download' id='track_rfp_ppa' href='https://blackbearenergy.quickbase.com/up/bk6wv3ukn/a/r".$rfp_rid."/e60/v0'>PPA</a>";
		} else {
			$rfp_ppa = "";
		}
		if ($rfp_lighting_audit_data){
			$rfp_lighting_audit_data = "<a class='track_download' id='track_rfp_lighting_audit_data' href='https://blackbearenergy.quickbase.com/up/bk6wv3ukn/a/r".$rfp_rid."/e90/v0'>Lighting Audit Data</a>";
		} else {
			$rfp_lighting_audit_data = "";
		}
		if ($rfp_site_utility_bills){
			$rfp_site_utility_bills = "<a class='track_download' id='track_rfp_site_utility_bills' href='https://blackbearenergy.quickbase.com/up/bk6wv3ukn/a/r".$rfp_rid."/e71/v0'>Utility Bills</a>";
		} else {
			$rfp_site_utility_bills = "";
		}
		if ($rfp_other_document){
			$rfp_other_document = "<a class='track_download' id='track_rfp_other_document' href='https://blackbearenergy.quickbase.com/up/bk6wv3ukn/a/r".$rfp_rid."/e122/v0'>Other RFP Documents</a>";
		} else {
			$rfp_other_document = "";
		}
		if ($rfp_channel_partner_qualification_form){
			$rfp_channel_partner_qualification_form = "<a class='track_download' id='track_channel_partner_qualification_form' href='https://blackbearenergy.quickbase.com/up/bk6wv3vtt/a/r".$client_rid."/e54/v0'>Channel Partner Qualification Form</a>";
		} else {
			$rfp_channel_partner_qualification_form = "";
		}

		//search bids for this optin
		//search optins 
		$query_optins = "{'10'.EX.'".$rfp_id."'}AND{'17'.EX.'".$channel_partner_id."'}";
		$optins = $qb->DoQuery(C_DBID_OPTINS, $query_optins, 'a', '3', 'sortorder-D');
		if ($optins[0]){
			$optin_rid = $optins[0][3];
			//uploaded file attachments
			$optin_rfp_response = $optins[0][19];
			$optin_laf = $optins[0][20];
			$optin_ppa = $optins[0][21];
			$optin_bid_form = $optins[0][22];
			$optin_other_document = $optins[0][82];
			$num_optin_bids = intval($optins[0][52]);
			$bid_notes = $optins[0][51];
			$fid53 = $optins[0][53];
			$fid54 = $optins[0][54];
			$fid55 = $optins[0][55];
			$fid56 = $optins[0][56];
			$fid57 = $optins[0][57];
			$fid58 = $optins[0][58];
			$fid59 = $optins[0][59];
			$fid60 = $optins[0][60];
			$fid61 = $optins[0][61];
			$fid78 = $optins[0][78];

			$bids_array = array();
			if ($num_optin_bids > 0){
				//search all bids for this optin and save in array
				$query_bids = "{'91'.EX.'".$optin_rid."'}";
				$bids = $qb->DoQuery(C_DBID_BIDS, $query_bids, 'a', '3');				
				if ($bids[0]){					
					for ($k = 0; $k < count($bids); $k++) { 
						$bid_rid = $bids[0][3];
						$bid_related_site = $bids[0][88];
						//add to array to compare later and decide whether add or update
						$bids_array[$bid_related_site] = $bid_rid;
					} 
				}
				$create_or_update_action = "update_bid";
			} else {
				$create_or_update_action = "create_bid";
			}
			if ($optin_rfp_response){
				$optin_rfp_response_link = "<a href='https://blackbearenergy.quickbase.com/up/bk6wv3wbh/a/r".$optin_rid."/e19/v0'>".$optin_rfp_response."</a>";
			} else {
				$optin_rfp_response_link = "";
			}
			if ($optin_laf){
				$optin_laf_link = "<a href='https://blackbearenergy.quickbase.com/up/bk6wv3wbh/a/r".$optin_rid."/e20/v0'>".$optin_laf."</a>";
			} else {
				$optin_laf_link = "";
			}
			if ($optin_ppa){
				$optin_ppa_link = "<a href='https://blackbearenergy.quickbase.com/up/bk6wv3wbh/a/r".$optin_rid."/e21/v0'>".$optin_ppa."</a>";
			} else {
				$optin_ppa_link = "";
			}
			if ($optin_bid_form){
				$optin_bid_form_link = "<a href='https://blackbearenergy.quickbase.com/up/bk6wv3wbh/a/r".$optin_rid."/e22/v0'>".$optin_bid_form."</a>";
			} else {
				$optin_bid_form_link = "";
			}
			if ($optin_other_document){
				$optin_other_document_link = "<a href='https://blackbearenergy.quickbase.com/up/bk6wv3wbh/a/r".$optin_rid."/e82/v0'>".$optin_other_document."</a>";
			} else {
				$optin_other_document_link = "";
			}

			//search channel partner and see if they have a qualification form uploaded
			$channel_partners = $qb->DoQuery(C_DBID_CHANNEL_PARTNERS, "{'3'.EX.'".$channel_partner_id."'}", 'a', '3');
			if ($channel_partners[0]){
				$channel_partner_qualification_form = $channel_partners[0][51];
			}

			if ($channel_partner_qualification_form){
				$channel_partner_qualification_form_link = "<a href='https://blackbearenergy.quickbase.com/up/bk6wv3waw/a/r".$channel_partner_id."/e51/v0'>".$channel_partner_qualification_form."</a>";
			} else {
				$channel_partner_qualification_form_link = "";
			}


			$track_psi_download_date= $optins[0][23];

			//distinguish between general and awarded
			if($optins[0][6]=="Opt In"){
				$action1 = '0';
				$action2 = 'optout';
				$rfp_opted_in[$rfp_id] = true;			
				$button1 = "<span class='custom_label_primary' style='font-weight:400;float:right;color:white;background:#DC3796;border-radius:0;width:90px;padding:6px 10px;font-size:14px;line-height:1.6;cursor:default;display:block;text-align:center;white-space:nowrap;vertical-align:middle;'>Opted In</span>";
				$button2 = "<input class=' btn btn-info btn-outline' type='button' value='Opt Out' style='width:90px;float:right;' data-toggle='modal' data-target='#optoutModal' data-whatever='@mdo'>";
				$form1 = $button1;
				$form2 = $button2."
						<div class='modal fade' id='optoutModal' tabindex='-1' role='dialog' aria-labelledby='optoutModalLabel'>
                            <div class='modal-dialog' role='document'>
                                <div class='modal-content'>
                                    <div class='modal-header'>
                                        <button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                                        <h4 class='modal-title' id='optoutModalLabel'>Opting Out</h4> 
                                    </div>
                                	<form action='res/actions.php?channel_partner_id=".$channel_partner_id."&rfp_id=".$rfp_id."&action=".$action2."&rfp_energy_technology=".$rfp_energy_technology."&optin_rid=".$optin_rid."&redirect=2' method='post'>
                                		<div class='modal-body'>
	                                        <input type='hidden' name='rfp_rid' value='".$rfp_rid."'>
	                                        <input type='hidden' name='opted_out_by' value='".$_SESSION['userFirstName']." ".$_SESSION['userLastName']."'>
	                                        <div class='form-group'>
	                                            <label for='reason_text' class='control-label'>Reason for opting out</label>
	                                            <textarea class='form-control' name='reason_text' id='reason_text' required></textarea>
	                                        </div>
	                                    </div>
	                                    <div class='modal-footer'>
	                                        <button type='button' class='btn btn-default' data-dismiss='modal'>Close</button>
	                                        <input type='submit' class='btn btn-primary btn_interaction' id='".$rfp_id."' value='Opt Out'>
	                                    </div>
                                  	</form>
                                </div>
                            </div>
                    	</div>";
			} elseif ($optins[0][6]=="Opt Out"){
				$action1 = 'optin';
				$action2 = '0';
				$rfp_opted_in[$rfp_id] = false;
				//$button1 = "<input class=' btn btn-primary btn-outline btn_interaction' id='".$rfp_id."' type='submit' value='Opt In' style='width:90px;'>";

				//new button 1 envoking a modal
				$button1 = "<input class=' btn btn-primary btn-xs btn-outline btn_interaction' type='button' value='Opt In' style='width:80px;' data-toggle='modal' data-target='#optinModal".$optin_rid."' data-whatever='@mdo'>";
				$button2 = "<span class='custom_label_info' style='font-weight:400;float:right;color:white;background:#41A0C8;border-radius:0;width:90px;padding:6px 10px;font-size:14px;line-height:1.6;cursor:default;display:block;text-align:center;white-space:nowrap;vertical-align:middle;'>Opted Out</span>";



				//$form1 = "<form style='margin:0 auto;padding:0;float:right;' action='res/actions.php?channel_partner_id=".$channel_partner_id."&rfp_id=".$rfp_id."&rfp_rid=".$rfp_rid."&action=".$action1."&rfp_energy_technology=".$rfp_energy_technology."&optin_rid=".$optin_rid."&redirect=2' method='post'>".$button1."</form>";

				//new opt in
				$form1 = $button1."
						<div class='modal fade' id='optinModal".$optin_rid."' tabindex='-1' role='dialog' aria-labelledby='optinModalLabel'>
                            <div class='modal-dialog' role='document'>
                                <div class='modal-content'>
                                    <div class='modal-header'>
                                        <button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                                        <h2 class='modal-title' id='optinModalLabel'>Opting In</h2> 
                                        <h4 class='modal-title' id='optinModalLabel'>Acknowledgement of Request For Proposal Terms and Conditions</h4>
                                        <div style='max-height:400px;overflow:scroll;'>".$terms_and_conditions."</div>
                                    </div>
                                	<form action='res/actions.php?channel_partner_id=".$channel_partner_id."&rfp_id=".$rfp_id."&action=".$action1."&rfp_energy_technology=".$rfp_energy_technology."&optin_rid=".$optin_rid."&redirect=2' method='post'>
                                		<div class='modal-body'>
	                                        <input type='hidden' name='rfp_rid' value='".$rfp_rid."'>
	                                        <input type='hidden' name='opted_in_by' value='".$_SESSION['userFirstName']." ".$_SESSION['userLastName']."'>
	                                        <div class='form-group'>
	                                            <label for='acknowlege_checkbox' class='control-label'>I acknowlege and agree to the terms and conditions</label>
	                                            <input style='float:left;margin-right:12px;' id='acknowlege_checkbox' name='acknowlege_checkbox' type='checkbox' required>
	                                        </div>
	                                    </div>
	                                    <div class='modal-footer'>
	                                        <button type='button' class='btn btn-default' data-dismiss='modal'>Close</button>
	                                        <input type='submit' class='btn btn-primary btn_interaction' id='".$rfp_rid."' value='Submit'>
	                                    </div>
                                  	</form>
                                </div>
                            </div>
                    	</div>";

				$form2 = $button2;
			}
		} else {
			$optin_rid = 0;
			$action1 = 'optin';
			$action2 = 'optout';
			$rfp_opted_in[$rfp_id] = false;
			//$button1 = "<input class=' btn btn-primary btn-outline btn_interaction' id='".$rfp_id."' type='submit' value='Opt In' style='width:90px;float:right;'>";
			$button1 = "<input class=' btn btn-primary btn-outline btn_interaction' type='button' value='Opt In' id='".$rfp_rid."' style='width:90px;float:right;' data-toggle='modal' data-target='#optinModal".$optin_rid."' data-whatever='@mdo'>";
			$button2 = "<input class=' btn btn-info btn-outline' type='button' value='Opt Out' style='width:90px;float:right;' data-toggle='modal' data-target='#optoutModal' data-whatever='@mdo'>";
			//$form1 = "<form action='res/actions.php?channel_partner_id=".$channel_partner_id."&rfp_id=".$rfp_id."&rfp_rid=".$rfp_rid."&action=".$action1."&rfp_energy_technology=".$rfp_energy_technology."&optin_rid=".$optin_rid."&redirect=2' method='post'>".$button1."</form>";

			$form1 = $button1."
						<div class='modal fade' id='optinModal".$optin_rid."' tabindex='-1' role='dialog' aria-labelledby='optinModalLabel'>
                            <div class='modal-dialog' role='document'>
                                <div class='modal-content'>
                                    <div class='modal-header'>
                                        <button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                                        <h2 class='modal-title' id='optinModalLabel'>Opting In</h2> 
                                        <h4 class='modal-title' id='optinModalLabel'>Acknowledgement of Request For Proposal Terms and Conditions</h4>
                                        <div style='max-height:400px;overflow:scroll;'>".$terms_and_conditions."</div>
                                    </div>
                                	<form action='res/actions.php?channel_partner_id=".$channel_partner_id."&rfp_id=".$rfp_id."&action=".$action1."&rfp_energy_technology=".$rfp_energy_technology."&optin_rid=".$optin_rid."&redirect=2' method='post'>
                                		<div class='modal-body'>
	                                        <input type='hidden' name='rfp_rid' value='".$rfp_rid."'>
	                                        <input type='hidden' name='opted_in_by' value='".$_SESSION['userFirstName']." ".$_SESSION['userLastName']."'>
	                                        <div class='form-group'>
	                                            <label for='acknowlege_checkbox' class='control-label'>I acknowlege and agree to the terms and conditions</label>
	                                            <input style='float:left;margin-right:12px;' id='acknowlege_checkbox' name='acknowlege_checkbox' type='checkbox' required>
	                                        </div>
	                                    </div>
	                                    <div class='modal-footer'>
	                                        <button type='button' class='btn btn-default' data-dismiss='modal'>Close</button>
	                                        <input type='submit' class='btn btn-primary btn_interaction' id='".$rfp_rid."' value='Submit'>
	                                    </div>
                                  	</form>
                                </div>
                            </div>
                    	</div>";

			//$form2 = "<div class='button-box'>".$button2."</div>
			$form2 = $button2."
					<div class='modal fade' id='optoutModal' tabindex='-1' role='dialog' aria-labelledby='optoutModalLabel'>
                        <div class='modal-dialog' role='document'>
                            <div class='modal-content'>
                                <div class='modal-header'>
                                    <button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                                    <h4 class='modal-title' id='optoutModalLabel'>Opting Out</h4> 
                                </div>
                            	<form action='res/actions.php?channel_partner_id=".$channel_partner_id."&rfp_id=".$rfp_id."&action=".$action2."&rfp_energy_technology=".$rfp_energy_technology."&optin_rid=".$optin_rid."&redirect=2' method='post'>
                            		<div class='modal-body'>
                                        <input type='hidden' name='rfp_rid' value='".$rfp_rid."'>
                                        <input type='hidden' name='opted_out_by' value='".$_SESSION['userFirstName']." ".$_SESSION['userLastName']."'>
                                        <div class='form-group'>
                                            <label for='reason_text' class='control-label'>Reason for opting out</label>
                                            <textarea class='form-control' name='reason_text' id='reason_text' required></textarea>
                                        </div>
                                    </div>
                                    <div class='modal-footer'>
                                        <button type='button' class='btn btn-default' data-dismiss='modal'>Close</button>
                                        <input type='submit' class='btn btn-primary btn_interaction' id='".$rfp_id."' value='Opt Out'>
                                    </div>
                              	</form>
                            </div>
                        </div>
                	</div>";
		}
			
		$out .= "<div class='row'>
	                <div class='col-md-4 col-lg-4 col-sm-12'>
	                    <div class='white-box'>";
		$out .= "<h3>RFPs / Q&A -> ".$rfp_id." - <b>".$rfp_status."</b></h3>";
		$out .= "<h3 style='width:200px;float:left;'>RFP Summary</h3>";
		if($show_optin == true){
			$out .= $form1.$form2;
		}		
		$out .= "<table style='border-collapse:collapse;' class='tablesaw table-bordered table-hover table'><tbody>";
		$out .= "<tr><td>Products</td><td>".$rfp_energy_technology."</td></tr>";
		$out .= "<tr><td>Release Date</td><td>".$rfp_release_date."</td></tr>";
		$out .= "<tr><td>Due Date</td><td>".$rfp_due_date."</td></tr>";
		$out .= "<tr><td>Client</td><td>".$rfp_client."</td></tr>";
		$out .= "<tr><td>Building Owner</td><td>".$rfp_building_owner."</td></tr>";
		if ($rfp_energy_technology !== "LED"){
			$out .= "<tr><td>Offtaker</td><td>".$rfp_offtaker."</td></tr>";
		}
		$out .= "<tr><td>Number of Sites</td><td>".$rfp_num_of_sites."</td></tr>";
		$out .= "<tr><td>Location</td><td>".$rfp_location."</td></tr>";
		$out .= "<tr><td>Opportunity (est.)</td><td>".$rfp_opportunity."</td></tr>";
		// if ($rfp_energy_technology !== "LED"){
		// 	$out .= "<tr><td>Offtaker</td><td>".$rfp_offtaker."</td></tr>";
		// }
		if ($rfp_energy_technology !== "LED"){
			$out .= "<tr><td>Type of Install</td><td>".$rfp_type_of_install."</td></tr>";
		}
		$out .= "<tr><td>High Level of Request</td><td>".$rfp_high_level_of_request."</td></tr>";
		if ($rfp_energy_technology !== "LED"){
			$out .= "<tr><td>Roof Age</td><td>".$rfp_roof_age."</td></tr>";
		}
		if ($rfp_energy_technology !== "LED"){
			$out .= "<tr><td>Offtaker Credit</td><td>".$rfp_outtaker_credit."</td></tr>";
		}
		$out .= "</tbody></table></div></div>";
		//email preferences
		$out .= "<div class='col-md-3 col-lg-3 col-sm-12'>
					<div class='white-box'>";
		$out.=schowChanelPartnerUsersEmailPreferences($rfp_rid, 'general', $rfp_id);
		$out.="</div></div>";
		//below will only show if user opted in
		if($optins[0][6] == "Opt In" ){
			//echo $rfp_status;
			//echo $optins[0][6];
			if( $rfp_status != "Pending"){
				//files to download
				$out .= "<div class='col-md-5 col-lg-5 col-sm-12'>
						<div class='white-box'>";
				$out .= "<h3>RFP Document Downloads</h3>";
				$out .= "<table style='border-collapse:collapse;' class='tablesaw table-bordered table-hover table'><tbody>";
				$out .= "<tr><td>".$rfp_property_site_info."</td><td>".$rfp_rfp."</td></tr>";
				$out .= "<tr><td>".$rfp_appendix."</td><td>".$rfp_language_acceptance_form."</td></tr>";
				$out .= "<tr><td>".$rfp_bid_submission_form."</td>";
				if ($rfp_energy_technology !== "LED"){
					$out .= "<td>".$rfp_site_lease."</td></tr>";
				} else {
					$out .= "<td>".$rfp_led_procurement_contract."</td></tr>";
				}
				if ($rfp_energy_technology !== "LED"){
					$out .= "<tr><td>".$rfp_ppa."</td>";
				} else {
					$out .= "<tr><td>".$rfp_lighting_audit_data."</td>";
				}
				$out .= "<td>".$rfp_site_utility_bills."</td></tr>";
				$out .= "<tr><td>".$rfp_other_document."</td>";
				$out .= "<td>".$rfp_channel_partner_qualification_form."</td></tr>";
				$out .= "</tbody></table></div></div>";	
				//files to upload
				$out .= "<div class='col-md-8 col-lg-8 col-sm-12'>
						<div class='white-box'>";
				$out .= "<form enctype='multipart/form-data' method='post' action='res/actions.php?action=upload&rfp_rid=".$rfp_rid."&optin_rid=".$optin_rid."&channel_partner_id=".$channel_partner_id."&category=".$category."'>";
				$out .= "<h3 style='width:260px;float:left;'>RFP Document Uploads</h3>";
				$out .= "<input style='width:180px;float:right;' class='btn btn-primary btn_interaction' id='".$rfp_id."' type='submit' value='Upload Documents'>";
				$out .= "<table style='border-collapse:collapse;' class='tablesaw table-bordered table-hover table'>
					<thead>
						<tr>
							<th>Name</th>
							<th>Uploaded</th>
							<th>File</th>
						</tr>
					</thead>
					<tbody>";
				$out .= "<tr>
							<td>RFP Response</td>
							<td>".$optin_rfp_response_link."</td>
							<td>
								<input type='file' name='optin_rfp_response_file'>
							</td>
						</tr>";
				$out .= "<tr>
							<td>Language Acceptance Form (LAF)</td>
							<td>".$optin_laf_link."</td>
							<td>
								<input type='file' name='optin_laf_file'>
							</td>
						</tr>";
				if ($rfp_energy_technology !== "LED"){
					$out .= "<tr>
							<td>PPA</td>
							<td>".$optin_ppa_link."</td>
							<td>
								<input type='file' name='optin_ppa_file'>
							</td>
						</tr>";
				}
				if ($show_bid_form_upload){
					$out .= "<tr>
								<td>Bid Form</td>
								<td>".$optin_bid_form_link."</td>
								<td>
									<input type='file' name='optin_bid_form_file'>
								</td>
							</tr>";
				}
				$out .= "<tr>
							<td>Other RFP Documents</td>
							<td>".$optin_other_document_link."</td>
							<td>
								<input type='file' name='optin_other_document_file'>
							</td>
						</tr>";
				$out .= "<tr>
							<td>Channel Partner Qualification Form</td>
							<td>".$channel_partner_qualification_form_link."</td>
							<td>
								<input type='file' name='channel_partner_qualification_form_file'>
							</td>
						</tr>";
				$out .= "</tbody></table></form></div></div></div>";
				//site rfp table
				$sites = $qb->DoQuery(C_DBID_SITE_RFPS, "{'54'.EX.'".$rfp_rid."'}", 'a', '3');
				if ($sites[0]){
					$out .= "<div class='row'>
					                <div class='col-md-12 col-lg-12 col-sm-12'>
					                    <div class='white-box'>";
					$out .= "<h3>".$rfps_title."</h3>";
					if ($activate_accordion){
						$out .= "<table id='demo-foo-row-toggler' style='border-collapse:collapse;' class='tablesaw table-bordered table-hover table toggle-circle' data-tablesaw-mode='stack' data-tablesaw-minimap data-tablesaw-mode-switch data-page-size='300'>";
						$out.="<thead>
							<tr>
								<th data-toggle='true' scope='col' data-tablesaw-sortable-col data-tablesaw-priority='persist'>Site Name</th>
								<th scope='col' data-tablesaw-sortable-col data-tablesaw-sortable-default-col data-tablesaw-priority='3'>Street</th>
								<th scope='col' data-tablesaw-sortable-col data-tablesaw-sortable-default-col data-tablesaw-priority='3'>City</th>
								<th scope='col' data-tablesaw-sortable-col data-tablesaw-sortable-default-col data-tablesaw-priority='3'>State</th>
								<th scope='col' data-tablesaw-sortable-col data-tablesaw-sortable-default-col data-tablesaw-priority='3'>Zip</th>
								<th scope='col' data-tablesaw-sortable-col data-tablesaw-sortable-default-col data-tablesaw-priority='3'>Utility</th>";
								if ($rfp_energy_technology !== "LED"){
									$out.="<th scope='col' data-tablesaw-sortable-col data-tablesaw-sortable-default-col data-tablesaw-priority='3'>Roof kW est.</th>";
								}
								$out.="<th scope='col' data-tablesaw-sortable-col data-tablesaw-sortable-default-col data-tablesaw-priority='3'>Status</th>
								<th data-hide='all' scope='col'></th>
							</tr>
						</thead>";
					} else {
						$out .= "<table style='border-collapse:collapse;' class='tablesaw table-bordered table-hover table' data-tablesaw-mode='stack' data-tablesaw-minimap data-tablesaw-mode-switch data-page-size='300'>";
						$out.="<thead>
							<tr>
								<th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='persist'>Site Name</th>
								<th scope='col' data-tablesaw-sortable-col data-tablesaw-sortable-default-col data-tablesaw-priority='3'>Street</th>
								<th scope='col' data-tablesaw-sortable-col data-tablesaw-sortable-default-col data-tablesaw-priority='3'>City</th>
								<th scope='col' data-tablesaw-sortable-col data-tablesaw-sortable-default-col data-tablesaw-priority='3'>State</th>
								<th scope='col' data-tablesaw-sortable-col data-tablesaw-sortable-default-col data-tablesaw-priority='3'>Zip</th>
								<th scope='col' data-tablesaw-sortable-col data-tablesaw-sortable-default-col data-tablesaw-priority='3'>Utility</th>";
								if ($rfp_energy_technology !== "LED"){
									$out.="<th scope='col' data-tablesaw-sortable-col data-tablesaw-sortable-default-col data-tablesaw-priority='3'>Roof kW est.</th>";
								}
								$out.="<th scope='col' data-tablesaw-sortable-col data-tablesaw-sortable-default-col data-tablesaw-priority='3'>Status</th>
							</tr>
						</thead>";
					}
					
					for ($l = 0; $l < count($sites); $l++) { 
						$site_rfp_rid = $sites[$l][3];
						$site_rfp_name = $sites[$l][30];
						$site_rfp_street = $sites[$l][39];
						$site_rfp_city = $sites[$l][41];
						$site_rfp_state = $sites[$l][40];
						$site_rfp_zip = $sites[$l][42];
						$site_rfp_utility = $sites[$l][43];
						$site_rfp_roof_kw = $sites[$l][52];
						$site_rfp_hosting_company = $sites[$l][85];
						$site_rfp_lease_owned = $sites[$l][86];
						$site_rfp_roof_area = $sites[$l][87];
						$site_rfp_roof_year = $sites[$l][88];
						$site_rfp_anticipated_reroof_year = $sites[$l][89];
						$site_rfp_parent_credit_rating = $sites[$l][90];

						//penetrating mounting options
						$bid_penetrating_mounting_selection = "";
						$bid_penetrating_mounting_array = array('Y','N');

						//query bids if there for this site
						$query_bids = "{'91'.EX.'".$optin_rid."'}AND{'88'.EX.'".$site_rfp_rid."'}";
						$bids = $qb->DoQuery(C_DBID_BIDS, $query_bids, 'a', '3');
						if ($bids[0]){
							for ($z = 0; $z < count($bids); $z++) { 
								$bid_rid = $bids[$z][3];
								$bid_host_company = $bids[$z][3];
								$bid_lease_owned = $bids[$z][3];
								$bid_roof_area = $bids[$z][3];
								$bid_roof_year = $bids[$z][3];
								$bid_anticipated_reroof_area = $bids[$z][3];
								$bid_parent_credit_rating = $bids[$z][3];
								$bid_estimated_break_even_ppa_rate = $bids[$z][27];
								$bid_epc_price = $bids[$z][35];
								$bid_installed_capacity = $bids[$z][37];
								$bid_array_platform_area = $bids[$z][100];
								$bid_array_tilt = $bids[$z][38];
								$bid_array_azimuth = $bids[$z][39];
								$bid_estimated_annual_generation = $bids[$z][3];
								$bid_estimated_annual_demand = $bids[$z][40];
								$bid_penetrating_mounting = $bids[$z][43];

								//led bid level fields
								$bid_subtotal_cost_before_rebate = $bids[$z][107];
								$bid_watts_of_led_lights = $bids[$z][108];
								$bid_rebate = $bids[$z][109];
								$bid_net_price = $bids[$z][110];
								$bid_demand_reduction = $bids[$z][111];
								$bid_annual_energy_savings = $bids[$z][112];
								$bid_estimated_annual_savings = $bids[$z][113];
								$bid_annual_maintenance_savings = $bids[$z][114];
								$bid_relamp_cost = $bids[$z][115];
								$bid_black_bear_fee = $bids[$z][116];

								foreach ($bid_penetrating_mounting_array as $penetrating_mounting) {
									if($penetrating_mounting == $bid_penetrating_mounting){
										$bid_penetrating_mounting_selection.="<option value='".$penetrating_mounting."' selected='selected'>".$penetrating_mounting."</option>";
									} else {
										$bid_penetrating_mounting_selection.="<option value='".$penetrating_mounting."'>".$penetrating_mounting."</option>";
									}
								}
								$bid_module = $bids[$z][44];
								$bid_inverter = $bids[$z][45];
								$bid_ppa_agreement_signed = convertQBDateHtml5($bids[$z][46]);
								$bid_permits_received = convertQBDateHtml5($bids[$z][47]);
								$bid_start_of_construction = convertQBDateHtml5($bids[$z][48]);
								$bid_commercial_operation_rate = convertQBDateHtml5($bids[$z][49]);
								$submitted = $bids[$z][102];
							}
							$new_or_update_rid = $bid_rid;
							$create_or_update='update';
							$disabled='';
							$save_or_update_button = "Save Bid";
						} else {
							$new_or_update_rid="a".$site_rfp_rid;//just to fill with something to make unique name attr if new record
							$create_or_update='add';
							$disabled='disabled';
							$save_or_update_button = "Create Bid";
							$bid_rid = '';
							$bid_host_company = '';
							$bid_lease_owned = '';
							$bid_roof_area = '';
							$bid_roof_year = '';
							$bid_anticipated_reroof_area = '';
							$bid_parent_credit_rating = '';
							$bid_estimated_break_even_ppa_rate = .0000;
							$bid_epc_price = '';
							$bid_installed_capacity = '';
							$bid_array_platform_area = '';
							$bid_array_tilt = '';
							$bid_array_azimuth = '';
							$bid_estimated_annual_generation = '';
							$bid_estimated_annual_demand = '';
							$bid_penetrating_mounting = '';
							$bid_penetrating_mounting_selection.="<option value='Y'>Y</option><option value='N'>N</option>";
							$bid_module =  '';
							$bid_inverter = '';
							$bid_ppa_agreement_signed = '';
							$bid_permits_received = '';
							$bid_start_of_construction = '';
							$bid_commercial_operation_rate = '';
							$submitted='';
							//led bid level fields
							$bid_subtotal_cost_before_rebate = '';
							$bid_watts_of_led_lights = '';
							$bid_rebate = '';
							$bid_net_price = '';
							$bid_demand_reduction = '';
							$bid_annual_energy_savings = '';
							$bid_estimated_annual_savings = '';
							$bid_annual_maintenance_savings = '';
							$bid_relamp_cost = '';
							$bid_black_bear_fee = '';
						}
						if ($submitted !=""){
							//$submitted_indicator = "<span class='custom_label_info' style='font-weight:400;float:right;color:white;background:#00c292;border-radius:0;width:90px;padding:2px 7px;font-size:12px;line-height:1.5;cursor:default;display:block;text-align:center;white-space:nowrap;vertical-align:middle;'>Submitted</span>";
							$submitted_indicator = "<span class='custom_label_info' style='font-weight:400;color:white;background:#464A63;border-radius:0;width:100px;padding:2px 7px;font-size:12px;line-height:1.5;cursor:default;display:block;text-align:center;white-space:nowrap;vertical-align:middle;margin:0 auto;'><i class='fa fa-check'> </i> Submitted</span>";
						} else {
							$submitted_indicator = "";
						}

						$open_tab_site_rfp = "";
						if (isset($_GET['open_site_rfp']) && $_GET['open_site_rfp'] == $site_rfp_rid  && $activate_accordion){
							$open_tab_site_rfp.=" class=' footable-detail-show'";
						}
						$out .= "<tr ".$open_tab_site_rfp.">
							<td>".$site_rfp_name."</td>
							<td>".$site_rfp_street."</td>
							<td>".$site_rfp_city."</td>
							<td>".$site_rfp_state."</td>
							<td>".$site_rfp_zip."</td>
							<td>".$site_rfp_utility."</td>";
							if ($rfp_energy_technology !== "LED"){
								$out .= "<td>".$site_rfp_roof_kw."</td>";
							}
							$out .= "<td>".$submitted_indicator."</td>";
						if ($activate_accordion){
							$out.="<td>
							<h3 style='width:350px;'>Submit a Bid</h3>
							<form id='".$site_rfp_rid."' action='res/actions.php?action=create_bid&rfp_rid=".$rfp_rid."&channel_partner_id=".$channel_partner_id."&site_rfp_rid=".$site_rfp_rid."&category=".$category."' method='post'>
								<span style='float:right;'>
									<input type='submit' class='btn btn-info btn_interaction' id='".$rfp_id."' value='".$save_or_update_button."'>
									<input type='button' onclick='submitBid(".$site_rfp_rid.",".$bid_rid.",102)' class='btn btn-primary btn_interaction' id='".$rfp_id."' value='Submit Bid' ".$disabled.">
								</span>
								<table style='border-collapse:collapse;width:50%;' class='tablesaw table-bordered table'>";
								if ($rfp_energy_technology !== "LED"){
									$out.="<tr>
										<td style='width:50%;'>Host Company</td>
										<td>".$site_rfp_hosting_company."</td>
									</tr>
									<tr>
										<td>Lease/Owned</td>
										<td>".$site_rfp_lease_owned."</td>
									</tr>
									<tr>
										<td>Roof Area</td>
										<td>".$site_rfp_roof_area."</td>
									</tr>
									<tr>
										<td>Roof Year</td>
										<td>".$site_rfp_roof_year."</td>
									</tr>
									<tr>
										<td>Anticipated Reroof Year</td>
										<td>".$site_rfp_anticipated_reroof_year."</td>
									</tr>
									<tr>
										<td>Parent Credit Rating</td>
										<td>".$site_rfp_parent_credit_rating."</td>
									</tr>";
									if ($show_ppa){
										$out.= "<tr>
											<td>Estimated Break Even PPA Rate</td>
											<td><input name='".$create_or_update.",".C_DBID_BIDS.",".$new_or_update_rid.",27' type='number' value='".$bid_estimated_break_even_ppa_rate."' min='0' max='1' step='.0001'></td>
										</tr>";
									}
									$out.="<tr>
										<td>EPC Price ($/W)</td>
										<td><input class='wholenumber' name='".$create_or_update.",".C_DBID_BIDS.",".$new_or_update_rid.",35' type='number' value='".$bid_epc_price."' step='.0001'></td>
									</tr>
									<tr>
										<td>Installed Capacity (MW)</td>
										<td><input class='wholenumber' name='".$create_or_update.",".C_DBID_BIDS.",".$new_or_update_rid.",37' type='number' value='".$bid_installed_capacity."' step='.0001'></td>
									</tr>
									<tr>
										<td>Gross Array Area (sqf)</td>
										<td><input class='number' name='".$create_or_update.",".C_DBID_BIDS.",".$new_or_update_rid.",100'  value='".$bid_array_platform_area."'></td>
									</tr>
									<tr>
										<td>Array Tilt (Degrees)</td>
										<td><input name='".$create_or_update.",".C_DBID_BIDS.",".$new_or_update_rid.",38' type='number' value='".$bid_array_tilt."'></td>
									</tr>
									<tr>
										<td>Array Azimuth (Degrees)</td>
										<td><input name='".$create_or_update.",".C_DBID_BIDS.",".$new_or_update_rid.",39' type='number' value='".$bid_array_azimuth."'></td>
									</tr>
									<tr>
										<td>Estimated Annual Generation (kWh)</td>
										<td><input class='number' name='".$create_or_update.",".C_DBID_BIDS.",".$new_or_update_rid.",40' value='".$bid_estimated_annual_generation."'></td>
									</tr>
									<tr>
										<td>Estimated Annual Demand (kWh)</td>
										<td><input class='number' name='".$create_or_update.",".C_DBID_BIDS.",".$new_or_update_rid.",41' value='".$bid_estimated_annual_demand."'></td>
									</tr>
									<tr>
										<td>Penetrating Mounting (Y/N)</td>
										<td>
											<select name='".$create_or_update.",".C_DBID_BIDS.",".$new_or_update_rid.",43'>
												<option value='null'></option>
												".$bid_penetrating_mounting_selection."
											</select></td>
									</tr>
									<tr>
										<td>Module</td>
										<td><input name='".$create_or_update.",".C_DBID_BIDS.",".$new_or_update_rid.",44' type='text' value='".$bid_module."'></td>
									</tr>
									<tr>
										<td>Inverter</td>
										<td><input name='".$create_or_update.",".C_DBID_BIDS.",".$new_or_update_rid.",45' type='text' value='".$bid_inverter."'></td>
									</tr>
									<tr>
										<td>PPA / Agreement Signed (Est. Date)</td>
										<td><input name='".$create_or_update.",".C_DBID_BIDS.",".$new_or_update_rid.",46' type='date' value='".$bid_ppa_agreement_signed."'></td>
									</tr>
									<tr>
										<td>Permits Received (Est. Date)</td>
										<td><input name='".$create_or_update.",".C_DBID_BIDS.",".$new_or_update_rid.",47' type='date' value='".$bid_permits_received."'></td>
									</tr>
									<tr>
										<td>Start Of Construction (Est. Date)</td>
										<td><input name='".$create_or_update.",".C_DBID_BIDS.",".$new_or_update_rid.",48' type='date' value='".$bid_start_of_construction."'></td>
									</tr>
									<tr>
										<td>Commercial Operation Date (COD) (Est. Date)</td>
										<td><input name='".$create_or_update.",".C_DBID_BIDS.",".$new_or_update_rid.",49' type='date' value='".$bid_commercial_operation_rate."'></td>
									</tr>";
								} else { //LED here
									$out.="<tr>
										<td>Subtotal Cost before Rebate ($)</td>
										<td><input class='number' name='".$create_or_update.",".C_DBID_BIDS.",".$new_or_update_rid.",107' value='".$bid_subtotal_cost_before_rebate."'></td>
									</tr>
									<tr>
										<td>Watts of LED Lights</td>
										<td><input class='number' name='".$create_or_update.",".C_DBID_BIDS.",".$new_or_update_rid.",108' value='".$bid_watts_of_led_lights."'></td>
									</tr>
									<tr>
										<td>Rebate ($)</td>
										<td><input class='number' name='".$create_or_update.",".C_DBID_BIDS.",".$new_or_update_rid.",109' value='".$bid_rebate."'></td>
									</tr>
									<tr>
										<td>Net Price ($)</td>
										<td><input class='number' name='".$create_or_update.",".C_DBID_BIDS.",".$new_or_update_rid.",110' value='".$bid_net_price."'></td>
									</tr>
									<tr>
										<td>Demand Reduction (kW)</td>
										<td><input class='number' name='".$create_or_update.",".C_DBID_BIDS.",".$new_or_update_rid.",111' value='".$bid_demand_reduction."'></td>
									</tr>
									<tr>
										<td>Annual Energy Savings (kWh)</td>
										<td><input class='number' name='".$create_or_update.",".C_DBID_BIDS.",".$new_or_update_rid.",112' value='".$bid_annual_energy_savings."'></td>
									</tr>
									<tr>
										<td>Estimated Annual Savings ($)</td>
										<td><input class='number' name='".$create_or_update.",".C_DBID_BIDS.",".$new_or_update_rid.",113' value='".$bid_estimated_annual_savings."'></td>
									</tr>
									<tr>
										<td>Annual Maintenance Savings ($)</td>
										<td><input class='number' name='".$create_or_update.",".C_DBID_BIDS.",".$new_or_update_rid.",114' value='".$bid_annual_maintenance_savings."'></td>
									</tr>
									<tr>
										<td>Relamp Cost ($)</td>
										<td><input class='number' name='".$create_or_update.",".C_DBID_BIDS.",".$new_or_update_rid.",115' value='".$bid_relamp_cost."'></td>
									</tr>
									<tr>
										<td>Black Bear Fee ($)</td>
										<td><input class='number' name='".$create_or_update.",".C_DBID_BIDS.",".$new_or_update_rid.",116' value='".$bid_black_bear_fee."'></td>
									</tr>";
								}// end of if LED
									
									$out.= "<input type='hidden' name='".$create_or_update.",".C_DBID_BIDS.",".$new_or_update_rid.",88' value='". $site_rfp_rid ."'>
									<input type='hidden' name='".$create_or_update.",".C_DBID_BIDS.",".$new_or_update_rid.",91' value='". $optin_rid ."'>
								</table>";
								if ($rfp_energy_technology !== "LED"){
									//search for all bid scenarios

									$out.= "<table style='border-collapse:collapse;' class=' table-bordered table'>
										<tr>";
										if ($show_ppa){
											$out.="<th>PPA Term</th>";
										}
											
										$out.=	"<th>Lease Term</th>
											<th>'Value In'</th>
											<th>Credit Support</th>
											<th>Parent Guarantee</th>";
											if ($show_ppa){
												$out.="<th>PPA Price($)</th>";
												if ($shortlisted==true){
													$out.="<th>PPA Price($) shortlisted</th>";
												}
											}
											$out.="<th>Up Front Rent ($)</th>
											<th>Annual Rent($)</th>";
											if ($shortlisted==true){
													$out.="<th>Up Front Rent($) shortlisted</th>
											<th>Annual Rent($) shortlisted</th>";
												}
										$out.="</tr>";
									//search for all bid-bid scenarios for this site rfp and this partner. if find - put in array
									//if not find - they will all be new.
									$existing_bid_bid_scenarios_array =  array();
									$bid_bid_scenarios = $qb->DoQuery(C_DBID_BID_BID_SCENARIOS, "{'15'.EX.'".$site_rfp_rid."'}AND{'29'.EX.'".$optin_rid."'}", 'a', '3');
									if ( is_array($bid_bid_scenarios) ) {
										foreach ($bid_bid_scenarios as $scenario) { 
											$bidBidScenarios[ $scenario[3] ] = $scenario; 
										}
										//$creationDate = $bidBidScenarios[$rid][$fid]; //$rid=2 $fid=1
										foreach ($bid_bid_scenarios as $bid_bid_scenario) {
											$bid_bid_scenario_rid = $bid_bid_scenario[3];
											$bid_bid_scenario_related_bid_scenario = $bid_bid_scenario[18];
											//add to arrray so that we compare below if new or update
											$existing_bid_bid_scenarios_array[$bid_bid_scenario_related_bid_scenario] = $bid_bid_scenario_rid;
										}
									}
									//$newout = "{'15'.EX.'".$site_rfp_rid."'}AND{'28'.EX.'".$channel_partner_id."'}";
									//return $newout;
									$bid_scenarios = $qb->DoQuery(C_DBID_BID_SCENARIOS, "{'45'.EX.'".$rfp_id."'}", 'a', '3');
									if ( is_array($bid_scenarios) ) {
										// for ($m = 0; $m < count($bid_scenarios); $m++) { 
										foreach ($bid_scenarios as $bid_scenario) { 
											$bid_scenario_rid = $bid_scenario[3];
											$bid_scenario_ppa_term = $bid_scenario[6];
											$bid_scenario_lease_term = $bid_scenario[18];
											$bid_scenario_value_in = $bid_scenario[14];
											$bid_scenario_credit_support = $bid_scenario[12];
											$bid_scenario_parent_guarantee = $bid_scenario[26];
											//check if we have this bid - bid scenario already created
											//look line 998
											if(array_key_exists($bid_scenario_rid,$existing_bid_bid_scenarios_array)){
												
												$new_or_update_rid = $existing_bid_bid_scenarios_array[$bid_scenario_rid]; 
												$create_or_update='update';

												if ( $bidBidScenarios[$new_or_update_rid][24] != ""){
													$ppa_price = number_format(floatval ($bidBidScenarios[$new_or_update_rid][24]), 0, '.', ',');
												} else {
													$ppa_price = "";
												}
												if ( $bidBidScenarios[$new_or_update_rid][25] != ""){
													$up_front_rent = number_format(floatval ($bidBidScenarios[$new_or_update_rid][25]), 0, '.', ',');
												} else {
													$up_front_rent = "";
												}
												if ( $bidBidScenarios[$new_or_update_rid][26] != ""){
													$annual_rent = number_format(floatval ($bidBidScenarios[$new_or_update_rid][26]), 0, '.', ',');
												} else {
													$annual_rent = "";
												}
												if ( $bidBidScenarios[$new_or_update_rid][33] != ""){
													$ppa_price_shortlisted = number_format(floatval ($bidBidScenarios[$new_or_update_rid][33]), 0, '.', ',');
												} else {
													$ppa_price_shortlisted = "";
												}
												if ( $bidBidScenarios[$new_or_update_rid][34] != ""){
													$up_front_rent_shortlisted = number_format(floatval ($bidBidScenarios[$new_or_update_rid][34]), 0, '.', ',');
												} else {
													$up_front_rent_shortlisted = "";
												}
												if ( $bidBidScenarios[$new_or_update_rid][35] != ""){
													$annual_rent_shortlisted = number_format(floatval ($bidBidScenarios[$new_or_update_rid][35]), 0, '.', ',');
												} else {
													$annual_rent_shortlisted = "";
												}
											} else {
												$new_or_update_rid="d".$bid_scenario_rid;//just to fill with something to make unique name attr if new record
												$create_or_update='add';
												$ppa_price = "";
												$up_front_rent = "";
												$annual_rent = "";
												$ppa_price_shortlisted = "";
												$up_front_rent_shortlisted = "";
												$annual_rent_shortlisted = "";
											}
											if ($bid_rid==""){
												$disabled='disabled';
											} else{
												$disabled='';
											}
											//$newout = $existing_bid_bid_scenarios_array;
											//$newout = $bid_scenarios;
											//$newout = $bid_bid_scenarios;
											//return $newout;
											$out.= "<tr>";
											if ($show_ppa){
												$out.="<td>".$bid_scenario_ppa_term."</td>";
											}
											$out.= "<td>".$bid_scenario_lease_term."</td>
												<td>".$bid_scenario_value_in."</td>
												<td>".$bid_scenario_credit_support."</td>
												<td>".$bid_scenario_parent_guarantee."</td>";
												if ($show_ppa){
													if ($shortlisted==true){
														$out.="<td><input type='number' name='".$create_or_update.",".C_DBID_BID_BID_SCENARIOS.",".$new_or_update_rid.",24' value='".$ppa_price."' step='.0001' disabled></td>";
														$out.="<td><input type='number' name='".$create_or_update.",".C_DBID_BID_BID_SCENARIOS.",".$new_or_update_rid.",33' value='".$ppa_price_shortlisted."' ".$disabled." step='.0001'></td>";
													} else {
														$out.="<td><input type='number' name='".$create_or_update.",".C_DBID_BID_BID_SCENARIOS.",".$new_or_update_rid.",24' value='".$ppa_price."' ".$disabled." step='.0001'></td>";
													}
												}
												if ($shortlisted==true){
													$out.="<td><input name='".$create_or_update.",".C_DBID_BID_BID_SCENARIOS.",".$new_or_update_rid.",25' value='".$up_front_rent."' class='number' disabled></td>";
													$out.="<td><input name='".$create_or_update.",".C_DBID_BID_BID_SCENARIOS.",".$new_or_update_rid.",34' value='".$up_front_rent_shortlisted."' ".$disabled." class='number'></td>";
													$out.="<td><input name='".$create_or_update.",".C_DBID_BID_BID_SCENARIOS.",".$new_or_update_rid.",26' value='".$annual_rent."' class='number' disabled></td>";
													$out.="<td><input name='".$create_or_update.",".C_DBID_BID_BID_SCENARIOS.",".$new_or_update_rid.",35' value='".$annual_rent_shortlisted."' ".$disabled." class='number'></td>";
												} else {
													$out.="<td><input name='".$create_or_update.",".C_DBID_BID_BID_SCENARIOS.",".$new_or_update_rid.",25' value='".$up_front_rent."' ".$disabled." class='number'></td>";
													$out.="<td><input name='".$create_or_update.",".C_DBID_BID_BID_SCENARIOS.",".$new_or_update_rid.",26' value='".$annual_rent."' ".$disabled." class='number'></td>";
												}
												$out.="<input type='hidden' name='".$create_or_update.",".C_DBID_BID_BID_SCENARIOS.",".$new_or_update_rid.",6' value='". $bid_rid ."'>
												<input type='hidden' name='".$create_or_update.",".C_DBID_BID_BID_SCENARIOS.",".$new_or_update_rid.",15' value='". $site_rfp_rid ."'>
												<input type='hidden' name='".$create_or_update.",".C_DBID_BID_BID_SCENARIOS.",".$new_or_update_rid.",18' value='". $bid_scenario_rid ."'>
												</td>
											</tr>";
										}
										$bid_rid = '';
										$bid_host_company = '';
										$bid_lease_owned = '';
										$bid_roof_area = '';
										$bid_roof_year = '';
										$bid_anticipated_reroof_area = '';
										$bid_parent_credit_rating = '';
										$bid_estimated_break_even_ppa_rate = '';
										$bid_epc_price = '';
										$bid_installed_capacity = '';
										$bid_array_platform_area = '';
										$bid_array_tilt = '';
										$bid_array_azimuth = '';
										$bid_estimated_annual_generation = '';
										$bid_estimated_annual_demand = '';
										$bid_penetrating_mounting = '';
										$bid_module =  '';
										$bid_inverter = '';
										$bid_ppa_agreement_signed = '';
										$bid_permits_received = '';
										$bid_start_of_construction = '';
										$bid_commercial_operation_rate = '';
										$ppa_price = '';
										$up_front_rent = '';
										$annual_rent = '';
										//led bid level fields
										$bid_subtotal_cost_before_rebate = '';
										$bid_watts_of_led_lights = '';
										$bid_rebate = '';
										$bid_net_price = '';
										$bid_demand_reduction = '';
										$bid_annual_energy_savings = '';
										$bid_estimated_annual_savings = '';
										$bid_annual_maintenance_savings = '';
										$bid_relamp_cost = '';
										$bid_black_bear_fee = '';
									}
									$out.= "</table>";
								}// end led
							$out.= "</form>";
							//fixture types 
							if ($rfp_energy_technology == "LED") {
								$out.="<form action='res/actions.php?action=add_fixture_type' style='width:990px;float:right;' method='post'>
								<span><input type='hidden' name='bid_rid' value='".$bid_rid."'>
								<input type='hidden' name='rfp_rid' value='".$rfp_rid."'>
								<input type='text' name='fixture_type' placeholder='fixture type' required>
								<input type='text' name='manufacturer' placeholder='manufacturer' required>
								<input type='number' name='warranty' placeholder='warranty' required>
								<input type='number' name='quantity' placeholder='quantity' required>
								<input type='number' name='unit_price' placeholder='unit price' required></span>
								<span><input type='submit' value='Add Fixture Type' class='btn btn-primary' required></span>
								</form>";
								//search fixture types
								$query = "{'11'.EX.'".$bid_rid."'}";
								$fixture_types = $qb->DoQuery(C_DBID_FIXTURE_TYPES, $query, 'a', '3');
								if ($fixture_types[0]){
									$out.= "<table style='border-collapse:collapse;' class=' table-bordered table'>
									<thead>
									<tr>
										<th>Fixture Type</th>
										<th>Manufacturer</th>
										<th>Warranty (Years)</th>
										<th>Quantity</th>
										<th>Unit Price</th>
										<th style='width:50px;'></th>
									</tr>
									</thead>
									<tbody id=".$bid_rid.">
									";
									for ($z = 0; $z < count($fixture_types); $z++) {
										$fixture_rid = $fixture_types[$z][3];
										$fixture_type = $fixture_types[$z][6];
										$fixture_manufacturer = $fixture_types[$z][7];
										$fixture_warranty = $fixture_types[$z][8];
										$fixture_quantity = $fixture_types[$z][9];
										$fixture_unit_price = $fixture_types[$z][10];

										$out.="<tr>
											<td>".$fixture_type."</td>
											<td>".$fixture_manufacturer."</td>
											<td>".$fixture_warranty."</td>
											<td>".$fixture_quantity."</td>
											<td>".$fixture_unit_price."</td>
											<td><form action='res/actions.php?action=delete_existing_fixture&fixture_rid=".$fixture_rid."&rfp_rid=".$rfp_rid."&channel_partner_id=".$channel_partner_id."' method='post'><input type='submit' class='btn btn-default' value='X'></form></td>
										</tr>
										";
									}
									$out.="</tbody></table>";
								}
							}
						$out .= "</td>";
						} //end if activate accordion

						$out.="</tr>";
		 			}
					$out .= "</tbody></table>";//end of all bid forms
					$out .= "</div></div></div>";
					//show and hide notes and confirmations for certain energy technologies.
					if( $rfp_energy_technology == "Solar" || $rfp_energy_technology == "NJ Wholesale Solar" || $rfp_energy_technology == "LED"){
						$out .= "<div class='row'>";
						//bid notes
						$out .= showBidNotes($optin_rid, $rfp_rid,$category,$channel_partner_id,$bid_notes,$rfp_id);
						//confirmations
						$out .= showConfirmations($rfp_energy_technology,$optin_rid, $rfp_rid,$category,$channel_partner_id,$fid53,$fid54,$fid55,$fid56,$fid57,$fid58,$fid59,$fid60,$fid61,$fid78,$rfp_id);
						$out .= "</div>";
					}
					if (!$track_psi_download_date){
						$out .= "<script>
						$( document ).ready(function() {
							$('#track_psi_download_date').click(function(){
								var user_name = '".$_SESSION['userFirstName']." ".$_SESSION['userLastName']."';
								var optin_rid = '".$optin_rid."';
								var timestamp = Date();
								$.ajax({
									url: 'track_download_date.php',
									method: 'POST',
									data: { user_name: user_name, optin_rid: optin_rid, timestamp: timestamp }
								});
							});
						});
						</script>";
					}
				}
			} // end if !pending
			//Q&A table
			$out .= "<div class='row'>
			                <div class='col-md-12 col-lg-12 col-sm-12'>
			                    <div class='white-box'>";
			$out .= "<h3 style='width:140px;float:left;'>Q&A</h3>";
			$out .= "
					<div class='button-box'>
                        <input style='width:140px;float:right;' class='btn btn-primary' type='submit' data-toggle='modal' data-target='#exampleModal' data-whatever='@mdo' value='Ask a Question'>
                    </div>
                    <div class='modal fade' id='exampleModal' tabindex='-1' role='dialog' aria-labelledby='exampleModalLabel1'>
                        <div class='modal-dialog' role='document'>
                            <div class='modal-content'>
                                <div class='modal-header'>
                                    <button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                                    <h4 class='modal-title' id='exampleModalLabel1'>New message</h4> 
                                </div>
                            	<form method='post' action='res/actions.php?action=ask_question'>
                            		<div class='modal-body'>
                                        <div class='form-group'>
                                            <label for='submitted_by' class='control-label'>Author</label>
                                            <input type='text' class='form-control' id='submitted_by' value='".$_SESSION['userEmail']."' disabled>
                                        </div>
                                        <input type='hidden' name='rfp_id' value='".$rfp_id."'>
                                        <input type='hidden' name='rfp_rid' value='".$rfp_rid."'>
                                        <input type='hidden' name='category' value='".$category."'>
                                        <input type='hidden' name='channel_partner_id' value='".$_GET['channel_partner_id']."'> 
                                        <input type='hidden' name='submitted_by' value='".$_SESSION['uid']."'>
                                        <div class='form-group'>
                                            <label for='question_text' class='control-label'>Message</label>
                                            <textarea class='form-control' name='question_text' id='question_text' required></textarea>
                                        </div>
                                    </div>
                                    <div class='modal-footer'>
                                        <button type='button' class='btn btn-default' data-dismiss='modal'>Close</button>
                                        <input type='submit' class='btn btn-primary btn_interaction' id='".$rfp_id."' value='Ask a Question'>
                                    </div>
                              </form>
                            </div>
                        </div>
                    </div>";

			$out .= "<table style='border-collapse:collapse;' class='tablesaw table-bordered table-hover table tablesaw-sortable'>
				<thead>
					<tr>
						<th>Question</th>
						<th>Answer</th>
						<th>Status</th>
						<th>Submited</th>
						<th>Submitted By</th>
					</tr>
				</thead><tbody>";
			$questions = $qb->DoQuery(C_DBID_QUESTIONS,"{'14'.EX.'".$rfp_id."'}", 'a', '3');
			if ($questions[0]){
				for ($m = 0; $m < count($questions); $m++) {
					$rfp_id = $questions[$m][20];
					$question = $questions[$m][6];
					$answer = $questions[$m][7];
					$status = $questions[$m][10];
					$submitted = convertQBDate($questions[$m][17]);
					$submitted_by = $questions[$m][22];
					$related_channel_partner_id = $questions[$m][25];
					if ($status !== "Answered" && $related_channel_partner_id !== $channel_partner_id){
							continue;
						}
					if ($related_channel_partner_id !== $channel_partner_id){
						$submitted_by = "";
					}
					$out .= "<tr>";
					$out .= "<td>".$question."</td>";
					$out .= "<td>".$answer."</td>";
					$out .= "<td>".$status."</td>";
					$out .= "<td>".$submitted."</td>";
					$out .= "<td>".$submitted_by."</td>";
					$out .= "</tr>";
				}
			}
			$out .= "</tbody></table></div></div></div>";
		}//if opted in
		$out .= "</div>";
	} else {$out .= "no rfps found";}
	return $out;
}

function showCustomerSingleRFP($rfp_id) {
	qbLogin(); //login if not already;
	global $qb;
	$out = "";
	$disabled='disabled';
	$query = "{'3'.EX.'".$rfp_id."'}";
	//look for energy_technology required for each rfp and only show those that match current users partner energy_technologies.
	$rfps = $qb->DoQuery(C_DBID_CUSTOMER_RFPS, $query, 'a', '3');
	if ($rfps[0]){
		$rfp_id = $rfps[0][6];
		$rfp_rid = $rfps[0][3];
		$rfp_status = $rfps[0][7];
		$rfp_status_numeric = $rfps[0][61];
		$rfp_status = explode("-", $rfp_status);
		//determine whether to show or hide the optin/out button
		
		if ($rfp_status_numeric == '7.1') {
			$shortlisted=true;
		} else {
			$shortlisted=false;
		}
		$rfp_status = $rfp_status[1];
		$rfp_release_date = convertQBDate($rfps[0][8]);
		$rfp_due_date = convertQBDate($rfps[0][9]);
		$rfp_location = $rfps[0][56];
		$rfp_opportunity = $rfps[0][55];
		$rfp_energy_technology = $rfps[0][44];

		$rfp_client = $rfps[0][47];

		$rfp_offtaker = $rfps[0][11];
		$rfp_num_of_sites = $rfps[0][54];
		$rfp_type_of_install = $rfps[0][66];
		$rfp_high_level_of_request = $rfps[0][74];
		if ($rfp_high_level_of_request == "Site Lease Rent"){
			$show_ppa = false;
		} else {
			$show_ppa = true;
		}
		$rfp_roof_age = $rfps[0][68];
		$rfp_outtaker_credit = $rfps[0][69];
		$rfp_building_owner = $rfps[0][65];
		//file attachments
		$rfp_property_site_info = $rfps[0][25];
		$rfp_rfp = $rfps[0][29];
		$rfp_appendix = $rfps[0][30];
		$rfp_language_acceptance_form = $rfps[0][57];
		$rfp_bid_submission_form = $rfps[0][58];
		$rfp_site_lease = $rfps[0][59];
		$rfp_ppa = $rfps[0][60];
		$rfp_site_utility_bills = $rfps[0][71];
		$rfp_led_procurement_contract = $rfps[0][89];
		$rfp_lighting_audit_data = $rfps[0][90];
		//if file exists create links for download
		if ($rfp_property_site_info){
			$rfp_property_site_info = "<a class='track_download' id='track_psi_download_date' href='https://blackbearenergy.quickbase.com/up/bk6wv3ukn/a/r".$rfp_rid."/e25/v0'>Property Site Information</a>";
		} else {
			$rfp_property_site_info = "";
		}
		if ($rfp_rfp){
			$rfp_rfp = "<a class='track_download' id='track_rfp_rfp' href='https://blackbearenergy.quickbase.com/up/bk6wv3ukn/a/r".$rfp_rid."/e29/v0'>RFP</a>";
		} else {
			$rfp_rfp = "";
		}
		if ($rfp_appendix){
			$rfp_appendix = "<a class='track_download' id='track_rfp_appendix' href='https://blackbearenergy.quickbase.com/up/bk6wv3ukn/a/r".$rfp_rid."/e30/v0'>Appendix</a>";
		} else {
			$rfp_appendix = "";
		}
		if ($rfp_language_acceptance_form){
			$rfp_language_acceptance_form = "<a class='track_download' id='track_language_acceptance_form' href='https://blackbearenergy.quickbase.com/up/bk6wv3ukn/a/r".$rfp_rid."/e57/v0'>Language Acceptance Form</a>";
		} else {
			$rfp_language_acceptance_form = "";
		}
		if ($rfp_bid_submission_form){
			$rfp_bid_submission_form = "<a class='track_download' id='track_frp_bid_submission_form' href='https://blackbearenergy.quickbase.com/up/bk6wv3ukn/a/r".$rfp_rid."/e58/v0'>Bid Submission Form</a>";
		} else {
			$rfp_bid_submission_form = "";
		}
		if ($rfp_site_lease){
			$rfp_site_lease = "<a class='track_download' id='track_rfp_site_lease' href='https://blackbearenergy.quickbase.com/up/bk6wv3ukn/a/r".$rfp_rid."/e59/v0'>Site Lease</a>";
		} else {
			$rfp_site_lease = "";
		}
		if ($rfp_led_procurement_contract){
			$rfp_led_procurement_contract = "<a class='track_download' id='track_led_procurement_contract' href='https://blackbearenergy.quickbase.com/up/bk6wv3ukn/a/r".$rfp_rid."/e89/v0'>LED Procurement Contract</a>";
		} else {
			$rfp_led_procurement_contract = "";
		}
		if ($rfp_ppa){
			$rfp_ppa = "<a class='track_download' id='track_rfp_ppa' href='https://blackbearenergy.quickbase.com/up/bk6wv3ukn/a/r".$rfp_rid."/e60/v0'>PPA</a>";
		} else {
			$rfp_ppa = "";
		}
		if ($rfp_lighting_audit_data){
			$rfp_lighting_audit_data = "<a class='track_download' id='track_rfp_lighting_audit_data' href='https://blackbearenergy.quickbase.com/up/bk6wv3ukn/a/r".$rfp_rid."/e90/v0'>Lighting Audit Data</a>";
		} else {
			$rfp_lighting_audit_data = "";
		}
		if ($rfp_site_utility_bills){
			$rfp_site_utility_bills = "<a class='track_download' id='track_rfp_site_utility_bills' href='https://blackbearenergy.quickbase.com/up/bk6wv3ukn/a/r".$rfp_rid."/e71/v0'>Utility Bills</a>";
		} else {
			$rfp_site_utility_bills = "";
		}
		//search bids for this optin
		$out .= "<div class='row'>
	                <div class='col-md-4 col-lg-4 col-sm-12'>
	                    <div class='white-box'>";
		$out .= "<h3>RFPs / Q&A -> ".$rfp_id." - <b>".$rfp_status."</b></h3>";
		$out .= "<h3 style='width:200px;float:left;'>RFP Summary</h3>";
		
		$out .= "<table style='border-collapse:collapse;' class='tablesaw table-bordered table-hover table'><tbody>";
		$out .= "<tr><td>Products</td><td>".$rfp_energy_technology."</td></tr>";
		$out .= "<tr><td>Release Date</td><td>".$rfp_release_date."</td></tr>";
		$out .= "<tr><td>Due Date</td><td>".$rfp_due_date."</td></tr>";
		$out .= "<tr><td>Client</td><td>".$rfp_client."</td></tr>";
		$out .= "<tr><td>Building Owner</td><td>".$rfp_building_owner."</td></tr>";
		if ($rfp_energy_technology !== "LED"){
			$out .= "<tr><td>Offtaker</td><td>".$rfp_offtaker."</td></tr>";
		}
		$out .= "<tr><td>Number of Sites</td><td>".$rfp_num_of_sites."</td></tr>";
		$out .= "<tr><td>Location</td><td>".$rfp_location."</td></tr>";
		$out .= "<tr><td>Opportunity (est.)</td><td>".$rfp_opportunity."</td></tr>";
		// if ($rfp_energy_technology !== "LED"){
		// 	$out .= "<tr><td>Offtaker</td><td>".$rfp_offtaker."</td></tr>";
		// }
		if ($rfp_energy_technology !== "LED"){
			$out .= "<tr><td>Type of Install</td><td>".$rfp_type_of_install."</td></tr>";
		}
		$out .= "<tr><td>High Level of Request</td><td>".$rfp_high_level_of_request."</td></tr>";
		if ($rfp_energy_technology !== "LED"){
			$out .= "<tr><td>Roof Age</td><td>".$rfp_roof_age."</td></tr>";
		}
		if ($rfp_energy_technology !== "LED"){
			$out .= "<tr><td>Offtaker Credit</td><td>".$rfp_outtaker_credit."</td></tr>";
		}
		$out .= "</tbody></table></div></div>";
		//email preferences
		$out .= "<div class='col-md-3 col-lg-3 col-sm-12'>
					<div class='white-box'>";
		$out.=schowCustomerUsers();
		$out.="</div></div>";
		//below will only show if user opted in
		//if($optins[0][6] == "Opt In" ){
			//echo $rfp_status;
			//echo $optins[0][6];
			//if( $rfp_status != "Pending"){
				//files to download
				$out .= "<div class='col-md-5 col-lg-5 col-sm-12'>
						<div class='white-box'>";
				$out .= "<h3>RFP Document Downloads</h3>";
				$out .= "<table style='border-collapse:collapse;' class='tablesaw table-bordered table-hover table'><tbody>";
				$out .= "<tr><td>".$rfp_property_site_info."</td><td>".$rfp_rfp."</td></tr>";
				$out .= "<tr><td>".$rfp_appendix."</td><td>".$rfp_language_acceptance_form."</td></tr>";
				$out .= "<tr><td>".$rfp_bid_submission_form."</td>";
				if ($rfp_energy_technology !== "LED"){
					$out .= "<td>".$rfp_site_lease."</td></tr>";
				} else {
					$out .= "<td>".$rfp_led_procurement_contract."</td></tr>";
				}
				if ($rfp_energy_technology !== "LED"){
					$out .= "<tr><td>".$rfp_ppa."</td>";
				} else {
					$out .= "<tr><td>".$rfp_lighting_audit_data."</td>";
				}
				$out .= "<td>".$rfp_site_utility_bills."</td></tr>";
				$out .= "</tbody></table></div></div>";	
				//files to upload
				$out .= "<div class='col-md-8 col-lg-8 col-sm-12'>
						<div class='white-box'>";
				
				
				$sites = $qb->DoQuery(C_DBID_SITE_RFPS, "{'54'.EX.'".$rfp_rid."'}", 'a', '3');
				if ($sites[0]){
					$out .= "<div class='row'>
					                <div class='col-md-12 col-lg-12 col-sm-12'>
					                    <div class='white-box'>";
					$out .= "<h3>Site RFPs</h3>";
					
					$out .= "<table style='border-collapse:collapse;' class='tablesaw table-bordered table-hover table' data-tablesaw-mode='stack' data-tablesaw-minimap data-tablesaw-mode-switch>";
					$out.="<thead>
						<tr>
							<th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='persist'>Site Name</th>
							<th scope='col' data-tablesaw-sortable-col data-tablesaw-sortable-default-col data-tablesaw-priority='3'>Street</th>
							<th scope='col' data-tablesaw-sortable-col data-tablesaw-sortable-default-col data-tablesaw-priority='3'>City</th>
							<th scope='col' data-tablesaw-sortable-col data-tablesaw-sortable-default-col data-tablesaw-priority='3'>State</th>
							<th scope='col' data-tablesaw-sortable-col data-tablesaw-sortable-default-col data-tablesaw-priority='3'>Zip</th>
							<th scope='col' data-tablesaw-sortable-col data-tablesaw-sortable-default-col data-tablesaw-priority='3'>Utility</th>";
							if ($rfp_energy_technology !== "LED"){
								$out.="<th scope='col' data-tablesaw-sortable-col data-tablesaw-sortable-default-col data-tablesaw-priority='3'>Roof kW est.</th>";
							}
							$out.="
						</tr>
					</thead>";

					for ($l = 0; $l < count($sites); $l++) { 
						$site_rfp_rid = $sites[$l][3];
						$site_rfp_name = $sites[$l][30];
						$site_rfp_street = $sites[$l][39];
						$site_rfp_city = $sites[$l][41];
						$site_rfp_state = $sites[$l][40];
						$site_rfp_zip = $sites[$l][42];
						$site_rfp_utility = $sites[$l][43];
						$site_rfp_roof_kw = $sites[$l][52];
						$site_rfp_hosting_company = $sites[$l][85];
						$site_rfp_lease_owned = $sites[$l][86];
						$site_rfp_roof_area = $sites[$l][87];
						$site_rfp_roof_year = $sites[$l][88];
						$site_rfp_anticipated_reroof_year = $sites[$l][89];
						$site_rfp_parent_credit_rating = $sites[$l][90];

						$out .= "<tr>
							<td>".$site_rfp_name."</td>
							<td>".$site_rfp_street."</td>
							<td>".$site_rfp_city."</td>
							<td>".$site_rfp_state."</td>
							<td>".$site_rfp_zip."</td>
							<td>".$site_rfp_utility."</td>";
						if ($rfp_energy_technology !== "LED"){
							$out .= "<td>".$site_rfp_roof_kw."</td>";
						}
						$out.="</tr>";
		 			}
					$out .= "</tbody></table>";//end of all bid forms
					$out .= "</div></div></div>";
				}
			//} // end if !pending
			//Q&A table
			$out .= "<div class='row'>
			                <div class='col-md-12 col-lg-12 col-sm-12'>
			                    <div class='white-box'>";
			$out .= "<h3 style='width:140px;float:left;'>Q&A</h3>";

			$out .= "<table style='border-collapse:collapse;' class='tablesaw table-bordered table-hover table tablesaw-sortable'>
				<thead>
					<tr>
						<th>Question</th>
						<th>Answer</th>
						<th>Status</th>
						<th>Submited</th>
						<th>Submitted By</th>
					</tr>
				</thead><tbody>";
			$questions = $qb->DoQuery(C_DBID_QUESTIONS,"{'14'.EX.'".$rfp_id."'}", 'a', '3');
			if ($questions[0]){
				for ($m = 0; $m < count($questions); $m++) {
					$rfp_id = $questions[$m][20];
					$question = $questions[$m][6];
					$answer = $questions[$m][7];
					$status = $questions[$m][10];
					$submitted = convertQBDate($questions[$m][17]);
					$submitted_by = $questions[$m][22];
					$related_channel_partner_id = $questions[$m][25];
					$out .= "<tr>";
					$out .= "<td>".$question."</td>";
					$out .= "<td>".$answer."</td>";
					$out .= "<td>".$status."</td>";
					$out .= "<td>".$submitted."</td>";
					$out .= "<td>".$submitted_by."</td>";
					$out .= "</tr>";
				}
			}
			$out .= "</tbody></table></div></div></div>";
		//}//if opted in
		$out .= "</div>";
	} else {$out .= "no rfps found";}
	return $out;
}


function schowCustomerUsers(){
	qbLogin(); //login if not already;
	global $qb;
	$out="";
	if(isset($_SESSION['related_customer_rid']) && !empty($_SESSION['related_customer_rid'])){
		$related_customer_rid=$_SESSION['related_customer_rid'];
		//querry all members for this channel partner
		//then query their preferences for this client rfp 
		//and fill the table accordingly
		$out.="<h3>Member Email Preferences</h3>";
		
		$members_query = "{'15'.EX.'".$related_customer_rid."'}";
		$members = $qb->DoQuery(C_DBID_USERS, $members_query, 'a', '3');
		if ($members[0]){
			for ($j = 0; $j < count($members); $j++) { // for each prescription
				$member_rid = $members[$j][3];
				$member_name = $members[$j][6];
				$member_last_name = $members[$j][7];
				$out.=$member_name." ".$member_last_name;
				$out.="<br>";
			}
		} else {
			$out.="No customer rid";
		}
		
	}
	return $out;
}

function showAwardedQuestions($channel_partner_id) {
	$out = "";
	if($channel_partner_id !=""){
		qbLogin(); //login if not already;
		global $qb;	
		
	    $site_rfps = $qb->DoQuery(C_DBID_SITE_RFPS,"{'73'.EX.'".$channel_partner_id."'}", 'a', '3');
		if ($site_rfps[0]){
			$current_rfp_id ='';
			$out .= "<h3>Q&A</h3>";
			$out .= "<table style='border-collapse:collapse;' class='tablesaw table-bordered table-hover table' data-tablesaw-mode='stack' data-tablesaw-sortable data-tablesaw-sortable-switch data-tablesaw-minimap data-tablesaw-mode-switch>
	            <thead>
	                <tr>
	                    <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='persist'><abbr title='RFP Name'>RFP Name</abbr></th>
	                    <th scope='col' data-tablesaw-sortable-col data-tablesaw-sortable-default-col data-tablesaw-priority='3'>Question</th>
	                    <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='1'>Answer</th>
	                    <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='4'>Status</th>
	                    <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='4'>Submitted</th>
	                    <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='4'>Submitted By</th>
	                </tr>
	            </thead><tbody>";
			for ($i = 0; $i < count($site_rfps); $i++) {
				$rfp_rid = $site_rfps[$i][54];
				$questions = $qb->DoQuery(C_DBID_QUESTIONS,"{'15'.EX.'".$rfp_rid."'}", 'a', '3');
				
				
				if ($questions[0] && $rfp_rid != $current_rfp_id){
					for ($k = 0; $k < count($questions); $k++) {
						$rfp_id = $questions[$k][20];
						$question = $questions[$k][6];
						$answer = $questions[$k][7];
						$status = $questions[$k][10];
						$submitted = convertQBDate($questions[$k][17]);
						$submitted_by = $questions[$k][22];
						$related_channel_partner_id = $questions[$k][25];
						if ($status !== "Answered" && $related_channel_partner_id !== $channel_partner_id){
							continue;
						}
						if ($related_channel_partner_id !== $channel_partner_id){
							$submitted_by = "";
						}
						$out .= "<tr>";
						$out .= "<td  class='title'>".$rfp_id."</td>";
						$out .= "<td>".$question."</td>";
						$out .= "<td>".$answer."</td>";
						$out .= "<td>".$status."</td>";
						$out .= "<td>".$submitted."</td>";
						$out .= "<td>".$submitted_by."</td>";		
						$out .= "</tr>";
					}
				} else {
					//$out .= "no questions found";
				}
				$current_rfp_id = $rfp_rid;
			}
			$out .= "</tbody></table>";
		}
	} else {
		$out .= "no channel partner id";
	}
	return $out;
}

function showQuestions($channel_partner_id) {
	qbLogin(); //login if not already;
	global $qb;
	$out = "";
	$out .= "<h3>Q&A</h3>";
	$out .= "<table style='border-collapse:collapse;' class='tablesaw table-bordered table-hover table' data-tablesaw-mode='stack' data-tablesaw-sortable data-tablesaw-sortable-switch data-tablesaw-minimap data-tablesaw-mode-switch>
            <thead>
                <tr>
                    <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='persist'><abbr title='RFP Name'>RFP Name</abbr></th>
                    <th scope='col' data-tablesaw-sortable-col data-tablesaw-sortable-default-col data-tablesaw-priority='3'>Question</th>
                    <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='1'>Answer</th>
                    <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='4'>Status</th>
                    <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='4'>Submitted</th>
                    <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='4'>Submitted By</th>
                </tr>
            </thead><tbody>";
	//search all shortlisted
    if ($_SESSION['customer_deactivated'] =='1'){
    	$query = "{'3'.XEX.'0'}AND{'17'.EX.'".$channel_partner_id."'}AND{'36'.EX.'7.1'}AND{'29'.OBF.'".$_SESSION['customer_deactivation_date']."'}";
    } else {
    	$query = "{'3'.XEX.'0'}AND{'17'.EX.'".$channel_partner_id."'}AND{'36'.EX.'7.1'}";
    }
    $shortlisted = $qb->DoQuery(C_DBID_OPTINS, $query, 'a', '3');
    // $out .= "{'3'.XEX.'0'}AND{'17'.EX.'".$channel_partner_id."'}AND{'36'.EX.'7.1'}";
    // return $out;
	if ($shortlisted[0]){
		$query = "";
		for ($a = 0; $a < count($shortlisted); $a++) {
			$rfp_id = $shortlisted[$a][10];	
			$rfp_rid = $shortlisted[$a][11];
			//search questions
			$questions = $qb->DoQuery(C_DBID_QUESTIONS,"{'14'.EX.'".$rfp_id."'}", 'a', '3');
				if ($questions[0]){
					for ($k = 0; $k < count($questions); $k++) {
						$question = $questions[$k][6];
						$answer = $questions[$k][7];
						$status = $questions[$k][10];
						$submitted = convertQBDate($questions[$k][17]);
						$submitted_by = $questions[$k][22];
						$related_channel_partner_id = $questions[$k][25];
						if ($status !== "Answered" && $related_channel_partner_id !== $channel_partner_id){
							continue;
						}

						if ($related_channel_partner_id !== $channel_partner_id){
							$submitted_by = "";
						}
						$out .= "<tr>";
						$out .= "<td  class='title'>".$rfp_id."</td>";
						$out .= "<td>".$question."</td>";
						$out .= "<td>".$answer."</td>";
						$out .= "<td>".$status."</td>";
						$out .= "<td>".$submitted."</td>";
						$out .= "<td>".$submitted_by."</td>";
						$out .= "</tr>";
					}
				}
		}
	}

	//search for all specialties for that channel partner
	$specializations = $qb->DoQuery(C_DBID_SPECIALIZATIONS, "{'3'.XEX.'0'}AND{'12'.EX.'".$channel_partner_id."'}", 'a', '3');
	if ($specializations[0]){
		
		$query = "";
		for ($i = 0; $i < count($specializations); $i++) {
			$energy_technology = $specializations[$i][7];
			//for each energy_technology put in query string
			if( $i > 0 ){ $query.="OR"; }
			$query .= "{'44'.EX.'".$energy_technology."'}";
		}
		if ($_SESSION['customer_deactivated'] =='1'){
	    	$query .="AND({'61'.LTE.'7'}AND{'61'.GTE.'3'}AND{'8'.OBF.'".$_SESSION['customer_deactivation_date']."'})";
	    } else {
	    	$query .="AND({'61'.LTE.'7'}AND{'61'.GTE.'3'})";
	    }
	    //$out.=$query;
		//look for energy_technology required for each rfp and only show those that match current users partner energy_technologies.
		$rfps = $qb->DoQuery(C_DBID_CUSTOMER_RFPS, $query, 'a', '3');
		if ($rfps[0]){
			for ($j = 0; $j < count($rfps); $j++) {
				$rfp_id = $rfps[$j][6];
				$questions = $qb->DoQuery(C_DBID_QUESTIONS,"{'14'.EX.'".$rfp_id."'}", 'a', '3');
				if ($questions[0]){
					for ($k = 0; $k < count($questions); $k++) {
						$question = $questions[$k][6];
						$answer = $questions[$k][7];
						$status = $questions[$k][10];
						$submitted = convertQBDate($questions[$k][17]);
						$submitted_by = $questions[$k][22];
						$related_channel_partner_id = $questions[$k][25];
						if ($status !== "Answered" && $related_channel_partner_id !== $channel_partner_id){
							continue;
						}

						if ($related_channel_partner_id !== $channel_partner_id){
							$submitted_by = "";
						}
						$out .= "<tr>";
						$out .= "<td  class='title'>".$rfp_id."</td>";
						$out .= "<td>".$question."</td>";
						$out .= "<td>".$answer."</td>";
						$out .= "<td>".$status."</td>";
						$out .= "<td>".$submitted."</td>";
						$out .= "<td>".$submitted_by."</td>";
						$out .= "</tr>";
					}
				}
			}
			$out .="</tbody></table>";
		} else {$out .= "no rfps found";}
	} else {
		$out .= "no results found";
	}
	return $out;
}

function askQuestion($submitted_by,$question_text,$rfp_id, $channel_partner_id,$rfp_rid,$category){
	qbLogin();
	global $qb;
	$fields = array(
		array('fid' => '21', 'value' => $submitted_by), 
		array('fid' => '6', 'value' => $question_text),
		array('fid' => '15', 'value' => $rfp_rid)
	);
	$qb->AddRecord(C_DBID_QUESTIONS, $fields, false);

	if ($category=='general'){
		$redirect_file='rfp_detail';
	} elseif ($category=='awarded'){
		$redirect_file='awarded_rfp_detail';
	}
	redirect($redirect_file.".php?rfp_rid=".$rfp_rid."&channel_partner_id=".$channel_partner_id);
}

function optIn($channel_partner_id, $rfp_id, $rfp_energy_technology, $optin_rid, $redirect, $rfp_rid) {
	qbLogin();
	global $qb;
	//search specialization based on rfp and partner id
	$query = "{'12'.EX.'".$channel_partner_id."'}AND({'7'.EX.'".$rfp_energy_technology."'}OR{'7'.EX.'Invite Only'})";
	$specializations = $qb->DoQuery(C_DBID_SPECIALIZATIONS, $query, 'a', '3');
	if ($specializations[0]){
		$specialization_rid = $specializations[0][3];
	} else {
		//die("cannot find speciaization");
		//create new special invite specialization
		//get it's rid and proceed the same
		$response = $qb->AddRecord(C_DBID_SPECIALIZATIONS, array(array('fid' => '12', 'value' => $channel_partner_id), array('fid' => '7', 'value' => "Invite Only")), false);
		$specialization_rid = $response->rid;
	}
	if($optin_rid == '0'){
		$qb->AddRecord(C_DBID_OPTINS, array(array('fid' => '7', 'value' => $specialization_rid), array('fid' => '11', 'value' => $rfp_rid),array('fid' => '6', 'value' => "Opt In"),array('fid' => '111', 'value' => "1")), false);
	} else {
		$qb->EditRecord(C_DBID_OPTINS, $optin_rid, array(array('fid' => '7', 'value' => $specialization_rid), array('fid' => '11', 'value' => $rfp_rid),array('fid' => '6', 'value' => "Opt In"),array('fid' => '111', 'value' => "1")), false);
	}
	if($redirect =='1'){
		redirect ('index.php');
	} elseif ($redirect =='2'){
		redirect ("rfp_detail.php?rfp_rid=".$rfp_rid."&channel_partner_id=".$channel_partner_id);
	}
}

function optOut($channel_partner_id, $rfp_id, $rfp_energy_technology, $optin_rid, $opted_out_by, $reason_text, $redirect, $rfp_rid) {
	qbLogin();
	global $qb;
	//search specialization based on rfp and partner id
	$query = "{'12'.EX.'".$channel_partner_id."'}AND({'7'.EX.'".$rfp_energy_technology."'}OR{'7'.EX.'Invite Only'})";
	$specializations = $qb->DoQuery(C_DBID_SPECIALIZATIONS, $query, 'a', '3');
	if ($specializations[0]){
		$specialization_rid = $specializations[0][3];
	} else {
		//die("cannot find speciaization");
		//create new special invite specialization
		//get it's rid and proceed the same
		$response = $qb->AddRecord(C_DBID_SPECIALIZATIONS, array(array('fid' => '12', 'value' => $channel_partner_id), array('fid' => '7', 'value' => "Invite Only")), false);
		$specialization_rid = $response->rid;
	}
	if($optin_rid == '0'){
		$qb->AddRecord(C_DBID_OPTINS, array(array('fid' => '7', 'value' => $specialization_rid), array('fid' => '11', 'value' => $rfp_rid),array('fid' => '6', 'value' => "Opt Out"),array('fid' => '38', 'value' => "Opted out by: ".$opted_out_by,", Reason: ".$reason_text)), false);
	} else {
		$qb->EditRecord(C_DBID_OPTINS, $optin_rid, array(array('fid' => '7', 'value' => $specialization_rid), array('fid' => '11', 'value' => $rfp_rid),array('fid' => '6', 'value' => "Opt Out"),array('fid' => '38', 'value' => "Opted out by: ".$opted_out_by.", Reason: ".$reason_text)), false);
	}
	if($redirect =='1'){
		redirect ('index.php');
	} elseif ($redirect =='2'){
		redirect ("rfp_detail.php?rfp_rid=".$rfp_rid."&channel_partner_id=".$channel_partner_id);
	}	
}

function uploadFileOptin($rfp_rid, $optin_rid, $channel_partner_id, $optin_rfp_response_file, $optin_laf_file, $optin_ppa_file, $optin_bid_form_file,$optin_other_document_file,$channel_partner_qualification_form_file,$category){
	qbLogin(); //login if not already;
	global $qb;
	// Validate that the file uploaded is an image
	//$check = getimagesize($file["tmp_name"]);
	//if(!$check) return false; //Invalid image
	// Get file name
	$filename1 = $optin_rfp_response_file['name'];
	$filename2 = $optin_laf_file['name'];
	$filename3 = $optin_ppa_file['name'];
	$filename4 = $optin_bid_form_file['name'];
	$filename5 = $optin_other_document_file['name'];
	$filename6 = $channel_partner_qualification_form_file['name'];
	$fields = array();	
	//Get file contents (data)
	if ($filename1) {
		$fileContents1 = file_get_contents($optin_rfp_response_file["tmp_name"]);
		array_push($fields, array(
			'fid' => 19,
			'value' => base64_encode($fileContents1),
			'filename' => $filename1
		));
	}
	if ($filename2) {
		$fileContents2 = file_get_contents($optin_laf_file["tmp_name"]);
		array_push($fields, array(
			'fid' => 20,
			'value' => base64_encode($fileContents2),
			'filename' => $filename2
		));
	}
	if ($filename3) {
		$fileContents3 = file_get_contents($optin_ppa_file["tmp_name"]);
		array_push($fields, array(
			'fid' => 21,
			'value' => base64_encode($fileContents3),
			'filename' => $filename3
		));
	}
	if ($filename4) {
		$fileContents4 = file_get_contents($optin_bid_form_file["tmp_name"]);
		array_push($fields, array(
			'fid' => 22,
			'value' => base64_encode($fileContents4),
			'filename' => $filename4
		));
	}
	if ($filename5) {
		$fileContents5 = file_get_contents($optin_other_document_file["tmp_name"]);
		array_push($fields, array(
			'fid' => 82,
			'value' => base64_encode($fileContents5),
			'filename' => $filename5
		));
	}
	$response = $qb->EditRecord(C_DBID_OPTINS, $optin_rid, $fields);

	//save this file to channel partners table
	$fields = array();
	//Get file contents (data)
	if ($filename6) {
		$fileContents6 = file_get_contents($channel_partner_qualification_form_file["tmp_name"]);
		array_push($fields, array(
			'fid' => 51,
			'value' => base64_encode($fileContents6),
			'filename' => $filename6
		));
		array_push($fields, array(
			'fid' => 54,
			'value' => date('m/d/Y h:i:s a', time())
		));
	}
	$response = $qb->EditRecord(C_DBID_CHANNEL_PARTNERS, $channel_partner_id, $fields);


	//die();
	//return ($response)?true:false;
	if ($category=='general'){
		$redirect_file='rfp_detail';
	} elseif ($category=='awarded'){
		$redirect_file='awarded_rfp_detail';
	}
	redirect($redirect_file.".php?rfp_rid=".$rfp_rid."&channel_partner_id=".$channel_partner_id);
}

function showMetrics($channel_partner_id) {
	qbLogin(); //login if not already;
	global $qb;
	$out = "";
	//search that channel partner
	$metrics = $qb->DoQuery(C_DBID_CHANNEL_PARTNERS, "{'3'.EX.'".$channel_partner_id."'}", 'a', '3');
	if ($metrics[0]){
		$out .= "<h3>Metrics</h3>";
		$out .= "<table style='border-collapse:collapse;' class=' table-bordered table-hover table'>
            <thead>
                <tr>
                    <th scope='col' data-tablesaw-sortable-col >Key</th>
                    <th scope='col' data-tablesaw-sortable-col >Value</th>
                </tr>
            </thead><tbody>";
		$num_rfps_opted_in = $metrics[0][33];
		$num_rfps_downloaded = $metrics[0][31];
		$average_download_time = $metrics[0][40];
		$num_questions_asked = $metrics[0][32];
		$num_rfps_submitted = $metrics[0][34];
		$num_rfps_won = $metrics[0][35];
		$num_rfps_lost = $metrics[0][36];
		$out .= "<tr>";
		$out .= "<td  class='title'># RFPs Opted IN</td>";
		$out .= "<td>".$num_rfps_opted_in."</td>";
		$out .= "</tr>";
		$out .= "<tr>";
		$out .= "<td  class='title'># RFPs Downloaded</td>";
		$out .= "<td>".$num_rfps_downloaded."</td>";
		$out .= "</tr>";
		$out .= "<tr>";
		$out .= "<td  class='title'>RFP Download Date vs. Isue Date – Average</td>";
		$out .= "<td>".$average_download_time."</td>";
		$out .= "</tr>";
		$out .= "<tr>";
		$out .= "<td  class='title'># Questions Asked</td>";
		$out .= "<td>".$num_questions_asked."</td>";
		$out .= "</tr>";
		$out .= "<tr>";
		$out .= "<td  class='title'># RFPs Submitted</td>";
		$out .= "<td>".$num_rfps_submitted."</td>";
		$out .= "</tr>";
		$out .= "<tr>";
		$out .= "<td  class='title'># RFPs Won</td>";
		$out .= "<td>".$num_rfps_won."</td>";
		$out .= "</tr>";
		$out .= "<tr>";
		$out .= "<td  class='title'># RFPs Lost</td>";
		$out .= "<td>".$num_rfps_lost."</td>";
		$out .= "</tr>";		
		$out .= "</tbody></table>";
	} else {
		$out .= "no results found";
	}
	return $out;
}

//cutomers
function showAwardedSiteRfps($customer_id) {
	qbLogin(); //login if not already;
	global $qb;
	$out = "";
	if($customer_id !=""){
		//$query = "({'60'.XEX.''}OR{'93'.XEX.''})AND{'44'.EX.'".$customer_id."'}";
		$query = "{'44'.EX.'".$customer_id."'}";
		//look for energy_technology required for each rfp and only show those that match current users partner energy_technologies.
		$site_rfps = $qb->DoQuery(C_DBID_SITE_RFPS, $query, 'a', '3');
		if ($site_rfps[0]){
			$out .= "<h3>Site RFPs</h3>";
			$out .= "<table id='demo-foo-row-toggler' style='border-collapse:collapse;' class='tablesaw table-bordered table-hover table toggle-circle' data-tablesaw-mode='stack' data-tablesaw-sortable data-tablesaw-sortable-switch data-tablesaw-minimap data-tablesaw-mode-switch>
	                <thead>
	                    <tr>
	                        <th data-toggle='true' scope='col' data-tablesaw-sortable-col data-tablesaw-priority='persist'>Site Name</th>
	                        <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='1'>Street</th>
	                        <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='1'>RFP Name</th>
	                        <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='1'>Technology</th>
	                        <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='1'>RFP Status</th>
	                        <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='1'>Channel Partner</th>
	                        <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='4'>Contract Executed</th>
	                        <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='4'>System Size (MWp)</th>
	                        <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='4'>Est. Yr 1 Annual Savings</th>
	                        <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='4'>Contract Term (years)</th>
	                        <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='4'>Commercial Operation Date</th>
	                        <th data-hide='all' scope='col'>Utility</th>
	                        <th data-hide='all' scope='col'>Contract Executed</th>
	                        <th data-hide='all' scope='col'>Permits Received</th>
	                        <th data-hide='all' scope='col'>Start of Construction</th>
	                        <th data-hide='all' scope='col'>Commercial Operation Date</th>
	                    </tr>
	                </thead><tbody>";
			for ($j = 0; $j < count($site_rfps); $j++) { 
				$out .= "<tr>";				
				$site_rfp_name = $site_rfps[$j][30];
				$site_rfp_rfp_rid = $site_rfps[$j][54];
				$site_rfp_rfp_name = $site_rfps[$j][32];
				$site_rfp_technology = $site_rfps[$j][45];
				$site_rfp_street = $site_rfps[$j][39];
				$site_rfp_city = $site_rfps[$j][41];
				$site_rfp_channel_partner = $site_rfps[$j][60];
				$site_rfp_state = $site_rfps[$j][40];
				$site_rfp_contract_executed = $site_rfps[$j][62];
				$site_rfp_system_size = $site_rfps[$j][63]; //opportunity
				$site_rfp_est_tr_1_annual_savins = $site_rfps[$j][64];
				$site_rfp_contract_term = $site_rfps[$j][65];
				$site_rfp_commercial_operation_date = convertQBDate($site_rfps[$j][66]);
				$site_rfp_utility = $site_rfps[$j][43];
				$site_rfp_contract_executed_on = convertQBDate($site_rfps[$j][67]);
				$site_rfp_permits_received = $site_rfps[$j][68];
				$site_rfp_start_of_construction = convertQBDate($site_rfps[$j][69]);
				$site_rfp_status = $site_rfps[$j][33];
				$site_rfp_status = explode("-", $site_rfp_status);
				$site_rfp_status = $site_rfp_status[1];
				
				$out .= "<td  class='title'>".$site_rfp_name."</td>";//$out .= "<td><a href='customer_rfp_detail.php?rfp_rid=".$rfp_rid."&related_customer_rid=".$related_customer_rid."'><b>View -></b></td>";
				$out .= "<td>".$site_rfp_street."</td>";
				$out .= "<td><a href='customer_rfp_detail.php?rfp_rid=".$site_rfp_rfp_rid."'>".$site_rfp_rfp_name."</a></td>";
				$out .= "<td>".$site_rfp_technology."</td>";
				$out .= "<td>".$site_rfp_status."</td>";
				$out .= "<td>".$site_rfp_channel_partner."</td>";
				$out .= "<td>".$site_rfp_contract_executed."</td>";
				$out .= "<td>".$site_rfp_system_size."</td>";
				$out .= "<td>$".$site_rfp_est_tr_1_annual_savins."</td>";
				$out .= "<td>".$site_rfp_contract_term."</td>";
				$out .= "<td>".$site_rfp_commercial_operation_date."</td>";
				$out .= "<td>".$site_rfp_utility."</td>";
				$out .= "<td>".$site_rfp_contract_executed_on."</td>";
				$out .= "<td>".$site_rfp_permits_received."</td>";
				$out .= "<td>".$site_rfp_start_of_construction."</td>";
				$out .= "<td>".$site_rfp_commercial_operation_date."</td>";
				$out .= "</tr>";
			}
			$out .= "</tbody></table>";
		} else {
			$out .= "no awarded awarded rfps found";
		} 
	} else {
			$out .= "no customer id";
	}	
	return $out;
}

function showCustomerRFPsCustomerView($related_customer_rid) {
	qbLogin(); //login if not already;
	global $qb;
	$out = "";
	$thiss="";
	$placeholder_related_customer_rfp="n";
	//$out .= $related_customer_rid;
	if($related_customer_rid !=""){
		//search awarded site rfps for this partner
		$query = "{'44'.EX.'".$related_customer_rid."'}";		
		$site_rfps = $qb->DoQuery(C_DBID_SITE_RFPS, $query, 'a', '54');
		if ($site_rfps[0]){

			$out .= "<h3>RFPs</h3>";
			$out .= "<table id='demo-foo-row-toggler' style='border-collapse:collapse;' class='tablesaw table-bordered table-hover table toggle-circle' data-tablesaw-mode='stack' data-tablesaw-sortable data-tablesaw-sortable-switch data-tablesaw-minimap data-tablesaw-mode-switch>
	                <thead>
	                    <tr>
	                        <th data-toggle='true' scope='col' data-tablesaw-sortable-col data-tablesaw-priority='persist'>RFP Name</th>
	                        <th scope='col' data-tablesaw-sortable-col data-tablesaw-sortable-default-col data-tablesaw-priority='3'>Product Type</th>
	                        <th scope='col' data-tablesaw-sortable-col data-tablesaw-sortable-default-col data-tablesaw-priority='3'>Status</th>
	                        <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='2'>Release Date</th>
	                        <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='1'>Due Date</th>
	                        <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='4'>Location</th>
	                        <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='4'>Opportunity</th>
	                        <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='4'># Opt Ins</th>
	                        <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='4'># Opt Outs</th>
	                        <th data-hide='all' scope='col'>More Details</th>
	                        <th data-hide='all' scope='col'>Client</th>
	                        <th data-hide='all' scope='col'>Building Owner</th>
	                        <th data-hide='all' scope='col'>Offtaker</th>
	                        <th data-hide='all' scope='col'>Number of Sites</th>
	                        <th data-hide='all' scope='col'>Type of Install</th>
	                        <th data-hide='all' scope='col'>High Level of Request</th>
	                        <th data-hide='all' scope='col'>Roof Age</th>
	                        <th data-hide='all' scope='col'>Offtaker Credit</th>
	                    </tr>
	                </thead><tbody>";
			for ($j = 0; $j < count($site_rfps); $j++) {
				$site_rfp_rid = $site_rfps[$j][3];
				$rfp_rid = $site_rfps[$j][54];
				if ($placeholder_related_customer_rfp==$rfp_rid)
					continue;
				$rfp_id = $site_rfps[$j][32];
				$rfp_status = $site_rfps[$j][33];
				$rfp_status = explode("-", $rfp_status);
				$rfp_status = $rfp_status[1];
				$rfp_release_date = convertQBDate($site_rfps[$j][74]);
				$rfp_due_date = convertQBDate($site_rfps[$j][75]);
				$rfp_location = $site_rfps[$j][76];
				$rfp_opportunity = $site_rfps[$j][63];
				$rfp_num_optins = $site_rfps[$j][95];
				if ($rfp_num_optins==""){$rfp_num_optins=0;}
				$rfp_num_optouts = $site_rfps[$j][96];
				if ($rfp_num_optouts==""){$rfp_num_optouts=0;}
				$rfp_energy_technology = $site_rfps[$j][77];
				$rfp_client = $site_rfps[$j][36];
				$rfp_offtaker = $site_rfps[$j][78];
				$rfp_num_of_sites = $site_rfps[$j][79];
				$rfp_type_of_install = $site_rfps[$j][80];
				$rfp_high_level_of_request = $site_rfps[$j][81];
				$rfp_roof_age = $site_rfps[$j][82];
				$rfp_outtaker_credit = $site_rfps[$j][83];
				$rfp_building_owner = $site_rfps[$j][84];
				$out .= "<tr>";
				$out .= "<td  class='title'>".$rfp_id."</td>";
				$out .= "<td>".$rfp_energy_technology."</td>";
				$out .= "<td>".$rfp_status."</td>";
				$out .= "<td>".$rfp_release_date."</td>";
				$out .= "<td>".$rfp_due_date."</td>";
				$out .= "<td>".$rfp_location."</td>";
				$out .= "<td>".$rfp_opportunity."</td>";
				$out .= "<td>".$rfp_num_optins."</td>";
				$out .= "<td>".$rfp_num_optouts."</td>";
				$out .= "<td><a href='customer_rfp_detail.php?rfp_rid=".$rfp_rid."&related_customer_rid=".$related_customer_rid."'><b>View -></b></td>";
				
				$out .= "<td>".$rfp_client."</td>";
				$out .= "<td>".$rfp_building_owner."</td>";
				$out .= "<td>".$rfp_offtaker."</td>";
				$out .= "<td>".$rfp_num_of_sites."</td>";
				$out .= "<td>".$rfp_type_of_install."</td>";
				$out .= "<td>".$rfp_high_level_of_request."</td>";
				$out .= "<td>".$rfp_roof_age."</td>";
				$out .= "<td>".$rfp_outtaker_credit."</td>";
				$out .= "</tr>";
				$placeholder_related_customer_rfp=$rfp_rid;
				// if($rfp_energy_technology=="LED"){
				// 	$out.="<script>
				// 		$('.remove_if_led".$rfp_id."').hide();
				// 	</script>";
				// }
			}
			$out .= "</tbody></table>";
		} else {
			$out .= "no awarded rfps found";
		}
	}
	return $out;
}

function showMasterSiteList($related_customer_rid) {
	qbLogin(); //login if not already;
	global $qb;
	$out = "";
	//search for all sites for this customer
	$sites = $qb->DoQuery(C_DBID_SITES, "{'3'.XEX.'0'}AND{'56'.EX.'".$related_customer_rid."'}", 'a', '3');
	if ($sites[0]){
		$out .= "<h3>Master Site List</h3>";
		$out .= "<table style='border-collapse:collapse;' class='tablesaw table-bordered table-hover table' data-tablesaw-mode='stack' data-tablesaw-sortable data-tablesaw-sortable-switch data-tablesaw-minimap data-tablesaw-mode-switch>
                <thead>
                    <tr>
                        <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='persist'>Site Name</th>
                        <th scope='col' data-tablesaw-sortable-col data-tablesaw-sortable-default-col data-tablesaw-priority='3'>Street</th>
                        <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='1'>City</th>
                        <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='4'>State</th>
                        <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='4'>Zipcode</th>
                        <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='4'>Utility By</th>
                        <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='4'>Previously RFPd By</th>
                        <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='4'>Number of RFPs</th>
                        <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='4'>Roof Area (sqf)</th>
                        <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='4'>Roof Install Year</th>
                    </tr>
                </thead><tbody>";		
		
		for ($j = 0; $j < count($sites); $j++) {
			$site_name = $sites[$j][7];
			$site_street = $sites[$j][12];
			$site_city = $sites[$j][13];
			$site_state = $sites[$j][14];
			$site_zipcode = $sites[$j][15];
			$site_utility = $sites[$j][72];
			$site_number_of_rfps = intval($sites[$j][74]);
			$site_roof_area = $sites[$j][68];
			$site_roof_install_year = $sites[$j][28];
			if ($site_number_of_rfps >0){
				$site_previously_rfpd = "Yes";
			} else {
				$site_previously_rfpd = "No";
			}					
			$out .= "<tr>";
			$out .= "<td  class='title'>".$site_name."</td>";
			$out .= "<td>".$site_street."</td>";
			$out .= "<td>".$site_city."</td>";
			$out .= "<td>".$site_state."</td>";
			$out .= "<td>".$site_zipcode."</td>";
			$out .= "<td>".$site_utility."</td>";
			$out .= "<td>".$site_previously_rfpd."</td>";
			$out .= "<td>".$site_number_of_rfps."</td>";
			$out .= "<td>".$site_roof_area."</td>";
			$out .= "<td>".$site_roof_install_year."</td>";
			$out .= "</tr>";
		}
		$out .= "</tbody></table>";
	} else {
		$out .= "no sites found";
	}
	return $out;
}

function uploadMasterSiteList($related_customer_rid, $master_site_list_file){
	qbLogin(); //login if not already;
	global $qb;
	// Get file name
	$filename = $master_site_list_file['name'];
	$fields = array();	
	if ($filename) {
		$fileContents = file_get_contents($master_site_list_file["tmp_name"]);
		array_push($fields, array(
			'fid' => 7,
			'value' => base64_encode($fileContents),
			'filename' => $filename
		));
		array_push($fields, array(
			'fid' => 6,
			'value' => $related_customer_rid
		));
		$response = $qb->AddRecord(C_DBID_CUSTOMER_FILES, $fields, false);
	}
	redirect("master_sites.php");
}

function showUploadedMasterSiteList($related_customer_rid){
	qbLogin(); //login if not already;
	global $qb;
	$out ="";
	//search qb for previous uploads
	$files = $qb->DoQuery(C_DBID_CUSTOMER_FILES, "{'3'.XEX.'0'}AND{'6'.EX.'".$related_customer_rid."'}", 'a', '3');
	if ($files[0]){
		$out .= "<h3>Uploaded Files</h3>";
		$out .= "<table style='border-collapse:collapse;width:50%;' class='tablesaw table-bordered table'>
                <thead>
                    <tr>
                        <th>File Link</th>
                        <th>Upload Date</th>
                    </tr>
                </thead><tbody>";				
		for ($j = 0; $j < count($files); $j++) {
			$file_link = $files[$j][7];
			$file_rid = $files[$j][3];
			$date_created = convertQBDate($files[$j][1]);
			$out.="<tr><td><a target='_blank' href='https://blackbearenergy.quickbase.com/up/bmcyw2qk7/a/r".$file_rid."/e7/v0'>".$file_link."</a></td><td>".$date_created."</td></tr>";
		}
		$out.="</tbody></table>";
	}
	return $out;
}

function schowChanelPartnerUsersEmailPreferences($rfp_rid, $category,$rfp_id){//not finished
	qbLogin(); //login if not already;
	global $qb;
	$out="";
	if(isset($_SESSION['channel_partner']) && !empty($_SESSION['channel_partner'])){
		$channel_partner=$_SESSION['channel_partner'];
		//querry all members for this channel partner
		//then query their preferences for this client rfp 
		//and fill the table accordingly
		$out.="<form action='res/actions.php?action=save_email_preferences&rfp_rid=".$rfp_rid."&category=".$category."' method='post'>		
		<h3>Member Email Preferences</h3>";
		
		$members_query = "{'20'.EX.'".$channel_partner."'}";
		$members = $qb->DoQuery(C_DBID_USERS, $members_query, 'a', '3');
		if ($members[0]){
			for ($j = 0; $j < count($members); $j++) { // for each prescription
				$member_rid = $members[$j][3];
				$member_name = $members[$j][6];
				$member_last_name = $members[$j][7];
				$out.=$member_name." ".$member_last_name;
				//query email preferences
				$preferences_query = "{'7'.EX.'".$member_rid."'}AND{'12'.EX.'".$rfp_rid."'}";
				$preferences = $qb->DoQuery(C_DBID_CHANNEL_PARTNERS_RFP_EMAIL_PREFERENCES, $preferences_query, 'a', '3');
				if ($preferences[0]){
					$preference_rid = $preferences[0][3];
					$preference = $preferences[0][10];
					$preference_selection = "";
					$preference_array = array('Yes','No');
					foreach ($preference_array as $user_preference) {
						if($user_preference == $preference){
							$preference_selection.="<option value='".$user_preference."' selected='selected'>".$user_preference."</option>";
						} else {
							$preference_selection.="<option value='".$user_preference."'>".$user_preference."</option>";
						}
					}
					$out.=" <select class='pull-right' name='update,".C_DBID_CHANNEL_PARTNERS_RFP_EMAIL_PREFERENCES.",".$preference_rid.",10'>
					<option value='null'></option>".$preference_selection."</select><div style='clear:both;line-height: 0.4;'></div>";
					
				} else {
					$out.=" <select class='pull-right' name='add,".C_DBID_CHANNEL_PARTNERS_RFP_EMAIL_PREFERENCES.",n".$member_rid.",10'>
					<option value='Yes'>Yes</option><option value='No'>No</option></select><div style='clear:both;line-height: 0.4;'></div>";
					//related user
					$out.=" <input name='add,".C_DBID_CHANNEL_PARTNERS_RFP_EMAIL_PREFERENCES.",n".$member_rid.",7' type='hidden' value='".$member_rid."'>";
					//relater RFP
					$out.=" <input name='add,".C_DBID_CHANNEL_PARTNERS_RFP_EMAIL_PREFERENCES.",n".$member_rid.",12' type='hidden' value='".$rfp_rid."'>";
					
				}
				//$out.="<br>";
			}
		} else {
			$out.="No channel partner rid";
		}
		$out.="<br><input class='btn btn-primary btn_interaction' id='".$rfp_id."' type='submit' value='Save Email Preferences'></form>";
	}
	return $out;
}

function showBidNotes($optin_rid, $rfp_rid,$category,$channel_partner_id,$bid_notes,$rfp_id){
	qbLogin(); //login if not already;
	global $qb;
	$out="";
	$out .= "<div class='col-md-4 col-lg-4 col-sm-12'><div class='white-box'>
	<h3>Bid Notes</h3>
	<form action='res/actions.php?action=save_bid_notes&rfp_rid=".$rfp_rid."&optin_rid=".$optin_rid."&category=".$category."&channel_partner_id=".$channel_partner_id."' method='post'>
		<div class='form-group'>
			<textarea style='height:218px;' class='form-control' name='bid_notes' id='bid_notes'>".$bid_notes."</textarea>
		</div>
		<input type='submit' class='btn btn-primary btn_interaction' id='".$rfp_id."' value='Save Bid Notes'>
	</form>";

	$out .= "</div></div>";
	return $out;
}

function saveBidNotesQB($optin_rid, $bid_notes, $rfp_rid,$category,$channel_partner_id){
	qbLogin();
	global $qb;
	$fields = array(
		array(
			'fid' => 51,
			'value' => $bid_notes
		)
	);
	$qb->EditRecord(C_DBID_OPTINS, $optin_rid, $fields);
	if ($category=='general'){
		$redirect_file='rfp_detail';
	} elseif ($category=='awarded'){
		$redirect_file='awarded_rfp_detail';
	}
	redirect($redirect_file.".php?rfp_rid=".$rfp_rid."&channel_partner_id=".$channel_partner_id);

}

function showConfirmations($rfp_energy_technology,$optin_rid, $rfp_rid,$category,$channel_partner_id,$fid53=0,$fid54=0,$fid55=0,$fid56=0,$fid57=0,$fid58=0,$fid59=0,$fid60=0,$fid61=0,$fid78=0,$rfp_id){
	qbLogin(); //login if not already;
	global $qb;
	$out="";
	$options_array = array ('Yes','No');
	$out .= "<div class='col-md-8 col-lg-8 col-sm-12'>
		<div class='white-box'>
			<h3>Bid Confirmations</h3>";
			
			if ($rfp_energy_technology !== "LED"){
				$out .= "<form action='res/actions.php?action=save_bid_confirmations&rfp_rid=".$rfp_rid."&optin_rid=".$optin_rid."&category=".$category."&channel_partner_id=".$channel_partner_id."&rfp_energy_technology=".$rfp_energy_technology."' method='post'>";
				$out .= "<div class='row'><div class='pull-left col-md-11 col-lg-11 col-sm-11'>Confirm bid includes installation and operating and maintenance costs of array</div>";
				$out .= "<select name='fid53' class='pull-right'>";
				$out .= populateSelects($fid53,$options_array);
				$out .= "</select></div>";

				$out .= "<div class='row'><div class='pull-left col-md-11 col-lg-11 col-sm-11'>Confirm bid has decommissioning reserve account</div>";
				$out .= "<select name='fid54' class='pull-right'>";
				$out .= populateSelects($fid54,$options_array);
				$out .= "</select></div>";

				$out .= "<div class='row'><div class='pull-left col-md-11 col-lg-11 col-sm-11'>Confirm bid includes monitoring system to provide historical, current and forecasted data regarding system performance</div>";
				$out .= "<select name='fid55' class='pull-right'>";
				$out .= populateSelects($fid55,$options_array);
				$out .= "</select></div>";

				$out .= "<div class='row'><div class='pull-left col-md-11 col-lg-11 col-sm-11'>Confirm bid includes estimated timeline for deployment</div>";
				$out .= "<select name='fid56' class='pull-right'>";
				$out .= populateSelects($fid56,$options_array);
				$out .= "</select></div>";

				$out .= "<div class='row'><div class='pull-left col-md-11 col-lg-11 col-sm-11'>Confirm system designed is in compliance with CALFIRE</div>";
				$out .= "<select name='fid57' class='pull-right'>";
				$out .= populateSelects($fid57,$options_array);
				$out .= "</select></div>";

				$out .= "<div class='row'><div class='pull-left col-md-11 col-lg-11 col-sm-11'>Confirm reserve areas specified in array designs</div>";
				$out .= "<select name='fid58' class='pull-right'>";
				$out .= populateSelects($fid58,$options_array);
				$out .= "</select></div>";

				$out .= "<div class='row'><div class='pull-left col-md-11 col-lg-11 col-sm-11'>Confirm bid includes slip sheets</div>";
				$out .= "<select name='fid59' class='pull-right'>";
				$out .= populateSelects($fid59,$options_array);
				$out .= "</select></div>";

				$out .= "<div class='row'><div class='pull-left col-md-11 col-lg-11 col-sm-11'>Confirm bid includes Black Bear project fees of $0.10/W paid 50% at contract signing and 50% at commencement of construction</div>";
				$out .= "<select name='fid60' class='pull-right'>";
				$out .= populateSelects($fid60,$options_array);
				$out .= "</select></div>";

				$out .= "<div class='row'><div class='pull-left col-md-11 col-lg-11 col-sm-11'>Confirm bid includes Black Bear carry fees of 2% of gross project revenue paid annually from PTO</div>";
				$out .= "<select name='fid61' class='pull-right'>";
				$out .= populateSelects($fid61,$options_array);
				$out .= "</select></div>";
			} else {
				$out .= "<form action='res/actions.php?action=save_bid_confirmations_led&rfp_rid=".$rfp_rid."&optin_rid=".$optin_rid."&category=".$category."&channel_partner_id=".$channel_partner_id."&rfp_energy_technology=".$rfp_energy_technology."' method='post'>";
				$out .= "<div class='row'><div class='pull-left col-md-11 col-lg-11 col-sm-11'>Confirm bid includes estimated timeline for deployment</div>";
				$out .= "<select name='fid56' class='pull-right'>";
				$out .= populateSelects($fid56,$options_array);
				$out .= "</select></div>";

				$out .= "<div class='row'><div class='pull-left col-md-11 col-lg-11 col-sm-11'>Confirm bid includes Black Bear project fee of $0.50/W paid 50% at contract signing and 50% at construction start.</div>";
				$out .= "<select name='fid78' class='pull-right'>";
				$out .= populateSelects($fid78,$options_array);
				$out .= "</select></div>";
			}
			

			$out .= "<input type='submit' class='btn btn-primary btn_interaction' id='".$rfp_id."' value='Save Bid Confirmations'>
			</form>";

	$out .= "</div></div>";
	return $out;
}

function showConfirmationsQB($rfp_energy_technology,$optin_rid, $rfp_rid,$category,$channel_partner_id,$fid53,$fid54,$fid55,$fid56,$fid57,$fid58,$fid59,$fid60,$fid61){
	qbLogin();
	global $qb;
	$fields = array(
		array(
			'fid' => 53,
			'value' => $fid53
		),
		array(
			'fid' => 54,
			'value' => $fid54
		),
		array(
			'fid' => 55,
			'value' => $fid55
		),
		array(
			'fid' => 56,
			'value' => $fid56
		),
		array(
			'fid' => 57,
			'value' => $fid57
		),
		array(
			'fid' => 58,
			'value' => $fid58
		),
		array(
			'fid' => 59,
			'value' => $fid59
		),
		array(
			'fid' => 60,
			'value' => $fid60
		),
		array(
			'fid' => 61,
			'value' => $fid61
		)
	);
	$qb->EditRecord(C_DBID_OPTINS, $optin_rid, $fields);
	if ($category=='general'){
		$redirect_file='rfp_detail';
	} elseif ($category=='awarded'){
		$redirect_file='awarded_rfp_detail';
	}
	redirect($redirect_file.".php?rfp_rid=".$rfp_rid."&channel_partner_id=".$channel_partner_id);
}

function showConfirmationsLEDQB($rfp_energy_technology,$optin_rid, $rfp_rid,$category,$channel_partner_id,$fid56,$fid78){
	qbLogin();
	global $qb;
	$fields = array(
		array(
			'fid' => 56,
			'value' => $fid56
		),
		array(
			'fid' => 78,
			'value' => $fid78
		)
	);
	$qb->EditRecord(C_DBID_OPTINS, $optin_rid, $fields);
	if ($category=='general'){
		$redirect_file='rfp_detail';
	} elseif ($category=='awarded'){
		$redirect_file='awarded_rfp_detail';
	}
	redirect($redirect_file.".php?rfp_rid=".$rfp_rid."&channel_partner_id=".$channel_partner_id);
}

function populateSelects($value,$options_array){
	$selections ="<option value='null'></option>";
	foreach ($options_array as $option) {
		if($option == $value){
			$selections.="<option value='".$option."' selected='selected'>".$option."</option>";
		} else {
			$selections.="<option value='".$option."'>".$option."</option>";
		}
	}
	return $selections;
}

//this function is not being used
function parseAssoc($returnArray, $key, $haystack) {  //simple parse of url style concatenations & as delimiter = as key = value;
	$associations = explode("&", $haystack);
	foreach ($associations as $association) {
		$assoc = explode("=", $association);
		if (isset($assoc[1])) { //was there a key/value pair
			if (is_array($assoc[1])) { $returnArray[$key][$assoc[0]] = implode('',$assoc[1]); }  //if array (happens with textarea)
			else { $returnArray[$key][$assoc[0]] = $assoc[1]; }
		}
	}
	return($returnArray);
}

function parseSubmit($data) { //parse submitted data the original
	global $results;
	global $recordAdds;
	foreach ($data as $name=>$value) {
		$nameData = explode(",",$name);
		switch ($nameData[0]) {
			case 'update': //DBID, RID, FID, ORIGINAL VALUE
				if (is_array($value)) { $value = implode('',$value); }
				$results[] = qbUpdate($nameData[1], $nameData[2], $nameData[3], $value);
				break;
			case 'add':  //(1)DBID, (2)NEW_RECORD_TEMP_ID, (3)FID FOR FIELD VALUE, key=value for related record
				if (isset($value) && !empty($value)) {
					if (is_array($value)) { $value = implode('',$value); }
					$recordAdds[$nameData[2]]['dbid'] = $nameData[1];
					$recordAdds[$nameData[2]][$nameData[3]] = $value;
					//$recordAdds = parseAssoc($recordAdds, $nameData[2], $nameData[4]);
				}
				break;
			case 'addSelect': //add based on Select:Option, information in the option value
				if (!empty($value)) {
					$values = explode(",",$value);  //(0)DBID, (1)NEW_RECORD_TEMP_ID, (2)Associations
					$recordAdds[$values[1]]['dbid'] = $values[0];
					//$recordAdds = parseAssoc($recordAdds, $values[1], $values[2]);
				}
				break;
		}
	}
}

//ajax stuff
// if (isset($_POST['ajax'])) {
// 	header('Content-type: application/json');
// 	switch ($_POST['ajax']) {
// 		case 'add':
// 			$nameData = explode(",", $_POST['name']);
// 			$associations = explode('&', $nameData[4]); //build array of fid=>value associations from field name
// 			foreach ($associations as $association) {
// 				$assoc = explode("=",$association);
// 				if (isset($assoc[1])) { //was there a key/value pair
// 					if (is_array($assoc[1])) { $record[$assoc[0]] = implode('',$assoc[1]); }  //if array (happens with textarea)
// 					else { $record[$assoc[0]] = $assoc[1]; }
// 				}
// 			}
// 			$record[$nameData[3]] = $_POST['value'];
// 			$result = qbAdd($nameData[1], $record);  //dbid fid=>values
// 			$return[$_POST['id']]['type'] = 'name';
// 			$return[$_POST['id']]['value'] = "update," . $nameData[1] . "," . $result['rid'] . "," . $nameData[3] . "," . $_POST['value'];
// 			echo json_encode($return);
// 			break;
// 		case 'update':  //update existing field value
// 			parseSubmit(array($_POST['name']=>$_POST['value']));
// 			echo json_encode(array()); //return null for client
// 			break;
// 		case 'updateClient':
// 		default:
// 			if (isset($_POST['job']) && isset($_POST['unit']))
// 			getClientUpdates($_POST['job'], $_POST['unit'], $_POST['lastUpdate']);
// 			break;
// 	}
// } else { //parse submitted page data for updates
	// parseSubmit($_POST);
	// redirect("rfp_detail.php?rfp_rid=".$rfp_rid."&channel_partner_id=".$channel_partner_id."&open_site_rfp=".$site_rfp_rid);
	//var_dump($updates);
//}

function addOrEditBids($rfp_rid,$channel_partner_id,$site_rfp_rid,$category){
	global $recordAdds;
	parseSubmit($_POST);
	if (isset($recordAdds) && count($recordAdds)) {
		foreach ($recordAdds as $key => $record) { //$recordAdds is a nested array of key:subkey=value subkeys are either dbid or fid
			$dbid = $record['dbid'];
			unset($record['dbid']);  //remove the dbid from the array before passing it to qbAdd function
			$results[] = qbAdd($dbid, $record);  //qbAdd dbid, array of fid=value
			$adds[]=array($dbid, $record);
		}
	}
	if ($category=='general'){
		$redirect_file='rfp_detail';
	} elseif ($category=='awarded'){
		$redirect_file='awarded_rfp_detail';
	}
	redirect($redirect_file.".php?rfp_rid=".$rfp_rid."&channel_partner_id=".$channel_partner_id."&open_site_rfp=".$site_rfp_rid);
}

function saveChanelPartnerUsersEmailPreferences($rfp_rid,$category){
	global $recordAdds;
	parseSubmit($_POST);
	if (isset($recordAdds) && count($recordAdds)) {
		foreach ($recordAdds as $key => $record) { //$recordAdds is a nested array of key:subkey=value subkeys are either dbid or fid
			$dbid = $record['dbid'];
			unset($record['dbid']);  //remove the dbid from the array before passing it to qbAdd function
			$results[] = qbAdd($dbid, $record);  //qbAdd dbid, array of fid=value
			$adds[]=array($dbid, $record);
		}
	}
	if ($category=='general'){
		$redirect_file='rfp_detail';
	} elseif ($category=='awarded'){
		$redirect_file='awarded_rfp_detail';
	}
	redirect($redirect_file.".php?rfp_rid=".$rfp_rid."&channel_partner_id=".$_SESSION['channel_partner']);
}

function trackPageViewStart($lat_lon,$page,$file_url,$rfp_rid=0){
	qbLogin();
	global $qb;
	//log activity in QB
	$fields = array(
		array(
			'fid' => 6,//start timestamp
			'value' => date('m/d/Y h:i:s a', time())
		),
		array(
			'fid' => 34,//start time
			'value' => date('h:i:s a', time())
		),
		array(
			'fid' => 21,//user rid
			'value' => $_SESSION['uid']
		),
		array(
			'fid' => 10,//page url
			'value' => C_PROJECT_DIRECTORY.$file_url.'.php'
		),
		array(
			'fid' => 11,//page name
			'value' => $page
		),
		array(
			'fid' => 13,//geolocation
			'value' => $lat_lon
		),
		array(
			'fid' => 19,//IP address
			'value' => getRealIpAddr()
		),
		array(
			'fid' => 29,//action name
			'value' => 'navigation'
		),
		array(
			'fid' => 20,//action name
			'value' => 'page view'
		),
		array(
			'fid' => 22,//target type 
			'value' => 'link'
		)
	);

	//query rfp to get ID
	if ($rfp_rid != ""){
		// $response_rfp = $qb->DoQuery(C_DBID_CUSTOMER_RFPS, "{'3'.EX.'".$rfp_rid."'}", 'a','3');
		// if ($response_rfp[0]) {
		// 	$rfp_rid = $response_rfp[0][6];
			$fields[] = array(
				'fid' => 49,//related rfp
				'value' => $rfp_rid
			);
		//}
	}

	$response = $qb->AddRecord(C_DBID_TRACKING, $fields, false);
	if ($response){
		$activity_rid = $response->rid;
		$data['errorcode'] = 0;
		$data['new_rid'] = $activity_rid;
		$data['rfp_rid'] = $rfp_rid;
		//$data['response_rfp'] = $response_rfp;
	} else {
		$data['errorcode'] = 1;
		$data['message'] = "did not add";
	}
	
	return $data;
}

function trackPageViewEnd($page_rid){
	qbLogin();
	global $qb;
	$fields = array(
		array(
			'fid' => 7,//end timestamp
			'value' => date('m/d/Y h:i:s a', time())
		),
		array(
			'fid' => 38,//end time
			'value' => date('h:i:s a', time())
		)
	);
	//log activity in QB
	$response = $qb->EditRecord(C_DBID_TRACKING, $page_rid, $fields);
	if ($response){
		$data['errorcode'] = 0;
	} else {
		$data['errorcode'] = 1;
		$data['message'] = "did not edit";
	}
	return $data;
}

function trackPageInteraction($type,$file_url,$id,$rfp_rid=null,$page,$lat_lon,$target_type){
	qbLogin();
	global $qb;
	//log activity in QB
	$fields = array(
		array(
			'fid' => 6,//start timestamp
			'value' => date('m/d/Y h:i:s a', time())
		),
		array(
			'fid' => 34,//start time
			'value' => date('h:i:s a', time())
		),
		array(
			'fid' => 21,//user rid
			'value' => $_SESSION['uid']
		),
		array(
			'fid' => 10,//page url
			'value' => $file_url
		),
		array(
			'fid' => 11,//page name
			'value' => $page
		),
		array(
			'fid' => 13,//geolocation
			'value' => $lat_lon
		),
		array(
			'fid' => 19,//IP address
			'value' => getRealIpAddr()
		),
		array(
			'fid' => 29,//action name
			'value' => $type
		),
		array(
			'fid' => 23,//action,link or button name
			'value' => $id
		),
		array(
			'fid' => 22,//target type
			'value' => $target_type
		),
		array(
			'fid' => 20,//activity name
			'value' => $target_type
		)
	);

	//query rfp to get ID
	if ($rfp_rid != ""){
		// $response_rfp = $qb->DoQuery(C_DBID_CUSTOMER_RFPS, "{'3'.EX.'".$rfp_rid."'}", 'a','3');
		// if ($response_rfp[0]) {
		// 	$rfp_rid = $response_rfp[0][6];
			$fields[] = array(
				'fid' => 49,//related rfp
				'value' => $rfp_rid
			);
		//}
	}

	$response = $qb->AddRecord(C_DBID_TRACKING, $fields, false);
	if ($response){
		//$activity_rid = $response->rid;
		$data['errorcode'] = 0;
		//$data['new_rid'] = $activity_rid;
		//$data['rfp_rid'] = $rfp_rid;
		//$data['response_rfp'] = $response_rfp;
	} else {
		$data['errorcode'] = 1;
		$data['message'] = "did not add";
	}
	
	return $data;
}

function addFixtureType($bid_rid,$fixture_type,$manufacturer,$warranty,$quantity,$unit_price,$rfp_rid){
	qbLogin();
	global $qb;
	$fields = array(
			array(
				'fid' => 11,
				'value' => $bid_rid
			),
			array(
				'fid' => 6,
				'value' => $fixture_type
			),
			array(
				'fid' => 7,
				'value' => $manufacturer
			),
			array(
				'fid' => 8,
				'value' => $warranty
			),
			array(
				'fid' => 9,
				'value' => $quantity
			),
			array(
				'fid' => 10,
				'value' => $unit_price
			)
		);
		$qb->AddRecord(C_DBID_FIXTURE_TYPES, $fields, false);
		redirect("rfp_detail.php?rfp_rid=".$rfp_rid."&channel_partner_id=".$_SESSION['channel_partner']);
}

function deleteFixtureType($fixture_rid,$rfp_rid){
	qbLogin();
	global $qb;
	
		$response = $qb->DeleteRecord(C_DBID_FIXTURE_TYPES, $fixture_rid);
		//echo $qb;
		//die();

	
	redirect("rfp_detail.php?rfp_rid=".$rfp_rid."&channel_partner_id=".$_SESSION['channel_partner']);
}

//add any new records needed (currently in a function above)
// if (isset($recordAdds) && count($recordAdds)) {
// 	foreach ($recordAdds as $key => $record) { //$recordAdds is a nested array of key:subkey=value subkeys are either dbid or fid
// 		$dbid = $record['dbid'];
// 		unset($record['dbid']);  //remove the dbid from the array before passing it to qbAdd function
// 		$results[] = qbAdd($dbid, $record);  //qbAdd dbid, array of fid=value
// 		$adds[]=array($dbid, $record);
// 	}
// }

function redirect($url){
    $baseUri=C_PROJECT_DIRECTORY;
    if(headers_sent()){
        $string = '<script type="text/javascript">';
        $string .= 'window.location = "' . $baseUri.$url . '"';
        $string .= '</script>';
        echo $string;
    }else{
    if (isset($_SERVER['HTTP_REFERER']) AND ($url == $_SERVER['HTTP_REFERER']))
        header('Location: '.$_SERVER['HTTP_REFERER']);
    else
        header('Location: '.$baseUri.$url);
    }
    exit;
}

/**
 * Generate a random string, using a cryptographically secure 
 * pseudorandom number generator (random_int)
 * 
 * For PHP 7, random_int is a PHP core function
 * For PHP 5.x, depends on https://github.com/paragonie/random_compat
 * 
 * @param int $length      How many characters do we want?
 * @param string $keyspace A string of all possible characters
 *                         to select from
 * @return string
 */
function random_str($length){
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

// Reset a user's password using their temporary password and a new password
function userResetPassword($email, $temp_password, $new_password){
	$qb = qbLogin(C_QB_USERNAME, C_QB_PASSWORD, C_QB_APPTOKEN);
	if ($qb) {
		// Find user matching $email, and $temp_password
		$query = "{'".C_FID_USER_EMAIL."'.EX.'".$email."'}AND{'".C_FID_USER_TEMPORARY_PASSWORD."'.EX.'".$temp_password."'}";
		$response = $qb->DoQuery(C_DBID_USERS, $query, '3');
		if ($response[0]['3']) {
			$fields = array(
				array(
					'fid' => C_FID_USER_TEMPORARY_PASSWORD,
					'value' => null
 				),
				array(
					'fid' => C_FID_USER_PASSWORD,
					'value' => $new_password
				)
			);
			// Save the new password and remove the temporary password in QuickBase
			$qb->EditRecord(C_DBID_USERS, $response[0]['3'], $fields);
		}
	}
}

function convertQBDate($date, $format="m/d/Y"){
	if (isset($date)) { return(date($format, $date / 1000)); }
}

function convertQBDateHtml5($date, $format="Y-m-d"){
	if (isset($date) && !empty($date)) { 
		return(date($format, $date/1000)); 
	} else {
		return "";
	}
}
?>