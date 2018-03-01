<?php
error_reporting(-1);

// if (session_status() == PHP_SESSION_NONE) { session_start(); }
session_start();
require_once('constants_root.php');
require_once('constants.php');
include_once('qbFunc.php');
include_once('lib/swift/swift_required.php');

$qb = false; //quickbase session variable

function logout(){ //end php session
	//unlock a open unit
	unlockUnit();
	// remove all session variables
	session_unset(); 
	// destroy the session 
	session_destroy();
	// paranoia 
	setcookie('PHPSESSID',"", time() - 9999);
	unset($_COOKIE['PHPSESSID']);
	//header('Location: ' . LANDING_PAGE);
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

function userSignup($email, $password, $firstname, $lastname){
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
			)
		);
		$qb->AddRecord(C_DBID_USERS, $fields, false);	
		var_dump(sendMail($email, $key, 'validate', null));
	} else {
		return false;
	}
}

function userLogin($email, $pass) {
	qbLogin();
	global $qb;

	$response = $qb->DoQuery(C_DBID_USERS, "{'" . C_FID_USER_EMAIL . "'.EX.'" . $email . "'}AND{'" . C_FID_USER_PASSWORD . "'.EX.'" . $pass . "'}", 'a');

	if ($response[0]) {
		if ($response[0][C_FID_USER_VALIDATED]) {
			$_SESSION['userEmail'] = $response[0][C_FID_USER_EMAIL];
			$_SESSION['userFirstName'] = $response[0][C_FID_USER_FIRST_NAME];
			$_SESSION['userLastName'] = $response[0][C_FID_USER_LAST_NAME];
			$_SESSION['uid'] = $response[0][3];
			$_SESSION['channel_partner'] = $response[0][20];
			return ("success");
		} else { // User is signed up, but not validated. Inform user to validate through email.
			return ("Error: This account is not validated. Please check your email and use the validation link sent to you.");
		} 
	} else { return ("<header><div class='contentheader'>
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

//WRG specific functions
//////////////////////////////////////////////////////////////////////////////////////////////////////
function showCustomerRFPs($channel_partner_id) {
	qbLogin(); //login if not already;
	global $qb;
	$out = "";

	//search for all specialties for that channel partner
	$specializations = $qb->DoQuery(C_DBID_SPECIALIZATIONS, "{'3'.XEX.'0'}AND{'12'.EX.'".$channel_partner_id."'}", 'a', '3');
	if ($specializations[0]){
		$query = "";
		for ($i = 0; $i < count($specializations); $i++) { // for each prescription
			$energy_technology = $specializations[$i][7];
			//for each energy_technology put in query string
			$query .= "{'44'.EX.'".$energy_technology."'}";
		}
		//look for energy_technology required for each rfp and only show those that match current users partner energy_technologies.
		$rfps = $qb->DoQuery(C_DBID_CUSTOMER_RFPS, $query, 'a', '3');
		if ($rfps[0]){
			$out .= "<h3>RFPs</h3>";
			//$out .= "<table id='rfps'><tr><th></th><th></th><th></th><th></th><th>Location</th><th></th><th>Location</th><th>Opt In</th><th>Submit Bid</th></tr>";

			$out .= "<class='tablesaw table-bordered table-hover table' data-tablesaw-mode='swipe'>
                            <thead>
                                <tr>
                                    <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='persist'><abbr title='RFP Name'>RFP Name</abbr></th>
                                    <th scope='col' data-tablesaw-sortable-col data-tablesaw-sortable-default-col data-tablesaw-priority='3'>Status</th>
                                    <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='2'>Release Date</th>
                                    <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='1'>Due Date</th>
                                    <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='4'>Location</th>
                                    <th scope='col' data-tablesaw-sortable-col data-tablesaw-priority='4'>Opportunity</th>
                                    <th scope='col'>Opt In</th>
                                    <th scope='col'>Submit Bid</th>
                                </tr>
                            </thead>";
			for ($j = 0; $j < count($rfps); $j++) { // for each prescription
				$out .= "<tbody><tr>";
				$rfp_id = $rfps[$j][6];
				$rfp_status = $rfps[$j][7];
				$rfp_release_date = convertQBDate($rfps[$j][8]);
				$rfp_due_date = convertQBDate($rfps[$j][9]);
				$rfp_location = $rfps[$j][56];
				$rfp_opportunity = $rfps[$j][55];

				$out .= "<td  class='title'>".$rfp_id."</td>";
				$out .= "<td>".$rfp_status."</td>";
				$out .= "<td>".$rfp_release_date."</td>";
				$out .= "<td>".$rfp_due_date."</td>";
				$out .= "<td>".$rfp_location."</td>";
				$out .= "<td>".$rfp_opportunity."</td>";
				$out .= "<td><form action='actions.php?specialization=".$energy_technology."&rfp=".$rfp_id."'><input type='button' value='Opt In'></form></td>";
				$out .= "<td><input type='button' value='Submit Bid'></td>";
				$out .= "</tr>";
			}
			$out .= "</tbody></table>";
		} else {$out .= "no rfps found";}
	} else {
		$out .= "no results found";
	}
	return $out;
}

function showQuestions($channel_partner_id) {
	qbLogin(); //login if not already;
	global $qb;
	$out = "";

	//search for all specialties for that channel partner
	$specializations = $qb->DoQuery(C_DBID_SPECIALIZATIONS, "{'3'.XEX.'0'}AND{'12'.EX.'".$channel_partner_id."'}", 'a', '3');
	if ($specializations[0]){
		$out .= "<h3>Q&A</h3>";
		$out .= "<table id='rfps'><tr><th>RFP Name</th><th>QID</th><th>Question</th><th>Answer</th><th>Status</th><th>Submitted</th><th>Submitted By</th></tr>";
		$query = "";
		for ($i = 0; $i < count($specializations); $i++) { // for each prescription
			$energy_technology = $specializations[$i][7];
			//for each energy_technology put in query string
			$query .= "{'44'.EX.'".$energy_technology."'}";
		}
		//look for energy_technology required for each rfp and only show those that match current users partner energy_technologies.
		$rfps = $qb->DoQuery(C_DBID_CUSTOMER_RFPS, $query, 'a', '3');
		if ($rfps[0]){
			for ($j = 0; $j < count($rfps); $j++) { // for each prescription
				$rfp_id = $rfps[$j][6];
				$questions = $qb->DoQuery(C_DBID_QUESTIONS,"{'14'.EX.'".$rfp_id."'}", 'a', '3');
				if ($questions[0]){
					
					for ($k = 0; $k < count($questions); $k++) { // for each prescription
						$question = $questions[$k][6];
						$answer = $questions[$k][7];
						$status = $questions[$k][10];
						$submitted = convertQBDate($questions[$k][17]);
						$submitted_by = $questions[$k][9];

						$out .= "<tr>";

						$out .= "<td>".$rfp_id."</td>";
						$out .= "<td>".$question."</td>";
						$out .= "<td>".$answer."</td>";
						$out .= "<td>".$status."</td>";
						$out .= "<td>".$submitted."</td>";
						$out .= "<td>".$rfp_id."</td>";
						$out .= "<td>".$submitted_by."</td>";

						$out .= "</tr>";
					}
				}
			}
			$out .= "</table>";
		} else {$out .= "no rfps found";}
	} else {
		$out .= "no results found";
	}
	return $out;
}

function optIn($specialization, $rfp_id) {
	global $qb;
	$qb->AddRecord(C_DBID_OPTINS, array(array('fid' => '7', 'value' => $specialization), array('fid' => '10', 'value' => $rfp_id)), false);
	redirect ('index.php');
}

function lockUnit($unitRID) { //lock a unit for a current user
	if (!isset($unitRID) || empty($unitRID)) { return; } //no unit rid supplied
	else {
		//unlock the unit they already have if they have..
		unlockUnit();
		//lock this unit
		global $qb;
		$qb->EditRecord(C_DBID_UNITS, $unitRID, array(array('fid' => '177', 'value' => time()), array('fid' => '178', 'value' => $_SESSION['uid']), array('fid' => '181', 'value' => (time()*1000))));
		$_SESSION['lockedUnitRID'] = $unitRID;
	}
}

function unlockUnit() {  //unlock the unit they have
	//do they even have a unit open
	if (!isset($_SESSION['lockedUnitRID']) || is_null($_SESSION['lockedUnitRID'])) { return; }
	else {
		qbLogin();
		global $qb;

		$rows = $qb->DoQuery(C_DBID_UNITS, "{'3'.EX.'" . $_SESSION['lockedUnitRID'] . "'}" , 'a', '3');
		if (is_array($rows)) {
			$unit = array_shift($rows);

			if ($unit[178] == $_SESSION['uid']) { //locked by this user, unlock 
				$qb->EditRecord(C_DBID_UNITS, $_SESSION['lockedUnitRID'], array(array('fid' => '177', 'value' => ''), array('fid' => '178', 'value' => ''), array('fid' => '181', 'value' => '')));
			}
		}
		//clear session locked unit
		$_SESSION['lockedUnitRID'] = null;
	}
}

function getJobInfo($jobId, $names) {
	if(isset($jobId) && !empty($jobId)) { return(getQbRec(C_DBID_JOBS, $jobId, $names)); }
	else { return(false); }
}

function getJob($id) { //get job info from ID (rid)
	qbLogin(); //login if not already;
	global $qb;
	$response = $qb->DoQuery(C_DBID_JOBS, "{'3'.EX.'" . $id . "'}", 'a', '3');
	$job = array_shift($response);
	$job['units'] = $qb->DoQuery(C_DBID_UNITS, "{'3'.XEX.'0'}AND{'9'.EX.'" . $id . "'}" , 'a', '3');
	return($job);
}

function getUnitAffectedAreas($unitRid) {
	qbLogin(); //login if not already;
	global $qb;
	$affectedAreas = $qb->DoQuery(C_DBID_AFFECTED_AREAS, "{'3'.XEX.'0'}AND{'11'.EX.'" . $unitRid . "'}", 'a', '3');
	if (is_array($affectedAreas)) {
		foreach($affectedAreas as &$area) {
			$area['monitoring_days'] = $qb->DoQuery(C_DBID_MONITORING_DAYS, "{'3'.XEX.'0'}AND{'39'.EX.'" . $area[3] . "'}", 'a', '60');
			$area['works_done'] = $qb->DoQuery(C_DBID_WORKS_DONE, "{'3'.XEX.'0'}AND{'17'.EX.'" . $area[3] . "'}", 'a', '3');
		}
	}
	return ($affectedAreas);
}

function getPriceList() {
	qbLogin(); //login if not already;
	global $qb;
	return($qb->DoQuery(C_DBID_PRICELIST, "{'12'.EX.'1'}", 'a', '3'));
}

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

function getClientUpdates($job, $unit, $lastUpdateTime) {  //get the updates for a client based on when they last got an update
	$updateArray['time'] = time();
	echo json_encode($updateArray);
}

function parseSubmit($data) { //parse submitted data
	global $results;
	global $recordAdds;

	foreach ($data as $name=>$value) {
		$nameData = explode(",",$name);
		switch ($nameData[0]) {
			case 'update': //DBID, RID, FID, ORIGINAL VALUE
				if (is_array($value)) { $value = implode('',$value); }
				$results[] = qbUpdate($nameData[1], $nameData[2], $nameData[3], $value, $nameData[4]);
				break;
			case 'add':  //(1)DBID, (2)NEW_RECORD_TEMP_ID, (3)FID FOR FIELD VALUE, key=value for related record
				if (isset($value) && !empty($value)) {
					if (is_array($value)) { $value = implode('',$value); }
					$recordAdds[$nameData[2]]['dbid'] = $nameData[1];
					$recordAdds[$nameData[2]][$nameData[3]] = $value;
					$recordAdds = parseAssoc($recordAdds, $nameData[2], $nameData[4]);
				}
				break;
			case 'addSelect': //add based on Select:Option, information in the option value
				if (!empty($value)) {
					$values = explode(",",$value);  //(0)DBID, (1)NEW_RECORD_TEMP_ID, (2)Associations
					$recordAdds[$values[1]]['dbid'] = $values[0];
					$recordAdds = parseAssoc($recordAdds, $values[1], $values[2]);
				}
				break;
		}
	}
}

//ajax stuff
if (isset($_POST['ajax'])) {
	header('Content-type: application/json');
	switch ($_POST['ajax']) {
		case 'add':
			$nameData = explode(",", $_POST['name']);
			$associations = explode('&', $nameData[4]); //build array of fid=>value associations from field name
			foreach ($associations as $association) {
				$assoc = explode("=",$association);
				if (isset($assoc[1])) { //was there a key/value pair
					if (is_array($assoc[1])) { $record[$assoc[0]] = implode('',$assoc[1]); }  //if array (happens with textarea)
					else { $record[$assoc[0]] = $assoc[1]; }
				}
			}
			$record[$nameData[3]] = $_POST['value'];
			$result = qbAdd($nameData[1], $record);  //dbid fid=>values
			$return[$_POST['id']]['type'] = 'name';
			$return[$_POST['id']]['value'] = "update," . $nameData[1] . "," . $result['rid'] . "," . $nameData[3] . "," . $_POST['value'];
			echo json_encode($return);
			break;
		case 'update':  //update existing field value
			parseSubmit(array($_POST['name']=>$_POST['value']));
			echo json_encode(array()); //return null for client
			break;
		case 'updateClient':
		default:
			if (isset($_POST['job']) && isset($_POST['unit']))
			getClientUpdates($_POST['job'], $_POST['unit'], $_POST['lastUpdate']);
			break;
	}
} else { //parse submitted page data for updates
	parseSubmit($_POST);
}

//add any new records needed
if (isset($recordAdds) && count($recordAdds)) {
	foreach ($recordAdds as $key => $record) { //$recordAdds is a nested array of key:subkey=value subkeys are either dbid or fid
		$dbid = $record['dbid'];
		unset($record['dbid']);  //remove the dbid from the array before passing it to qbAdd function
		$results[] = qbAdd($dbid, $record);  //qbAdd dbid, array of fid=value
		$adds[]=array($dbid, $record);
	}
}

function redirect($url)
{
    $baseUri=C_PROJECT_DIRECTORY;

    if(headers_sent())
    {
        $string = '<script type="text/javascript">';
        $string .= 'window.location = "' . $baseUri.$url . '"';
        $string .= '</script>';

        echo $string;
    }
    else
    {
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
function random_str($length)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

// Reset a user's password using their temporary password and a new password
function userResetPassword($email, $temp_password, $new_password)
{
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
?>