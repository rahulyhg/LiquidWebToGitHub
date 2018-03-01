<?php 
require_once('res/func.php');
if (isset($_SESSION['channel_partner'])){$_SESSION['channel_partner'] = $_SESSION['channel_partner'];} else {redirect('login.php');}
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
    <link href="plugins/bower_components/bootstrap-select/bootstrap-select.min.css" rel="stylesheet" />
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
    //if (isset($_SESSION['uid'])){$_SESSION['uid'] = $_SESSION['uid'];} else {$_SESSION['uid'] = '23';}
    
?>
        <!-- Page Content -->
    <div id="page-wrapper">
        <div class="container-fluid">
            <div class="row bg-title">
                <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12">
                    <h4 class="page-title"><?php echo $_SESSION['customer_name']; ?></h4> </div>
                <div class="col-lg-9 col-sm-8 col-md-8 col-xs-12">
                    <?php echo breadcrumbs(); ?>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="white-box"> 
                        <?php 
                                echo showMasterSiteList($_SESSION['related_customer_rid']); 
                            
                        ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="white-box">
                        <h3>Note for Client</h3>
                        <h4>Please include the folowing columns in your upload:</h4>
                        <ol>
                            <li><b>Site Name</b></li>
                            <li><b>Street</b></li>
                            <li><b>City</b></li>
                            <li><b>State</b></li>
                            <li><b>Zipcode</b></li>
                            <li>Square Feet</li>
                            <li>Roof Installation Year</li>
                            <li>Anticipated Reroof Year</li>
                        </ol>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="white-box">
                        <h3>Upload New Master Site List</h3>
                        <form enctype='multipart/form-data' action='res/actions.php?action=upload_master_site_list&related_customer_rid=<?php echo $_SESSION['related_customer_rid']; ?>' method='post'>
                            <input type='file' name='master_site_list_file' class='pull-left'>
                            <input type='submit' class='btn btn-primary pull-right' value='Upload File'>
                            <div style='clear:both;'></div>
                        </form>
                        <?php echo showUploadedMasterSiteList($_SESSION['related_customer_rid']); ?>
                    </div>
                </div>
            </div>
            <?php include 'php/right-sidebar.php';?>
        </div>
        <!-- /.container-fluid -->
        <?php //include 'php/footer.php';?>
    </div>
    <!-- /#page-wrapper -->
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
    });
    </script>
    </body>
</html>