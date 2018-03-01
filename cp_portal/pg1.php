<?php
ini_set('session.save_path',realpath(dirname($_SERVER['DOCUMENT_ROOT']) . '/sessions'));

session_start();

//echo $_SERVER['DOCUMENT_ROOT'];
//var_dump ($_SERVER);


$_SESSION['name'] = "name";
var_dump ($_SESSION);
session_write_close();

//header("Location: pg2.php");

exit();
?>