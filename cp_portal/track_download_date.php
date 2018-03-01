<?php
	require_once('res/func.php');
	date_default_timezone_set('America/Los_Angeles');

	function getServerTimeOffset() {
		$gmtime = strtotime(gmdate('m/d/y H:i:s'));
		$serverTime = strtotime(date('m/d/y H:i:s'));
		$offset = $gmtime - $serverTime;
		return($offset);
	}

	$user_name = $_POST['user_name'];
	$optin_rid = $_POST['optin_rid'];
	//$timestamp = strtotime(date('m/d/y H:i:s'))-getServerTimeOffset();
	$timestamp = strtotime(date('m/d/y H:i:s'));

	qbLogin(); //login if not already;
	global $qb;

	$fields = array(
		array('fid' => '39', 'value' => $user_name), 
		//array('fid' => '23', 'value' => strtotime(date('m/d/y H:i:s'))*1000)
		//array('fid' => '23', 'value' => $timestamp*1000 )
		array('fid' => '23', 'value' => date("Y-m-d h:i:sa" ) )
	);

	$qb->EditRecord(C_DBID_OPTINS, $optin_rid, $fields);

?>