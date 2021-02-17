<?php

/* For licensing terms, see /license.txt */

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';
$current_course_tool = TOOL_COURSE_MAINTENANCE;

api_protect_admin_script();
api_protect_limit_for_session_admin();
api_set_more_memory_and_time_limits();

$courseId = isset($_REQUEST['course_id']) ? (int) $_REQUEST['course_id'] : 0;
$sourceSessionId = isset($_REQUEST['source_session_id']) ? (int) $_REQUEST['source_session_id'] : 0;
$destinationSessionId = isset($_REQUEST['destination_session_id']) ? (int) $_REQUEST['destination_session_id'] : 0;
$page = 1;
if (isset($_GET['page']) && !empty($_GET['page'])) {
    $page = (int) $_GET['page'];
}

$courseOptions = [];
$defaults = [];
$courseInfo = [];
if (!empty($courseId)) {
    $defaults['course_id'] = $courseId;
    $courseInfo = api_get_course_info_by_id($courseId);
    $courseOptions[$courseId] = $courseInfo['name'];
}

$currentUrl = api_get_self().'?course_id='.$courseId.
    '&source_session_id='.$sourceSessionId.'&destination_session_id='.$destinationSessionId;

$form = new FormValidator('course', 'GET', api_get_self().'?course_id='.$courseId);
$form->addHeader(get_lang('MoveUsersFromCourseToSession'));
$form->addSelectAjax(
    'course_id',
    get_lang('Course'),
    $courseOptions,
    [
        'url' => api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=search_course',
    ]
);
$form->addHidden('page', $page);
$form->addButtonSearch(get_lang('Search'));
$content = '';
if (!empty($courseId)) {
    if (!empty($sourceSessionId) && $sourceSessionId === $destinationSessionId) {
        Display::addFlash(Display::return_message(get_lang('CantMoveToTheSameSession')));
        api_location(api_get_self());
    }

    $sessions = SessionManager::get_session_by_course($courseId);
    if (!empty($sessions)) {
        $sessions = array_column($sessions, 'name', 'id');
        $form->addHtml(Display::page_subheader2(get_lang('Sessions')));
        $sessionsWithBase = [0 => get_lang('BaseCourse')] + $sessions;
        $form->addSelect(
            'source_session_id',
            get_lang('Source'),
            $sessionsWithBase
        );

        $form->addSelect(
            'destination_session_id',
            get_lang('Destination'),
            $sessions
        );

        $form->addButtonSearch(get_lang('CompareStats'), 'compare');
        $form->addButtonCopy(get_lang('Move'), 'move');
    }

    if (empty($sourceSessionId)) {
        $count = CourseManager::get_user_list_from_course_code($courseInfo['code'], 0, null, null, STUDENT, true);
    } else {
        $count = CourseManager::get_user_list_from_course_code(
            $courseInfo['code'],
            $sourceSessionId,
            null,
            null,
            0,
            true
        );
    }

    $students = [];
    if (isset($_REQUEST['compare']) || isset($_REQUEST['move'])) {
        /*$default = 20;
        $nro_pages = round($count / $default) + 1;
        $begin = $default * ($page - 1);
        $end = $default * $page;

        if ($count > $default) {
            $navigation = "$begin - $end  / $count<br />";
            if ($page > 1) {
                $navigation .= '<a href="'.$currentUrl.'&compare=1&page='.($page - 1).'">'.get_lang('Previous').'</a>';
            } else {
                $navigation .= get_lang('Previous');
            }
            $navigation .= '&nbsp;';

            if ($page < $nro_pages) {
                $page++;
                $navigation .= '<a href="'.$currentUrl.'&compare=1&page='.$page.'">'.get_lang('Next').'</a>';
            } else {
                $navigation .= get_lang('Next');
            }

            $content .= $navigation;
        }*/

        //$limit = "LIMIT $begin, $default";
        $limit = null;
        if (empty($sourceSessionId)) {
            $students = CourseManager::get_user_list_from_course_code($courseInfo['code'], 0, $limit, null, STUDENT);
        } else {
            $students = CourseManager::get_user_list_from_course_code(
                $courseInfo['code'],
                $sourceSessionId,
                $limit,
                null,
                0
            );
        }
        foreach ($students as $student) {
            $studentId = $student['user_id'];
            $name = $student['firstname'].' '.$student['lastname'];
            $content .= "<h2>$name #$studentId </h2>";
            $subscribed = SessionManager::isUserSubscribedAsStudent($destinationSessionId, $studentId);

            if ($subscribed) {
                $content .= Display::return_message(get_lang('AlreadySubscribed'));
                continue;
            }

            if (isset($_REQUEST['move'])) {
                // Registering user to the new session.
                SessionManager::subscribeUsersToSession(
                    $destinationSessionId,
                    [$studentId],
                    false,
                    false
                );
            }

            ob_start();
            Tracking::processUserDataMove(
                $studentId,
                $courseInfo,
                $sourceSessionId,
                $destinationSessionId,
                isset($_REQUEST['move'])
            );
            $tableResult = ob_get_contents();
            ob_get_clean();
            $content .= $tableResult;
        }
    }
}

Display::display_header();
$form->setDefaults($defaults);
$form->display();
echo $content;
Display::display_footer();
