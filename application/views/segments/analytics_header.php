<div class="header navbar navbar-inverse navbar-fixed-top">
		<!-- BEGIN TOP NAVIGATION BAR -->
		<div class="navbar-inner">
			<div class="container-fluid">
				<!-- BEGIN LOGO -->
				<a class="brand username" href="index.html">
				<img src="<?php echo base_url()?>images/moh_dash.png" alt="logo" />Ministry of Health, Data Management Portal
				</a>
				<!-- END LOGO -->
				<!-- BEGIN RESPONSIVE MENU TOGGLER -->
				<a href="javascript:;" class="btn-navbar collapsed" data-toggle="collapse" data-target=".nav-collapse">
				<img src="assets/img/menu-toggler.png" alt="" />
				</a>          
				<!-- END RESPONSIVE MENU TOGGLER -->				
				<!-- BEGIN TOP NAVIGATION MENU -->					
				<ul class="nav pull-right">
					
					<!-- BEGIN USER LOGIN DROPDOWN -->
					<li class="dropdown user">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown">
						Logged on as:<i class="icon-user"></i>
						<span class="username">Admin User</span>
						<i class="icon-angle-down"></i>
						</a>
						<ul class="dropdown-menu">
							
							<li class="divider"></li>
							<li><a href="<?php echo base_url();?>session/close"><i class="icon-key"></i> Log Out</a></li>
						</ul>
					</li>
					<!-- END USER LOGIN DROPDOWN -->
				</ul>
				<!-- END TOP NAVIGATION MENU -->	
			</div>
		</div>
		<!-- END TOP NAVIGATION BAR -->
	</div>