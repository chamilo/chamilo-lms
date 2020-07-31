<?php

/* For licensing terms, see /license.txt */

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);

$userId = api_get_user_id();

$sql = "SELECT c_id, session_id
        FROM $table
        WHERE
            user_id  = $userId
        ORDER BY logout_course_date DESC
        LIMIT 1 ";

$result = Database::query($sql);

if (Database::num_rows($result)) {
    $result = Database::fetch_array($result, 'ASSOC');
    $courseId = (int) $result['c_id'];
    $sessionId = (int) $result['session_id'];
    $courseInfo = api_get_course_info_by_id($courseId);
    if (!empty($courseInfo)) {
        $url = $courseInfo['course_public_url'].'?id_session='.$sessionId;
        api_location($url);
    }
}

Display::addFlash(Display::return_message(get_lang('YouDidNotVisitACourseHereTheCourseList')));
api_location(api_get_path(WEB_PATH).'user_portal.php');
