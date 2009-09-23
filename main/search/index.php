<?php
/* For licensing terms, see /dokeos_license.txt */

/**
 * This file includes lp_list_search to avoid duplication of code, it
 * bootstraps dokeos api enough to make lp_list_search work.
 */
$language_file = array('admin');
require_once ('../inc/global.inc.php');
$this_section =  SECTION_COURSES;

if (extension_loaded('xapian')) {
	include_once (api_get_path(LIBRARY_PATH).'course.lib.php');
	include_once (api_get_path(LIBRARY_PATH).'debug.lib.inc.php');
	include_once (api_get_path(LIBRARY_PATH).'system_announcements.lib.php');
	include_once (api_get_path(LIBRARY_PATH).'groupmanager.lib.php');
	include_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
	//api_block_anonymous_users(); // only users who are logged in can proceed
	require '../newscorm/lp_list_search.php';
} else {
	Display::display_header(get_lang('Search'));
	Display::display_error_message(get_lang('SearchXapianModuleNotInstaled'));
	Display::display_footer();
	exit;
}

?>
