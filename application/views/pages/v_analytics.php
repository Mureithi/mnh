<!DOCTYPE html>
<!--[if IE 8]> <html lang="en" class="ie8"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9"> <![endif]-->
<!--[if !IE]><!--> <html lang="en"> <!--<![endif]-->
<!-- BEGIN HEAD -->
<head>
	<meta charset="utf-8" />
	<?php $this->load->view('segments/meta');?>
	<?php $this->load->view('segments/analytics_css');?>
	<script src="<?php echo base_url()?>/js/jquery-1.8.2.min.js"></script>	
</head>
<!-- END HEAD -->
<!-- BEGIN BODY -->
<body class="fixed-top">
	<!-- BEGIN HEADER -->
	
		<?php $this->load->view('segments/header');?>
	<div style="width:90%;margin:auto">
	<?php $this -> load -> view('segments/nav-public'); ?>
	</div>
	
	<!-- END HEADER -->
	<!-- BEGIN CONTAINER -->	
	<div class="page-container row-fluid">
		<!-- BEGIN SIDEBAR -->
		<?php $this->load->view('segments/analytics_sidebar_menu');?>
		<!-- END SIDEBAR -->
		<!-- BEGIN PAGE -->
		<div class="page-content">
			<!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
			<div id="portlet-config" class="modal hide">
				<div class="modal-header">
					<button data-dismiss="modal" class="close" type="button"></button>
					<h3>portlet Settings</h3>
				</div>
				<div class="modal-body">
					<p>Here will be a configuration form</p>
				</div>
			</div>
			<!-- END SAMPLE PORTLET CONFIGURATION MODAL FORM-->
			<!-- BEGIN PAGE CONTAINER-->
			<div class="container-fluid">
				<!-- BEGIN PAGE HEADER-->
				<div class="row-fluid">
					<div class="span12">	
								
						<!-- BEGIN PAGE TITLE & BREADCRUMB-->			
						<h3 class="page-title">
							<?php echo $analytics_main_title; ?> for <?php echo $this->session->userdata('county_analytics')." County" ?><small><?php echo $analytics_mini_title; ?></small>
							<h4 style="float:right;padding-right:3%;"><?php //echo $this->session->userdata('county_analytics') ?>
								
								
							</h4>
						</h3>
						
						<ul class="breadcrumb">
							<li><a id="breadcrumb-title"></a></li>
							<i class="icon-angle-right"></i>
							<li><a id="breadcrumb-sub-title"></a></li>
							<label href="#reportingCountiesModal" data-toggle="modal" id="reportingLabel"><h6>Reporting Statistics</h6>
									<div id="reportingBar">
										
									</div>
									</label>
							<label style="margin:0 5% 0 0;display:inline-block;float:right;" for="county_select">Select a County
							<select name="county_select" id="county_select"style="margin-bottom:0" class="input">
								<option selected=selected>No County Selected</option>
									<?php echo $this->selectReportingCounties;?>
								</select></label>
								
						</ul>
						<!-- END PAGE TITLE & BREADCRUMB-->
					</div>
				</div>
				<?php $this->load->view($analytics_content_to_load);?>
				<!-- END PAGE CONTENT-->
			</div>
			<!-- BEGIN PAGE CONTAINER-->		
		</div>
		<!-- END PAGE -->	
	</div>
	<!-- END CONTAINER -->
	<!-- BEGIN FOOTER -->
	<div class="footer">
	      &copy; <?php echo date('Y');?> Ministry of Health, Government of Kenya
		<div class="span pull-right">
			<span class="go-top"><i class="icon-angle-up"></i></span>
		</div>
	</div>
	<!-- END FOOTER -->
	<!-- BEGIN JAVASCRIPTS -->
	<?php $this->load->view('segments/analytics_js'); ?>
	
	<script src="<?php echo base_url();?>js/analytics.js"></script>
	<script>
	var base_url = "<?php echo base_url();?>";
	var county   = "<?php echo $this->session->userdata('county_analytics');?>";
	var survey   = "<?php echo $this->session->userdata('survey')?>";
	$(document).ready(startAnalytics(base_url,county,survey));
	</script>	
	<!-- END JAVASCRIPTS -->
	<?php $this->load->view('segments/modals')?>
</body>
<!-- END BODY -->
</html>