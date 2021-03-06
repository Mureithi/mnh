<?php
//$this->load->view('segments/head');
$chartSize=0;
if($resultArraySize<=6){
	$chartSize='300';
}
if($resultArraySize>6){
	$chartSize='600';
}
if($resultArraySize>10){
	$chartSize='900';
}
if($resultArraySize>15){
	$chartSize='1200';
}
if($resultArraySize>20){
	$chartSize='1500';
}
if($resultArraySize>25){
	$chartSize='3000';
}
if($resultArraySize>30){
	$chartSize='6000';
}

if($resultArray!=null){
?>

<script>
		$(function () {
	$('<?php echo "#" . $container; ?>').highcharts({
		colors: [
		'#66aaf7',
		'#f66c6f',
		'#8bbc21',
		'#910000',
		'#1aadce',
		'#492970',
		'#f28f43',
		'#77a1e5',
		'#c42525',
		'#a6c96a'
		],
		chart: {            
            zoomType: 'x',            
			height:<?php echo $chartSize;?>,
			type: '<?php echo $chartType ?>',
			 marginBottom: <?php echo $chartMargin ?>
		},
		title: {
			text: '<?php echo $chartTitle; ?>'
		},
		xAxis:
		{
			categories:  <?php echo $categories; ?>,
			
	
	},
	
                
	yAxis: {
		min: 0,
		title: {
			text: '<?php echo $yAxis; ?>',
			align: 'high'
		},
		labels: {
			overflow: 'justify'
		}
		},
		tooltip: {
			valueSuffix: ''
		},
		plotOptions: {
			
			bar: {
				dataLabels: {
					enabled: true,
					formatter: function() {
                            if (this.y != 0) {
                              return this.y;
                            } else {
                              return '';
                            }
                        }
		}
		}
		},
		legend: {
			layout: 'horizontal',
			align: 'left',
			floating: true,
			borderWidth: 1,
			backgroundColor: '#FFFFFF',
			shadow: true
		},
		credits: {
			enabled: false
		},
		series:<?php echo$resultArray?>
		});
		});
</script>

<div class="graph">
	
	<div id="<?php echo $container;?>"  style="width:98%"  '>
</div>
</div>
<?php
}
else{?>
<div class="graph">	
	<h4>No records found for this statistic</h4>
</div>
</div>
<?php
}
?>
