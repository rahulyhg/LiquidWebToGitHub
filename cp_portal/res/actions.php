<?php 

	require_once('func.php');

	$action = $_REQUEST['action'];

	if ($action == 'optin'){

		optIn($_REQUEST['channel_partner_id'],$_REQUEST['rfp_id'],$_REQUEST['rfp_energy_technology'],$_REQUEST['optin_rid'],$_REQUEST['redirect'],$_REQUEST['rfp_rid']);

	}

	if ($action == 'optout'){

		optOut($_REQUEST['channel_partner_id'],$_REQUEST['rfp_id'],$_REQUEST['rfp_energy_technology'],$_REQUEST['optin_rid'],$_REQUEST['opted_out_by'],$_REQUEST['reason_text'], $_REQUEST['redirect'],$_REQUEST['rfp_rid']);

	}

	if ($action == 'upload'){

// 		var_dump($_FILES);


// 		header('Content-Type: text/plain; charset=utf-8');

// try {
    
//     // Undefined | Multiple Files | $_FILES Corruption Attack
//     // If this request falls under any of them, treat it invalid.
//     if (
//         !isset($_FILES['optin_rfp_response_file']['error']) ||
//         is_array($_FILES['optin_rfp_response_file']['error'])
//     ) {
//         throw new RuntimeException('Invalid parameters.');
//     }

//     // Check $_FILES['upfile']['error'] value.
//     switch ($_FILES['optin_rfp_response_file']['error']) {
//         case UPLOAD_ERR_OK:
//             break;
//         case UPLOAD_ERR_NO_FILE:
//             throw new RuntimeException('No file sent.');
//         case UPLOAD_ERR_INI_SIZE:
//         	throw new RuntimeException('UPLOAD_ERR_INI_SIZE.');
//         case UPLOAD_ERR_FORM_SIZE:
//             throw new RuntimeException('Exceeded filesize limit.');
//         default:
//             throw new RuntimeException('Unknown errors.');
//     }

//     // You should also check filesize here. 
//     if ($_FILES['optin_rfp_response_file']['size'] > 1000000) {
//         throw new RuntimeException('Exceeded filesize limit.');
//     }

//     // DO NOT TRUST $_FILES['upfile']['mime'] VALUE !!
//     // Check MIME Type by yourself.
//     $finfo = new finfo(FILEINFO_MIME_TYPE);
//     if (false === $ext = array_search(
//         $finfo->file($_FILES['optin_rfp_response_file']['tmp_name']),
//         array(
//             'jpg' => 'image/jpeg',
//             'png' => 'image/png',
//             'gif' => 'image/gif',
//             'pdf' => 'application/pdf',
//         ),
//         true
//     )) {
//         throw new RuntimeException('Invalid file format.');
//     }

//     // You should name it uniquely.
//     // DO NOT USE $_FILES['upfile']['name'] WITHOUT ANY VALIDATION !!
//     // On this example, obtain safe unique name from its binary data.
//     if (!move_uploaded_file(
//         $_FILES['optin_rfp_response_file']['tmp_name'],
//         sprintf('uploads/%s.%s',
//             sha1_file($_FILES['optin_rfp_response_file']['tmp_name']),
//             $ext
//         )
//     )) {
//         throw new RuntimeException('Failed to move uploaded file.');
//     }

//     echo 'File is uploaded successfully.';

// } catch (RuntimeException $e) {

//     echo $e->getMessage();

// }



// 	die();

		uploadFileOptin($_REQUEST['rfp_rid'], $_REQUEST['optin_rid'],$_REQUEST['channel_partner_id'],$_FILES['optin_rfp_response_file'],$_FILES['optin_laf_file'],$_FILES['optin_ppa_file'],$_FILES['optin_bid_form_file'],$_FILES['optin_other_document_file'],$_FILES['channel_partner_qualification_form_file'],$_REQUEST['category']);

	} 

	if ($action == 'ask_question'){

		askQuestion($_REQUEST['submitted_by'],$_REQUEST['question_text'],$_REQUEST['rfp_id'],$_REQUEST['channel_partner_id'],$_REQUEST['rfp_rid'],$_REQUEST['category']);

	}	

	if ($action == 'upload_master_site_list'){

		uploadMasterSiteList($_REQUEST['related_customer_rid'],$_FILES['master_site_list_file']);

	}

	if ($action == 'create_bid'){

		addOrEditBids($_REQUEST['rfp_rid'],$_REQUEST['channel_partner_id'],$_REQUEST['site_rfp_rid'],$_REQUEST['category']);

	}

	if ($action == 'save_email_preferences'){

		saveChanelPartnerUsersEmailPreferences($_REQUEST['rfp_rid'],$_REQUEST['category']);

	}

	if ($action == 'save_bid_notes'){

		saveBidNotesQB($_REQUEST['optin_rid'],$_REQUEST['bid_notes'],$_REQUEST['rfp_rid'],$_REQUEST['category'],$_REQUEST['channel_partner_id']);

	}

	if ($action == 'save_bid_confirmations'){

		showConfirmationsQB($_REQUEST['rfp_energy_technology'],$_REQUEST['optin_rid'],$_REQUEST['rfp_rid'],$_REQUEST['category'],$_REQUEST['channel_partner_id'],$_REQUEST['fid53'],$_REQUEST['fid54'],$_REQUEST['fid55'],$_REQUEST['fid56'],$_REQUEST['fid57'],$_REQUEST['fid58'],$_REQUEST['fid59'],$_REQUEST['fid60'],$_REQUEST['fid61']);

	}

	if ($action == 'save_bid_confirmations_led'){

		showConfirmationsLEDQB($_REQUEST['rfp_energy_technology'],$_REQUEST['optin_rid'],$_REQUEST['rfp_rid'],$_REQUEST['category'],$_REQUEST['channel_partner_id'],$_REQUEST['fid56'],$_REQUEST['fid78']);

	}

	if ($action == 'add_fixture_type'){

		addFixtureType($_REQUEST['bid_rid'],$_REQUEST['fixture_type'],$_REQUEST['manufacturer'],$_REQUEST['warranty'],$_REQUEST['quantity'],$_REQUEST['unit_price'],$_REQUEST['rfp_rid']);

	}

	if ($action == 'delete_existing_fixture'){

		deleteFixtureType($_REQUEST['fixture_rid'],$_REQUEST['rfp_rid']);

	}

	if ($action == 'page_view'){

		echo json_encode(trackPageViewStart($_REQUEST['loc'],$_REQUEST['page'],$_REQUEST['file_url'],$_REQUEST['rfp_rid']));

	}

	if ($action == 'page_view_end'){

		trackPageViewEnd($_REQUEST['page_rid']);

	}

	if ($action == 'interaction'){

		trackPageInteraction($_REQUEST['type'],$_REQUEST['file_url'],$_REQUEST['id'],$_REQUEST['rfp_rid'],$_REQUEST['page'],$_REQUEST['loc'],$_REQUEST['target_type']);

	}



	/*switch( $action ) {

		case 'interaction':

		break;

	}*/



?>