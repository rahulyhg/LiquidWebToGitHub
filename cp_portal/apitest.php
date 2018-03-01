<?php

require_once('res/func.php');
//echo "Testing<br>";
//$url = "https://thelanguagebanc.quickbase.com/db/bjmcqrgej?a=API_DoQuery&query={3.EX.15426}&clist=3&apptoken=jsqy4bs8jhfycap6qkcb2epw5y";
//var_dump($url);
//echo "<br>";
//echo "Request URL: ".$url;
//echo "<br>";

//$response = file_get_contents($url);
//$response = http_get($url, array("timeout"=>1), $info);
//print_r($info);
//echo "<br>";
				//var_dump($response);
//echo "<br>";

qbLogin();
//var_dump($qb);
$response = $qb->DoQuery('bk6wv3wbm', "{'8'.EX.'ykowal@datacollaborative.com'}", 'a');

if (isset($response[0]['3'])) {
        var_dump ($response[0]['3']);
	echo "api is working";
} else {
	echo "api is not working";
}
?>