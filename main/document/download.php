<?php
/* For licensing terms, see /license.txt */
/**
 *	This file is responsible for  passing requested documents to the browser.
 *
 *	@package chamilo.document
 */
/**
 * Code
 * Many functions updated and moved to lib/document.lib.php
 */
session_cache_limiter('none');

require_once '../inc/global.inc.php';
$this_section = SECTION_COURSES;

// Protection
api_protect_course_script();

if (!isset($_course)) {
    api_not_allowed(true);
}

$doc_url = $_GET['doc_url'];
// Change the '&' that got rewritten to '///' by mod_rewrite back to '&'
$doc_url = str_replace('///', '&', $doc_url);
// Still a space present? it must be a '+' (that got replaced by mod_rewrite)
$doc_url = str_replace(' ', '+', $doc_url);

$doc_url = str_replace(array('../', '\\..', '\\0', '..\\'), array('', '', '', ''), $doc_url); //echo $doc_url;

if (strpos($doc_url,'../') OR strpos($doc_url,'/..')) {
   $doc_url = '';
}

// Dealing with image included into survey: when users receive a link towards a
// survey while not being authenticated on the plateform.
// The administrator should probably be able to disable this code through admin
// inteface.
$refer_script = strrchr($_SERVER["HTTP_REFERER"], '/');

$sys_course_path = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';

if (substr($refer_script, 0, 15) == '/fillsurvey.php') {
	$invitation = substr(strstr($refer_script, 'invitationcode='), 15);
	$course = strstr($refer_script, 'course=');
	$course = substr($course, 7, strpos($course, '&') - 7);
	include '../survey/survey.download.inc.php';
	$_course = check_download_survey($course, $invitation, $doc_url);
	$_course['path'] = $_course['directory'];
} else {
	// If the rewrite rule asks for a directory, we redirect to the document explorer
	if (is_dir($sys_course_path.$doc_url)) {
		// Remove last slash if present
		// mod_rewrite can change /some/path/ to /some/path// in some cases, so clean them all off (René)
		while ($doc_url{$dul = strlen($doc_url) - 1} == '/') {
			$doc_url = substr($doc_url, 0, $dul);
		}
		// Group folder?
		$gid_req = ($_GET['gidReq']) ? '&gidReq='.Security::remove_XSS($_GET['gidReq']) : '';
		// Create the path
		$document_explorer = api_get_path(WEB_CODE_PATH).'document/document.php?curdirpath='.urlencode($doc_url).'&cidReq='.Security::remove_XSS($_GET['cidReq']).$gid_req;
		// Redirect
		header('Location: '.$document_explorer);
	}	
}

if (Security::check_abs_path($sys_course_path.$doc_url, $sys_course_path.'/')) {
    $full_file_name = $sys_course_path.$doc_url;
    // Check visibility of document and paths    doc_url
    //var_dump($document_id, api_get_course_id(), api_get_session_id(), api_get_user_id());
    $is_visible = false;
    //$course_info   = api_get_course_info(api_get_course_id());
    //$document_id = DocumentManager::get_document_id($course_info, $doc_url);

    //HotPotatoes_files
    //if ($document_id) {
    	// Correct choice for strict security (only show if whole tree visible)
        //$is_visible = DocumentManager::check_visibility_tree($document_id, api_get_course_id(), api_get_session_id(), api_get_user_id());
        // Correct choice for usability
    	$is_visible = DocumentManager::is_visible($doc_url, $_course, api_get_session_id());
    //}
	
	//Documents' slideshow thumbnails
	//correct $is_visible used in below and ??. Now the students can view the thumbnails too
	$doc_url_thumbs = str_replace('.thumbs/.', '', $doc_url);
	$is_visible = DocumentManager::is_visible($doc_url_thumbs, $_course, api_get_session_id());
	
    if (!api_is_allowed_to_edit() && !$is_visible) {
    	Display::display_error_message(get_lang('ProtectedDocument'));//api_not_allowed backbutton won't work.
    	exit; // You shouldn't be here anyway.
    }
    // Launch event
	event_download($doc_url);
    DocumentManager::file_send_for_download($full_file_name);
}
exit;
