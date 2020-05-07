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

$msqur->header();

/**
 * @brief Restructure file upload array
 *
 * Extended description goes here.
 * @param $file_post array
 */ 
function fixFileArray(&$file_post)
{//From php.net anonymous comment
	$file_ary = array();
	$file_count = count($file_post['name']);
	$file_keys = array_keys($file_post);
	
	for ($i = 0; $i < $file_count; $i++)
	{
		foreach ($file_keys as $key)
		{
			$file_ary[$i][$key] = $file_post[$key][$i];
		}
	}
	
	return $file_ary;
}

/**
 * @brief Sanity check for uploaded files.
 * @param $files array
 * @returns $files array with bad apples removed.
 */
function checkUploads($files)
{//Expects fixed array instead of $_FILES array
	foreach ($files as $index => $file)
	{
		//Discard any with errors
		if ($file['error'] != UPLOAD_ERR_OK)
		{
			unset($files[$index]);
			continue;
		}
		
		//Check sizes against 5MiB
		if ($file['size'] > 5*1048576)
		{
			unset($files[$index]);
			continue;
		}
		
		//Get and check mime types (ignoring provided ones)
		$finfo = new finfo(FILEINFO_MIME_TYPE);
		$mimeType = $finfo->file($file['tmp_name']);
		if (!acceptableMimeType($mimeType))
		{
			if (DEBUG) warn('File: ' . $file['tmp_name'] . ': Invalid MIME type ' . $mimeType);
			unset($files[$index]);
			continue;
		}
	}
	
	return $files;
}

/**
 * @brief Check that a mime type matches ones we think are OK.
 * @param $mimeType {string} MIME type
 * @returns true or false
 */
function acceptableMimeType($mimeType) {
	switch ($mimeType) {
		case "application/xml":
		case "text/xml":
		case "text/plain":
			return true;
		default:
			return FALSE;
	}
}

//var_export($_POST);
//var_export($_FILES);
echo '<div class="uploadOutput">';

if (isset($_POST['upload']) && isset($_FILES))
{
	$files = checkUploads(fixFileArray($_FILES['files']));
	if ($rusefi->userid < 1)
	{
		echo '<div class="error">You are not logged into rusEFI forum! Please login <a href="'.$rusefi->forum_login_url.'">here</a>!</div>';
	}
	else if (count($files) != 1)
	{
		//No files made it past the check
		echo '<div class="error">Cannot upload!</div>';
	}
	else if ($rusefi->isTuneAlreadyExists($files, -1))
	{
		echo '<div class="error">This tune file already exists in our Database!</div>';
	}
	else
	{
		echo '<div class="info">The file has been uploaded.</div>';
		
		if (DEBUG) debug('Adding engine: ' . $_POST['name'] . ', ' . $_POST['make'] . ', ' . $_POST['code'] . ', ' . $_POST['displacement'] . ', ' . $_POST['compression'] . ', ' . $_POST['induction']);
		
		$engineid = $msqur->addOrUpdateVehicle($rusefi->userid, 
			$rusefi->processValue($_POST['name']), 
			$rusefi->processValue($_POST['make']), 
			$rusefi->processValue($_POST['code']), 
			$rusefi->processValue($_POST['displacement']),
			$rusefi->processValue($_POST['compression']), 
			$rusefi->processValue($_POST['induction'])
		);
		$fileList = $msqur->addMSQs($files, $engineid);
		
		$safeName = htmlspecialchars($_POST['name']);
		$safeMake = htmlspecialchars($_POST['make']);
		$safeCode = htmlspecialchars($_POST['code']);
		
		if ($fileList != null)
		{
			//echo '<div class="info">Successful saved MSQ to database.</div>';
			echo '<div class="info"><ul id="fileList">';
			foreach ($fileList as $id => $name)
			{
				echo '<li><a href="view.php?msq=' . $id . '">' . "$safeName ($safeMake $safeCode) - $name" . '</a></li>';

				// parse and update DB
				$msqur->view($id);
			}
			
			echo '</div></ul>';
			echo '<div class="info">Thank you!</div>';
		}
		else
		{
			echo '<div class="error">Unable to store uploaded file.</div>';
		}
	}
} else
{
	echo '<div class="error">Nothing to upload!</div>';
}

echo '</div>';

require "browse.php";

//$msqur->footer();
?>