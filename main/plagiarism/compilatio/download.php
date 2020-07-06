<?php
/* For licensing terms, see /license.txt */

exit;

/**
 *	This file is responsible for  passing requested documents to the browser.
 *	Html files are parsed to fix a few problems with URLs,
 *	but this code will hopefully be replaced soon by an Apache URL
 *	rewrite mechanism.
 */
session_cache_limiter('public');
require_once '../../inc/global.inc.php';
$current_course_tool = TOOL_STUDENTPUBLICATION;
$this_section = SECTION_COURSES;

// IMPORTANT to avoid caching of documents
header('Expires: Wed, 01 Jan 1990 00:00:00 GMT');
header('Cache-Control: public');
header('Pragma: no-cache');

api_protect_course_script(true);

$id = (int) $_GET['id'];
$courseInfo = api_get_course_info();

if (empty($courseInfo)) {
    api_not_allowed(true);
}

$tbl_student_publication = Database::get_course_table(TABLE_STUDENT_PUBLICATION);

if (!empty($courseInfo['real_id'])) {
    $courseId = $courseInfo['real_id'];
    $sql = "SELECT * FROM $tbl_student_publication 
	        WHERE c_id = $courseId AND id = $id";
    $result = Database::query($sql);
    if ($result && Database::num_rows($result)) {
        $row = Database::fetch_array($result, 'ASSOC');
        $full_file_name = $courseInfo['course_sys_path'].$row['url'];

        $item_info = api_get_item_property_info($courseId, 'work', $row['id']);
        if (empty($item_info)) {
            exit;
        }
        if ($courseInfo['show_score'] == 0 || $item_info['visibility'] == 1 && $row['accepted'] == 1 &&
            ($row['user_id'] == api_get_user_id() || api_is_allowed_to_edit())
        ) {
            $title = str_replace(' ', '_', $row['title']);
            Event::event_download($title);
            if (Security::check_abs_path($full_file_name, $courseInfo['course_sys_path'])) {
                DocumentManager::file_send_for_download($full_file_name, true, $title);
            }
        } else {
            api_not_allowed();
        }
    }
}

exit;
