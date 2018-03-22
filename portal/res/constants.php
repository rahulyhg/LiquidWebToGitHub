<?php
	// QB authentication
	define('C_QB_USERNAME','ykowal@datacollaborative.com');  // QuickBase user who will access the QuickBase. please change
	define('C_QB_PASSWORD','lviv357834');  // Password of this user. please change
	define('C_QB_REALM','blackbearenergy.quickbase.com');  // Quickbase realm
	define('C_QB_SSL','https://blackbearenergy.quickbase.com/db/');  // Quickbase SSL
	define('C_QB_DATABASE','bnhcjxfdb');  // Database ID of the QuickBase being accessed
	define('C_QB_APPTOKEN','cf8ms6vcyus7vicdmacmc66g9pa');  // Application token
	define('C_SALT', 'ABCDEFG'); 

	//////////////////////////// OLD  QB portal login data
	define('C_DBID_USERS','bk6wv3wbm'); // Users table id 
	define('C_FID_USER_EMAIL', '8'); //  email fid
	define('C_FID_USER_PASSWORD', '22'); //  password fid
	define('C_FID_USER_TEMPORARY_PASSWORD', '23'); //  password fid
	define('C_FID_USER_VALIDATED', '24'); //  validated fidgmail
	define('C_FID_USER_FIRST_NAME', '6'); //  first name fid
	define('C_FID_USER_LAST_NAME', '7'); //  last name fid
	define('C_FID_USER_URL_KEY', '25'); // url key from validation email fid
	define('C_FID_USER_ACCOUNT_TYPE', '14');
	define('C_FID_USER_COMPANY_NAME', '34');
	define('C_FID_USER_APPROVED', '26');
	define('C_FID_USER_PHONE_NUMBER', '10');


	//QB app data
	define('CUSTOMER_SITES','bnhcjyer3'); 
	define('CUSTOMER_SITES_RFP','bnhcjy8nw');
	define('CUSTOMER_RFP','bnhcjzvpg');











//////OLD//////////////////////
	// // Client details and preferences
	define('C_COMPANY','Black Bear Energy');   // Company name
	define('C_RESTRICT_EMAIL_DOMAIN', false);   // Option to restrict email domain name for user sign ups
	

	// //automatic emails using Swiftmailer library
	define('C_MAILED_FROM', 'admin@blackbearportal.com'); // automatic emails will appear from this address
	define('C_MAILED_FROM_NAME', 'Black Bear'); // automatic emails will appear from this name
	define('C_MAILED_SERVER', 'blackbearportal.com'); // automatic emails server
	define('C_MAILED_USERMAIL_LOGIN', 'admin@blackbearportal.com'); // automatic emails user account
	define('C_MAILED_USERMAIL_PASSWORD', 'kjhIUY7654'); // automatic emails user password

	// emails for new user signup
	define('C_SIGNUP_SUBJECT', "Please Validate Your Account with ".C_COMPANY); //automatic email sugn up subject.
	define('C_SIGNUP_BODY', "This email was used to sign up a user on the ".C_COMPANY." website. 
		If you did not sign up for this service, you may ignore this email.\n
		Please use the link below to validate your account .\nThank You,\n".C_COMPANY."\n
		Please do not reply to this email, this address can not accept incoming mail.\n\n"); //This is an automatic email to confirm a new account. the confirmation link will be below
	define('C_SIGNUP_BODY_HTML', "This email was used to sign up a user on the ".C_COMPANY." website. 
		If you did not sign up for this service, you may ignore this email.<br>
		Please use the link below to validate your account .<br>Thank You,<br>".C_COMPANY."<br>
		Please do not reply to this email, this address can not accept incoming mail.<br><br>"); //This is an automatic email to confirm a new account. the confirmation link will be below

	// emails for forgot password
	define('C_FORGOT_PASSWORD_SUBJECT', "Password Reset"); //automatic email forgot password subject.
	define('C_FORGOT_PASSWORD_BODY', "You submitted a password reset request. If you did not initiate this request, ignore this email.
		\n\nPlease do not reply to this email, this address does not accept incoming mail.\n
		Your temporary password is:\n\n"); //This is an automatic email to help reset a forgotten password. the confirmation link will appear below this text
	define('C_FORGOT_PASSWORD_BODY_HTML', "You submitted a password reset request. If you did not initiate this request, ignore this email.
		<br><br>Please do not reply to this email, this address does not accept incoming mail.<br>
		Your temporary password is:<br><br>"); //This is an automatic email to help reset a forgotten password. the confirmation link will appear below this text
	
?>