<?php
	$maxNumLogs = 10;
?>
<div class=tune-note-div>Logs using this tune: 
<?php
if (count($logs) < 1) {
?>
<span class=tune-no-logs>No logs</span>
<?php
}
$i = 0;
foreach ($logs as $l) {
	if ($i++ > $maxNumLogs)
		break;
?>
<a href="view.php?log=<?=$l["id"];?>"><?=$l["uploadDate"];?></a>
<?php
	if (count($logs) > 1) echo ",";
}
?>
</div>