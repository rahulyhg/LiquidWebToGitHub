<?php

include_once('res/func.php');

if (isset($_POST['email']) && isset($_POST['temp_password']) && isset($_POST['new_password']) && isset($_POST['verify_new_password'])) {
	if ($_POST['new_password'] != $_POST['verify_new_password']) { die('New passwords do not match'); }
	else {
		userResetPassword($_POST['email'], encrypt($_POST['temp_password']), encrypt($_POST['new_password']));
		header("Location: login.php?reset=1");
	}
}

if (!isset($_GET['e']) && isset($_POST['email'])) { 
	if (!isset($_SESSION['emailReset'])) {
		userSetTemporaryPassword($_POST['email']);
		$_SESSION['emailReset'] = true;
	}
}

?>
<html>
<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="description" content="">
		<meta name="author" content="">
		<link rel="icon" type="image/png" sizes="16x16" href="../plugins/images/favicon.png">
		<title>Black Bear Energy</title>
		<!-- Bootstrap Core CSS -->
		<link href="bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
		<!-- animation CSS -->
		<link href="css/animate.css" rel="stylesheet">
		<!-- Custom CSS -->
		<link href="css/style.css" rel="stylesheet">
		<!-- color CSS -->
		<link href="css/colors/gray-dark.css" id="theme"  rel="stylesheet">
		<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
		<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
		<!--[if lt IE 9]>
		    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
		    <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
		<![endif]-->
		<style>
		  #wrapper.login-register {
		    background: #464A63 !important;
		    overflow: auto;
		  }
		  .login-box,.white-box, form.form-horizontal#loginform {background: #464A63 !important;}
		  h3.box-title,input, label, select, .text-dark, p {
		    color:#ccc !important;
		  }
		</style>

	<script>
		function signupSuccess() { 
			$('#main-form').remove(); 
		}

		function validateEmail(email) { 
			var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
			return re.test(email);
		}

		function validate(){
			with (document.forms[0]){
				if (!validateEmail(email.value)) {
					alert ("Please enter a valid email address.");
					return false;
				}
				if (temp_password.value.length < 1){
					alert ("Please enter the temporary password emailed to you");
					return false;
				}
				if (new_password.value.length < 8){
					alert ("Please enter a password of at least 8 characters");
					return false;
				}
				if (new_password.value !== verify_new_password.value){
					alert ("Please enter the same new password in both fields");
					return false;
				}
				return true;
			}
		}
	</script>
</head>
<body>
	<div class='preloader'>
	  	<div class='cssload-speeding-wheel'></div>
	</div>
	<section id='wrapper' class='login-register'>
	  	<div class='login-box'>
	    	<a href='javascript:void(0)' class='text-center db'><img style='width:360px;max-width:100%;' src='img/logo.png' alt='Home' /><br/></a>
	    
	    	<div class='white-box'>
	    		<div>
	    			<?php if (isset($_POST['email'])){echo ("<div class='well'>Thanks. Instructions for re-setting your password will be sent to you via email. You may close this window.</div>");}?>
	    		</div>

	    		<form id="main-form" name="main-form" onsubmit="return validate()" action="forgot.php" method="POST" class="form-horizontal form-material" id="loginform">
	    			<h3 class='box-title m-b-20'>Forgot Password</h3>

	    			<div class='form-group'>
		          		<div class='col-xs-12'>
							<input class='form-control' type='text' name='email' placeholder='Email Address' value="<?php if (isset($_GET['e'])) echo $_GET['e']; ?>">
						</div>
		        	</div>

					<?php
					if (isset($_GET['e'])) { ?>

						<div class='form-group'>
			          		<div class='col-xs-12'>
								<input class='form-control' type='password' name='temp_password' placeholder='Temporary Password'>
							</div>
			        	</div>
			        	<div class='form-group'>
			          		<div class='col-xs-12'>
								<input class='form-control' type='password' name='new_password' name='new_password' placeholder='New Password'>
							</div>
			        	</div>
			        	<div class='form-group'>
			          		<div class='col-xs-12'>
								<input class='form-control' type='password' name='verify_new_password' name='verify_new_password' placeholder='Verify New Password'>
							</div>
			        	</div>

					<?php } ?>

					<div class='form-group text-center m-t-20'>
      					<div class='col-xs-12'>
        					<button class='btn btn-info btn-lg btn-block text-uppercase waves-effect waves-light' type='submit' name='submit'>Reset Password</button>
      					</div>
    				</div>

				</form>
				</div>
			</div>
	</section>

	<script src="plugins/bower_components/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap Core JavaScript -->
    <script src="bootstrap/dist/js/bootstrap.min.js"></script>
    <!-- Menu Plugin JavaScript -->
    <script src="plugins/bower_components/sidebar-nav/dist/sidebar-nav.min.js"></script>
    <!--slimscroll JavaScript -->
    <script src="js/jquery.slimscroll.js"></script>
    <!--Wave Effects -->
    <script src="js/waves.js"></script>
    <!-- Custom Theme JavaScript -->
    <script src="js/custom.min.js"></script>
    <!-- jQuery peity -->
    <script src="plugins/bower_components/tablesaw-master/dist/tablesaw.js"></script>
    <script src="plugins/bower_components/tablesaw-master/dist/tablesaw-init.js"></script>
    <!--Style Switcher -->
    <script src="plugins/bower_components/styleswitcher/jQuery.style.switcher.js"></script>
    <!-- Footable -->
    <script src="plugins/bower_components/footable/js/footable.all.min.js"></script>
    <script src="plugins/bower_components/bootstrap-select/bootstrap-select.min.js" type="text/javascript"></script>
    <!--FooTable init-->
    <script src="js/footable-init.js"></script>

</body>
</html>