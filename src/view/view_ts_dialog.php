<?php

//$html["debug"] = print_r($msqMap["dialog"]["injectionSettings"], TRUE);
//$html["debug"] = print_r($msqMap["dialog"]["injectionBasic"], TRUE);
//$html["debug"] = print_r($msqMap["menu"]["&Base &Engine"], TRUE);

function printTsItem($mn) {
	return preg_replace(array("/&([A-Za-z])/", "/\"/"), array("<u>$1</u>", ""), $mn);
}

function getDialogTitle($msqMap, $dlg) {
	if (is_array($dlg["dialog"][0])) {
		$dlgName = $dlg["dialog"][0][0];
		$dlgTitle = printTsItem($dlg["dialog"][0][1]);
	} else {
		$dlgName = $dlg["dialog"][0];
		$dlgTitle = "";
	}
	if (empty($dlgTitle)) {
		// take the title from menu
		foreach ($msqMap["menu"] as $menu) {
			foreach ($menu["subMenu"] as $sub) {
				if ($sub[0] == $dlgName) {
					$dlgTitle = printTsItem($sub[1]);
				}
			}
		}
	}
	return $dlgTitle;
}

function printField($msqMap, $field) {
	global $rusefi;
	
	if (isset($msqMap["Constants"][$field[1]])) {
		$disabled = FALSE;
		// disable the field if required
		if (isset($field[2])) {
			try
			{
				// see INI::parseExpression()
				$disabled = !eval($field[2]);
			} catch (Throwable $t) {
				// todo: should we react somehow?
			}
		}
		if ($disabled)
			$disabled = " disabled='disabled' ";

		$curValue = $rusefi->getMsqConstant($field[1]);

		// print text label
		echo "<td class='ts-field-td-label'><label for=".$field[1]." class='ts-field-label' $disabled>".printTsItem($field[0])."</label></td>\r\n";
		echo "<td class='ts-field-td-item'>\r\n";

		$cons = $msqMap["Constants"][$field[1]];
		if ($cons[0] == "bits") {
			echo "<select class='ts-field-item' id='".$field[1]."' $disabled>\r\n";
        	for ($i = 4; $i < count($cons); $i++) {
        		$selected = ($curValue == ($i - 4)) ? " selected='selected' " : "";
        		echo "<option class='ts-field-item-option' $selected>".printTsItem($cons[$i])."</option>\r\n";
        	}
      		echo "</select>\r\n";
		}
		else if ($cons[0] == "scalar") {
			echo "<input id='".$field[1]."' class='ui-spinner-input ts-field-item' value='".$curValue."' $disabled>";
		}
		else if ($cons[0] == "string") {
			echo "<input id='".$field[1]."' class='ts-field-item' value='".$curValue."' $disabled>";
		}
		echo "</td>";
		//print_r($cons);
		return;
	}

	// text field?
	if (is_string($field)) {
		$field = printTsItem($field);
		// colorize some labels
		if (!empty($field) && ($field[0] == '#' || $field[0] == '!')) {
			$clr = ($field[0] == '#') ? "ts-label-blue" : "ts-label-red";
			$field = substr($field, 1);
		}
		else {
			$clr = "";
		}
		echo "<td class='ts-label $clr' colspan='2'>$field</td>\r\n";
	}
}

