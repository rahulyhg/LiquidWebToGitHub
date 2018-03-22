<?php
  require_once('../res/func.php');
  $_SESSION['customer_rid'] = 16;
  $active_solar = "";
  $active_storage = "";
  $active_led = "";

  if (isset($_REQUEST['technology'])){
    $technology = $_REQUEST['technology'];
    if ($technology == 'solar'){
      $active_solar = 'active';
    }
    if ($technology == 'storage'){
      $active_storage = 'active';
    }
    if ($technology == 'led'){
      $active_led = 'active';
    }
  } else{
    die('no technology');
  }
  if (isset($_REQUEST['site_rfp_rid'])){
    $site_rfp_rid = $_REQUEST['site_rfp_rid'];
  } else{
    die('no site_rfp_rid');
  }
  
  if (isset($_REQUEST['site_rid'])){
    $site_rid = $_REQUEST['site_rid'];
  } else{
    die('no site_rid');
  }

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <!-- Basic Page Needs
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <meta charset="utf-8">
  <title>Black Bear Energy</title>
  <meta name="description" content="">
  <meta name="author" content="">
  <!-- Mobile Specific Metas
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- FONT
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <link href="//fonts.googleapis.com/css?family=Raleway:400,300,600" rel="stylesheet" type="text/css">
  <!-- CSS
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <link rel="stylesheet" href="../../portal/css/normalize.css">
  <link rel="stylesheet" href="../../portal/css/skeleton.css">
  <link rel="stylesheet" href="../../portal/css/style.css">
  <style>
    .container {width: 90%;max-width: 90%; }
    html {
      font-size:80%;
     /* background: url('../images/header.png');
      background-repeat: no-repeat;
      background-size: 100% auto;*/
    }
    #download {
      cursor:pointer;
      position:relative;
      bottom:-140px;
      width:70px;
      height:70px;
      background:#333;
      background: url('../images/download.png');
      background-repeat: no-repeat;
      background-size: 100% auto;
      border-radius:50px;
    }
    #download:hover {
      opacity: .8; 
    }

    a.detail-nav.active {
      font-weight:bold;
      border-left:3px;
      border-left-style:solid;
      border-left-color:#DC3796;
      color:black;

    }
    a.detail-nav {
      padding-left:12px;
      cursor:pointer;
      color:#aaa;
    }

    a.detail-nav:hover {
      color:#DC3796;
    }
    a.detail-nav.active:hover {
      color:#000;
      cursor:default;
    }

    tr:hover {
      color:#DC3796;
    }

    table {
      font-size: 120%;
    }
  
    .time {
      background: #53c7de;
      background: #41a0c8;
      padding: 30px;
      color: white;
    }

    td:first-child {
      padding-left:5%;
    }
    td:last-child {
      /*float:right;*/
      padding-right:5%;
    }
    td:last-child input {
      float:right;
    }

    td {
      padding-top: 26px;
      padding-bottom: 26px;
    }

    .footer {
      color:white;
      background:#526175;
      height:400px;
    }

  </style>

  <!-- Favicon
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <link rel="icon" type="image/png" href="../images/favicon.ico">

  <script>

    function getParameterByName(name, url) {
        if (!url) url = window.location.href;
        name = name.replace(/[\[\]]/g, "\\$&");
        var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
            results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, " "));
    }

    var site_rfp_rid = getParameterByName('site_rfp_rid');
    var technology = getParameterByName('technology');

  </script>
</head>
<body>
  <?php include('top_nav.php'); ?>

  <!-- Primary Page Layout
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  

  <div class="row" style="margin-top:0;margin-bottom:0;height:260px;background: url('../images/header.jpg');background-repeat: no-repeat;background-size:cover;">
    <div class="nine columns">
      <h1 style='position:relative;bottom:-140px;font-size:4vw;font-weight:100;color:white;' id='site_name_header'>Site Name</h1>
    </div>
    <div class="two columns">
      <div id='download' class='u-pull-right;' title="Download CSV"></div>
    </div>
  </div>

  <div class="row" style='margin-top:10px;margin-left:5%;'>
      <a href='site_detail.php?site_rid=<?php echo $site_rid; ?>' class="two columns detail-nav">Site Details</a>
      <a class="two columns detail-nav <?php echo $active_solar; ?>" href='http://67.227.193.153/portal/customer/project_detail.php?technology=solar&site_rfp_rid=<?php echo $site_rfp_rid; ?>'>Solar Project</a>
      <a class="two columns detail-nav <?php echo $active_storage; ?>" href='http://67.227.193.153/portal/customer/project_detail.php?technology=storage&site_rfp_rid=<?php echo $site_rfp_rid; ?>'>Storage Project</a>
      <a class="two columns detail-nav <?php echo $active_led; ?>" href='http://67.227.193.153/portal/customer/project_detail.php?technology=led&site_rfp_rid=<?php echo $site_rfp_rid; ?>'>LED Project</a>
      <a href="" class="two columns detail-nav" style='margin-left:2%;'>Contracts</a>
      <a href="" class="two columns detail-nav" style='margin-left:0;width:17.3333333333%;'>Construction Photos</a>
    </div>

  <div class="container">

    <div class="row" style='margin-top:20px;margin-bottom:20px;'>
      <div class="four columns time u-full-width">Timeline placeholder</div>
      <div class="three columns time u-full-width" style='background: #53c7de;margin-left: 0;'>Timeline placeholder</div>
      <div class="five columns time u-full-width" style='background: #ccc;margin-left: 0;width:47%;'>Timeline placeholder</div>
    </div>
  </div><!-- end container -->

    <div class="row" style='margin-top:20px;'>
      <div class="twelve columns">
        <div class="preloader">
          <img src='../images/load.gif'>
        </div>
        <table class='u-full-width' id='' style='margin-bottom:0;'>
          
          <tbody id=''>
            <tr>
              <td>Site Analysis</td>
              <td>Plan Date: <span class='date' id='date_site_analisys_plan'></span></td>
              <td>Completion Date: <span class='date' id='date_site_analisys_copletion'></span></td>
              <td><input type='checkbox' id='date_site_analisys_checkbox'></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  <div class='footer'>
    <div class="row" style='padding-left:5%;padding-right:5%;'>
      <div class="six columns" style='margin-top:40px;'><h3>Project Documents</h3></div>
      <div class="one column offset-by-five" style='margin-top:60px;'>Download</div>
    </div>
  </div>

<!-- End Document
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <script src="../js/jquery-3.3.1.min.js" type="text/javascript"></script>
  <script>

    $('#my_projects').addClass('active');

    $( document ).ready(function() {
      
      
      function getTimeline(action, site_rfp_rid, technology) {
        $.ajax({
          type: "POST",
          dataType: "json",
          url: "../res/actions.php",
          data: {action: action, site_rfp_rid: site_rfp_rid, technology: technology},
          success: receivePage,
          error: ajaxError
        });
      }

      function receivePage( data ) {
        console.log(data);
        if(data.message){

        }else{
          $('#date_site_analisys_plan').html(data.html.date.date_site_analisys_plan);
          $('#date_site_analisys_copletion').html(data.html.date.date_site_analisys_copletion);

          $('.preloader').hide();
        }    
        
      }

      function ajaxError(a,b,c){
          alert('Error');
      }

       getTimeline('get_timeline', site_rfp_rid, technology);

    });

   
  </script>
  <script src="../js/sorttable.js"></script><!--https://www.kryogenix.org/code/browser/sorttable/#ajaxtables-->
</body>
</html>