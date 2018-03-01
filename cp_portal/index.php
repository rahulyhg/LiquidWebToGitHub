<?php 
ini_set('session.save_path',realpath(dirname($_SERVER['DOCUMENT_ROOT']) . '/sessions'));
session_start();
//---------------- Add for Switch Server from Newtek to Hostek  -----------------------------
// Use redirect function instead of header here.
//if (!function_exists('redirect')) {
//    function redirect($url) {
//        if(!headers_sent()) {
//            //If headers not sent yet... then do php redirect
//            header('Location: '.$url);
//            exit;
//        } else {
//            //If headers are sent... do javascript redirect... if javascript disabled, do html redirect.
//            echo '<script type="text/javascript">';
//            echo 'window.location.href="'.$url.'";';
//            echo '</script>';
//            echo '<noscript>';
//            echo '<meta http-equiv="refresh" content="0;url='.$url.'" />';
//            echo '</noscript>';
//            exit;
//        }
//    }
//}
//redirect("https://backup.blackbearportal.com/login.php");
require_once('res/func.php');
//echo $_SERVER['SCRIPT_FILENAME'];
//die();

 //if (!isset($_SESSION['temp_user'])){
         ////header('Location: https://blackbearportal.com/maintenance.php');
//header('Location: https://physicsfinance.com/maintenance.php');
         //exit;
     //}

//die(var_dump($_SESSION));

if (isset($_SESSION['channel_partner'])){
    $_SESSION['channel_partner'] = $_SESSION['channel_partner'];
    //die($_SESSION['channel_partner']);
} else {
    die('no session');
    redirect('login.php');
}

?>

<!DOCTYPE html>

<html lang="en">



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

     <!-- Footable CSS -->

    <link href="plugins/bower_components/footable/css/footable.core.css" rel="stylesheet">

    <link href="plugins/bower_components/bootstrap-select/bootstrap-select.min.css" rel="stylesheet" >

    <!-- Menu CSS -->

    <link href="plugins/bower_components/sidebar-nav/dist/sidebar-nav.min.css" rel="stylesheet">

    <link href="plugins/bower_components/tablesaw-master/dist/tablesaw.css" rel="stylesheet">

    <!-- toast CSS -->

    <link href="plugins/bower_components/toast-master/css/jquery.toast.css" rel="stylesheet">

    <!-- morris CSS -->

    <link href="plugins/bower_components/morrisjs/morris.css" rel="stylesheet">

    <!-- animation CSS -->

    <link href="css/animate.css" rel="stylesheet">

    <!-- Custom CSS -->

    <link href="css/style.css" rel="stylesheet">

    <!-- color CSS -->

    <link href="css/colors/gray-dark.css" id="theme" rel="stylesheet">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->

    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->

    <!--[if lt IE 9]>

    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>

    <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>

<![endif]-->

    <script>
        // (function (i, s, o, g, r, a, m) {

        //     i['GoogleAnalyticsObject'] = r;

        //     i[r] = i[r] || function () {

        //         (i[r].q = i[r].q || []).push(arguments)

        //     }, i[r].l = 1 * new Date();

        //     a = s.createElement(o), m = s.getElementsByTagName(o)[0];

        //     a.async = 1;

        //     a.src = g;

        //     m.parentNode.insertBefore(a, m)

        // })(window, document, 'script', 'https://www.google-analytics.com/analytics.js', 'ga');

        // ga('create', 'UA-19175540-9', 'auto');

        // ga('send', 'pageview');

    </script>

    <style>

        table, th, td, tr {

            border-collapse: collapse !important;

        }

        .table>tbody>tr>td, .table>tbody>tr>th, .table>tfoot>tr>td, .table>tfoot>tr>th, .table>thead>tr>td, .table>thead>tr>th {

            padding:5px;

        }



        .footable > thead > tr > th > span.footable-sort-indicator {

            display:none;

        }

        .clickable-row {
            cursor:pointer;
        }

        /*.btn-info, .btn-info:hover {

            background:#DC3796;

            border:1px solid #DC3796;

        }

        .btn-success,.btn-success:hover  {

            background:#A7A9AC;

            border:1px solid #A7A9AC;

            background:#41A0C8;

            border:1px solid #41A0C8;

        }*/





    </style>

</head>

<?php 



    include 'php/header.php';

    include 'php/left-sidebar.php'; include 'php/breadcrumbs.php';

    $out ='';

    if ($_SESSION['contact_type'] == "Customer"){

        $greeting= $_SESSION['customer_name'];

    } else {

        $greeting= $_SESSION['channel_partner_name'];

    }



    //if (isset($_SESSION['uid'])){$_SESSION['uid'] = $_SESSION['uid'];} else {$_SESSION['uid'] = '23';}

    

