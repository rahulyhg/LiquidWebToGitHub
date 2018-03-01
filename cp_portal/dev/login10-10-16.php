<?php
require_once('res/func.php');

//if (isset($_SESSION['uid'])) { header('location: index.php'); } //already logged in
if (isset($_SESSION['uid'])) { redirect('index.php'); } //already logged in

else {
$validated = false;

//start output variable;
$out = '';

// If link is from Email
if (isset($_GET['e']) && isset($_GET['k'])) {
	$validated = validateUser($_GET['e'],$_GET['k']);

	if ($validated) { 
		$out .= "<div class='dialog'>Congratulations! Your account has been validated.</div>";
	} else {
		$out .= "<div class='dialog'>This link may have expired or has already been validated.</div>";	
	}
}

// If signup was sent via URL, show signup form
if (isset($_GET['signup'])) { 
	$out .= "<header><div class='contentheader'>
			<img src='img/logo.png' style='height:100px;'>
		</div>
	</header>";
	$out .= "<div class='headline'>Please Sign Up</div>";
	if (isset($_POST['submit']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['first_name']) && isset($_POST['last_name'])) {  //new signup completed
		$userCheck = checkForUser($_POST['email']);
		switch ($userCheck) {
			case 1:
				$out .= "<div class='dialog' >Email has to be company email. Please correct your entries here or <a href=index.php>login</a> to the already existing account.</div>";
				break;
			case 2:
				$out .= "<div class='dialog' >This email belongs to another user. Please correct your entries here or <a href=index.php>login</a> to the already existing account.</div>";
				break;
			default:
				userSignup($_POST['email'], encrypt($_POST['password']), $_POST['first_name'], $_POST['last_name']);
				$out .= "<div class='dialog' >You have almost completed the sign-up process. In a few minutes, you will receive an email at  ".$_POST['email']." from ".C_COMPANY.
					". Click the validation link in that email to complete your account setup. That link will return you to the login screen, so you may close this tab.</div>";
				break;
		}
	} else if ( isset($_POST['submit']) ) {  //signup incomplete
		$out .= "Error: Please fill in all fields.";
	}
//login submitted, not a signup
} else if (isset($_POST['submit'])) {  
	if (isset($_POST['email']) && isset($_POST['password'])) {
		$result = userLogin($_POST['email'], encrypt($_POST['password']));
		if ($result == "success") {
			if (isset($_POST['remember'])) {
				setcookie('remember_me', $_POST['email'], time()+31556926);
				// var_dump($_COOKIE);
				// die();
			} else if (!isset($_POST['remember'])) {
				if(isset($_COOKIE['remember_me'])) {
					setcookie('remember_me', '', time()- 9999);
					unset($_COOKIE['remember_me']);
				}
			}
			//header('Location: index.php');
			redirect('index.php');

		} else { $out .= $result; }
	} else {
		$out .= "<header><div class='contentheader'>
			<img src='img/logo.png' style='height:100px;'>
		</div>
	</header>";
		$out .= "Error: Please fill in all fields.";
	}
} else { //not a signup
	//$out .= "<div class='headline'>Black Bear Energy Portal</div>";
	$out .= "<header><div class='contentheader'>
			<img src='img/logo.png' style='height:100px;'>
		</div>
	</header>";
}


$out .= '<form id="main-form" name="main-form" onsubmit="return validate()" action="' . $_SERVER['REQUEST_URI'] . '" method="POST">';


if (!isset($userCheck) || $userCheck !=0) {
	if (isset($_GET['signup'])) {
		$out .= "<input class='field' type=\"text\" name=\"first_name\" placeholder=\"First Name\">
			<input class='field' type=\"text\" name=\"last_name\" placeholder=\"Last Name\">
			<input class='field' type=\"text\" name=\"email\" placeholder=\"Email Address\" size=\"30\">
			<input class='field' type=\"password\" name=\"password\" placeholder=\"Password\" size=\"30\">";
	} else {
		if (isset($_COOKIE['remember_me'])) {
			// $out .= __LINE__;
			$out .= "<input class='field' type='text' name='email' value='" . $_COOKIE['remember_me'] . "' placeholder='Email Address' size='30'>
				<input class='field' type=\"password\" name=\"password\" placeholder=\"Password\" size=\"30\"><br>
				<input type='checkbox' checked='checked' name='remember' id='remember_me'/><label style='color:white;font-size:18px;font-style:italic;' for='remember_me'>Remember Me</label>";
		} else {
			// $out .= __LINE__;
			$out .= "<input class='field' type='text' name='email' value='' placeholder='Email Address' size='30'>
				<input class='field' type=\"password\" name=\"password\" placeholder=\"Password\" size=\"30\"><br>
				<input type='checkbox' name='remember' id='remember_me'/><label style='color:white;font-size:18px;font-style:italic;' for='remember_me'>Remember Me</label>";
		}
	}

	$out .= "<input class='button' type='submit' name='submit' id='submit_button' value='";
	if (isset($_GET['signup'])) { $out .= "Create New Account" ; }
	else { $out .= "Log In"; } 

	$out .= "'><div style='clear:both;'></div><div class='register-forget'>";

	if (isset($_GET['signup'])) {
		$out .= "<a style='float:right;' class='return_link' href='index.php'>Back to Login Page</a><div style='clear:both;'></div>";
	} else {
		$out .= "<a style='float:left;' class='forgot' href='forgot.php'>Forgot Password</a><a style='float:right;' class='register' href='login.php?signup=1'>Register</a>";
	}

}

include_once('res/header.php');
?>

<script>
function signupSuccess(){
	$('#main-form').remove();
}

function validateEmail(email) { 
	var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
	return re.test(email);
}

function validate() {
	with (document.forms[0]){
		if (!validateEmail(email.value)) {
			alert ("Please enter a valid email address.");
			return false;
		}
		if (password.value.length < 6) {
			alert ("Please enter a password of at least 6 characters");
			return false;
		}
		if (document.getElementsByName("first_name") && ! first_name.value){
			alert ("Please enter a first name");
			return false;
		}
		if (document.getElementsByName("last_name") && ! last_name.value){
			alert ("Please enter a last name");
			return false;
		}
		return true;
	}
}
</script>
<div id="login" class="login">
<?php echo $out; ?>
</div>

</form>
</div>
<footer>
<?php } ?>