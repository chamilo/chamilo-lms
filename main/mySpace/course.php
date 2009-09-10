<?php
/*
 * Created on 28 juil. 2006 by Elixir Interactive http://www.elixir-interactive.com
 */
ob_start();
$nameTools = 'Cours';
// name of the language file that needs to be included 
$language_file = array ('admin', 'registration', 'index', 'trad4all', 'tracking');
$cidReset = true;

require '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'tracking.lib.php';
require_once api_get_path(LIBRARY_PATH).'export.lib.inc.php';
require_once api_get_path(LIBRARY_PATH).'course.lib.php';

$this_section = "session_my_space";
$id_session = intval($_GET['id_session']);

api_block_anonymous_users();
$interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('MySpace'));

if (isset($_GET["id_session"]) && $_GET["id_session"] != "") {
	$interbreadcrumb[] = array ("url" => "session.php", "name" => get_lang('Sessions'));
}

if (isset($_GET["user_id"]) && $_GET["user_id"] != "" && isset($_GET["type"]) && $_GET["type"] == "coach") {
	 $interbreadcrumb[] = array ("url" => "coaches.php", "name" => get_lang('Tutors'));
}

if (isset($_GET["user_id"]) && $_GET["user_id"] != "" && isset($_GET["type"]) && $_GET["type"] == "student") {
	 $interbreadcrumb[] = array ("url" => "student.php", "name" => get_lang('Students'));
}

if (isset($_GET["user_id"]) && $_GET["user_id"] != "" && !isset($_GET["type"])) {
	 $interbreadcrumb[] = array ("url" => "teachers.php", "name" => get_lang('Teachers'));
}

function count_courses() {
	global $nb_courses;
	return $nb_courses;
}

//checking if the current coach is the admin coach
$show_import_icon = false;

if (api_get_setting('add_users_by_coach') == 'true') {
	if (!api_is_platform_admin()) {
		$sql = 'SELECT id_coach FROM '.Database :: get_main_table(TABLE_MAIN_SESSION).' WHERE id='.$id_session;
		$rs = Database::query($sql, __FILE__, __LINE__);
		if (Database::result($rs, 0, 0) != $_user['user_id']) {
			api_not_allowed(true);  
		} else {
			$show_import_icon=true;	
		}
	}
}

Display :: display_header($nameTools);

// Database Table Definitions 
$tbl_course 				= Database :: get_main_table(TABLE_MAIN_COURSE);
$tbl_user_course 			= Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_user 					= Database :: get_main_table(TABLE_MAIN_USER);
$tbl_session_course 		= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session 				= Database :: get_main_table(TABLE_MAIN_SESSION);
$tbl_session_course_user 	= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
 
if (isset($_GET['action'])) {
	if ($_GET['action'] == 'show_message') {
		Display :: display_normal_message(stripslashes($_GET['message']), false);
	}

	if ($_GET['action'] == 'error_message') {
		Display :: display_error_message(stripslashes($_GET['message']), false);
	}
}			

if ($show_import_icon) {
	echo "<div align=\"right\">";
	echo '<a href="user_import.php?id_session='.$id_session.'&action=export&amp;type=xml">'.Display::return_icon('excel.gif', get_lang('ImportUserListXMLCSV')).'&nbsp;'.get_lang('ImportUserListXMLCSV').'</a>';
	echo "</div><br />";
}

$a_courses = Tracking :: get_courses_followed_by_coach($_user['user_id'], $id_session);
$nb_courses = count($a_courses);

$table = new SortableTable('tracking_list_course', 'count_courses');
$table -> set_header(0, get_lang('CourseTitle'), false, 'align="center"');
$table -> set_header(1, get_lang('NbStudents'), false);
$table -> set_header(2, get_lang('TimeSpentInTheCourse'), false);
$table -> set_header(3, get_lang('AvgStudentsProgress'), false);
$table -> set_header(4, get_lang('AvgCourseScore'), false);
//$table -> set_header(5, get_lang('AvgExercisesScore'), false);// no code for this?
$table -> set_header(5, get_lang('AvgMessages'), false);
$table -> set_header(6, get_lang('AvgAssignments'), false);
$table -> set_header(7, get_lang('Details'), false);

