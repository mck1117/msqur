<?php
/* msqur - MegaSquirt .msq file viewer web application
Copyright 2014-2019 Nicholas Earwood nearwood@gmail.com https://nearwood.dev

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>. */

require "msqur.php";

global $i, $id, $msqs;

if (isset($_GET['msq1']) && isset($_GET['msq2'])) {
	$ids = array(intval($_GET['msq1']), intval($_GET['msq2']));
	//$dialogId = parseQueryString('dialog');
	$settings = explode("|", parseQueryString('settings'));
	$tuneParams = array();
	$engine = array();
	$isOwner = array();
	$htmls = array();
	$msqs = array();
	foreach ($ids as $i=>$id) {
		// set globals for external view/*.php files
		$msq = new MSQ();
		$msqs[$i] = $msq;
		// parse the tune
		$htmls[$i] = $rusefi->getTs($msq, $id, $settings, "diff");
		$engine[$i] = $rusefi->getEngineFromTune($id);
		// get extra tune params
		$tuneParams[$i] = $msqur->browse(array("m.id"=>$id), 0, "msq");
		if (count($tuneParams[$i]) == 1)
			$tuneParams[$i] = $tuneParams[$i][0];
		$isOwner[$i] = $engine[$i]["user_id"] == $rusefi->userid;
	}

	// gather constants
	$msqConsts = [$msqs[0]->msqMap["Constants"], $msqs[1]->msqMap["Constants"]];
	$consts = array();
	foreach($msqConsts as $mc){
        foreach($mc as $cn=>$c){
            $consts[] = $cn;
        }
    }

    // gather panel info
    $dialogs = array_merge($msqs[0]->msqMap["dialog"], $msqs[1]->msqMap["dialog"]);
	$fieldPanels = array();
	foreach ($dialogs as $dlg) {
		if (!isset($dlg["field"]))
			continue;
		foreach ($dlg["field"] as $f) {
			if (is_array($f) && isset($f[1])) {
				$dlgName = is_array($dlg["dialog"]) ? $dlg["dialog"][0] : $dlg["dialog"];
				$fieldPanels[$f[1]][] = $dlgName;
			}
		}
	}

	// find the differences!
    $consts = array_unique($consts);
    $panels = array();
	foreach ($consts as $c) {
		$v0 = isset($msqs[0]->msqMap["Constants"][$c]) ? $rusefi->getMsqConstantFull($c, $msqs[0], $digits) : null;
		$v1 = isset($msqs[1]->msqMap["Constants"][$c]) ? $rusefi->getMsqConstantFull($c, $msqs[1], $digits) : null;
		// pack the values for comparison
		$sv0 = serialize($v0);
		$sv1 = serialize($v1);
		if ($sv0 != $sv1) {
			// add the panel to the display list
			if (isset($fieldPanels[$c])) {
				foreach ($fieldPanels[$c] as $fp) {
					$panels[$fp] = $fp;
					// highlight the field
					for ($i = 0; $i < 2; $i++) {
						if (isset($msqs[$i]->msqMap["dialog"][$fp])) {
							$dlg = &$msqs[$i]->msqMap["dialog"][$fp];
							if (isset($dlg["field"])) {
								foreach ($dlg["field"] as &$f) {
									if ($f[1] == $c) {
										$f["highlight"] = true;
									}
								}
							}
						}
					}
				}
			}
		}
	}

	$pageStart = 0;
	$pageNum = 10;
	$panels = array_values(array_slice($panels, $pageStart, $pageNum));
	/*print_r($panels);
	die;*/

	$html = array($htmls[0]);
	include_once "view/view_diff.php";
	
	include "view/header.php";
	//include "view/tune_note.php";
	//include "view/more_about_vehicle.php";
	
	echo $html["ts"];
	include "view/footer.php";
} else {
	include "index.php";
}

?>