function printDialog($msqMap, $dialogId, $isPanel) {
	if (isset($msqMap["dialog"][$dialogId])) {
		$dlg = $msqMap["dialog"][$dialogId];
		$dlgTitle = getDialogTitle($msqMap, $dlg);
	} else if (isset($msqMap["CurveEditor"][$dialogId])) {
		$curve = $msqMap["CurveEditor"][$dialogId];
		$dlgTitle = $curve['desc'];
		printCurve($msqMap, $dialogId, $curve);
		return $dlgTitle;
	} else if (isset($msqMap["TableEditor"][$dialogId])) {
		$curve = $msqMap["TableEditor"][$dialogId];
		$dlgTitle = $curve['desc'];
		printCurve($msqMap, $dialogId, $curve);
		return $dlgTitle;
	}

	$isHorizontal = (isset($dlg["dialog"][0][2]) && $dlg["dialog"][0][2] == "xAxis");
	// draw panels (recursive)
	if (isset($dlg["panel"])) {
		foreach ($dlg["panel"] as $panel) {
			if (is_array($panel))
				$panel = $panel[0];
			if (isset($msqMap["dialog"][$panel])) {
				$p = $msqMap["dialog"][$panel];
				$pt = getDialogTitle($msqMap, $p);
			} else {
				$pt = "";
			}
				//print_r($p);
				$pClass = $isHorizontal ? "class='ts-controlgroup ts-controlgroup-horizontal'" : "class='ts-controlgroup ts-controlgroup-vertical'";
				$fClass = $isHorizontal ? "class='ts-panel ts-panel-horizontal'" : "class='ts-panel ts-panel-vertical'";
				if (!empty($pt)) {
?>
	<fieldset <?=$fClass;?>><legend><?=$pt;?></legend>
<?php
				} else {
			$fClass = $isHorizontal ? "class='ts-panel-notitle ts-panel-horizontal'" : "class='ts-panel-notitle'";
?>
	<fieldset  <?=$fClass;?>>
<?php
				}
?>
    <div <?=$pClass;?>>
<?php
				printDialog($msqMap, $panel, TRUE);
?>
    </div>
  </fieldset>
<?php
		}
	}

	// draw fields
	if (isset($dlg["field"])) {
		// create a fake panel for dialogs without panels
		if (!$isPanel) {
?>
<fieldset class='ts-panel-notitle'><div class='ts-controlgroup ts-controlgroup-vertical'>
<?php
		}
?>
<table class='ts-field-table' cellspacing="2" cellpadding="2"><tbody>
<?php
		foreach ($dlg["field"] as $field) {
			//$f = getDialogTitle($msqMap, $field);
			//print_r($field);
?>
<tr>
<?php
			printField($msqMap, $field);
?>
</tr>
<?php
		}
?>
</tbody></table>
<?php
		if (!$isPanel) {
?>
</div></fieldset>
<?php
		}
	}

	return $dlgTitle;
}

function printCurve($msqMap, $id, $curve) {
	global $rusefi;

	$help = array_key_exists('topicHelp', $curve) ? $curve['topicHelp'] : NULL;

	echo "<script>	var accordionOptions = {
		animate: false,
		active: true, collapsible: true, //to avoid hooking 'create' to make the first graph render
		heightStyle: 'content',
	};</script>";

	$tabId = "tab_" . $id;
	echo "<div id='".$tabId."'>";

	if (array_keys_exist($curve, 'desc', 'xBinConstant', 'yBinConstant', 'zBinConstant'))
	{
		$xBins = $rusefi->msq->findConstant($rusefi->msq->msq, $curve['xBinConstant']);
		$yBins = $rusefi->msq->findConstant($rusefi->msq->msq, $curve['yBinConstant']);
		$zBins = $rusefi->msq->findConstant($rusefi->msq->msq, $curve['zBinConstant']);
		$xAxis = preg_split("/\s+/", trim($xBins));
		$yAxis = preg_split("/\s+/", trim($yBins));
		$zData = preg_split("/\s+/", trim($zBins));//, PREG_SPLIT_NO_EMPTY); //, $limit);
		echo $rusefi->msq->msqTable3D($curve, $xAxis, $yAxis, $zData, $help, true);

	}	
	else if (array_keys_exist($curve, 'desc', 'xBinConstant', 'yBinConstant', 'xMin', 'xMax', 'yMin', 'yMax'))
	{
		$xBins = $rusefi->msq->findConstant($rusefi->msq->msq, $curve['xBinConstant']);
		$yBins = $rusefi->msq->findConstant($rusefi->msq->msq, $curve['yBinConstant']);
		$xAxis = preg_split("/\s+/", trim($xBins));
		$yAxis = preg_split("/\s+/", trim($yBins));
		echo $rusefi->msq->msqTable2D($curve, $curve['xMin'], $curve['xMax'], $xAxis, $curve['yMin'], $curve['yMax'], $yAxis, $help, true);
	}

	echo "</div><script>$('div#".$tabId."').accordion(accordionOptions);
		processChart($('div#".$tabId."'));
		colorizeTables();
		</script>";
	
}

?>