<?php
	$logGeneral = array();
	if (isset($logValues["estimatedDuration"])) {
		$logGeneral["estimatedDuration"] = "Estimated duration: ~";
		$logGeneral["duration"] = "Partial duration:";
	}
	else {
		$logGeneral["duration"] = "Total duration:";
	}
	$logGeneral["dataRate"] = "Data Rate: ~";

	$logTable = array(
		"logStart" => array("Starting", array(
			"startingNum" => "Number of starts:",
			"startingNumCold" => "Cold starts:",
			"startingSuccessRate" => "Success rate:",
			"startingFastestTime" => "Fastest time:",
			"startingNumTriggerErrors" => "Trigger errors:",
		)),
		"logIdling" => array("Idling", array(
			"idlingDuration" => "Duration:",
			"idlingAverageRpm" => "Average RPM:",
			"idlingAfrAccuracy" => "AFR accuracy:",
			"idlingNumTriggerErrors" => "Trigger errors:",
		)),
		"logRunning" => array("Running", array(
			"runningDuration" => "Duration:",
			"runningMaxRpm" => "Max RPM:",
			"runningMaxThrottle" => "Max Throttle:",
			"runningMaxVe" => "Max VE:",
			"runningMaxSpeed" => "Max Speed:",
			"runningAfrAccuracy" => "AFR accuracy:",
		)),
	);			

	function putField($lfn, $lf, $val, $oneLine = false) { ?>
	<div>
	<label for="<?=$lfn;?>" class=logLabel><?=$lf;?>
<?php 
	if ($oneLine) { 
		echo " " . $val . "</label>";
	} else { 
?>
	</label><input id="<?=$lfn;?>" name="<?=$lfn;?>" value="<?=$val;?>" type="text" maxlength="5" class=logInput readonly/>
<?php
	}
?>
	</div>
<?php }
	
	foreach ($logGeneral as $lfn=>$lf) {
		putField($lfn, $lf, $logValues[$lfn], true);
	}

if (isset($logValues["data"])) {
?>
<table class=logContainer cellspacing="0" cellpadding="0"><tr>
<td class=logTd width="50%"><div>
	<canvas id="logCanvas" height="300"></canvas>
</div></td>

<script>
	$(document).ready(function() {
		var ctx = $('#logCanvas');
		var config = {
			type: 'LineWithLine',
			data: {
<?php
	// convert duration into seconds
	$numSeconds = $rusefi->getDurationInSeconds($logValues["duration"]);

	$labels = array();
	$numDataPoints = count(reset($logValues["data"]));
	for ($i = 0; $i < $numDataPoints; $i++) {
		$labels[$i] = "'".round($i * $numSeconds / $numDataPoints, 2) . "s'";
	}
?>
				labels: [<?=implode(",", $labels);?>],
				datasets: [
<?php
	$ldColors = array("white", "red", "green", "yellow");
	$ldYAxisDisplay = array("true", "false", "false", "false");
	$j = 0;
	foreach ($logValues["data"] as $ldName=>$ld) {
?>
				{ 
					data: [<?=implode(",", $ld);?>],
					label: "<?=$ldName;?>",
					yAxisID: "<?=$j;?>",
					borderColor: "<?=$ldColors[$j++];?>",
					fill: false
				},
<?php
	}
?>
				]
			},
			options: {
				legend: { display: true },
				responsive: true,
				maintainAspectRatio: false,
				elements: {
					point:{
						radius: 0
					}
				},
				tooltips: {
					intersect: false,
					axis: 'x'
				},
				scales: {
					xAxes: [{
						display: true,
						gridLines: {
							display: true,
							color: "#3F3F3F"
						},
						scaleLabel: {
							display: true,
							labelString: 'Time'
						},
					}],
					yAxes: [
<?php
	$j = 0;
	foreach ($logValues["data"] as $ldName=>$ld) {
?>
					{
						id: "<?=$j;?>",
						display: <?=$ldYAxisDisplay[$j++];?>,
						ticks: {
							beginAtZero: true
						},
						gridLines: {
							display: true,
							color: "#3F3F3F"
						},
						scaleLabel: {
							display: true,
							labelString: 'Value'
						}
					},
<?php
	}
?>
					]
				}
			}
		};

		// draw a vertical line on the chart under the cursor
		Chart.defaults.LineWithLine = Chart.defaults.line;
		Chart.controllers.LineWithLine = Chart.controllers.line.extend({
		   draw: function(ease) {
			  Chart.controllers.line.prototype.draw.call(this, ease);

			  if (this.chart.tooltip._active && this.chart.tooltip._active.length) {
				 var activePoint = this.chart.tooltip._active[0],
					 ctx = this.chart.ctx,
					 x = activePoint.tooltipPosition().x,
					 topY = this.chart.scales['0'].top,
					 bottomY = this.chart.scales['0'].bottom;

				 // draw line
				 ctx.save();
				 ctx.beginPath();
				 ctx.moveTo(x, topY);
				 ctx.lineTo(x, bottomY);
				 ctx.lineWidth = 2;
				 ctx.strokeStyle = '#07C';
				 ctx.stroke();
				 ctx.restore();
			  }
		   }
		});

		var chart = new Chart(ctx, config);

	});

</script>

<td class=logTd>
<?php
}
?>
<table class=logTable border=0><tr>
<?php

	foreach ($logTable as $ln=>$lt) { ?>
		<th class=<?=$ln;?>><?=$lt[0];?></th>
<?php
	}
?>
<tr>
<?php
	foreach ($logTable as $lt) { ?>
<td class=logStart><?php
		foreach ($lt[1] as $lfn=>$lf) {
			putField($lfn, $lf, $logValues[$lfn]);
	} ?>
</td>
<?php
} 
?>
</tr></table>
<?php
if (isset($logValues["data"])) {
?>
</td></tr></table>
<?php
}
?>