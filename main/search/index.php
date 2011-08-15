<?php
/* For licensing terms, see /license.txt */
/**
 * This file includes lp_list_search to avoid duplication of code, it
 * bootstraps chamilo api enough to make lp_list_search work.
 * @package chamilo.search
 */
/**
 * Code
 */
$language_file = array('admin');
require_once '../inc/global.inc.php';
$this_section =  SECTION_COURSES;

if (extension_loaded('xapian')) {	
	require_once api_get_path(LIBRARY_PATH).'system_announcements.lib.php';
	require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';	
	//api_block_anonymous_users(); // only users who are logged in can proceed
	require '../newscorm/lp_list_search.php';
} else {
	Display::display_header(get_lang('Search'));
	Display::display_error_message(get_lang('SearchXapianModuleNotInstalled'));
	Display::display_footer();
	exit;
}
