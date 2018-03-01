<?php

	/*function create_record( $table, $fields, $values ) {
	}*/

	//$file = file_get_contents('php://input');

	//$data = json_decode($file);

	$data = json_decode( file_get_contents('php://input') );

	file_put_contents('test.txt', $data->id);

	//echo $file;


	/*

	{
		"table": [TABLE NAME],
		"data": {
		"id":"[Related MRN2]",
		"kljh":"[Hospital / Clinic / Other Customer Name]"
		}
	}

	*/

	/*$fields = '';
	$values = '';

	foreach( $data->data as $name => $value ) {

	}

	$query = "INSERT INTO `{$data->table}` () VALUES ()";*/

?>