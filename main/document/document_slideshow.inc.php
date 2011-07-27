<?php
/* For licensing terms, see /license.txt */
/**
 *	This is a plugin for the documents tool. It looks for .jpg, .jpeg, .gif, .png
 *	files (since these are the files that can be viewed in a browser) and creates
 *	a slideshow with it by allowing to go to the next/previous image.
 *	You can also have a quick overview (thumbnail view) of all the images in
 *	that particular folder.
 *
 *	Each slideshow is folder based. Only
 *	the images of the chosen folder are shown.
 *
 *	This file has two large sections.
 *	1. code that belongs in document.php, but to avoid clutter I put the code here
 *	(not present) 2. the function resize_image that handles the image resizing
 *
 *	@author Patrick Cool, responsible author
 *	@author Roan Embrechts, minor cleanup
 *	@package chamilo.document
 */
/**
 *	General code that belongs in document.php
 *
 *	This code should indeed go in documents.php but since document.php is already a really ugly file with
 *	too much things in one file , I decided to put the code for document.php here and to include this
 *	file into document.php
 */
$accepted_extensions = array('.jpg', '.jpeg', '.gif', '.png');

// Resetting the images of the slideshow = destroying the slideshow
if (isset($_GET['action']) && $_GET['action'] == 'exit_slideshow') {
	$_SESSION['image_files_only'] = null;
	unset($image_files_only);
}

// We check if there are images in this folder by searching the extensions for .jpg, .gif, .png
// grabbing the list of all the documents of this folder
//$all_files = $fileList['name'];
$array_to_search = is_array($docs_and_folders) ? $docs_and_folders : array();

if (count($array_to_search) > 0) {
	while (list($key) = each($array_to_search)) {
		$all_files[] = basename($array_to_search[$key]['path']);
		//echo basename($array_to_search[$key]['path']).'<br />';
	}
}

$image_present = 0;

if (is_array($all_files) && count($all_files) > 0) {
	foreach ($all_files as & $file) {
		$slideshow_extension = strrchr($file, '.');
		$slideshow_extension = strtolower($slideshow_extension);
		if (in_array($slideshow_extension, $accepted_extensions)) {
			$image_present = 1;
			break;
		}
	}
}

$tablename_column = isset($_GET['tablename_column']) ? Security::remove_XSS($_GET['tablename_column']) : 0;
if ($tablename_column == 0) {
	$tablename_column = 1;
} else {
	$tablename_column = intval($tablename_column) - 1;
}
$tablename_direction = isset($_GET['tablename_direction']) ? Security::remove_XSS($_GET['tablename_direction']) : 'ASC';

$image_files_only = sort_files($array_to_search);
$_SESSION['image_files_only'] = $image_files_only;

function sort_files($table) {

	global $tablename_direction, $accepted_extensions;
	$temp = array();

	foreach ($table as & $file_array) {
		if ($file_array['filetype'] == 'file') {
			$slideshow_extension = strrchr($file_array['path'], '.');
            $slideshow_extension = strtolower($slideshow_extension);
			if (in_array($slideshow_extension, $accepted_extensions)) {
				$start_date = isset($file_array['insert_date']) ? $file_array['insert_date'] : null;
				$temp[] = array('file', basename($file_array['path']), $file_array['size'], $start_date);
			}
		}
	}

	if ($tablename_direction == 'DESC') {
		usort($temp, 'rsort_table');
	} else {
		usort($temp, 'sort_table');
	}

	$final_array = array();
	foreach ($temp as & $file_array) {
		$final_array[] = $file_array[1];
	}

	return $final_array;
}

function sort_table($a, $b) {
	global $tablename_column;
	return strnatcmp($a[$tablename_column], $b[$tablename_column]);
}

function rsort_table($a, $b) {
	global $tablename_column;
	return strnatcmp($b[$tablename_column], $a[$tablename_column]);
}
