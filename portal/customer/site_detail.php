<?php
  require_once('../res/func.php');
  $_SESSION['customer_rid'] = 16;
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
      bottom:-200px;
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
  

  <div class="row" style="margin-top:0;margin-bottom:0;height:360px;background: url('../images/header.png');background-repeat: no-repeat;background-size:cover;">
    <div class="ten columns u-full-width">
      <h1 class="u-full-width" style='position:relative;bottom:-180px;font-size:6vw;margin-left:5%;font-weight:100;color:white;'>Site Name</h1>
    </div>
    <div class="two columns u-full-width">
      <div id='download' class='u-pull-right;' title="Download CSV" style=""></div>
    </div>
  </div>

  <div class="container">

    <div class="row" style='margin-top:20px;'>
      <a href=''  class="two columns detail-nav active">Site Details</a>
      <a class="two columns detail-nav" href='project_detail.php'>Solar Project</a>
      <a href="" class="two columns detail-nav">Storage Project</a>
      <a href="" class="two columns detail-nav">LED Project</a>
      <a href="" class="two columns detail-nav" style='margin-left:2%;'>Contracts</a>
      <a href="" class="two columns detail-nav" style='margin-left:0;width:17.3333333333%;'>Construction Photos</a>
    </div>

    <div class="row" style='margin-top:40px;'>
      <div class="twelve columns grey-header u-full-width">Energy Tech Viability</div>
    </div>

    <div class="row" style='margin-top:40px;'>
      <div class="four columns viability-box"><h2>Solar</h2><div class='yes'>Yes</div></div>
      <div class="four columns viability-box"><h2>Storage</h2><div class='no'>No</div></div>
      <div class="four columns viability-box"><h2>LED</h2><div class='yes'>Yes</div></div>
    </div>

    <div class="row" style='margin-top:40px;'>
      <div class="twelve columns grey-header u-full-width">Initial Site Information</div>
    </div>

    <div class="row" style='margin-top:40px;'>
      <div class="three columns site-info-label">Site</div>
      <div class="three columns site-info-data">Harpers Ferry</div>
      <div class="three columns site-info-label">Unique Identifier</div>
      <div class="three columns site-info-data">MARK007</div>
    </div>
    <div class="row" style='margin-top:20px;'>
      <div class="three columns site-info-label">Address</div>
      <div class="three columns site-info-data">3412 Fairy Lane</div>
      <div class="three columns site-info-label">Market Segment</div>
      <div class="three columns site-info-data">Industrial</div>
    </div>
    <div class="row" style='margin-top:20px;'>
      <div class="three columns site-info-label">City</div>
      <div class="three columns site-info-data">Yucatan</div>
      <div class="three columns site-info-label">Building Sq. Footage</div>
      <div class="three columns site-info-data">30,000</div>
    </div>
    <div class="row" style='margin-top:20px;'>
      <div class="three columns site-info-label">State</div>
      <div class="three columns site-info-data">CA</div>
      <div class="three columns site-info-label">Utility</div>
      <div class="three columns site-info-data">PG&E</div>
    </div>
    <div class="row" style='margin-top:20px;'>
      <div class="three columns site-info-label">Zip</div>
      <div class="three columns site-info-data">92034</div>
    </div>

    <div class="row" style='margin-top:40px;'>
      <div class="twelve columns grey-header u-full-width">Additional Information</div>
    </div>

    <br>
    <br>
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
    $( document ).ready(function() {

      $('.preloader').hide();

      function getNextPage(action, technology, page) {
        $.ajax({
          type: "POST",
          dataType: "json",
          url: "../res/actions.php",
          data: {action: action, customer_rid: <?php echo $_SESSION['customer_rid']; ?>, page: page, technology: technology},
          success: receivePage,
          error: ajaxError
        });
      }

      function receivePage( data ) {
        console.log(data);
        if(data.message){
            $('#table_sites').append(data.message);
            $('#load_all_listings').hide(); 
        }else{
            $('#table_sites').append(data.html);
            $('#tbody_sites').fadeIn();
          }    

        
      }

      function ajaxError(a,b,c){
          alert('Error');
      }

    });
  </script>
  <script src="../js/sorttable.js"></script><!--https://www.kryogenix.org/code/browser/sorttable/#ajaxtables-->
</body>
</html>