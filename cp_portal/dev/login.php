<?php
require_once('res/func.php');
// if (!isset($_REQUEST['user'])){
// 	header('Location: https://blackbearportal.com/maintenance.php');
// 	exit;
// } else {
// 	$_SESSION['temp_user'] = $_REQUEST['user'];
// }


if (isset($_GET['logout'])) {logout();}

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
		$out .= "<div class='well'>Congratulations! Your account has been validated.</div>";
	} else {
		$out .= "<div class='well'>This link may have expired or has already been validated.</div>";	
	}
}

// If signup was sent via URL, show signup form
if (isset($_GET['signup'])) { 
	// $out .= "<header><div class='contentheader'>
	// 		<img src='img/logo.png' style='height:100px;'>
	// 	</div>
	// </header>";
	// $out .= "<div class='headline'>Please Sign Up</div>";
	if (isset($_POST['submit']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['first_name']) && isset($_POST['last_name'])) {  //new signup completed
		$userCheck = checkForUser($_POST['email']);
		switch ($userCheck) {
			case 1:
				$out .= "<div class='well' >Email has to be company email. Please correct your entries here or <a href='login.php'>login</a> to the already existing account.</div>";
				break;
			case 2:
				$out .= "<div class='well' >This email belongs to another user. Please correct your entries here or <a href='login.php'>login</a> to the already existing account.</div>";
				break;
			default:
				userSignup($_POST['email'], encrypt($_POST['password']), $_POST['first_name'], $_POST['last_name'], $_POST['company_name'], $_POST['account_type'], $_POST['phone_number']);
				$out .= "<div class='well'>You have almost completed the sign-up process. In a few minutes, you will receive an email at  ".$_POST['email']." from ".C_COMPANY.
					". Click the validation link in that email to complete your account setup. That link will return you to the login screen, so you may close this tab.</div>";
				break;
		}
	} else if ( isset($_POST['submit']) ) {  //signup incomplete
		$out .= "Error: Please fill in all fields.";
	}
//login submitted, not a signup
} else if (isset($_POST['submit'])) {  
	if (isset($_POST['email']) && isset($_POST['password'])) {
		$result = userLogin($_POST['email'], encrypt($_POST['password']),$_POST['lat_lon']);
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
		} else if ($result == "pending_approval"){
			redirect('pending.php');
		} else if ($result == "blacklisted"){
			//$out .= "<header><div class='contentheader'>
					//<img src='img/logo.png' style='height:100px;'>
				//</div>
			//</header>";
			
			$out.="<!-- Preloader -->
				<div class='preloader'>
				  <div class='cssload-speeding-wheel'></div>
				</div>
				<section id='wrapper' class='login-register'>
				  <div class='login-box'>
				    <a href='javascript:void(0)'' class='text-center db'><img style='width:360px;max-width:100%;' src='img/logo.png' alt='Home' /><br/></a>
				    <!-- <div class='white-box'> -->
				    <div class='white-box'>";
				    $out .= "<div style='background:white;width:100%;padding:10px;color:#DC3796;font-weight:bold;'>Error: Invalid Login.</div>";
			//redirect('pending.php');

		} else { $out .= $result; }
	} else {
		$out .= "<header><div class='contentheader'>
			<img src='img/logo.png' style='height:100px;'>
		</div>
	</header>";
		$out .= "Error: Please fill in all fields.";
	}
} else { //not a signup
	$out.="<!-- Preloader -->
			<div class='preloader'>
			  <div class='cssload-speeding-wheel'></div>
			</div>
			<section id='wrapper' class='login-register'>
			  <div class='login-box'>
			    <a href='javascript:void(0)'' class='text-center db'><img style='width:360px;max-width:100%;' src='img/logo.png' alt='Home' /><br/></a>
			    <!-- <div class='white-box'> -->
			    <div class='white-box'>";
}


$out .= '<form id="main-form" name="main-form" onsubmit="return validate()" action="' . $_SERVER['REQUEST_URI'] . '" method="POST" class="form-horizontal form-material" id="loginform" >';


