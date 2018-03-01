<?php 
    require_once('func.php');
    if (isset($_REQUEST['rfp_id'])){
        $rfp_id=$_REQUEST['rfp_id'];
    } else {
        die('no rfp');
    }
    if (isset($_REQUEST['energy_technology'])){
        $energy_technology=$_REQUEST['energy_technology'];
    } else {
        die('no energy_technology');
    }
    if (isset($_REQUEST['rfp_rid'])){
        $rfp_rid=$_REQUEST['rfp_rid'];
    } else {
        die('no rfp_rid');
    }

    createEmailPreferences($rfp_rid, $rfp_id, $energy_technology);
?>