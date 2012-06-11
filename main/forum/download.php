<?php
/* For licensing terms, see /license.txt */
/**
 * This file is responsible for  passing requested documents to the browser.
 * Html files are parsed to fix a few problems with URLs,
 * but this code will hopefully be replaced soon by an Apache URL
 * rewrite mechanism.
 *
 * @package chamilo.document
 */

/*
        MAIN CODE
*/

session_cache_limiter('public');

require_once '../inc/global.inc.php';
$this_section=SECTION_COURSES;

require_once 'forumconfig.inc.php';

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

if (! isset($_course)) {
    api_not_allowed(true);
}

$full_file_name = api_get_path(SYS_COURSE_PATH).api_get_course_path().'/upload/forum/'.$doc_url;

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

$tbl_forum_attachment  = Database::get_course_table(TABLE_FORUM_ATTACHMENT);
$tbl_forum_post 	   = Database::get_course_table(TABLE_FORUM_POST);

$course_id = api_get_course_int_id();

// launch event
event_download($doc_url);

$sql='SELECT thread_id, forum_id,filename FROM '.$tbl_forum_post.'  f  INNER JOIN '.$tbl_forum_attachment.' a
        ON a.post_id=f.post_id 
      WHERE f.c_id = '.$course_id.' AND a.c_id = '.$course_id.' AND path LIKE BINARY "'.$doc_url.'"';

$result = Database::query($sql);
$row    = Database::fetch_array($result);

$forum_thread_visibility = api_get_item_visibility(api_get_course_info($course_code),TOOL_FORUM_THREAD,$row['thread_id'], api_get_session_id());
$forum_forum_visibility  = api_get_item_visibility(api_get_course_info($course_code),TOOL_FORUM,$row['forum_id'], api_get_session_id());

if ($forum_thread_visibility==1 && $forum_forum_visibility==1) {
    if (Security::check_abs_path($full_file_name, api_get_path(SYS_COURSE_PATH).api_get_course_path().'/upload/forum/')) {
       DocumentManager::file_send_for_download($full_file_name, TRUE, $row['filename']);
    }
}
exit;