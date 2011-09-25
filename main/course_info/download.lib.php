<?php
/* For licensing terms, see /license.txt */
/**
 * Course info library
 * @package chamilo.course_info
 */
/**
 * Code
 */

// TODO: Where this file is used? Is it needed at all?

//require '../inc/global.inc.php';

function create_backup_is_admin($_cid) {

	$this_section = SECTION_COURSES;
	if (isset($_GET['session']) && $_GET['session']) {
		$archive_path = api_get_path(SYS_ARCHIVE_PATH);
		$_cid = true;
		$is_courseAdmin = true;
	} else {
		$archive_path = api_get_path(SYS_ARCHIVE_PATH);
	}

	$archive_file = $_GET['archive'];
	$archive_file = str_replace(array('..', '/', '\\'), '', $archive_file);

	list($extension) = getextension($archive_file);

	if (empty($extension) || !file_exists($archive_path.$archive_file)) {
		return false;
	}

	$extension = strtolower($extension);
	$content_type = '';
	if (in_array($extension, array('xml', 'csv')) && (api_is_platform_admin(true) || api_is_drh())) {
		$content_type = 'application/force-download';
	// TODO: The following unclear condition is commented ant is to be checked. A replacement has been proposed.
	//} elseif (strtolower($extension) == 'zip' || ('html' && $_cid && (api_is_platform_admin(true) || $is_courseAdmin))) {
	} elseif ($extension == 'zip' && $_cid && (api_is_platform_admin(true) || $is_courseAdmin)) {
	//
		$content_type = 'application/force-download';
	}
	if (empty($content_type)) {
		return false;
	}
	return true;
}
return true; // TODO: Why is this line here?
