<?php

global $rusefi;
global $dialogId;
global $msqs;

include_once("view/view_ts_dialog.php");

ob_start();
?>
<div class="ts-dialogs ts-diff" isAutoOpen="true">
<div class="tsDialog" id="dlgDiff" title="Difference Report">
<table class='ts-field-table' border=0>
<?php

foreach ($panels as $mi) {

		//$dlg = $msqMap["dialog"][$mi];
		//$dlgName = $dlg["dialog"][0][0];
		//$dlgTitle = getDialogTitle($msqMap, $dlg);
?>
<tr><td class='ts-label'>
<?php
	$msqMap = $msqs[0]->msqMap;
	if (isset($msqMap["dialog"][$mi])) {
		printDialog(0, $msqMap, $msqs[0], $mi, FALSE);
	}
?>
</td><td class='ts-diff-separator'></td>
<td class='ts-label'>
<?php
	$msqMap = $msqs[1]->msqMap;
	if (isset($msqMap["dialog"][$mi])) {
		printDialog(1, $msqMap, $msqs[1], $mi, FALSE);
	}
?>
</td></tr>
<?php
}
?>
</table>
</div>
</div>

<script src="view/ts.js"></script>

<?php

$html["ts"] = ob_get_contents();
ob_end_clean();

?>