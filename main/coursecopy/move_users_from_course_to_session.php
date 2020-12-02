<?php

/* For licensing terms, see /license.txt */

/**
 * Copy resources from one course in a session to another one.
 *
 * @author Christian Fasanando
 * @author Julio Montoya <gugli100@gmail.com> Lots of bug fixes/improvements
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';
$current_course_tool = TOOL_COURSE_MAINTENANCE;

api_protect_admin_script();
api_protect_limit_for_session_admin();
api_set_more_memory_and_time_limits();

$courseId = isset($_REQUEST['course_id']) ? (int) $_REQUEST['course_id'] : 0;
$sessionId = isset($_REQUEST['session_id']) ? (int) $_REQUEST['session_id'] : 0;

$courseOptions = [];
$defaults = [];
$courseInfo = [];
if (!empty($courseId)) {
    $defaults['course_id'] = $defaults;
    $courseInfo = api_get_course_info_by_id($courseId);
    $courseOptions[$courseId] = $courseInfo['name'];
}
Display::display_header();
$form = new FormValidator('course', 'GET', api_get_self());
$form->addHeader(get_lang('MoveUsersFromCourseToSession'));
$form->addSelectAjax(
    'course_id',
    get_lang('Course'),
    $courseOptions,
    [
        'url' => api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=search_course',
    ]
);
$form->addButtonSearch(get_lang('Search'));
$content = '';
if ($form->validate()) {
    $values = $form->getSubmitValues();
    if (!empty($courseId)) {
        $sessions = SessionManager::get_session_by_course($courseId);
        if (!empty($sessions)) {
            $sessions = array_column($sessions, 'name', 'id');
            $form->addSelect(
                'session_id',
                get_lang('Session'),
                $sessions
            );

            $form->addButtonSearch(get_lang('CompareStats'), 'compare');
            $form->addButtonCopy(get_lang('Move'), 'move');
        }
    }

    $count = CourseManager::get_user_list_from_course_code($courseInfo['code'], 0, null, null, STUDENT, true);
    $students = [];
    if (isset($values['compare']) || isset($values['move'])) {
        $default = 20;
        $page = 1;
        if (isset($_GET['page']) && !empty($_GET['page'])) {
            $page = (int) $_GET['page'];
        }
        $nro_pages = round($count / $default) + 1;
        $begin = $default * ($page - 1);
        $end = $default * $page;

        if ($count > $default) {
            $navigation = "$begin - $end  / $count<br />";
            if ($page > 1) {
                $navigation .= '<a href="'.api_get_self().'?page='.($page - 1).'">'.get_lang('Previous').'</a>';
            } else {
                $navigation .= get_lang('Previous');
            }
            $navigation .= '&nbsp;';
            $page++;
            if ($page < $nro_pages) {
                $navigation .= '<a href="'.api_get_self().'?page='.$page.'">'.get_lang('Next').'</a>';
            } else {
                $navigation .= get_lang('Next');
            }

            $content .= $navigation;
        }

        $limit = "LIMIT $begin, $default";
        $students = CourseManager::get_user_list_from_course_code($courseInfo['code'], 0, $limit, null, STUDENT);
        foreach ($students as $student) {
            $studentId = $student['user_id'];
            $name = $student['firstname'].' '.$student['lastname'];
            $content .= "<h2>$name #$studentId </h2>";

            $subscribed = SessionManager::isUserSubscribedAsStudent($sessionId, $studentId);

            if ($subscribed) {
                $content .= Display::return_message(get_lang('AlreadySubscribed'));
                continue;
            }

            if (isset($values['move'])) {
                // Registering user to the new session.
                SessionManager::subscribeUsersToSession(
                    $sessionId,
                    [$studentId],
                    false,
                    false
                );
            }

            ob_start();
            Tracking::processUserDataMove(
                $studentId,
                $courseInfo,
                0,
                $sessionId,
                isset($values['move'])
            );
            $tableResult = ob_get_contents();
            ob_get_clean();
            $content .= $tableResult;
        }
    }
}

$form->display();
echo $content;
Display::display_footer();
