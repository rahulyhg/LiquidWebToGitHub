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
    html {font-size:80%;}
    .clickable-row {cursor:pointer;}

  </style>

  <!-- Favicon
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <link rel="icon" type="image/png" href="../images/favicon.ico">

</head>
<body>
    
  <?php include('top_nav.php'); ?>
  <!-- Primary Page Layout
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <div class="container">

    <div class="row" style="margin-top:3rem; margin-bottom:.5rem">
      <div class="twelve columns"><h4 style='font-weight:100;margin-bottom:.2rem;'>Project Listing</h4></div>
    </div>
    <div class="row" style="">
      <div class="twelve columns"><span id='sortby'><b>SORT BY </b> / </span></div>
    </div>

    <div class="row" style="margin-bottom:2rem;margin-top:3rem;">
      <div class="twelve columns">
        <span class="label-primary u-full-width" id='sites_all'>All Projects</span>
        <span class="label u-full-width" id='sites_solar'>Solar Projects</span>
        <span class="label u-full-width" id='sites_storage'>Storage Projects</span>
        <span class="label u-full-width" id='sites_led'>LED Projects</span>
        
        <div class="u-pull-right" id='total_number_sites' style='display:none;'>Displaying 
          <span id='5of_all'></span><span id='number_all'>0</span>
          <span id='5of_solar' style='display:none;'></span><span style='display:none;' id='number_solar'>0</span>
          <span id='5of_storage' style='display:none;'></span><span style='display:none;' id='number_storage'>0</span>
          <span id='5of_led' style='display:none;'></span><span style='display:none;' id='number_led'>0</span>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="twelve columns">
      
        <div class="preloader">
            <img src='../images/load.gif'>
          </div>
        <input class='search' type="text" id="myInput_all" title="Type in a word">
        <table class='u-full-width sortable' id='table_sites'>
          <thead>
            <tr class='header-row'>
              <th style='width:20%;'>RFP NAME</th>
              <th style='width:20%;'>SITE</th>
              <th style='width:12%;'>SIZE</th>
              <th style='width:12%;'>PLAN DATE</th>
              <th style='width:12%;'>ENERGY TECH</th>
              <th style='width:12%;'>UTILITY</th>
              <th style='width:12%;'>PROGRESS</th>
            </tr>
          </thead>
         
          <tbody style='display:none;' id='tbody_sites'>
          </tbody>
        </table>
        <input class='search' type="text" id="myInput_solar" title="Type in a word" style='display:none;'>
        <table class='u-full-width sortable' id='table_sites_solar' style='display:none;'>
          <thead>
            <tr class='header-row'>
              <th style='width:20%;'>RFP NAME</th>
              <th style='width:20%;'>SITE</th>
              <th style='width:12%;'>SIZE</th>
              <th style='width:12%;'>PLAN DATE</th>
              <th style='width:12%;'>ENERGY TECH</th>
              <th style='width:12%;'>UTILITY</th>
              <th style='width:12%;'>PROGRESS</th>
            </tr>
          </thead>
          
          <tbody style='display:none;' id='tbody_sites_solar'>
          </tbody>
        </table>
        <input class='search' type="text" id="myInput_storage" title="Type in a word" style='display:none;'>
        <table class='u-full-width sortable' id='table_sites_storage' style='display:none;'>
          <thead>
            <tr class='header-row'>
              <th style='width:20%;'>RFP NAME</th>
              <th style='width:20%;'>SITE</th>
              <th style='width:12%;'>SIZE</th>
              <th style='width:12%;'>PLAN DATE</th>
              <th style='width:12%;'>ENERGY TECH</th>
              <th style='width:12%;'>UTILITY</th>
              <th style='width:12%;'>PROGRESS</th>
            </tr>
          </thead>
         
          <tbody style='display:none;' id='tbody_sites_storage'>
          </tbody>
        </table>
        <input class='search' type="text" id="myInput_led" title="Type in a word" style='display:none;'>
        <table class='u-full-width sortable' id='table_sites_led' style='display:none;'>
          <thead>
            <tr class='header-row'>
              <th style='width:20%;'>RFP NAME</th>
              <th style='width:20%;'>SITE</th>
              <th style='width:12%;'>SIZE</th>
              <th style='width:12%;'>PLAN DATE</th>
              <th style='width:12%;'>ENERGY TECH</th>
              <th style='width:12%;'>UTILITY</th>
              <th style='width:12%;'>PROGRESS</th>
            </tr>
          </thead>

          <tbody style='display:none;' id='tbody_sites_led'>
          </tbody>
        </table>
        
      </div>
    </div>

    <div class="row">
      <div class="four columns offset-by-four">
        <input class="button-primary u-full-width" type="submit" value="Load All Listings" id='load_all_listings' style='display:none;'>
        <input class="button-primary u-full-width" type="submit" value="Load All Listings" id='load_all_listings_solar' style='display:none;'>
        <input class="button-primary u-full-width" type="submit" value="Load All Listings" id='load_all_listings_storage' style='display:none;'>
        <input class="button-primary u-full-width" type="submit" value="Load All Listings" id='load_all_listings_led' style='display:none;'>
      </div>
    </div>

  </div><!-- end container -->

