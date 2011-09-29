<?php
/* For licensing terms, see /license.txt */
/**
*	This file is responsible for  passing requested documents to the browser.
*	Html files are parsed to fix a few problems with URLs,
*	but this code will hopefully be replaced soon by an Apache URL
*	rewrite mechanism.
*
*	@package chamilo.announcements
*/

/*
		MAIN CODE
*/

session_cache_limiter('public');

require_once '../inc/global.inc.php';
$this_section=SECTION_COURSES;
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

if (strpos($doc_url,'../') OR strpos($doc_url,'/..')) {
   $doc_url = '';
}

if (!isset($_course)) {
	api_not_allowed(true);
}

$full_file_name = api_get_path(SYS_COURSE_PATH).api_get_course_path().'/upload/announcements/'.$doc_url;
//if the rewrite rule asks for a directory, we redirect to the document explorer
if (is_dir($full_file_name)) {
	//remove last slash if present
	//$doc_url = ($doc_url{strlen($doc_url)-1}=='/')?substr($doc_url,0,strlen($doc_url)-1):$doc_url;
	//mod_rewrite can change /some/path/ to /some/path// in some cases, so clean them all off (René)
	while ($doc_url{$dul = strlen($doc_url)-1}=='/') $doc_url = substr($doc_url,0,$dul);
	//create the path
	$document_explorer = api_get_path(WEB_COURSE_PATH).api_get_course_path(); // home course path
	//redirect
	header('Location: '.$document_explorer); 
}

$tbl_announcement_attachment = Database::get_course_table(TABLE_ANNOUNCEMENT_ATTACHMENT);

// launch event
event_download($doc_url);

$course_id = api_get_course_int_id();

$sql = "SELECT filename FROM $tbl_announcement_attachment
  	  	WHERE c_id = $course_id AND path LIKE BINARY $doc_url";

$result= Database::query($sql);
if (Database::num_rows($result) > 0) {
    $row= Database::fetch_array($result);
    $title = str_replace(' ','_', $row['filename']);
    if (Security::check_abs_path($full_file_name, api_get_path(SYS_COURSE_PATH).api_get_course_path().'/upload/announcements/')) {  
        DocumentManager::file_send_for_download($full_file_name,TRUE, $title);
    }
}
exit;