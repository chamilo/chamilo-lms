<?php
/* For licensing terms, see /license.txt */
/**
 * Learning paths reporting
 * @package chamilo.reporting
 */
require_once __DIR__.'/../inc/global.inc.php';

// resetting the course id
$cidReset = true;
$from_myspace = false;
$from_link = '';
if (isset($_GET['from']) && $_GET['from'] == 'myspace') {
    $from_link = '&from=myspace';
    $this_section = SECTION_TRACKING;
} else {
    $this_section = SECTION_COURSES;
}

$session_id = isset($_REQUEST['id_session']) && !empty($_REQUEST['id_session']) ? intval($_REQUEST['id_session']) : api_get_session_id();
$export_csv = isset($_GET['export']) && $_GET['export'] == 'csv' ? true : false;
$user_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : api_get_user_id();
$courseCode = isset($_GET['course']) ? Security::remove_XSS($_GET['course']) : api_get_course_id();
$origin = isset($_GET['origin']) ? Security::remove_XSS($_GET['origin']) : null;
$lp_id = intval($_GET['lp_id']);
$csv_content = array();
$course_info = api_get_course_info($courseCode);

if (empty($course_info) || empty($lp_id)) {
    api_not_allowed(api_get_origin() !== 'learnpath');
}
$userInfo = api_get_user_info($user_id);
$name = $userInfo['complete_name'];
$isBoss = UserManager::userIsBossOfStudent(api_get_user_id(), $user_id);

if (!api_is_platform_admin(true) &&
    !CourseManager :: is_course_teacher(api_get_user_id(), $courseCode) &&
    !$isBoss &&
    !Tracking :: is_allowed_to_coach_student(api_get_user_id(), $user_id) && !api_is_drh() && !api_is_course_tutor()
) {
    api_not_allowed(
        api_get_origin() !== 'learnpath'
    );
}

if ($origin == 'user_course') {
    $interbreadcrumb[] = array("url" => api_get_path(WEB_COURSE_PATH).$course_info['directory'], 'name' => $course_info['name']);
    $interbreadcrumb[] = array("url" => "../user/user.php?cidReq=".$courseCode, "name" => get_lang("Users"));
} else if ($origin == 'tracking_course') {
    $interbreadcrumb[] = array("url" => "../tracking/courseLog.php?cidReq=".$courseCode.'&id_session='.$session_id, "name" => get_lang("Tracking"));
} else {
    $interbreadcrumb[] = array("url" => "index.php", "name" => get_lang('MySpace'));
    $interbreadcrumb[] = array("url" => "student.php", "name" => get_lang("MyStudents"));
    $interbreadcrumb[] = array("url" => "myStudents.php?student=".$user_id, "name" => get_lang("StudentDetails"));
    $nameTools = get_lang("DetailsStudentInCourse");
}

$interbreadcrumb[] = array(
    "url" => "myStudents.php?student=".$user_id."&course=".$courseCode."&details=true&origin=".$origin,
    "name" => get_lang("DetailsStudentInCourse"),
);
$nameTools = get_lang('LearningPathDetails');
$sql = 'SELECT name	FROM '.Database::get_course_table(TABLE_LP_MAIN).' 
        WHERE c_id = '.$course_info['real_id'].' AND id='.$lp_id;
$rs  = Database::query($sql);
$lp_title = Database::result($rs, 0, 0);

$origin = 'tracking';

$output = require_once api_get_path(SYS_CODE_PATH).'lp/lp_stats.php';

Display :: display_header($nameTools);
echo '<div class ="actions">';
echo '<a href="javascript:history.back();">'.Display::return_icon('back.png', get_lang('Back'), '', ICON_SIZE_MEDIUM).'</a>';
echo '<a href="javascript: void(0);" onclick="javascript: window.print();">
        '.Display::return_icon('printer.png', get_lang('Print'), '', ICON_SIZE_MEDIUM).'</a>';
echo '<a href="'.api_get_self().'?export=csv&'.Security::remove_XSS($_SERVER['QUERY_STRING']).'">
        '.Display::return_icon('export_csv.png', get_lang('ExportAsCSV'), '', ICON_SIZE_MEDIUM).'</a>';
echo '</div>';
echo '<div class="clear"></div>';
$session_name = api_get_session_name($session_id);
$table_title = ($session_name ? Display::return_icon('session.png', get_lang('Session'), array(), ICON_SIZE_SMALL).' '.$session_name.' ' : ' ').
    Display::return_icon('course.png', get_lang('Course'), array(), ICON_SIZE_SMALL).' '.$course_info['name'].' '.
    Display::return_icon('user.png', get_lang('User'), array(), ICON_SIZE_SMALL).' '.$name;
echo Display::page_header($table_title);
echo Display::page_subheader(
    '<h3>'.Display::return_icon(
        'learnpath.png',
        get_lang('ToolLearnpath'),
        array(),
        ICON_SIZE_SMALL
    ).' '.$lp_title.'</h3>'
);
echo $output;
Display :: display_footer();
