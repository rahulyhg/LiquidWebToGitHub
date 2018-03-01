<?php
require_once('constants.php');
require_once('qb_new.php');

//General Quickbase app functions
function qbLogin($username=C_QB_USERNAME, $password=C_QB_PASSWORD, $apptoken=C_QB_APPTOKEN) {
	global $qb;
	if (isset($qb) && $qb) { return($qb); } //already have a quickbase session
	else {
		// QuickBase login
		$qb = new Quickbase();
		$qb->apptoken = $apptoken;
		$qb->authenticate($username, $password);
		if ($qb->errorcode==0) { return $qb; }
		else {
			echo "Error code: ".$qb->errorcode."<br/>";
			return false;
		}
	}
}


function getQbRec($dbid, $rid, $names=false) { //get quickbase record on dbid and rid
	qbLogin(); //login if not already;
	global $qb;
	$response = $qb->DoQuery($dbid, "{'3'.EX.'" . $rid . "'}", 'a', '3');
	if (is_array($response) && count($response)) {
		return($response[0]); //return the first matching record
	} else {
		return(false);
	}
}

function qbSchema($dbid) {
	qbLogin(); //login if not already;
	global $qb;
	global $schemas;
	if (!isset($schemas[$dbid])) { $schemas[$dbid] = $qb->GetSchema($dbid); }
	return($schemas[$dbid]);
}

function getFieldsFids($dbid) {
	global $schemas;
	global $fieldNames;
	global $fids;
	global $fieldChoice;

	if (!isset($fieldNames[$dbid]) || !isset($fids[$dbid])) {
		$schema = qbSchema($dbid);
		$fields = $schemas[$dbid]->table->fields->field;
		foreach($fields as $field) {
			$field = (array) $field;
			$fieldNames[$dbid][$field['@attributes']['id']] = $field['label'];
			$fids[$dbid][$field['label']] = $field['@attributes']['id'];
			if (isset($field['choice'])) { $fieldChoice[$dbid][$field['@attributes']['id']] = $field['choice']; }
		}
	}
	return(array('fieldNames'=>$fieldNames[$dbid], 'fids'=>$fids[$dbid], 'fieldChoice'=>$fieldChoice[$dbid]));
}

function recFieldKeys($data,$map) { //returns a data array with the keys from the map[oldkey]=newkey
	$returnArray = array();
	foreach ($data as $key=>$value) { if(isset($map[$key])) { $returnArray[$map[$key]] = $value; } }
	return($returnArray);
}

function recRowsFieldKeys($data, $map) {
	$returnArray = array();
	foreach ($data as $row) { $returnArray[] = recFieldKeys($row, $map); }
	return($returnArray);
}


//need to be logged in to quickbase for anything here to work
qbLogin();

?>