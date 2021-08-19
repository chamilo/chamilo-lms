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
session_cache_limiter('public');

require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);

$this_section = SECTION_COURSES;

// IMPORTANT to avoid caching of documents
header('Expires: Wed, 01 Jan 1990 00:00:00 GMT');
header('Cache-Control: public');
header('Pragma: no-cache');

$doc_url = $_GET['file'];
//change the '&' that got rewritten to '///' by mod_rewrite back to '&'
$doc_url = str_replace('///', '&', $doc_url);
//still a space present? it must be a '+' (that got replaced by mod_rewrite)
$doc_url = str_replace(' ', '+', $doc_url);
$doc_url = str_replace('/..', '', $doc_url);

$tbl_forum_attachment = Database::get_course_table(TABLE_FORUM_ATTACHMENT);
$tbl_forum_post = Database::get_course_table(TABLE_FORUM_POST);

$course_id = api_get_course_int_id();
$courseInfo = api_get_course_info_by_id($course_id);

$sql = 'SELECT thread_id, forum_id,filename
        FROM '.$tbl_forum_post.'  f
        INNER JOIN '.$tbl_forum_attachment.' a
        ON a.post_id=f.post_id
        WHERE
            f.c_id = '.$course_id.' AND
            a.c_id = '.$course_id.' AND
            path LIKE BINARY "'.Database::escape_string($doc_url).'"';

$result = Database::query($sql);
$row = Database::fetch_array($result);

if (empty($row)) {
    api_not_allowed();
}

$forum_thread_visibility = api_get_item_visibility(
    $courseInfo,
    TOOL_FORUM_THREAD,
    $row['thread_id'],
    api_get_session_id()
);
$forum_forum_visibility = api_get_item_visibility(
    $courseInfo,
    TOOL_FORUM,
    $row['forum_id'],
    api_get_session_id()
);

if ($forum_thread_visibility == 1 && $forum_forum_visibility == 1) {
    $full_file_name = api_get_path(SYS_COURSE_PATH).api_get_course_path().'/upload/forum/'.$doc_url;
    if (Security::check_abs_path(
        $full_file_name,
        api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/upload/forum/'
    )) {
        // launch event
        Event::event_download($doc_url);

        $result = DocumentManager::file_send_for_download(
            $full_file_name,
            true,
            $row['filename']
        );

        if ($result === false) {
            api_not_allowed(true);
        }
    }
}

api_not_allowed();
