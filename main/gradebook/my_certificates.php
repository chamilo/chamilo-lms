<?php

/* For licensing terms, see /license.txt */
/**
 * List of achieved certificates by the current user
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.gradebook
 */
$cidReset = true;

require_once '../inc/global.inc.php';

if (api_is_anonymous()) {
    api_not_allowed(true);
}

$userId = api_get_user_id();
$courses = CourseManager::get_courses_list_by_user_id($userId);
$sessions = SessionManager::get_sessions_by_user($userId);

$courseList = $sessionList = [];

foreach ($courses as $course) {
    $courseGradebookCategory = Category::load(null, null, $course['code']);

    if (empty($courseGradebookCategory)) {
        continue;
    }

    $courseGradebookId = $courseGradebookCategory[0]->get_id();

    $certificateInfo = GradebookUtils::get_certificate_by_user_id($courseGradebookId, $userId);

    if (empty($certificateInfo)) {
        continue;
    }

    $courseInfo = api_get_course_info($course['code']);

    $courseList[] = [
        'course' => $courseInfo['title'],
        'score' => $certificateInfo['score_certificate'],
        'date' => api_format_date($certificateInfo['created_at'], DATE_FORMAT_SHORT),
        'link' => api_get_path(WEB_PATH) . "certificates/index.php?id={$certificateInfo['id']}"
    ];
}

foreach ($sessions as $session) {
    if (empty($session['courses'])) {
        continue;
    }

    $sessionCourses = SessionManager::get_course_list_by_session_id($session['session_id']);

    foreach ($sessionCourses as $sessionCourse) {
        $courseGradebookCategory = Category::load(
            null,
            null,
            $sessionCourse['code'],
            null,
            null,
            $session['session_id']
        );

        if (empty($courseGradebookCategory)) {
            continue;
        }

        $courseGradebookId = $courseGradebookCategory[0]->get_id();

        $certificateInfo = GradebookUtils::get_certificate_by_user_id($courseGradebookId, $userId);

        if (empty($certificateInfo)) {
            continue;
        }

        $sessionList[] = [
            'session' => $session['session_name'],
            'course' => $sessionCourse['title'],
            'score' => $certificateInfo['score_certificate'],
            'date' => api_format_date($certificateInfo['created_at'], DATE_FORMAT_SHORT),
            'link' => api_get_path(WEB_PATH) . "certificates/index.php?id={$certificateInfo['id']}"
        ];
    }
}

$template = new Template(get_lang('MyCertificates'));

$template->assign('course_list', $courseList);
$template->assign('session_list', $sessionList);
$content = $template->fetch('default/gradebook/my_certificates.tpl');

if (empty($courseList) || empty($sessionList)) {
    $template->assign(
        'message',
        Display::return_message(get_lang('YouNotYetAchievedCertificates'), 'warning')
    );
}

$template->assign('content', $content);
$template->display_one_col_template();