<!-- End Document
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <script src="../js/jquery-3.3.1.min.js" type="text/javascript"></script>
  <script>

 $('#my_projects').addClass('active');


    var xhr, xhr2;
    var requests = [];
    var requests2 = [];

    $( document ).ready(function() {

      var page_all = 0;
      var page_solar = 0;
      var page_storage = 0;
      var page_led = 0;

      function getNextPage(action, technology, page, instant) {
        var tab;
        if(xhr){
          if(instant) requests.unshift(arguments);
          else requests.push(arguments);
        } else {
          tab = $('.label-primary').attr('id').substring(6);
          if (technology == tab) $('.preloader').show();
          xhr = $.ajax({
            type: "POST",
            dataType: "json",
            url: "../res/actions.php",
            data: {action: action, page: page, technology: technology},
            success: somethingElse,
            error: ajaxError
          });
        }
      }

      function somethingElse(data){
        xhr = null;
        receivePage(data);
        if(requests.length) getNextPage.apply(null,requests.shift());
      }

      function receivePage( data ) {
        console.log(data);
        if(data.message){
          if(data.technology=='all'){
            $('#table_sites').append(data.message);
            $('#load_all_listings').hide();
          }
          if(data.technology=='solar'){
            $('#table_sites_solar').append(data.message);
            $('#load_all_listings_solar').hide();
          }
          if(data.technology=='storage'){
            $('#table_sites_storage').append(data.message);
            $('#load_all_listings_storage').hide();
          }
          if(data.technology=='led'){
            $('#table_sites_led').append(data.message);
            $('#load_all_listings_led').hide();
          }        
        }else{
          if(data.technology=='all'){
            $('#table_sites').append(data.html);
            $('#tbody_sites').fadeIn();
            if( page_all == 0 ){
              $('#load_all_listings').fadeIn();
              $('#5of_all').html(data.num + ' of ');
              $('#number_all').show();
            }
            page_all++;
            if( page_all > 1 && data.html ) {
              getNextPage('get_projects','all',page_all);
              $( '#load_all_listings' ).hide();
            }
          } 
          if(data.technology=='solar'){
            $('#table_sites_solar').append(data.html);
            $('#tbody_sites_solar').fadeIn();
            if( page_solar == 0 ){
              $('#5of_solar').html(data.num + ' of ');
            }
            page_solar++;
            if( page_solar > 1 && data.html ) {
              getNextPage('get_projects_solar','solar',page_solar);
              $( '#load_all_listings_solar' ).hide();
            }
          } 
          if(data.technology=='storage'){
            $('#table_sites_storage').append(data.html);
            $('#tbody_sites_storage').fadeIn();
            if( page_storage == 0 ){
              $('#5of_storage').html(data.num + ' of ');
            }
            page_storage++;
            if( page_storage > 1 && data.html ) {
              getNextPage('get_projects_storage','storage',page_storage);
              $( '#load_all_listings_storage' ).hide();
            }
          }
          if(data.technology=='led'){
            $('#table_sites_led').append(data.html);
            $('#tbody_sites_led').fadeIn();
            if( page_led == 0 ){
              $('#5of_led').html(data.num + ' of ');

            }
            page_led++;
            if( page_led > 1 && data.html ) {

              getNextPage('get_projects_led','led',page_led);
              $( '#load_all_listings_led' ).hide();
            }
          }    
        }

        $('.preloader').hide();
      }

      function ajaxError(a,b,c){
          console.log('Error');
      }

      // function getTotalNumberSites(technology) {
      //   xhr2 = $.ajax({
      //     type: "POST",
      //     dataType: "json",
      //     url: "../res/actions.php",
      //     data: {action: "get_total_number_projects", technology:technology },
      //     success: receiveTotalNumberSites,
      //     error: ajaxError
      //   });
      // }

      function getTotalNumberSites(technology) {
        if(xhr2){
          requests2.push(arguments);
        } else {
          xhr2 = $.ajax({
            type: "POST",
            dataType: "json",
            url: "../res/actions.php",
            data: {action: "get_total_number_projects", technology:technology },
            success: receiveTotalNumberSites,
            error: ajaxError
          });
        }
      }

      function receiveTotalNumberSites( data ) {
        console.log(data);
        if(data.message){
        }else{
          if (data.technology == 'all'){
            $('#number_all').html(data.html);
          }
          if (data.technology == 'solar'){
            $('#number_solar').html(data.html);
          }
          if (data.technology == 'storage'){
            $('#number_storage').html(data.html);
          }
          if (data.technology == 'led'){
            $('#number_led').html(data.html);
          }
          
          $('#total_number_sites').fadeIn();
        }
        xhr2 = null;
        if(requests2.length) getTotalNumberSites.apply(null,requests2.shift());
      }

      $( '#sites_all' ).click( function(){
        $( '#sites_all' ).addClass('label-primary').removeClass('primary');
        $( '#sites_solar' ).addClass('label').removeClass('label-primary');
        $( '#sites_storage' ).addClass('label').removeClass('label-primary');
        $( '#sites_led' ).addClass('label').removeClass('label-primary');
        $( '#load_all_listings' ).show();
        $( '#load_all_listings_solar' ).hide();
        $( '#load_all_listings_storage' ).hide();
        $( '#load_all_listings_led' ).hide();

        $( '#table_sites' ).show();
        $( '#table_sites_solar' ).hide();
        $( '#table_sites_storage' ).hide();
        $( '#table_sites_led' ).hide();

        $('#number_all').show();
        $('#number_solar').hide();
        $('#number_storage').hide();
        $('#number_led').hide();

        $('#5of_all').show();
        $('#5of_solar').hide();
        $('#5of_storage').hide();
        $('#5of_led').hide();

        $('#myInput_all').show();
        $('#myInput_solar').hide();
        $('#myInput_storage').hide();
        $('#myInput_led').hide();

        $('#sortby').html("<b>SORT BY </b> / ");
      });

      $( '#sites_solar' ).click( function(){
        $( '#sites_solar' ).addClass('label-primary').removeClass('primary');
        $( '#sites_all' ).addClass('label').removeClass('label-primary');
        $( '#sites_storage' ).addClass('label').removeClass('label-primary');
        $( '#sites_led' ).addClass('label').removeClass('label-primary');
        $( '#load_all_listings_solar' ).show();
        $( '#load_all_listings' ).hide();
        $( '#load_all_listings_storage' ).hide();
        $( '#load_all_listings_led' ).hide();

        $( '#table_sites' ).hide();
        $( '#table_sites_solar' ).show();
        $( '#table_sites_storage' ).hide();
        $( '#table_sites_led' ).hide();

        $('#number_all').hide();
        $('#number_solar').show();
        $('#number_storage').hide();
        $('#number_led').hide();

        $('#5of_all').hide();
        $('#5of_solar').show();
        $('#5of_storage').hide();
        $('#5of_led').hide();

        $('#myInput_all').hide();
        $('#myInput_solar').show();
        $('#myInput_storage').hide();
        $('#myInput_led').hide();

        $('#sortby').html("<b>SORT BY </b> / ");
      });

      $( '#sites_storage' ).click( function(){
        $( '#sites_storage' ).addClass('label-primary').removeClass('primary');
        $( '#sites_all' ).addClass('label').removeClass('label-primary');
        $( '#sites_solar' ).addClass('label').removeClass('label-primary');
        $( '#sites_led' ).addClass('label').removeClass('label-primary');
        $( '#load_all_listings_storage' ).show();
        $( '#load_all_listings' ).hide();
        $( '#load_all_listings_solar' ).hide();
        $( '#load_all_listings_led' ).hide();

        $( '#table_sites' ).hide();
        $( '#table_sites_solar' ).hide();
        $( '#table_sites_storage' ).show();
        $( '#table_sites_led' ).hide();

        $('#number_all').hide();
        $('#number_solar').hide();
        $('#number_storage').show();
        $('#number_led').hide();

        $('#5of_all').hide();
        $('#5of_solar').hide();
        $('#5of_storage').show();
        $('#5of_led').hide();

        $('#myInput_all').hide();
        $('#myInput_solar').hide();
        $('#myInput_storage').show();
        $('#myInput_led').hide();

        $('#sortby').html("<b>SORT BY </b> / ");
      });

      $( '#sites_led' ).click( function(){
        $( '#sites_led' ).addClass('label-primary').removeClass('primary');
        $( '#sites_all' ).addClass('label').removeClass('label-primary');
        $( '#sites_storage' ).addClass('label').removeClass('label-primary');
        $( '#sites_solar' ).addClass('label').removeClass('label-primary');
        $( '#load_all_listings_led' ).show();
        $( '#load_all_listings' ).hide();
        $( '#load_all_listings_solar' ).hide();
        $( '#load_all_listings_storage' ).hide();

        $( '#table_sites' ).hide();
        $( '#table_sites_solar' ).hide();
        $( '#table_sites_storage' ).hide();
        $( '#table_sites_led' ).show();

        $('#number_all').hide();
        $('#number_solar').hide();
        $('#number_storage').hide();
        $('#number_led').show();

        $('#5of_all').hide();
        $('#5of_solar').hide();
        $('#5of_storage').hide();
        $('#5of_led').show();

        $('#myInput_all').hide();
        $('#myInput_solar').hide();
        $('#myInput_storage').hide();
        $('#myInput_led').show();

        $('#sortby').html("<b>SORT BY </b> / ");
      });


      $( '#load_all_listings' ).click( function(){
        getNextPage('get_projects','all', page_all, true);
        $('#5of_all').html('');
      });

      $( '#load_all_listings_solar' ).click( function(){
        getNextPage('get_projects_solar', 'solar', page_solar, true);
        $('#5of_solar').html('');
      });

      $( '#load_all_listings_storage' ).click( function(){
        getNextPage('get_projects_storage', 'storage', page_storage, true);
        $('#5of_storage').html('');
      });

      $( '#load_all_listings_led' ).click( function(){
        getNextPage('get_projects_led', 'led', page_led, true);
        $('#5of_led').html('');  
      });

      getNextPage('get_projects','all', page_all);
      getTotalNumberSites('all');
      getNextPage('get_projects_solar','solar', page_solar);
      getNextPage('get_projects_storage','storage', page_storage);
      getNextPage('get_projects_led','led', page_led);
      getTotalNumberSites('solar');
      getTotalNumberSites('storage');
      getTotalNumberSites('led');

      $('th').click(function(){
        var header = this.innerHTML.split('<');
        header= header[0];
        $('#sortby').html("<b>SORT BY </b> / " + header);
      });

      function myFunction(myInput,myTable) {
        //https://www.w3schools.com/howto/tryit.asp?filename=tryhow_js_filter_table
        var input, filter, table, tr, td, i;
        input = document.getElementById(myInput);
        filter = input.value.toUpperCase();
        table = document.getElementById(myTable);
        tr = table.getElementsByTagName("tr");
        for (i = 1; i < tr.length; i++) {
          if (tr[i].innerHTML.toUpperCase().indexOf(filter) > -1) {
            tr[i].style.display = "";
          } else {
            tr[i].style.display = "none";
          }       
        }
      };

      $("#myInput_all").keyup(function(e){
          var input = document.getElementById("myInput_all").value;
          var code = e.keyCode || e.which;
          if (input.length >= 2 || code == 13 || code == 127 || code == 8){
            myFunction('myInput_all','table_sites');
          }
          var newlen = $('#table_sites tr:visible').length;
          $('#5of_all').html((newlen-1) + " of ");
      });
      $("#myInput_solar").on('keyup', function(e){
        var input = document.getElementById("myInput_solar").value;
          var code = e.keyCode || e.which;
          if (input.length >= 2 || code == 13 ){
            myFunction('myInput_solar','table_sites_solar');
          }
          var newlen = $('#table_sites_solar tr:visible').length;
          $('#5of_solar').html((newlen-1) + " of ");
      });
      $("#myInput_storage").on('keyup', function(e){
          var input = document.getElementById("myInput_storage").value;
          console.log(input);
          var code = e.keyCode || e.which;
          if (input.length >= 2 || code == 13 || code == 127 || code == 8){
            myFunction('myInput_storage','table_sites_storage');
          }
          var newlen = $('#table_sites_storage tr:visible').length;
          $('#5of_storage').html((newlen-1) + " of ");
      });
      $("#myInput_led").on('keyup', function(e){
          var input = document.getElementById("myInput_led").value;
          console.log(input);
          var code = e.keyCode || e.which;
          if (input.length >= 2 || code == 13 || code == 127 || code == 8){
            myFunction('myInput_led','table_sites_led');
          }
          var newlen = $('#table_sites_led tr:visible').length;
          $('#5of_led').html((newlen-1) + " of ");
      });

       $(".container").on('click', '.clickable-row', function() {
        stopAjax();
        window.location = $(this).data("href");
      });

      function stopAjax(){
        if(xhr) xhr.abort();
        if(xhr2) xhr2.abort();
        requests=[];
        requests2=[];
      }

    });

  </script>
  <script src="../js/sorttable.js"></script><!--https://www.kryogenix.org/code/browser/sorttable/#ajaxtables-->
</body>
</html>