<?php
error_reporting(-1);
ini_set('session.save_path',realpath(dirname($_SERVER['DOCUMENT_ROOT']) . '/sessions'));
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once('constants_root.php');
require_once('constants.php');
include_once('qbFunc.php');
include_once('lib/swift/swift_required.php');
//session_start();
//allow_url_fopen=1;

//date_default_timezone_set('America/Denver');
date_default_timezone_set('America/Los_Angeles');


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
				$_SESSION['userEmail'] = $response[0][C_FID_USER_EMAIL];
				$_SESSION['userFirstName'] = $response[0][C_FID_USER_FIRST_NAME];
				$_SESSION['userLastName'] = $response[0][C_FID_USER_LAST_NAME];
				$_SESSION['uid'] = $response[0][3];
				$_SESSION['channel_partner'] = $response[0][20];
				$_SESSION['channel_partner_name'] = $response[0][27];
				$_SESSION['contact_type'] = $response[0][14];
				$_SESSION['related_customer_rid'] = $response[0][15];
				$_SESSION['customer_name'] = $response[0][16];

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
			} else { // User is signed up, but not validated. Inform user to validate through email.
				return ("<span style='background:yellow;font-size:120%;'>Error: This account is not validated. Please check your email and use the validation link sent to you.</span>");
			}
		} else {
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


//query sites
function getSites($page, $technology){
	qbLogin();
	global $qb;
	$out = "";
	$customer_rid = $_SESSION['customer_rid'];
	if( $page == 0 ) {
		$num_per_page = 5;
		$skip = 0;	
	} else {
		$num_per_page = 100;
		$skip = 5 + (($page-1) * $num_per_page);
	}

	if($technology == 'all'){
		$sites = $qb->DoQuery(CUSTOMER_SITES, "{'87'.EX.'".$customer_rid."'}", '3.63.22.23.41.64.69.71.79.86', '3', "skp-$skip.num-$num_per_page"); 
		if ($sites[0]) {
			for ($i = 0; $i < count($sites); $i++) {
				$site_rid = $sites[$i][63];
				$site_name = $sites[$i][64];	
				$site_address = $sites[$i][71];	
				$site_city = $sites[$i][22];	
				$site_state = $sites[$i][69];		
				$site_zip = $sites[$i][79];	
				$site_id = $sites[$i][23];	
				$site_energy = $sites[$i][3];//does not exist in qb yet
				$site_type = $sites[$i][41];
				$site_projects = $sites[$i][86];

				$out .= "<tr class='clickable-row' data-href='site_detail.php?site_rid=".$site_rid."'>
	              <td><b>$site_name</b></td>
	              <td>$site_address</td>
	              <td>$site_city</td>
	              <td>$site_state</td>
	              <td>$site_zip</td>
	              <td>$site_id</td>
	              <td>$site_energy</td>
	              <td>$site_type</td>
	              <td>$site_projects</td>
	            </tr>";
			}
		}
	} else {
		///need to redo this whiole thing to query sites not site rfps
		if ($technology == 'solar'){$query = "{'87'.EX.'".$customer_rid."'}AND{'88'.EX.'1'}";}
		if ($technology == 'storage'){$query = "{'87'.EX.'".$customer_rid."'}AND{'89'.EX.'1'}";}
		if ($technology == 'led'){$query = "{'87'.EX.'".$customer_rid."'}AND{'90'.EX.'1'}";}

		$sites = $qb->DoQuery(CUSTOMER_SITES, $query, '3.63.22.23.41.64.69.71.79.86', '3', "skp-$skip.num-$num_per_page"); 

		if ($sites[0]) {
			for ($i = 0; $i < count($sites); $i++) {
				$site_rid = $sites[$i][63];
				$site_name = $sites[$i][64];	
				$site_address = $sites[$i][71];	
				$site_city = $sites[$i][22];	
				$site_state = $sites[$i][69];		
				$site_zip = $sites[$i][79];	
				$site_id = $sites[$i][23];	
				$site_energy = $sites[$i][3];//does not exist in qb yet
				$site_type = $sites[$i][41];
				$site_projects = $sites[$i][86];

				$out .= "<tr class='clickable-row' data-href='site_detail.php?site_rid=".$site_rid."'>
	              <td><b>$site_name</b></td>
	              <td>$site_address</td>
	              <td>$site_city</td>
	              <td>$site_state</td>
	              <td>$site_zip</td>
	              <td>$site_id</td>
	              <td>$site_energy</td>
	              <td>$site_type</td>
	              <td>$site_projects</td>
	            </tr>";
			}
		}

	}																				
	
	if ($sites){
		$data['errorcode'] = 0;
		$data['html'] = $out;
		$data['num'] = count($sites);
		$data['technology'] = $technology;
	} else {
		$data['errorcode'] = 1;
		$data['technology'] = $technology;
		$data['num'] =0;
		$data['html'] = '';
		if( $page == 0 )
			$data['message'] = "no sites found";
	}
	return $data;
}

////query projects
function getProjects($page, $technology){
	qbLogin();
	global $qb;
	$out = "";
	$customer_rid = $_SESSION['customer_rid'];
	if( $page == 0 ) {
		$num_per_page = 5;
		$skip = 0;	
	} else {
		$num_per_page = 100;
		$skip = 5 + (($page-1) * $num_per_page);
	}

	if($technology == 'all'){
		$sites = $qb->DoQuery(CUSTOMER_SITES_RFP, "{'95'.EX.'".$customer_rid."'}", '3.82.92.111.131.134.151', '3', "skp-$skip.num-$num_per_page"); 
		if ($sites[0]) {
			for ($i = 0; $i < count($sites); $i++) {
				$site_rid = $sites[$i][134];
				$site_name = $sites[$i][82];	
				$site_site = $sites[$i][131];	
				$site_size = $sites[$i][3];	
				$site_plan_date = $sites[$i][3];		
				$site_energy_tech = $sites[$i][92];	
				$site_utility = $sites[$i][151];	
				$site_progress = $sites[$i][111];

				$out .= "<tr class='clickable-row' data-href='project_detail.php?site_rid=".$site_rid."'>
	              <td><b>$site_name</b></td>
	              <td>$site_site</td>
	              <td>$site_size</td>
	              <td>$site_plan_date</td>
	              <td>$site_energy_tech</td>
	              <td>$site_utility</td>
	              <td>$site_progress</td>
	            </tr>";
			}
		}
	} else {

		if ($technology == 'solar'){$query = "{'95'.EX.'".$customer_rid."'}AND{'85'.EX.'Solar_'}";}
		if ($technology == 'storage'){$query = "{'95'.EX.'".$customer_rid."'}AND{'85'.EX.'Storage'}";}
		if ($technology == 'led'){$query = "{'95'.EX.'".$customer_rid."'}AND{'85'.EX.'LED'}";}

		$sites = $qb->DoQuery(CUSTOMER_SITES_RFP, $query, '3.82.92.111.131.134.151', '3', "skp-$skip.num-$num_per_page"); 

		if ($sites[0]) {
			for ($i = 0; $i < count($sites); $i++) {
				$site_rid = $sites[$i][134];
				$site_name = $sites[$i][82];	
				$site_site = $sites[$i][131];	
				$site_size = $sites[$i][3];	
				$site_plan_date = $sites[$i][3];		
				$site_energy_tech = $sites[$i][92];	
				$site_utility = $sites[$i][151];	
				$site_progress = $sites[$i][111];

				$out .= "<tr class='clickable-row' data-href='site_detail.php?site_rid=".$site_rid."'>
	              <td><b>$site_name</b></td>
	              <td>$site_site</td>
	              <td>$site_size</td>
	              <td>$site_plan_date</td>
	              <td>$site_energy_tech</td>
	              <td>$site_utility</td>
	              <td>$site_progress</td>
	            </tr>";
			}
		}

	}																				
	
	if ($sites){
		$data['errorcode'] = 0;
		$data['html'] = $out;
		$data['num'] = count($sites);
		$data['technology'] = $technology;
	} else {
		$data['errorcode'] = 1;
		$data['technology'] = $technology;
		$data['num'] =0;
		$data['html'] = '';
		if( $page == 0 )
			$data['message'] = "no sites found";
	}
	return $data;
}

///query how many results
function getTotalNumberSites($technology){
	qbLogin();
	global $qb;
	$out = 0;
	$customer_rid = $_SESSION['customer_rid'];

	if ($technology =='all'){
		$sites = $qb->DoQuery(CUSTOMER_SITES, "{'87'.EX.'".$customer_rid."'}", '3', '3'); 

	} else {

		if ($technology == 'solar'){$query = "{'87'.EX.'".$customer_rid."'}AND{'88'.EX.'1'}";}
		if ($technology == 'storage'){$query = "{'87'.EX.'".$customer_rid."'}AND{'89'.EX.'1'}";}
		if ($technology == 'led'){$query = "{'87'.EX.'".$customer_rid."'}AND{'90'.EX.'1'}";}

		$sites = $qb->DoQuery(CUSTOMER_SITES, $query, '3', '3'); 
	}
	
	if ($sites[0]){
		$data['errorcode'] = 0;
		$data['technology'] = $technology;
		$data['html'] = count($sites);
	} else {
		$data['errorcode'] = 1;
		$data['html'] = 0;
		$data['technology'] = $technology;
		$data['message'] = "no sites found";
	}
	return $data;
}

function getTotalNumberProjects($technology){
	qbLogin();
	global $qb;
	$out = 0;
	$customer_rid = $_SESSION['customer_rid'];

	if ($technology =='all'){ 

		$customer_rid = $_SESSION['customer_rid'];
		$sites = $qb->DoQuery(CUSTOMER_SITES_RFP, "{'95'.EX.'".$customer_rid."'}", '3', '3'); 

	} else {

		if ($technology == 'solar'){$query = "{'95'.EX.'".$customer_rid."'}AND{'85'.EX.'Solar_'}";}
		if ($technology == 'storage'){$query = "{'95'.EX.'".$customer_rid."'}AND{'85'.EX.'Storage'}";}
		if ($technology == 'led'){$query = "{'95'.EX.'".$customer_rid."'}AND{'85'.EX.'LED'}";}

		$sites = $qb->DoQuery(CUSTOMER_SITES_RFP, $query, '3', '3'); 
	}
	
	if ($sites[0]){
		$data['errorcode'] = 0;
		$data['technology'] = $technology;
		$data['html'] = count($sites);
	} else {
		$data['errorcode'] = 1;
		$data['html'] = 0;
		$data['technology'] = $technology;
		$data['message'] = "no sites found";
	}
	return $data;
}

function getSite($site_rid){
	qbLogin();
	global $qb;
	$customer_rid = $_SESSION['customer_rid'];

	$sites = $qb->DoQuery(CUSTOMER_SITES, "{'63'.EX.'".$site_rid."'}AND{'87'.EX.'".$customer_rid."'}", 'a', '3'); 
	
	if ($sites[0]){
		$site_name = $sites[0][64];
		$site_address = $sites[0][13];
		$site_city = $sites[0][9];
		$site_state = $sites[0][12];
		$site_zip = $sites[0][11];
		$site_uid = $sites[0][23];
		$site_market = $sites[0][41];
		$site_fotage = $sites[0][16];
		$site_utility = $sites[0][27];
		$site_good_solar = $sites[0][88];
		$site_good_storage = $sites[0][89];
		$site_good_led = $sites[0][90];
		$site_num_solar_rfps = $sites[0][91];
		$site_num_storage_rfps = $sites[0][92];
		$site_num_led_rfps = $sites[0][93];
		$site_solar_rfp_rid = $sites[0][94];
		$site_storage_rfp_rid = $sites[0][95];
		$site_led_rfp_rid = $sites[0][96];

		$data['html']['site'] = $site_name;
		$data['html']['address'] = $site_address;
		$data['html']['city'] = $site_city;
		$data['html']['state'] = $site_state;
		$data['html']['zip'] = $site_zip;
		$data['html']['uid'] = $site_uid;
		$data['html']['market'] = $site_market;
		$data['html']['fotage'] = $site_fotage;
		$data['html']['utility'] = $site_utility;
		$data['html']['site_good_solar'] = $site_good_solar;
		$data['html']['site_good_storage'] = $site_good_storage;
		$data['html']['site_good_led'] = $site_good_led;
		$data['html']['site_num_solar_rfps'] = $site_num_solar_rfps;
		$data['html']['site_num_storage_rfps'] = $site_num_storage_rfps;
		$data['html']['site_num_led_rfps'] = $site_num_led_rfps;
		$data['html']['site_solar_rfp_rid'] = $site_solar_rfp_rid;
		$data['html']['site_storage_rfp_rid'] = $site_storage_rfp_rid;
		$data['html']['site_led_rfp_rid'] = $site_led_rfp_rid;

		$data['errorcode'] = 0;
	} else {
		$data['errorcode'] = 1;
		$data['message'] = "no site info found";
	}
	return $data;
}


function getTimeline($project_rid,$technology){
	qbLogin();
	global $qb;
	$customer_rid = $_SESSION['customer_rid'];
	$projects = $qb->DoQuery(CUSTOMER_SITES_RFP, "{'134'.EX.'".$project_rid."'}", 'a', '3'); 
	
	if ($projects[0]){
		$site_rfp_rid_new = $projects[0][134];
		$date_site_analisys_plan = $projects[0][191];
		$date_site_analisys_copletion = $projects[0][179];



		$data['html']['site_rfp_rid_new'] = $site_rfp_rid_new;
		$data['html']['date']['date_site_analisys_plan'] = $date_site_analisys_plan;
		$data['html']['date']['date_site_analisys_copletion'] = $date_site_analisys_copletion;

		$data['errorcode'] = 0;
	} else {
		$data['errorcode'] = 1;
		$data['message'] = "no site info found";
	}
	return $data;
}


/////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////


function uploadFileOptin($rfp_rid, $optin_rid, $channel_partner_id, $optin_rfp_response_file, $optin_laf_file, $optin_ppa_file, $optin_bid_form_file,$category){
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
	$response = $qb->EditRecord(C_DBID_OPTINS, $optin_rid, $fields);
	//die();
	//return ($response)?true:false;
	if ($category=='general'){
		$redirect_file='rfp_detail';
	} elseif ($category=='awarded'){
		$redirect_file='awarded_rfp_detail';
	}
	redirect($redirect_file.".php?rfp_rid=".$rfp_rid."&channel_partner_id=".$channel_partner_id);
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
		$response_rfp = $qb->DoQuery(C_DBID_CUSTOMER_RFPS, "{'3'.EX.'".$rfp_rid."'}", 'a','3');
		if ($response_rfp[0]) {
			$rfp_rid = $response_rfp[0][6];
			$fields[] = array(
				'fid' => 44,//related rfp
				'value' => $rfp_rid
			);
		}
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
		$response_rfp = $qb->DoQuery(C_DBID_CUSTOMER_RFPS, "{'3'.EX.'".$rfp_rid."'}", 'a','3');
		if ($response_rfp[0]) {
			$rfp_rid = $response_rfp[0][6];
			$fields[] = array(
				'fid' => 44,//related rfp
				'value' => $rfp_rid
			);
		}
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

function deleteFixtureType($fixture_rid,$rfp_rid){
	qbLogin();
	global $qb;
	
		$response = $qb->DeleteRecord(C_DBID_FIXTURE_TYPES, $fixture_rid);
		//echo $qb;
		//die();

	
	redirect("rfp_detail.php?rfp_rid=".$rfp_rid."&channel_partner_id=".$_SESSION['channel_partner']);
}

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

function convertQBDate($date, $format="m-d-Y"){
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