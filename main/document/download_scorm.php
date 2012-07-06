<?php
/* For licensing terms, see /license.txt */
/**
 *	This file is responsible for  passing requested documents to the browser.
 *
 *	@package chamilo.document
 */
/**
 * Code 
 */

session_cache_limiter('none');

require_once '../inc/global.inc.php';
$this_section = SECTION_COURSES;

// Protection
api_protect_course_script();

if (!isset($_course)) {
    api_not_allowed(true);
}

// If LP obj exists
if (isset($_SESSION['oLP'])) {
    $obj = $_SESSION['oLP'];
} else {
    api_not_allowed();
}

//If is visible for the current user
if (!$obj->is_lp_visible_for_student($obj->get_id(), api_get_user_id())) {
    api_not_allowed();
}

$doc_url = isset($_GET['url']) ? $_GET['url'] : null;

// Change the '&' that got rewritten to '///' by mod_rewrite back to '&'
$doc_url = str_replace('///', '&', $doc_url);
// Still a space present? it must be a '+' (that got replaced by mod_rewrite)
$doc_url = str_replace(' ', '+', $doc_url);

$doc_url = str_replace(array('../', '\\..', '\\0', '..\\'), array('', '', '', ''), $doc_url); //echo $doc_url;

if (strpos($doc_url,'../') OR strpos($doc_url,'/..')) {
    $doc_url = '';
}

$sys_course_path = api_get_path(SYS_COURSE_PATH).$_course['path'].'/scorm';

//var_dump($sys_course_path);

if (is_dir($sys_course_path.$doc_url)) {
    api_not_allowed();
}
 
if (Security::check_abs_path($sys_course_path.$doc_url, $sys_course_path.'/')) {
    $full_file_name = $sys_course_path.$doc_url;
    // Launch event
    event_download($doc_url);
    
    DocumentManager::file_send_for_download($full_file_name);
}
exit;