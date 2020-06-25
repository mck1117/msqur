<?php

//$html["debug"] = print_r($msqMap["dialog"]["injectionSettings"], TRUE);
//$html["debug"] = print_r($msqMap["dialog"]["injectionBasic"], TRUE);
//$html["debug"] = print_r($msqMap["menu"]["&Base &Engine"], TRUE);

function printTsItem($mn) {
	return preg_replace(array("/&([A-Za-z])/", "/\"/"), array("<u>$1</u>", ""), $mn);
}

function getDialogTitle($msqMap, $dlg) {
	$dlgName = $dlg["dialog"][0][0];
	$dlgTitle = printTsItem($dlg["dialog"][0][1]);
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

function printDialog($msqMap, $dlg, $isPanel) {
	$isHorizontal = (isset($dlg["dialog"][0][2]) && $dlg["dialog"][0][2] == "xAxis");
	// draw panels (recursive)
	if (isset($dlg["panel"])) {
		foreach ($dlg["panel"] as $panel) {
			if (is_array($panel))
				$panel = $panel[0];
			if (isset($msqMap["dialog"][$panel])) {
				$p = $msqMap["dialog"][$panel];
				$pt = getDialogTitle($msqMap, $p);
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
				printDialog($msqMap, $p, TRUE);
?>
    </div>
  </fieldset>
<?php
			}
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
}

ob_start();
?>

<div id="ts-menu">
<ul id="menu">
<?php 
	$mi = 1;
	$menuItems = array();
	if (isset($msqMap["menu"]))
	foreach ($msqMap["menu"] as $mn=>$menu) {
		$mn = printTsItem($mn);
		if ($mn == "Help") continue;
?>
<li class="tsMenuItem"><img class="tsMenuItemImg" src="view/img/ts-icons/menu<?=$mi;?>.png"><span class="tsMenuItemText"><?=$mn;?></span>
<ul>
<?php
		foreach ($menu["subMenu"] as $sm=>$sub) {
			if ($sub == "std_separator") {
				echo "<li class='tsMenuSeparator' type='separator'></li>\r\n";
			} else {
				$menuItems[] = $sub[0];
				$sm = printTsItem($sub[1]);
?>
	<li><a class="tsMenuItemText" href="#<?=$sub[0];?>" id="<?=$sub[0];?>"><?=$sm;?></a></li>
<?php
			}
		}
?>
</ul>
</li>
<?php
	$mi++;
	}
?>
</ul>
</div>

<div id="ts-dialogs">

<?php
//!!!!!!!!
$menuItems = array("engineChars", "injectionSettings");

foreach ($menuItems as $mi) {
	if (isset($msqMap["dialog"][$mi])) {
		$dlg = $msqMap["dialog"][$mi];
		$dlgName = $dlg["dialog"][0][0];
		$dlgTitle = getDialogTitle($msqMap, $dlg);
?>
<div class="tsDialog" id="dlg<?=$dlgName;?>" title="<?=$dlgTitle;?>">
<?php
		printDialog($msqMap, $dlg, FALSE);
?>
</div>
<?php
	}
}
?>

</div>

<script>
	////////////////////////////////////////////////////////////////////////////
	// menu
	$("#menu").menu({
		position: {at: "left bottom"},
		icons: { submenu: 'ui-icon-blank' }
	});
	$("#ts-menu").show();
	$(window).resize(function() {
		var avgWidth = (($(document).width() - 40) / $(".tsMenuItem").length) - 10;
		$(".tsMenuItem").each(function () {
			$(this).css("min-width", avgWidth + 'px');
		});
	});

	$('#ts-menu li a').click(function () {
		var dlg = $("#dlg" + $(this).attr("id"));
		if (!dlg)
			return;
		if (!dlg.dialog('isOpen')) {
    		dlg.dialog("open");
		}
		// the dialog is already opened somewhere, let's find it
		$([document.documentElement, document.body]).animate({
        	scrollTop: dlg.offset().top
    	}, 2000);
	});

	var tsMenuTop = $("#ts-menu").offset().top;
	// float the menu when scrolling
	$(window).scroll(function fix_element() {
    	$('#ts-menu').css(
			$(window).scrollTop() > tsMenuTop
	        	? { 'position': 'fixed', 'top': '2px' }
				: { 'position': 'relative', 'top': 'auto' }
	    );
	    return fix_element;
	}());

	////////////////////////////////////////////////////////////////////////////
	// dialogs

	function fixDialogPositions() {
		var maxWidth = 0;
		var totalHeight = 0;
		var prevDlg = $("#ts-dialogs");
		var at = "left top";
		$("#ts-dialogs>div").each(function () {
			//.top(totalHeight);
			var dlgContent = $(this).find('.ui-dialog-content');
			var dlg = dlgContent.data('ui-dialog');
			/*$(this).resizable({
				handles: "e, s, se",
				resize: function(event, ui) {
					fixDialogPositions();
				},
				alsoResize: dlgContent
			});*/
			dlg.option("resizable", false);
			dlg.option("position", { my: "left top", at: at, of: prevDlg, collision: "none" });
			totalHeight += $(this).height() + 10;
			maxWidth = Math.max(maxWidth, $(this).width());
			prevDlg = $(this);
			at = "left bottom+10";
		});

		$("#ts-dialogs>div").each(function () {
			//$(this).show();
		});

		$("#ts-dialogs").width(maxWidth);
		$("#ts-dialogs").height(totalHeight + 50);
	}

	$("#ts-dialogs").show();
	var prevDlg = $("#ts-dialogs");
	var at = "left top";
	$("#ts-dialogs>div").each(function () {
    	var dialog = $(this).dialog({
			modal: false,
			draggable: false,
			resizable: true,//false,
			autoOpen: /*false*/true,
			dialogClass: 'tsDialogClass',
			appendTo: $("#ts-dialogs"),
			position: { my: "left top", at: at, of: prevDlg, collision: "none" },
			width: 'auto',
			resize: function(event, ui) {
				fixDialogPositions();
			},
			open: function( event, ui ) {
			},
			close: function(event, ui) {
				fixDialogPositions();
			},
		});
		prevDlg = dialog.parent();
		at = "left bottom+10";
	});

	//alert(totalHeight);

	$(document).ready(function() {

		$(".ts-controlgroup-vertical").each(function () {
			$(this).controlgroup({
				"direction": "vertical"
			});
		});
		$(".ts-controlgroup-horizontal").each(function () {
			$(this).controlgroup({
				"direction": "horizontal"
			});
		});

		fixDialogPositions();

		//alert($('#ts-dialogs').height());
		//$('#footer').css('top', $('html').height() +'px');
	});
	
	

	$(window).trigger('resize');


</script>



<?php

$html["ts"] = ob_get_contents();
ob_end_clean();

?>