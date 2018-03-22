<?php
  require_once('../res/func.php');
  $_SESSION['customer_rid'] = 16;

  if (isset($_REQUEST['site_rid'])){
    $site_rid = $_REQUEST['site_rid'];
  } else {
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

    .grey-header {
      background:#eee;
      padding: 4px 18px;
      font-size:100%;
    }
    .viability-box {
      height:140px;
    }
    .viability-box h2 {
      text-align: center;
      margin-top:12px;
      margin-bottom:0;
    }

    .viability-box .yes, .viability-box .no {
      text-align: center;
      font-size:24pt;
      margin-top: -10px;
    }

    .yes {
      color:#DC3796;
    }
    .no {
      color:#ccc;
    }

    .viability-box:first-child {
      border-right:2px solid #ddd;
    }
    .viability-box:last-child {
      border-left:2px solid #ddd;
    }
    .site-info-label {
      font-weight:bold;
    }
    .site-info-data {
      color:#aaa;
    }

    

    /*css animations*/
    /*https://robots.thoughtbot.com/transitions-and-transforms*/
  </style>

  <!-- Favicon
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <link rel="icon" type="image/png" href="../images/favicon.ico">
</head>
<body>

  <!-- Primary Page Layout
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <!-- <div class="row" style="margin-top:0;margin-bottom:0;height:55px;">
    <a href="" class="u-pull-left top-nav">Bear</a>
    <a href="" class="u-pull-left top-nav active">Explore</a>
    <a href="" href="" class="u-pull-left top-nav">My Sites</a>
    <a href="" class="u-pull-left top-nav">My projects</a>
    <a href="" class="u-pull-left top-nav">Resources</a>
  </div> -->

  <?php include('top_nav.php'); ?>

  <!-- <div class="row" style="margin-top:0;margin-bottom:0;height:260px;background: url('../images/header.png');background-repeat: no-repeat;background-size:cover;">
    <div class="ten columns u-full-width">
      <h1 class="u-full-width" style='position:relative;bottom:-120px;font-size:4vw;margin-left:5%;font-weight:100;color:white;' id='site_name_header'></h1>
    </div>
    <div class="two columns u-full-width">
      <div id='download' class='u-pull-right;' title="Download CSV" style=""></div>
    </div>
  </div> -->

  <div class="row" style="margin-top:0;margin-bottom:0;height:260px;background: url('../images/header.jpg');background-repeat: no-repeat;background-size:cover;">
    <div class="nine columns">
      <h1 style='position:relative;bottom:-140px;font-size:4vw;font-weight:100;color:white;' id='site_name_header'>Site Name</h1>
    </div>
    <div class="two columns">
      <div id='download' class='u-pull-right;' title="Download CSV"></div>
    </div>
  </div>

  <div class="row" style='margin-top:10px;margin-left:5%;'>
      <a href=''  class="two columns detail-nav active">Site Details</a>
      <span id='tabs'></span>
      <a href="" class="two columns detail-nav" style='margin-left:2%;'>Contracts</a>
      <a href="" class="two columns detail-nav" style='margin-left:0;width:17.3333333333%;'>Construction Photos</a>
    </div>

  <div class="container">

    

    <div class="row" style='margin-top:20px;'>
      <div class="twelve columns grey-header u-full-width">Energy Tech Viability</div>
    </div>

    <div class="row" style='margin-top:20px;'>
      <div class="four columns viability-box"><h2>Solar</h2><div class='' id='good_solar'></div></div>
      <div class="four columns viability-box"><h2>Storage</h2><div class='' id='good_storage'></div></div>
      <div class="four columns viability-box"><h2>LED</h2><div class='' id='good_led'></div></div>
    </div>

    <div class="row" style='margin-top:20px;'>
      <div class="twelve columns grey-header u-full-width">Initial Site Information</div>
    </div>

    <div class="row" style='margin-top:20px;'>
      <div class="one columns site-info-label">Site</div>
      <div class="three columns site-info-data" id='site'>-</div>
      <div class="one columns site-info-label">Utility</div>
      <div class="three columns site-info-data" id='utility'>-</div>
      
      <div class="two columns site-info-label">Market Segment</div>
      <div class="two columns site-info-data" id='market'>-</div>
      
    </div>
    <div class="row" style='margin-top:10px;'> 
      <div class="one columns site-info-label">Address</div>
      <div class="three columns site-info-data" id='address'>-</div>
      <div class="one columns site-info-label">City</div>
      <div class="three columns site-info-data" id='city'>-</div>
      <div class="two columns site-info-label">Building Sq. Footage</div>
      <div class="two columns site-info-data" id='footage'>-</div>
    </div>
    <div class="row" style='margin-top:10px;'>
      <div class="one columns site-info-label">State</div>
      <div class="three columns site-info-data" id='state'>-</div>
      <div class="one columns site-info-label">Zip</div>
      <div class="three columns site-info-data" id='zip'>-</div>
      <div class="two columns site-info-label  u-full-width">Unique Identifier</div>
      <div class="two columns site-info-data" id='uid'>-</div>
           
    </div>


    <!-- <div class="row" style='margin-top:20px;'>
      <div class="two columns site-info-label">Site</div>
      <div class="four columns site-info-data" id='site'></div>
      <div class="two columns site-info-label">Unique Identifier</div>
      <div class="four columns site-info-data" id='uid'></div>
    </div>
    <div class="row" style='margin-top:20px;'>
      <div class="two columns site-info-label">Address</div>
      <div class="four columns site-info-data" id='address'></div>
      <div class="two columns site-info-label">Market Segment</div>
      <div class="four columns site-info-data" id='market'></div>
    </div>
    <div class="row" style='margin-top:20px;'>
      <div class="two columns site-info-label">City</div>
      <div class="four columns site-info-data" id='city'></div>
      <div class="two columns site-info-label">Building Sq. Footage</div>
      <div class="four columns site-info-data" id='footage'></div>
    </div>
    <div class="row" style='margin-top:20px;'>
      <div class="two columns site-info-label">State</div>
      <div class="four columns site-info-data" id='state'></div>
      <div class="two columns site-info-label">Utility</div>
      <div class="four columns site-info-data" id='utility'></div>
    </div>
    <div class="row" style='margin-top:20px;'>
      <div class="two columns site-info-label">Zip</div>
      <div class="four columns site-info-data" id='zip'></div>
    </div> -->

    <div class="row" style='margin-top:40px;'>
      <div class="twelve columns grey-header u-full-width">Additional Information</div>
    </div>

    <br>
    <br>
    <br>
    <br>
    <br>

  </div><!-- end container -->

  <div class="row">
    <div class="twelve columns">
      <div class="preloader">
        <img src='../images/load.gif'>
      </div>
    </div>
  </div>

<!-- End Document
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <script src="../js/jquery-3.3.1.min.js" type="text/javascript"></script>
  <script>

 $('#my_sites').addClass('active');





    $( document ).ready(function() {

      function getSite() {
        $.ajax({
          type: "POST",
          dataType: "json",
          url: "../res/actions.php",
          data: {action: 'get_site', site_rid: <?php echo $site_rid; ?>},
          success: receivePage,
          error: ajaxError
        });
      }

      function receivePage(data) {
        console.log(data);
        if(data.message){
          console.log(data.message);
        }else{
            $('#site_name_header').html(data.html.site);
            //$('#site').html(data.html.site);
            if (data.html.site !=""){
              $('#site').html(data.html.site);
            }
            //$('#address').html(data.html.address);
            if (data.html.address !=""){
              $('#address').html(data.html.address);
            }
            //$('#city').html(data.html.city);
            if (data.html.city !=""){
              $('#city').html(data.html.city);
            }
            //$('#state').html(data.html.state);
            if (data.html.state !=""){
              $('#state').html(data.html.state);
            }
            //$('#zip').html(data.html.zip);
            if (data.html.zip !=""){
              $('#zip').html(data.html.zip);
            }
            //$('#uid').html(data.html.uid);
            if (data.html.uid !=""){
              $('#uid').html(data.html.uid);
            }
            //$('#market').html(data.html.market);
            if (data.html.market !=""){
              $('#market').html(data.html.market);
            }
            //$('#fotage').html(data.html.fotage);
            if (data.html.fotage !=""){
              $('#fotage').html(data.html.fotage);
            }
            //$('#utility').html(data.html.utility);
            if (data.html.utility !=""){
              $('#utility').html(data.html.utility);
            }

            if (data.html.site_good_solar == '1'){
              $('#good_solar').html('Yes').addClass('yes');
            } else {
              $('#good_solar').html('No').addClass('no');
            }
            if (data.html.site_good_storage == '1'){
              $('#good_storage').html('Yes').addClass('yes');
            } else {
              $('#good_storage').html('No').addClass('no');
            }
            if (data.html.site_good_led == '1'){
              $('#good_led').html('Yes').addClass('yes');
            } else {
              $('#good_led').html('No').addClass('no');
            }
            if (parseInt(data.html.site_num_solar_rfps)>0){
              $('#tabs').append('<a class="two columns detail-nav" href="project_detail.php?technology=solar&site_rid=<?php echo $site_rid; ?>&site_rfp_rid='+data.html.site_solar_rfp_rid+'">Solar Project</a>');
            }
             if (parseInt(data.html.site_num_storage_rfps)>0){
              $('#tabs').append('<a class="two columns detail-nav" href="project_detail.php?technology=storage&site_rid=<?php echo $site_rid; ?>&site_rfp_rid='+data.html.site_storage_rfp_rid+'">Storage Project</a>');
            }
             if (parseInt(data.html.site_num_led_rfps)>0){
              $('#tabs').append('<a class="two columns detail-nav" href="project_detail.php?technology=led&site_rid=<?php echo $site_rid; ?>&site_rfp_rid='+data.html.site_led_rfp_rid+'">LED Project</a>');
            }
        } 
        $('.preloader').hide(); 
      }

      function ajaxError(a,b,c){
          console.log('Error');
      }
      getSite();

    });
  </script>
  <!-- <script src="../js/sorttable.js"></script> --><!--https://www.kryogenix.org/code/browser/sorttable/#ajaxtables-->
</body>
</html>