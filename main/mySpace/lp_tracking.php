<?php
/*
 * Created on 26 mars 07 by Eric Marguin
 *
 * Script to display the tracking of the students in the learning paths.
 */
 

$language_file = array ('registration', 'index', 'tracking', 'exercice', 'scorm');
$cidReset = true;
include ('../inc/global.inc.php');
include_once(api_get_path(LIBRARY_PATH).'tracking.lib.php');
include_once(api_get_path(LIBRARY_PATH).'export.lib.inc.php');
include_once(api_get_path(LIBRARY_PATH).'course.lib.php');
include_once('../newscorm/learnpath.class.php');


if(!CourseManager :: is_course_teacher($_user['user_id'], $_GET['course']) && !Tracking :: is_allowed_to_coach_student($_user['user_id'],$_GET['student_id']))
{
	Display::display_header('');
	api_not_allowed();
	Display::display_footer();
}

$_course = CourseManager :: get_course_information($_GET['course']);
$_course['dbNameGlu'] = $_configuration['table_prefix'] . $_course['db_name'] . $_configuration['db_glue'];
$cidReq = $_GET['course'];

if(!empty($_GET['origin']) && $_GET['origin'] == 'user_course')
{
	$interbreadcrumb[] = array ("url" => api_get_path(WEB_COURSE_PATH).$_course['directory'], 'name' => $_course['title']);
	$interbreadcrumb[] = array ("url" => "../user/user.php?cidReq=".$_GET['course'], "name" => get_lang("Users"));
}
else if(!empty($_GET['origin']) && $_GET['origin'] == 'tracking_course')
{
	$interbreadcrumb[] = array ("url" => api_get_path(WEB_COURSE_PATH).$_course['directory'], 'name' => $_course['title']);
	$interbreadcrumb[] = array ("url" => "../tracking/courseLog.php?cidReq=".$_GET['course'].'&studentlist=true', "name" => get_lang("Tracking"));
}
else
{
	$interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('MySpace'));
	$interbreadcrumb[] = array ("url" => "student.php", "name" => get_lang("MyStudents"));
 	$interbreadcrumb[] = array ("url" => "myStudents.php?student=".$_GET['student_id'], "name" => get_lang("StudentDetails"));
 	$nameTools=get_lang("DetailsStudentInCourse");
}

$interbreadcrumb[] = array("url" => "myStudents.php?student=".$_GET['student_id']."&course=".$_GET['course']."&details=true" , "name" => get_lang("StudentDetails"));

$nameTools = get_lang('LearningPathDetails');

Display :: display_header($nameTools);

$user_id = intval($_GET['student_id']);
$lp_id = intval($_GET['lp_id']);

$list = learnpath :: get_flat_ordered_items_list($lp_id);



include_once('../newscorm/lp_stats.php');

?>