?>

        <!-- Page Content -->

    <div id="page-wrapper">

        <div class="container-fluid">

            <div class="row bg-title">

                <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12">

                    <h4 class="page-title"><?php echo $greeting; ?></h4> </div>

                <div class="col-lg-9 col-sm-8 col-md-8 col-xs-12">

                    <?php echo breadcrumbs(); ?>

                </div>

                <!-- /.col-lg-12 -->

            </div>

            <?php 

            if ($_SESSION['contact_type'] == "Customer") {

                echo "<div class='row'>

                        <div class='col-md-12'>

                            <div class='white-box'>";

                echo showCustomerRFPsCustomerView($_SESSION['related_customer_rid']);

                echo "</div>

                    </div>

                </div>";

            }

            ?>

            <div class="row">

                <div class="col-md-12">

                    <div class="white-box"> 

                        <?php 

                            if ($_SESSION['contact_type'] == "Channel Partner") {

                                echo showCustomerRFPs($_SESSION['channel_partner']); 

                             } else {

                                echo showAwardedSiteRfps($_SESSION['related_customer_rid']);

                            }

                        ?>

                    </div>

                </div>

            </div>

            

            <?php 

            

            // if ($_SESSION['contact_type'] == "Channel Partner") {

            //     echo "<div class='row'>

            //             <div class='col-md-12'>

            //                 <div class='white-box'>";

            //     echo showQuestions($_SESSION['channel_partner']);

            //     echo "</div>

            //         </div>

            //     </div>";

            // }

                

            ?>

                    

            

        </div>

        <!-- /.container-fluid -->

        <?php //include 'php/footer.php';?>

    </div>

    <!-- /#page-wrapper -->

    <input type='hidden' id='page_view_start' value=''>

    <input type='hidden' id='page_view_end' value=''>

    <input type='hidden' id='activity_rid' value=''>

    <input type='hidden' id='lat_lon' value=''>

    <input type='hidden' id='rfp_rid' value=''>

    </div>

    <!-- /#wrapper -->

    <!-- jQuery -->

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

    <script>

        jQuery(document).ready(function($) {



            $(".clickable-row").click(function() {

                window.location = $(this).data("href");

            });



            $.get("https://ipinfo.io/json", function (response) {

                //$("#ip").html("IP: " + response.ip);

                $("#lat_lon").val(response.loc);

                //window.alert (response.city + ", " + response.region);

                console.log (response);

                var page = 'Home';

                var file_url = 'index';

                var rfp_rid = $("#rfp_rid").val();



                $.ajax({

                    type: "POST",

                    dataType: "json",

                    url: "res/actions.php",

                    data: {action: "page_view", loc: response.loc, page: 'Home', file_url: 'index', rfp_rid: rfp_rid},

                    success: function(data){

                        if(data.errorcode == 0){

                            $('#activity_rid').val(data.new_rid[0]);

                        }else{

                            alert("There was an error." + data.message);

                        }

                    },

                    error: function(a,b,c){

                        //alert('Error');

                    }

                });



            }, "jsonp");



            var acceptedClasses = '.btn_interaction, .track_download';



            // Listen for mouse/change on certain elements, figure out what caused it, and report ID back

            //$( document.body ).on( 'click change', acceptedClasses, function(e) {

            $( document.body ).on( 'click', acceptedClasses, function(e) {

                var id = e.target.value; 

                var rfp_rid = this.getAttribute("id");

                var loc = $("#lat_lon").val();



                if ($(event.target).attr('class') == "track_download"){

                    id = this.getAttribute("id");

                    rfp_rid = $("#rfp_rid").val();

                }



                var data = {

                    type: e.type,

                    action: 'interaction',

                    file_url: window.location.href,

                    id: id,

                    rfp_rid: rfp_rid,

                    page: "Home",

                    loc: loc,

                    target_type: event.target.type

                };



                // Post the event, target, and time to QB

                $.ajax( 'res/actions.php', {

                    type: 'post',

                    data: data,

                    success: function() {

                    },

                    error: function() {}

                } );

            } );



            $(window).on("beforeunload", function() {

                var page_tracking_rid = $('#activity_rid').val();

                console.log(page_tracking_rid);



                $.ajax({

                    type: "POST",

                    dataType: "json",

                    url: "res/actions.php",

                    data: {action: "page_view_end", page_rid: page_tracking_rid},

                    success: function(data){

                        if(data.errorcode == 0){

                            //$('#activity_rid').val(data.new_rid[0]);

                        }else{

                            //alert("There was an error." + data.message);

                        }

                    },

                    error: function(a,b,c){

                        //alert('Error');

                    }

                });

            });



        });

    </script>

    </body>

</html>