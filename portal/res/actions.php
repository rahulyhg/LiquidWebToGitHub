<?php 
	require_once('func.php');
	$action = $_REQUEST['action'];
	
	if ($action == 'upload_master_site_list'){
		uploadMasterSiteList($_REQUEST['related_customer_rid'],$_FILES['master_site_list_file']);
	}
	////////////////////////new
	if ($action == 'get_sites'){
		echo json_encode(getSites($_REQUEST['page'], 'all'));
	}
	if ($action == 'get_sites_solar'){
		echo json_encode(getSites($_REQUEST['page'], 'solar'));
	}
	if ($action == 'get_sites_storage'){
		echo json_encode(getSites($_REQUEST['page'], 'storage'));
	}
	if ($action == 'get_sites_led'){
		echo json_encode(getSites($_REQUEST['page'], 'led'));
	}

	if ($action == 'get_projects'){
		echo json_encode(getProjects($_REQUEST['page'], 'all'));
	}
	if ($action == 'get_projects_solar'){
		echo json_encode(getProjects($_REQUEST['page'], 'solar'));
	}
	if ($action == 'get_projects_storage'){
		echo json_encode(getProjects($_REQUEST['page'], 'storage'));
	}
	if ($action == 'get_projects_led'){
		echo json_encode(getProjects($_REQUEST['page'], 'led'));
	}

	if ($action == 'get_total_number_sites'){
		echo json_encode(getTotalNumberSites($_REQUEST['technology']));
	}
	if ($action == 'get_total_number_projects'){
		echo json_encode(getTotalNumberProjects($_REQUEST['technology']));
	}
	//site detail page
	if ($action == 'get_site'){
		echo json_encode(getSite($_REQUEST['site_rid']));
	}

	//projevt detail page timeline
	if ($action == 'get_timeline'){
		echo json_encode(getTimeline($_REQUEST['site_rfp_rid'],$_REQUEST['technology']));
	}
	/////////////////////////end new
	///analytics
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