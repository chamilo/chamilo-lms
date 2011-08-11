<?php
/* For licensing terms, see /license.txt */
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
require_once api_get_path(LIBRARY_PATH).'thematic.lib.php';

$this_section = SECTION_TRACKING;
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
		$rs = Database::query($sql);
		if (Database::result($rs, 0, 0) != $_user['user_id']) {
			api_not_allowed(true);
		} else {
			$show_import_icon=true;
		}
	}
}

Display :: display_header($nameTools);

$a_courses = array();
if (api_is_drh() || api_is_session_admin() || api_is_platform_admin()) {

	$title = '';
	if (empty($id_session)) {
		if (isset($_GET['user_id'])) {
			$user_id = intval($_GET['user_id']);
			$user_info = api_get_user_info($user_id);
			$title = get_lang('AssignedCoursesTo').' '.api_get_person_name($user_info['firstname'], $user_info['lastname']);
			$courses  = CourseManager::get_course_list_of_user_as_course_admin($user_id);
		} else {
			$title = get_lang('YourCourseList');
			$courses = CourseManager::get_courses_followed_by_drh($_user['user_id']);
		}
	} else {
		$session_name = api_get_session_name($id_session);
		$title = api_htmlentities($session_name,ENT_QUOTES,$charset).' : '.get_lang('CourseListInSession');
		$courses = Tracking::get_courses_list_from_session($id_session);
	}

	$a_courses = array_keys($courses);

	if (!api_is_session_admin()) {
		$menu_items[] = '<a href="index.php?view=drh_students&amp;display=yourstudents">'.get_lang('Students').'</a>';
		$menu_items[] = '<a href="teachers.php">'.get_lang('Teachers').'</a>';
		if (empty($_GET['user_id']) && empty($id_session)) {
			$menu_items[] = get_lang('Courses');
		} else {
			$menu_items[] = '<a href="course.php">'.get_lang('Courses').'</a>';
		}
		$menu_items[] = '<a href="session.php">'.get_lang('Sessions').'</a>';
	}

	echo '<div class="actions-title" style ="font-size:10pt;">';
	$nb_menu_items = count($menu_items);
	if ($nb_menu_items > 1) {
		foreach ($menu_items as $key => $item) {
			echo $item;
			if ($key != $nb_menu_items - 1) {
				echo '&nbsp;|&nbsp;';
			}
		}
	}
	if (count($a_courses) > 0) {
		echo '&nbsp;&nbsp;<a href="javascript: void(0);" onclick="javascript: window.print()"><img align="absbottom" src="../img/printmgr.gif">&nbsp;'.get_lang('Print').'</a> ';
	}
	echo '</div>';
	echo '<h4>'.$title.'</h4>';
}

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

if (!api_is_drh() && !api_is_session_admin() && !api_is_platform_admin()) {
	/*if (api_is_platform_admin()) {
		if (empty($id_session)) {
			$courses = CourseManager::get_real_course_list();
		} else {
			$courses = Tracking::get_courses_list_from_session($id_session);
		}
	} else {*/
		$courses = Tracking::get_courses_followed_by_coach($_user['user_id'], $id_session);
	//}
	$a_courses = array_keys($courses);
}

$nb_courses = count($a_courses);

$table = new SortableTable('tracking_list_course', 'count_courses');
$table -> set_header(0, get_lang('CourseTitle'), false, 'align="center"');
$table -> set_header(1, get_lang('NbStudents'), false);
$table -> set_header(2, get_lang('TimeSpentInTheCourse').Display :: return_icon('info3.gif', get_lang('TimeOfActiveByTraining'), array('align' => 'absmiddle', 'hspace' => '3px')), false);
$table -> set_header(3, get_lang('ThematicAdvance'), false);
$table -> set_header(4, get_lang('AvgStudentsProgress').Display :: return_icon('info3.gif', get_lang('AvgAllUsersInAllCourses'), array('align' => 'absmiddle', 'hspace' => '3px')), false);
$table -> set_header(5, get_lang('AvgCourseScore').Display :: return_icon('info3.gif', get_lang('AvgAllUsersInAllCourses'), array('align' => 'absmiddle', 'hspace' => '3px')), false);
//$table -> set_header(5, get_lang('AvgExercisesScore'), false);// no code for this?
$table -> set_header(6, get_lang('AvgMessages'), false);
$table -> set_header(7, get_lang('AvgAssignments'), false);
$table -> set_header(8, get_lang('Details'), false);

