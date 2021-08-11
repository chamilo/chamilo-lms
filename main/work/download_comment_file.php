<?php

/* For licensing terms, see /license.txt */

/**
 *  This file is responsible for  passing requested documents to the browser.
 *  Html files are parsed to fix a few problems with URLs,
 *  but this code will hopefully be replaced soon by an Apache URL
 *  rewrite mechanism.
 */
require_once __DIR__.'/../inc/global.inc.php';
require_once 'work.lib.php';

api_protect_course_script(true);

$commentId = isset($_GET['comment_id']) ? (int) $_GET['comment_id'] : null;
if (empty($commentId)) {
    api_not_allowed(true);
}
$workData = getWorkComment($commentId);
$courseInfo = api_get_course_info();

if (empty($workData)) {
    api_not_allowed(true);
}

if (empty($workData['file_path']) ||
    (isset($workData['file_path']) && !file_exists($workData['file_path']))
) {
    api_not_allowed(true);
}

$work = get_work_data_by_id($workData['work_id']);

protectWork($courseInfo, $work['parent_id']);

$userHasAccess = api_is_coach() ||
    api_is_allowed_to_edit(false, false, true) ||
    user_is_author($workData['work_id']);

$allowBaseCourseTeacher = api_get_configuration_value('assignment_base_course_teacher_access_to_all_session');
if (false === $userHasAccess && $allowBaseCourseTeacher) {
    // Check if user is base course teacher.
    if (CourseManager::is_course_teacher(api_get_user_id(), $courseInfo['code'])) {
        $userHasAccess = true;
    }
}

if ($userHasAccess ||
    $courseInfo['show_score'] == 0 &&
    $work['active'] == 1 &&
    $work['accepted'] == 1
) {
    if (Security::check_abs_path(
        $workData['file_path'],
        api_get_path(SYS_COURSE_PATH).api_get_course_path().'/'
    )
    ) {
        DocumentManager::file_send_for_download(
            $workData['file_path'],
            true,
            $workData['file_name_to_show']
        );
    }
} else {
    api_not_allowed(true);
}
