<?php
/* For licensing terms, see /license.txt */

/**
 * Reporting page on the user's own progress
 * @package chamilo.tracking
 */

$cidReset = true;
require_once '../inc/global.inc.php';

$this_section = SECTION_TRACKING;

$nameTools = get_lang('MyProgress');

api_block_anonymous_users();

$htmlHeadXtra[] = api_get_js('jquery.timelinr-0.9.5.js');
$htmlHeadXtra[] = "
<script language='javascript'>
$(function() {
    $().timelinr({
        containerDiv: '#my_timeline',
        autoPlayPause: 2000
    })
});
</script>";

$user_id = api_get_user_id();
$course_user_list = CourseManager::get_courses_list_by_user_id($user_id);
$dates = $issues = '';

$sessionId = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;
$courseCode = isset($_GET['course']) ? Security::remove_XSS($_GET['course']) : null;

if (!empty($course_user_list)) {
    $items = MySpace::get_connections_from_course_list($user_id, $course_user_list);

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
        $course_image = '<img src="'.$course_info['course_image'].'">';
        $dates .= '<li><a href="#'.$login.'">'.api_get_utc_datetime($login).'</a></li>';
        $issues .= '<li id ="'.$login.'">
                        <div class="row">
                            <div class="col-md-12"><div class="thumbnail">'.$course_image.'</div>
                        </div>
                        <div class="col-md-3">'.sprintf(
                            get_lang('YouHaveEnteredTheCourseXInY'),
                            $courseInfo['code'],
                            api_convert_and_format_date($login, DATE_FORMAT_LONG)
                        ).'</div>
                    </li>';
        $count++;
    }
}

$content = Tracking::show_user_progress(api_get_user_id(), $sessionId);
$content .= Tracking::show_course_detail(api_get_user_id(), $courseCode, $sessionId);

if (!empty($dates)) {
    if (!empty($content)) {
        $content .= '';
    }
    $content .= '<div class="row"><div class="col-md-12">'.Display::page_subheader(get_lang('Timeline'));
    $content .= '<div id="my_timeline">';
    
    $actionsLeft = '<a href="#" id="prev">' . Display::return_icon('previous.png',  get_lang('Previous'), null, ICON_SIZE_MEDIUM) . '</a>';
    $actionsRight = '<a href="#" id="next">' . Display::return_icon('next.png',  get_lang('Next'), null, ICON_SIZE_MEDIUM) . '</a>';
    $content .= Display::toolbarAction('toolbar-linetime',array(0 => $actionsLeft, 1 => $actionsRight), 2, true);
    
    $content .= '<ul id="dates">
        '.$dates.'
    </ul>
    <ul id="issues">
        '.$issues.'
    </ul>
    </div></div>';
}

$message = null;

if (empty($content)) {
    $message = Display::return_message(get_lang('NoDataAvailable'), 'warning');
}

$tpl = new Template($nameTools);

$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
