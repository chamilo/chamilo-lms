<?php

/* For licensing terms, see /license.txt */

/**
 * Reporting page on the user's own progress.
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

if (api_get_configuration_value('block_my_progress_page')) {
    api_not_allowed(true);
}

$this_section = SECTION_TRACKING;
$nameTools = get_lang('MyProgress');

$htmlHeadXtra[] = api_get_js('jquery.timelinr-0.9.54.js');
$htmlHeadXtra[] = "<script>
$(function() {
    $().timelinr({
        containerDiv: '#my_timeline',
        autoPlayPause: 2000
    })
});
</script>";

$pluginCalendar = api_get_plugin_setting('learning_calendar', 'enabled') === 'true';

if ($pluginCalendar) {
    $plugin = LearningCalendarPlugin::create();
    $plugin->setJavaScript($htmlHeadXtra);
}

$user_id = api_get_user_id();
$courseUserList = CourseManager::get_courses_list_by_user_id($user_id);
$dates = $issues = '';
$sessionId = isset($_GET['session_id']) ? (int) $_GET['session_id'] : 0;
$courseCode = isset($_GET['course']) ? Security::remove_XSS($_GET['course']) : null;
$showGraph = false === api_get_configuration_value('hide_session_graph_in_my_progress');

$isAllowedToEdit = api_is_allowed_to_edit();

if (!empty($courseUserList)) {
    $items = MySpace::get_connections_from_course_list(
        $user_id,
        $courseUserList
    );
    $first = null;
    $last = null;
    $last_item = count($items);
    $count = 1;
    foreach ($items as $result) {
        $login = $result['login'];
        $courseId = $result['c_id'];
        $courseInfo = api_get_course_info_by_id($courseId);

        if ($count == 1) {
            $first = '<a href="#'.$login.'">'.get_lang('First').'</a>';
        }
        if ($count == $last_item) {
            $last = '<a href="#'.$login.'">'.get_lang('Last').'</a>';
        }
        $course_info = api_get_course_info_by_id($result['c_id']);
        $course_image = '<img src="'.$course_info['course_image_large'].'">';
        $dates .= '<li><a href="#'.$login.'">'.api_convert_and_format_date($login, DATE_FORMAT_SHORT).'</a></li>';

        $entered = sprintf(
            get_lang('YouHaveEnteredTheCourseXInY'),
            '" '.$courseInfo['name'].' "',
            api_convert_and_format_date($login, DATE_TIME_FORMAT_LONG)
        );

        $issues .= '<li id ="'.$login.'">
                        <div class="img-course">'.$course_image.'</div>
                        <div class="text-course">
                            <p>'.$entered.'</p>
                        </div>
                    </li>';
        $count++;
    }
}

$content = Tracking::showUserProgress(
    $user_id,
    $sessionId,
    '',
    true,
    true,
    false,
    $showGraph
);
$showAllSessionCourses = api_get_configuration_value('my_progress_session_show_all_courses');

if ($showAllSessionCourses && !empty($sessionId) && empty($courseCode)) {
    $userSessionCourses = UserManager::get_courses_list_by_session($user_id, $sessionId);
    foreach ($userSessionCourses as $userSessionCourse) {
        $content .= Tracking::show_course_detail(
            $user_id,
            $userSessionCourse['course_code'],
            $sessionId,
            $isAllowedToEdit
        );
    }
} else {
    $content .= Tracking::show_course_detail($user_id, $courseCode, $sessionId, $isAllowedToEdit);
}

if (!empty($dates)) {
    $content .= Display::page_subheader(get_lang('Timeline'));
    $content .= '
    <div class="row">
      <div class="col-md-12">
          <div id="my_timeline">
              <ul id="dates">'.$dates.'</ul>
              <ul id="issues">'.$issues.'</ul>
              <div id="grad_left"></div>
              <div id="grad_right"></div>
              <a href="#" id="prev"></a>
              <a href="#" id="next"></a>
          </div>
       </div>
    </div>
    ';
}

if (api_get_configuration_value('private_messages_about_user_visible_to_user') === true) {
    $allowMessages = api_get_configuration_value('private_messages_about_user');
    if ($allowMessages === true) {
        $content .= Display::page_subheader2(get_lang('Messages'));
        $content .= MessageManager::getMessagesAboutUserToString(api_get_user_info());
    }
}

$message = null;
if (empty($content)) {
    $message = Display::return_message(get_lang('NoDataAvailable'), 'warning');
}

$tpl = new Template($nameTools);
$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