$csv_header[] = array(
	get_lang('CourseTitle', ''),
	get_lang('NbStudents', ''),
	get_lang('TimeSpentInTheCourse', ''),
	get_lang('ThematicAdvance', ''),
	get_lang('AvgStudentsProgress', ''),
	get_lang('AvgCourseScore', ''),
	//get_lang('AvgExercisesScore', ''),
	get_lang('AvgMessages', ''),
	get_lang('AvgAssignments', '')
);

if (is_array($a_courses)) {
	foreach ($a_courses as $course_code) {
		$nb_students_in_course = 0;
		$course = CourseManager :: get_course_information($course_code);
		$avg_assignments_in_course = $avg_messages_in_course = $avg_progress_in_course = $avg_score_in_course = $avg_time_spent_in_course = 0;

		// students directly subscribed to the course
		if (empty($id_session)) {
			$sql = "SELECT user_id FROM $tbl_user_course as course_rel_user WHERE course_rel_user.status='5' AND course_rel_user.course_code='$course_code'";
		} else {
			$sql = "SELECT id_user as user_id FROM $tbl_session_course_user srcu WHERE  srcu. course_code='$course_code' AND id_session = '$id_session' AND srcu.status<>2";
		}

		$rs = Database::query($sql);
		$users = array();
		while ($row = Database::fetch_array($rs)) { $users[] = $row['user_id']; }

		if (count($users) > 0) {
			$nb_students_in_course = count($users);
			// tracking datas
			$avg_progress_in_course = Tracking :: get_avg_student_progress ($users, $course_code, array(), $id_session);
			$avg_score_in_course = Tracking :: get_avg_student_score ($users, $course_code, array(), $id_session);
			$avg_time_spent_in_course = Tracking :: get_time_spent_on_the_course ($users, $course_code, $id_session);
			$messages_in_course = Tracking :: count_student_messages ($users, $course_code, $id_session);
			$assignments_in_course = Tracking :: count_student_assignments ($users, $course_code, $id_session);

			$avg_time_spent_in_course = api_time_to_hms($avg_time_spent_in_course / $nb_students_in_course);
			$avg_progress_in_course = round($avg_progress_in_course / $nb_students_in_course, 2);
			
			if (is_numeric($avg_score_in_course)) {
				$avg_score_in_course = round($avg_score_in_course / $nb_students_in_course, 2).'%';
			}

		} else {
			$avg_time_spent_in_course = null;
			$avg_progress_in_course = null;
			$avg_score_in_course = null;
			$messages_in_course = null;
			$assignments_in_course = null;
		}

		$tematic_advance_progress = 0;
		$thematic = new Thematic();
		$tematic_advance = $thematic->get_total_average_of_thematic_advances($course_code, $id_session);		

		if (!empty($tematic_advance)) {
			$tematic_advance_csv = $tematic_advance_progress.'%';
			$tematic_advance_progress = '<a title="'.get_lang('GoToThematicAdvance').'" href="'.api_get_path(WEB_CODE_PATH).'course_progress/index.php?cidReq='.$course_code.'&id_session='.$id_session.'">'.$tematic_advance.'%</a>';
		} else {
			$tematic_advance_progress = '-';
		}

		$table_row = array();
		$table_row[] = $course['title'];
		$table_row[] = $nb_students_in_course;
		$table_row[] = is_null($avg_time_spent_in_course)?'-':$avg_time_spent_in_course;
		$table_row[] = $tematic_advance_progress;
		$table_row[] = is_null($avg_progress_in_course) ? '-' : $avg_progress_in_course.'%';
		$table_row[] = is_null($avg_score_in_course) ? '-' : $avg_score_in_course;
		$table_row[] = is_null($messages_in_course)?'-':$messages_in_course;
		$table_row[] = is_null($assignments_in_course)?'-':$assignments_in_course;
		$table_row[] = '<a href="../tracking/courseLog.php?cidReq='.$course_code.'&studentlist=true&id_session='.$id_session.'"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a>';

		$csv_content[] = array (
			$course['title'],
			$nb_students_in_course,
			$avg_time_spent_in_course,
			$tematic_advance_csv,
			is_null($avg_progress_in_course) ? null : $avg_progress_in_course.'%',
			is_null($avg_score_in_course) ? null : $avg_score_in_course,
			$messages_in_course,
			$assignments_in_course,
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
