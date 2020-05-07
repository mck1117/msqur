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

require_once "msqur.php";

$page = parseQueryString('p') || 0;
$bq = array();
$bq['user_id'] = parseQueryString('user_id');
$bq['make'] = parseQueryString('engineMake'); //TODO Define these API method/strings in one place
$bq['code'] = parseQueryString('engineCode');
$bq['firmware'] = parseQueryString('firmware');
$bq['signature'] = parseQueryString('fwVersion'); //TODO might make dependant on firmware
//TODO Move column magic strings to some define/static class somewhere

//TODO Use http_build_query and/or parse_url and/or parse_str

$msqur->header();

//require "view/browse.php";
?>
<div class="browse" id="categories">
	<?php //TODO Make a categories function to reduce these
	if ($bq['make'] === null) {
		echo '<div>Makes: <div class="category" id="makes">';
		foreach ($msqur->getEngineMakeList() as $m) { ?>
			<div>
				<?php echo "<a href=\"?engineMake=$m\">$m</a>"; ?>
			</div>
		<?php
		}
		echo '</div>';
	}
	
	if ($bq['code'] === null)
	{
		echo '<div>Engine Codes: <div class="category" id="codes">';
		foreach ($msqur->getEngineCodeList() as $m) { ?>
			<div>
				<?php echo "<a href=\"?engineCode=$m\">$m</a>"; ?>
			</div>
		<?php
		}
		echo '</div>';
	}
	
	if ($bq['firmware'] === null)
	{
		echo '<div>Firmware: <div class="category" id="firmware">';
		foreach ($msqur->getFirmwareList() as $m) { ?>
			<div>
				<?php echo "<a href=\"?firmware=$m\">$m</a>"; ?>
			</div>
		<?php
		}
		echo '</div>';
	}
	
	if ($bq['signature']=== null)
	{
		echo '<div>Versions: <div class="category" id="versions">';
		foreach ($msqur->getFirmwareVersionList() as $m) { ?>
			<div>
				<?php echo "<a href=\"?fwVersion=$m\">$m</a>"; ?>
			</div>
		<?php
		}
		echo '</div>';
	} ?>
</div>
<!-- script src="view/browse.js"></script -->
<?php

$results = $msqur->browse($bq, $page);
$numResults = count($results);

echo '<div id="content"><div class="info">' . $numResults . ' results.</div>';
echo '<table ng-controller="BrowseController">';
echo '<tr><th>Uploaded</th><th>Owner</th><th>Vehicle Name</th><th>Engine Make</th><th>Engine Code</th><th>Cylinders</th><th>Liters</th><th>Compression</th><th>Aspiration</th><th>Firmware/Version</th><th>Views</th><th>Options</th></tr>';
for ($c = 0; $c < $numResults; $c++)
{
    $engine = $results[$c];
	$aspiration = $engine['induction'] == 1 ? "Turbo" : "Atmo";
	echo '<tr><td><a href="view.php?msq=' . $engine['mid'] . '">' . $engine['uploadDate'] . '</a></td>';
	echo '<td><a href=/forum/memberlist.php?mode=viewprofile&u=' . $engine['user_id'] . '>' . $rusefi->getUserNameFromId($engine['user_id']) . '</a></td>';
	echo '<td>' . $engine['name'] . '</td>';
	echo '<td>' . $engine['make'] . '</td>';
	echo '<td>' . $engine['code'] . '</td>';
	echo '<td>' . $engine['numCylinders'] . '</td>';
	echo '<td>' . $engine['displacement'] . '</td>';
	echo '<td>' . $engine['compression'] . ':1</td>';
	echo '<td>' . $aspiration . '</td>';
	echo '<td>' . $engine['firmware'] . '/' . $engine['signature'] . '</td>';
	echo '<td>' . $engine['views'] . '</td>';
	echo '<td><a title="Download MSQ" download href="download.php?msq=' . $engine['mid'] . '">💾</a></td></tr>';
}
echo '</table></div>';

$msqur->footer();
?>
