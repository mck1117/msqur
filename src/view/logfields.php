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
