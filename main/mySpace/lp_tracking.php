<?php
/* For licensing terms, see /license.txt */

/*
 * Created on 26 mars 07 by Eric Marguin
 * Script to display the tracking of the students in the learning paths.
 */

$language_file = array ('registration', 'index', 'tracking', 'exercice', 'scorm', 'learnpath');
//$cidReset = true;

require_once '../inc/global.inc.php';

$from_myspace = false;
$from_link = '';
if (isset($_GET['from']) && $_GET['from'] == 'myspace') {
	$from_link = '&from=myspace';
	$this_section = SECTION_TRACKING;
} else {
	$this_section = SECTION_COURSES;
}
include_once api_get_path(LIBRARY_PATH).'tracking.lib.php';
include_once api_get_path(LIBRARY_PATH).'export.lib.inc.php';
include_once api_get_path(LIBRARY_PATH).'course.lib.php';
include_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
include_once api_get_path(SYS_CODE_PATH).'newscorm/learnpath.class.php';
include_once api_get_path(SYS_CODE_PATH).'newscorm/learnpathItem.class.php';
require_once api_get_path(LIBRARY_PATH).'export.lib.inc.php';

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
	Display::display_header('');
	api_not_allowed();
	Display::display_footer();
}

$course_exits = CourseManager::course_exists($cidReq);

if (!empty($course_exits)) {
	$_course = CourseManager :: get_course_information($cidReq);
} else {
	api_not_allowed();
}

$_course['dbNameGlu'] = $_configuration['table_prefix'] . $_course['db_name'] . $_configuration['db_glue'];

if (!empty($_GET['origin']) && $_GET['origin'] == 'user_course') {
	$interbreadcrumb[] = array ("url" => api_get_path(WEB_COURSE_PATH).$_course['directory'], 'name' => $_course['title']);
	$interbreadcrumb[] = array ("url" => "../user/user.php?cidReq=".$cidReq, "name" => get_lang("Users"));
} else if(!empty($_GET['origin']) && $_GET['origin'] == 'tracking_course') {
	$interbreadcrumb[] = array ("url" => api_get_path(WEB_COURSE_PATH).$_course['directory'], 'name' => $_course['title']);
	$interbreadcrumb[] = array ("url" => "../tracking/courseLog.php?cidReq=".$cidReq.'&studentlist=true&id_session='.$_SESSION['id_session'], "name" => get_lang("Tracking"));
} else {
	$interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('MySpace'));
	$interbreadcrumb[] = array ("url" => "student.php", "name" => get_lang("MyStudents"));
 	$interbreadcrumb[] = array ("url" => "myStudents.php?student=".Security::remove_XSS($_GET['student_id']), "name" => get_lang("StudentDetails"));
 	$nameTools=get_lang("DetailsStudentInCourse");
}

$interbreadcrumb[] = array("url" => "myStudents.php?student=".Security::remove_XSS($_GET['student_id'])."&course=".$cidReq."&details=true&origin=".Security::remove_XSS($_GET['origin']) , "name" => get_lang("DetailsStudentInCourse"));
$nameTools = get_lang('LearningPathDetails');

$htmlHeadXtra[] = '
<style>
div.title {
	font-weight : bold;
	text-align : left;
}
div.mystatusfirstrow {
	font-weight : bold;
	text-align : left;
}
div.description {
	font-family : Arial, Helvetica, sans-serif;
	font-size: 10px;
	color: Silver;
}
</style>';

Display :: display_header($nameTools);

$lp_id = intval($_GET['lp_id']);

$sql = 'SELECT name	FROM '.Database::get_course_table(TABLE_LP_MAIN, $_course['db_name']).'	WHERE id='.$lp_id;
$rs  = Database::query($sql);
$lp_title = Database::result($rs, 0, 0);


echo '<div class ="actions">';
echo '<a href="javascript:window.back();">'.Display::return_icon('back.png',get_lang('Back'),'','32').'</a>';
echo '<a href="javascript: void(0);" onclick="javascript: window.print();">
'.Display::return_icon('printer.png',get_lang('Print'),'','32').'</a>';
echo '<a href="'.api_get_self().'?export=csv&'.Security::remove_XSS($_SERVER['QUERY_STRING']).'">
'.Display::return_icon('export_csv.png',get_lang('ExportAsCSV'),'','32').'</a>';
echo '</div>';

echo '<div class="clear"></div>';

$session_name = api_get_session_name(api_get_session_id());
$table_title = ($session_name? get_lang('Session').' : '.$session_name.' | ':'').get_lang('Course').' : '.$_course['title'].' | '.$name;
echo '<h2>'.$table_title.'</h2>';
echo '<h3>'.get_lang('ToolLearnpath').' : '.$lp_title.'</h3>';
    
$list = learnpath :: get_flat_ordered_items_list($lp_id);
$origin = 'tracking';
if ($export_csv) {
	include_once api_get_path(SYS_CODE_PATH).'newscorm/lp_stats.php';
	//Export :: export_table_csv($csv_content, 'reporting_student');
} else {
	ob_start();
	include_once  api_get_path(SYS_CODE_PATH).'newscorm/lp_stats.php';
	$tracking_content = ob_get_contents();
	ob_end_clean();
	echo api_utf8_decode($tracking_content, $charset);
}
Display :: display_footer();