if (!isset($userCheck) || $userCheck !=0) {
	if (isset($_GET['signup'])) {
		$out.="<!-- Preloader -->
			<div class='preloader'>
			  <div class='cssload-speeding-wheel'></div>
			</div>
			<section id='wrapper' class='login-register'>
			  <div class='login-box'>
			    <a href='javascript:void(0)'' class='text-center db'><img style='width:360px;max-width:100%;' src='img/logo.png' alt='Home' /><br/></a>
			    <!-- <div class='white-box'> -->
			    <div class='white-box'>";
		$out .= "
		<h3 class='box-title m-b-20'>Please Sign Up</h3>
			        <div class='form-group'>
			          	<div class='col-xs-12'>
							<input class='form-control' type='text' name='first_name' placeholder='First Name' required>
						</div>
			        </div>
			        <div class='form-group'>
			          	<div class='col-xs-12'>
							<input class='form-control' type='text' name='last_name' placeholder='Last Name' required>
						</div>
			        </div>
			        <div class='form-group'>
			          	<div class='col-xs-12'>
							<input class='form-control' type='text' name='company_name' placeholder='Company Name' required>
						</div>
			        </div>
			        <div class='form-group'>
			          	<div class='col-xs-12'>
							<input class='form-control' type='tel' name='phone_number' placeholder='Phone Number' required>
						</div>
			        </div>
			        <div class='form-group'>
			          	<div class='col-xs-12'>
							<select class='form-control form-control-line' name='account_type' placeholder='Please select Account Type' required>
								<option value=''>Account Type</option>
								<option value='Customer'>Customer</option>
								<option value='Channel Partner'>Channel Partner</option>
							</select>
						</div>
			        </div>
			        <div class='form-group'>
			          	<div class='col-xs-12'>
							<input class='form-control' type='text' name='email' placeholder='Email Address' required>
						</div>
			        </div>
			        <div class='form-group'>
			          	<div class='col-xs-12'>
							<input class='form-control' type='password' name='password' placeholder='Password' required>
						</div>
			        </div>
			        <div class='form-group'>
			        	<div class='col-xs-12'>
				            <button class='btn btn-info btn-lg btn-block text-uppercase waves-effect waves-light' type='submit' name='submit'>Sign Up</button>
				         </div>
				    </div>";
			//add button?
		$out .= "<div class='form-group m-b-0'>
			          <div class='col-sm-12 text-center'>
			             <a href='login.php' class='text-primary m-l-5'><b>Back to Login Page</b></a>
			          </div>
			     </div>";
	} else {
		if (isset($_COOKIE['remember_me'])) {
			$out .="<!--span style='color:white;'>Black Bear's RFP Platform will be down for scheduled maintenance from 3pm MST Saturday to 12pm MST Sunday<hr></span-->
			<h3 class='box-title m-b-20'>Sign In</h3>
			
			        <div class='form-group'>
			          	<div class='col-xs-12'>
							<input class='form-control' type='text' name='email' value='" . $_COOKIE['remember_me'] . "' placeholder='Email Address'>
						</div>
			        </div>
			        <div class='form-group'>
			        	<div class='col-xs-12'>
							<input class='form-control' type='password' name='password' placeholder='Password'>
						</div>
			        </div>
			        <div class='form-group'>
				        <div class='col-md-12'>
				            <div class='checkbox checkbox-primary pull-left p-t-0'>
							<input type='checkbox' checked='checked' name='remember' id='remember_me' style='position:relative;margin-left:5px;'>
							<label for='remember_me'>Remember Me</label>
						</div>";
		} else {
			$out .="<!--span style='color:white;'>Black Bear's RFP Platform will be down for scheduled maintenance from 3pm MST Saturday to 12pm MST Sunday<hr></span-->
			<h3 class='box-title m-b-20'>Sign In</h3>

			        <div class='form-group'>
			          	<div class='col-xs-12'>
							<input class='form-control' type='text' name='email' placeholder='Email Address'>
						</div>
			        </div>
			        <div class='form-group'>
			        	<div class='col-xs-12'>
							<input class='form-control' type='password' name='password' placeholder='Password'>
						</div>
			        </div>
			        <div class='form-group'>
				        <div class='col-md-12'>
				            <div class='checkbox checkbox-primary pull-left p-t-0'>
							<input type='checkbox' checked='checked' name='remember' id='remember_me' style='position:relative;margin-left:5px;'>
							<label for='remember_me'>Remember Me</label>
						</div>";
		}
		$out .= "<a href='forgot.php' id='to-recover' class='text-dark pull-right'><i class='fa fa-lock m-r-5'></i> Forgot pwd?</a> </div></div>";
		$out .="<div class='form-group text-center m-t-20'>
          <div class='col-xs-12'>
            <button class='btn btn-info btn-lg btn-block text-uppercase waves-effect waves-light' type='submit' name='submit'>Log In</button>
          	<input type='hidden' id='lat_lon' name='lat_lon' value=''>
          </div>
        </div>
        <div class='form-group m-b-0'>
          <div class='col-sm-12 text-center'>
            <p>Don't have an account? <a href='login.php?signup=1' class='text-primary m-l-5'><b>Sign Up</b></a></p>
          </div>
        </div>";
	}

	// $out .= "<input class='button' type='submit' name='submit' id='submit_button' value='";

	// if (isset($_GET['signup'])) { $out .= "Create New Account" ; }
	// else { $out .= "Log In"; } 

	// $out .= "'><div style='clear:both;'></div><div class='register-forget'>";

	// if (isset($_GET['signup'])) {
	// 	//$out .= "<a style='float:right;' class='return_link' href='index.php'>Back to Login Page</a><div style='clear:both;'></div>";

		
	// }
	// } else {
	// 	$out .= "<a style='float:left;' class='forgot' href='forgot.php'>Forgot Password</a>
	// 	<a style='float:right;' class='register' href='login.php?signup=1'>Register</a>";
	// }

}

