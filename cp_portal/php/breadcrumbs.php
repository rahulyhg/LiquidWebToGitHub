<?php
function breadcrumbs($sep = '', $home = 'Dashboard')
{
$site = 'http://'.$_SERVER['HTTP_HOST'];
$crumbs = explode('/', strrev($_SERVER["REQUEST_URI"]), 2);
$homeurl = $site.strrev($crumbs[1]);
$page = strrev($crumbs[0]);
$link = ucfirst(str_replace( array(".php","-","_"), array(""," "," ") ,$page));
$link = preg_replace('/\?[^\?]*$/', '', $link);
$bc = '<ol class="breadcrumb">';
$bc .= '<li><a href="'.$homeurl.'/index.php'.'">'.$home.'</a>'.$sep.'</li>';
    if($link == "Index")
    {
        $bc .= '<li class="active">All RFPs</li>';
    }
    elseif($link == "Awarded rfps")
    {
        $bc .= '<li class="active">Awarded RFPs</li>';
    }
    elseif($link == "Metrics")
    {
        $bc .= '<li class="active">Metrics</li>';
    }
    elseif($link == "Rfp detail")
    {
        $bc .= '<li class="active">RFP Detail</li>';
    }
    else
    {
        $bc .= '<li class="active">'.$link.'</li>'; 
    }
$bc .= '</ol>';
return $bc;
}
?>