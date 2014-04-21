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
$sys_course_path = api_get_path(SYS_COURSE_PATH).$_course['path'].'/scorm';
$user_id = api_get_user_id();

if ($_SESSION['oLP']) {
    $lp_id      = $_SESSION['oLP']->get_id();
    $lp_item_id = $_SESSION['oLP']->current;    
    $lp_item_info = new learnpathItem($lp_item_id);
    if (!empty($lp_item_info)) {
    //if (basename($lp_item_info->path) == basename($doc_url)) {
        $visible = learnpath::is_lp_visible_for_student($lp_id, $user_id);
        
        if ($visible) {
            event_download($doc_url);  
            if (Security::check_abs_path($sys_course_path.$doc_url, $sys_course_path.'/')) {
                $full_file_name = $sys_course_path.$doc_url;
                DocumentManager::file_send_for_download($full_file_name);
                exit;
            }
        }
    //}
    }        
}

Display::display_error_message(get_lang('ProtectedDocument'));//api_not_allowed backbutton won't work.
exit;