$csv_header[] = array(
	get_lang('CourseTitle', ''),
	get_lang('NbStudents', ''),
	get_lang('TimeSpentInTheCourse', ''),
	get_lang('AvgStudentsProgress', ''),
	get_lang('AvgCourseScore', ''),
	//get_lang('AvgExercisesScore', ''),
	get_lang('AvgMessages', ''),
	get_lang('AvgAssignments', '')
);

if (is_array($a_courses)) {
	foreach ($a_courses as $course_code) {
		$nb_students_in_course = 0;
		$a_students = array();
		$course = CourseManager :: get_course_information($course_code);
		$avg_assignments_in_course = $avg_messages_in_course = $avg_progress_in_course = $avg_score_in_course = $avg_time_spent_in_course = 0;

		// students subscribed to the course throw a session
		if (api_get_setting('use_session_mode') == 'true') {
			$sql = 'SELECT id_user as user_id
					FROM '.$tbl_session_course_user.'
					WHERE course_code="'.Database :: escape_string($course_code).'"
					AND id_session='.$id_session;
			$rs = Database::query($sql, __FILE__, __LINE__);
			
			while ($row = Database::fetch_array($rs)) {
				if (!in_array($row['user_id'], $a_students)) {
					$nb_students_in_course++;

					// tracking datas
					$avg_progress_in_course += Tracking :: get_avg_student_progress ($row['user_id'], $course_code);
					$avg_score_in_course += Tracking :: get_avg_student_score ($row['user_id'], $course_code);
					$avg_time_spent_in_course += Tracking :: get_time_spent_on_the_course ($row['user_id'], $course_code);
					$avg_messages_in_course += Tracking :: count_student_messages ($row['user_id'], $course_code);
					$avg_assignments_in_course += Tracking :: count_student_assignments ($row['user_id'], $course_code);
					$a_students[] = $row['user_id'];
				}
			}
		}
		if ($nb_students_in_course > 0) {
			$avg_time_spent_in_course = api_time_to_hms($avg_time_spent_in_course / $nb_students_in_course);
			$avg_progress_in_course = round($avg_progress_in_course / $nb_students_in_course, 2).'%';
			$avg_score_in_course = round($avg_score_in_course / $nb_students_in_course, 2).'%';
			$avg_messages_in_course = round($avg_messages_in_course / $nb_students_in_course, 2);
			$avg_assignments_in_course = round($avg_assignments_in_course / $nb_students_in_course, 2);
		} else {
			$avg_time_spent_in_course = null;
			$avg_progress_in_course = null;
			$avg_score_in_course = null;
			$avg_messages_in_course = null;
			$avg_assignments_in_course = null;
		}

		$table_row = array();
		$table_row[] = $course['title'];
		$table_row[] = $nb_students_in_course;
		$table_row[] = $avg_time_spent_in_course;
		$table_row[] = is_null($avg_progress_in_course) ? '' : $avg_progress_in_course.'%';
		$table_row[] = is_null($avg_score_in_course) ? '' : $avg_score_in_course.'%';
		$table_row[] = $avg_messages_in_course;
		$table_row[] = $avg_assignments_in_course;
		$table_row[] = '<a href="../tracking/courseLog.php?cidReq='.$course_code.'&studentlist=true&id_session='.$id_session.'"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a>';

		$csv_content[] = array (
			$course['title'],
			$nb_students_in_course,
			$avg_time_spent_in_course,
			is_null($avg_progress_in_course) ? null : $avg_progress_in_course.'%',
			is_null($avg_score_in_course) ? null : $avg_score_in_course.'%',
			$avg_messages_in_course,
			$avg_assignments_in_course,
		);

		$table -> addRow($table_row, 'align="right"');	
	}

	// $csv_content = array_merge($csv_header, $csv_content); // Before this statement you are allowed to sort (in different way) the array $csv_content.
}
$table -> setColAttributes(0, array('align' => 'left'));
$table -> setColAttributes(7, array('align' => 'center'));
$table -> display();

/*
 ==============================================================================
		FOOTER
 ==============================================================================
 */

Display :: display_footer();
