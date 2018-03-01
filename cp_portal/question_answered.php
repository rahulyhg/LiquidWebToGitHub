<?php
	require_once('../res/constants_root.php');
	require_once('../res/constants.php');
	include_once('../res/qbFunc.php');

	$data = json_decode( file_get_contents('php://input') );

	//file_put_contents('../test.txt', $data->question_rid);
	file_put_contents('../test.txt', $data->rfp_rid);
	//file_put_contents('../test.txt', $data->rfp_name);
	//file_put_contents('../test.txt', $data->rfp_release_date);

	$question_rid = $data->question_rid;
	$question_rfp_rid = $data->rfp_rid;
	$question_rfp_name = $data->rfp_name;
	$rfp_release_date = $data->rfp_release_date;

	

	//die();
	// if(isset($_REQUEST['question_rid'])){
	// 	$question_rid = $_REQUEST['question_rid'];
	// }else {
	// 	die('no rid');
	// }

	// if(isset($_REQUEST['rfp_rid'])){
	// 	$question_rfp_rid = $_REQUEST['rfp_rid'];
	// 	echo $question_rfp_rid;
	// }else {
	// 	die('no rfp rid');
	// }

	// if(isset($_REQUEST['rfp_name'])){
	// 	$question_rfp_name = $_REQUEST['rfp_name'];
	// 	echo $question_rfp_name;
	// }else {
	// 	die('no rfp name');
	// }

	//do queries of all opted in and for each edit the record to add value fo rfp name
	qbLogin();
	global $qb;
	//search optins
	//$query1 = "{'11'.EX.'"+$question_rfp_rid+"'}AND{'6'.EX.'Opt In'}AND{'83'.XEX.'1'}AND({'85'.EX.''}OR{'85'.OBF.'".$rfp_release_date."'})";
	$query1 = "{'11'.EX.'"+$question_rfp_rid+"'}AND{'6'.EX.'Opt In'}AND{'83'.XEX.'1'}";
	$response = $qb->DoQuery(C_DBID_OPTINS, $query1, 'a','3');
	if ($response[0]) {
		for ($i = 0; $i < count($response); $i++) {
			$channel_partner_rid = $response[$i][17];
			//search people for each partner

			$query = "{'20'.EX.'".$channel_partner_rid."'}AND{'21'.EX.'Active'}AND{'58'.XEX.'1'}AND{'53'.XEX.'1'}AND{'11'.EX.'Yes'}";
			//$query = "{'20'.EX.'102'}AND{'21'.EX.'Active'}AND{'58'.XEX.'1'}AND{'53'.XEX.'1'}AND{'11'.EX.'Yes'}";
			$response_contacts = $qb->DoQuery(C_DBID_USERS, $query, 'a', '3');
			if ($response_contacts[0]) {
				for ($j = 0; $j < count($response_contacts); $j++) {
					$contact_rid = $response_contacts[$j][3];

					//echo "<br>".$contact_rid;
					//edit record to add rfp name
					$fields = array(
						array(
							'fid' => 46,
							'value' => $question_rfp_name
						)
					);
					$qb->EditRecord(C_DBID_USERS, $contact_rid, $fields);

				}
				for ($j = 0; $j < count($response_contacts); $j++) {
					$contact_rid = $response_contacts[$j][3];
					//echo "<br>".$contact_rid;
					//edit email trigger checkbox
					$fields = array(
						array(
							'fid' => 56,
							'value' => 1
						)
					);
					$qb->EditRecord(C_DBID_USERS, $contact_rid, $fields);
				}
				for ($j = 0; $j < count($response_contacts); $j++) {
					$contact_rid = $response_contacts[$j][3];
					echo "<br>".$contact_rid;
					//edit record to add rfp name
					//sanitize same record
					$fields = array(
						array(
							'fid' => 56,
							'value' => 0
						)
					);
					$qb->EditRecord(C_DBID_USERS, $contact_rid, $fields);
				}
			}
		}

		qbLogin();
		global $qb;
		$fields = array(
			array(
				'fid' => 6,
				'value' => "Web Hook for Email Notification question answered<br>".file_get_contents('php://input') . " found contacts ". count($response_contacts)
			),
			array(
				'fid' => 7,
				'value' => $question_rid
			)
		);
		$qb->AddRecord("bmyfrc2nd", $fields, false);


	} else {

		qbLogin();
		global $qb;
		$fields = array(
			array(
				'fid' => 6,
				'value' =>$query1
			),
			array(
				'fid' => 7,
				'value' => $question_rid
			)
		);
		$qb->AddRecord("bmyfrc2nd", $fields, false);
	}

	//file_put_contents('../test.txt', $query1);

?>