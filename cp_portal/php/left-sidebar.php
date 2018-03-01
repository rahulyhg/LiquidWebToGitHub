<!-- Left navbar-header --> 
<div class="navbar-default sidebar" role="navigation" style='background:#464A63'>
    <div class="sidebar-nav navbar-collapse slimscrollsidebar">
        <!-- <div class="user-profile">
            <div class="dropdown user-pro-body">
                <div><!- <img src="../plugins/images/users/varun.jpg" alt="user-img" class="img-circle"> --><!-- </div>  -->
                <!-- <a href="#" class="dropdown-toggle u-dropdown" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Steave Gection <span class="caret"></span></a>
                <ul class="dropdown-menu animated flipInY">
                    <li><a href="#"><i class="ti-user"></i> My Profile</a></li>
                    <li><a href="#"><i class="ti-wallet"></i> My Balance</a></li>
                    <li><a href="#"><i class="ti-email"></i> Inbox</a></li>
                    <li role="separator" class="divider"></li>
                    <li><a href="#"><i class="ti-settings"></i> Account Setting</a></li>
                    <li role="separator" class="divider"></li>
                    <li><a href="login2.php"><i class="fa fa-power-off"></i> Logout</a></li>
                </ul>
            </div>
        </div>  -->
        <ul class="nav" id="side-menu">
            <!-- <li class="sidebar-search hidden-sm hidden-md hidden-lg">
                <!-input-group -->
                <!-- <div class="input-group custom-search-form">
                    <input type="text" class="form-control" placeholder="Search..."> <span class="input-group-btn">
            <button class="btn btn-default" type="button"> <i class="fa fa-search"></i> </button>
            </span> </div> -->
                <!-- /input-group -->
            <!-- </li>  -->
           <!--  <li class="nav-small-cap m-t-10">- Main Menu</li> -->
            <br><br><br>
            <li><!--  <a href="javascript:void(0);" class="waves-effect"><i class="linea-icon linea-basic fa-fw" data-icon="v"></i> <span class="hide-menu"> Dashboard <span class="fa arrow"></span> <span class="label label-rouded label-custom pull-right">2</span></span></a> -->
                <!-- <ul class="nav nav-second-level"> -->
                <?php if ($_SESSION['contact_type'] == "Channel Partner") { ?>
                                
                    <li> <a href="index.php"  class="waves-effect">All RFPs / Q&A</a> </li>
                    <li> <a href="awarded_rfps.php"  class="waves-effect">Awarded RFPs</a> </li>
                    <li> <a href="metrics.php"  class="waves-effect">Metrics</a> </li>

                <?php } else { ?>
                                
                    <li> <a href="index.php"  class="waves-effect">Awarded Sites</a> </li>
                    <li> <a href="master_sites.php"  class="waves-effect">Master Site List</a> </li>

                <?php    } ?>

                    <!-- <li><a href="login.php" class="waves-effect"><i class="icon-logout fa-fw"></i> <span class="hide-menu">Log out</span></a></li> -->
                <!-- </ul> -->
            </li>
           <!--  <li><a href="login2.php" class="waves-effect"><i class="icon-logout fa-fw"></i> <span class="hide-menu">Log out</span></a></li> -->
            <!-- <li class="nav-small-cap">- Support</li> -->
            <!-- <li><a href="documentation.php" class="waves-effect"><i class="fa fa-circle-o text-danger"></i> <span class="hide-menu">Documentation</span></a></li> -->
            <!-- <li><a href="gallery.php" class="waves-effect"><i class="fa fa-circle-o text-info"></i> <span class="hide-menu">Gallery</span></a></li> -->
            <!-- <li><a href="faq.php" class="waves-effect"><i class="fa fa-circle-o text-success"></i> <span class="hide-menu">Faqs</span></a></li> -->
        </ul>
    </div>
</div>
<!-- Left navbar-header end