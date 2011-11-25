<?php
/* For licensing terms, see /license.txt */

/**
 *	This file is responsible for  passing requested documents to the browser.
 *	Html files are parsed to fix a few problems with URLs,
 *	but this code will hopefully be replaced soon by an Apache URL
 *	rewrite mechanism.
 *
 *	@package chamilo.work
 */

session_cache_limiter('public');

require_once '../inc/global.inc.php';
$this_section = SECTION_COURSES;

require_once api_get_path(LIBRARY_PATH).'document.lib.php';

// IMPORTANT to avoid caching of documents
header('Expires: Wed, 01 Jan 1990 00:00:00 GMT');
header('Cache-Control: public');
header('Pragma: no-cache');

//protection
api_protect_course_script(true);

$doc_url = $_GET['file'];
//change the '&' that got rewritten to '///' by mod_rewrite back to '&'
$doc_url = str_replace('///', '&', $doc_url);
//still a space present? it must be a '+' (that got replaced by mod_rewrite)
$doc_url = str_replace(' ', '+', $doc_url);
$doc_url = str_replace('/..', '', $doc_url); //echo $doc_url;

$course_info = api_get_course_info();

if (empty($course_info)) {
	api_not_allowed(true);
}

$full_file_name 		 = api_get_path(SYS_COURSE_PATH).api_get_course_path().'/'.$doc_url;
$tbl_student_publication = Database::get_course_table(TABLE_STUDENT_PUBLICATION);

// launch event
event_download($doc_url);

if (!empty($_course['real_id'])) {
    $doc_url = Database::escape_string($doc_url);
	$sql = 'SELECT * FROM '.$tbl_student_publication.' WHERE c_id = '.$_course['real_id'].' AND url LIKE BINARY "'.$doc_url.'"';
	$result = Database::query($sql);
	if ($result && Database::num_rows($result)) {
	    $row = Database::fetch_array($result, 'ASSOC');	    
        $item_info = api_get_item_property_info(api_get_course_int_id(), 'work', $row['id']);
        if (empty($item_info)) {
            exit;
        }	    
	    if ($item_info['visibility'] == 1 && $row['accepted'] == 1 && ($row['user_id'] == api_get_user_id() || api_is_allowed_to_edit())) {
		    $title = str_replace(' ', '_', $row['title']);
		    if (Security::check_abs_path($full_file_name, api_get_path(SYS_COURSE_PATH).api_get_course_path().'/')) {
		        DocumentManager::file_send_for_download($full_file_name, true, $title);
		    }
	    }	    
	}
}
exit;