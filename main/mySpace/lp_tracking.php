<?php
/* For licensing terms, see /license.txt */

$language_file = array ('registration', 'index', 'tracking', 'exercice', 'scorm', 'learnpath');
require_once '../inc/global.inc.php';

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
require_once api_get_path(LIBRARY_PATH).'export.lib.inc.php';
require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpath.class.php';
require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpathItem.class.php';
require_once api_get_path(LIBRARY_PATH).'export.lib.inc.php';

$session_id  = isset($_REQUEST['id_session']) && !empty($_REQUEST['id_session']) ? intval($_REQUEST['id_session']) : api_get_session_id();

$export_csv = isset($_GET['export']) && $_GET['export'] == 'csv' ? true : false;
if ($export_csv) {
	ob_start();
}
$csv_content = array();
$user_id = intval($_GET['student_id']);

if (isset($_GET['course'])) {
	$cidReq = Security::remove_XSS($_GET['course']);
}

$user_infos = UserManager :: get_user_info_by_id($user_id);
$name = api_get_person_name($user_infos['firstname'], $user_infos['lastname']);

if (!api_is_platform_admin(true) && !CourseManager :: is_course_teacher($_user['user_id'], $cidReq) && !Tracking :: is_allowed_to_coach_student($_user['user_id'],$_GET['student_id']) && !api_is_drh() && !api_is_course_tutor()) {	
	api_not_allowed();	
}
$course_exits = CourseManager::course_exists($cidReq);

if (!empty($course_exits)) {	
	$course_info = api_get_course_info($cidReq);
} else {
	api_not_allowed();
}

if (!empty($_GET['origin']) && $_GET['origin'] == 'user_course') {
	$interbreadcrumb[] = array ("url" => api_get_path(WEB_COURSE_PATH).$course_info['directory'], 'name' => $course_info['name']);
	$interbreadcrumb[] = array ("url" => "../user/user.php?cidReq=".$cidReq, "name" => get_lang("Users"));
} else if(!empty($_GET['origin']) && $_GET['origin'] == 'tracking_course') {
//	$interbreadcrumb[] = array ("url" => api_get_path(WEB_COURSE_PATH).$course_info['directory'], 'name' => $course_info['name']);
	$interbreadcrumb[] = array ("url" => "../tracking/courseLog.php?cidReq=".$cidReq.'&studentlist=true&id_session='.$session_id, "name" => get_lang("Tracking"));
} else {
	$interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('MySpace'));
	$interbreadcrumb[] = array ("url" => "student.php", "name" => get_lang("MyStudents"));
 	$interbreadcrumb[] = array ("url" => "myStudents.php?student=".Security::remove_XSS($_GET['student_id']), "name" => get_lang("StudentDetails"));
 	$nameTools=get_lang("DetailsStudentInCourse");
}

$interbreadcrumb[] = array("url" => "myStudents.php?student=".Security::remove_XSS($_GET['student_id'])."&course=".$cidReq."&details=true&origin=".Security::remove_XSS($_GET['origin']) , "name" => get_lang("DetailsStudentInCourse"));
$nameTools = get_lang('LearningPathDetails');
Display :: display_header($nameTools);

$lp_id = intval($_GET['lp_id']);

$sql = 'SELECT name	FROM '.Database::get_course_table(TABLE_LP_MAIN).' WHERE c_id = '.$course_info['real_id'].' AND id='.$lp_id;
$rs  = Database::query($sql);
$lp_title = Database::result($rs, 0, 0);
echo '<div class ="actions">';
echo '<a href="javascript:window.back();">'.Display::return_icon('back.png',get_lang('Back'),'',ICON_SIZE_MEDIUM).'</a>';
echo '<a href="javascript: void(0);" onclick="javascript: window.print();">
'.Display::return_icon('printer.png',get_lang('Print'),'',ICON_SIZE_MEDIUM).'</a>';
echo '<a href="'.api_get_self().'?export=csv&'.Security::remove_XSS($_SERVER['QUERY_STRING']).'">
'.Display::return_icon('export_csv.png',get_lang('ExportAsCSV'),'',ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

echo '<div class="clear"></div>';

$session_name = api_get_session_name($session_id);
$table_title = ($session_name? Display::return_icon('session.png', get_lang('Session'), array(), ICON_SIZE_SMALL).' '.$session_name.' ':' ').
                Display::return_icon('course.png', get_lang('Course'), array(), ICON_SIZE_SMALL).' '.$course_info['name'].' '.
                Display::return_icon('user.png', get_lang('User'), array(), ICON_SIZE_SMALL).' '.$name;
echo Display::page_header($table_title);
echo Display::page_subheader('<h3>'.Display::return_icon('learnpath.png', get_lang('ToolLearnpath'), array(), ICON_SIZE_SMALL).' '.$lp_title.'</h3>');
 
//Needed in newscorm/lp_stats.php
$list = learnpath :: get_flat_ordered_items_list($lp_id, 0, $course_info['real_id']);

$origin = 'tracking';
if ($export_csv) {
	require_once api_get_path(SYS_CODE_PATH).'newscorm/lp_stats.php';
	//Export :: export_table_csv($csv_content, 'reporting_student');
} else {
	ob_start();
	require_once  api_get_path(SYS_CODE_PATH).'newscorm/lp_stats.php';
	$tracking_content = ob_get_contents();
	ob_end_clean();    
	echo api_utf8_decode($tracking_content, $charset);
    
}
Display :: display_footer();
