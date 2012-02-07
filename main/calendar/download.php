<?php // $Id: $
/* For licensing terms, see /license.txt */
/**
*	This file is responsible for  passing requested documents to the browser.
*	Html files are parsed to fix a few problems with URLs,
*	but this code will hopefully be replaced soon by an Apache URL
*	rewrite mechanism.
*
*	@package chamilo.calendar
*/
/**
 *		MAIN CODE
 */

session_cache_limiter('public');

require_once '../inc/global.inc.php';
$this_section = SECTION_COURSES;

require_once api_get_path(LIBRARY_PATH).'document.lib.php';
require_once 'agenda.inc.php';

// IMPORTANT to avoid caching of documents
header('Expires: Wed, 01 Jan 1990 00:00:00 GMT');
header('Cache-Control: public');
header('Pragma: no-cache');

$course_id      = intval($_REQUEST['course_id']);
$user_id        = api_get_user_id();
$course_info    = api_get_course_info_by_id($course_id);
$doc_url        = $_REQUEST['file'];
$session_id     = api_get_session_id();

if (empty($course_id)) {
    $course_id = api_get_course_int_id();
}
if (empty($course_id) || empty($doc_url)) {
    api_not_allowed();
}

$is_user_is_subscribed = CourseManager::is_user_subscribed_in_course($user_id, $course_info['code'], true, $session_id);

if (!api_is_allowed_to_edit() && !$is_user_is_subscribed) {    
    api_not_allowed();
}

//change the '&' that got rewritten to '///' by mod_rewrite back to '&'
$doc_url = str_replace('///', '&', $doc_url);
//still a space present? it must be a '+' (that got replaced by mod_rewrite)
$doc_url = str_replace(' ', '+', $doc_url);
$doc_url = str_replace('/..', '', $doc_url); //echo $doc_url;

$full_file_name = api_get_path(SYS_COURSE_PATH).$course_info['path'].'/upload/calendar/'.$doc_url;

//if the rewrite rule asks for a directory, we redirect to the document explorer
if (is_dir($full_file_name)) {
	//remove last slash if present
	//$doc_url = ($doc_url{strlen($doc_url)-1}=='/')?substr($doc_url,0,strlen($doc_url)-1):$doc_url;
	//mod_rewrite can change /some/path/ to /some/path// in some cases, so clean them all off (René)
	while ($doc_url{$dul = strlen($doc_url)-1}=='/') $doc_url = substr($doc_url,0,$dul);
	//create the path
	$document_explorer = api_get_path(WEB_COURSE_PATH).$course_info['path']; // home course path
	//redirect
	header('Location: '.$document_explorer);
	exit;
}

$tbl_agenda_attachment 	= Database::get_course_table(TABLE_AGENDA_ATTACHMENT);

// launch event
event_download($doc_url);

$sql='SELECT filename FROM '.$tbl_agenda_attachment.'
  	  WHERE c_id = '.$course_id.' AND path LIKE BINARY "'.Database::escape_string($doc_url).'"';

$result = Database::query($sql);
if (Database::num_rows($result)) {
    $row    = Database::fetch_array($result);    
    $title = str_replace(' ','_', $row['filename']);
    if (Security::check_abs_path($full_file_name, api_get_path(SYS_COURSE_PATH).$course_info['path'].'/upload/calendar/')) {
        DocumentManager::file_send_for_download($full_file_name,TRUE, $title);
    }
}
api_not_allowed();