include_once('res/header.php');
?>

<script>

	// jQuery(document).ready(function($) {
 //        $.get("https://ipinfo.io/json", function (response) {
 //            //$("#ip").html("IP: " + response.ip);
 //            $("#lat_lon").val(response.loc);
 //            //window.alert (response.city + ", " + response.region);
 //            console.log (response);
 //        }, "jsonp");
 //    });


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
		if (password.value.length < 8) {
			alert ("Please enter a password of at least 8 characters");
			return false;
		}
		// if (document.getElementsByName("first_name") && ! first_name.value){
		// 	alert ("Please enter a first name");
		// 	return false;
		// }
		// if (document.getElementsByName("last_name") && ! last_name.value){
		// 	alert ("Please enter a last name");
		// 	return false;
		// }
		return true;
	}
}


</script>
<!-- <div id="login" class="login"> -->
<?php echo $out; ?>
<!-- </div> -->

</form>
 </div>
  </div>
</section>
<!-- jQuery -->
<script src="../plugins/bower_components/jquery/dist/jquery.min.js"></script>
<script>

	jQuery(document).ready(function($) {
        $.get("https://ipinfo.io/json", function (response) {
            //$("#ip").html("IP: " + response.ip);
            $("#lat_lon").val(response.loc);
            //window.alert (response.city + ", " + response.region);
            console.log (response);
        }, "jsonp");
    });

</script>
<!-- Bootstrap Core JavaScript -->
<script src="bootstrap/dist/js/bootstrap.min.js"></script>
<!-- Menu Plugin JavaScript -->
<script src="../plugins/bower_components/sidebar-nav/dist/sidebar-nav.min.js"></script>

<!--slimscroll JavaScript -->
<script src="js/jquery.slimscroll.js"></script>
<!--Wave Effects -->
<script src="js/waves.js"></script>
<!-- Custom Theme JavaScript -->
<script src="js/custom.min.js"></script>
<!--Style Switcher -->
<script src="../plugins/bower_components/styleswitcher/jQuery.style.switcher.js"></script>
</body>
</html>
<?php } ?>