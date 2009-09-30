<?php

/*
 * Created on 28 juil. 2006 by Elixir Interactive http://www.elixir-interactive.com
 */

 // name of the language file that needs to be included
$language_file = array ('registration', 'index', 'tracking');
$cidReset = true;

require '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'tracking.lib.php';
require_once api_get_path(LIBRARY_PATH).'export.lib.inc.php';
require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';


$export_csv = isset($_GET['export']) && $_GET['export'] == 'csv' ? true : false;
if ($export_csv) {
	ob_start();
}
$csv_content = array();

if (isset($_GET['id_coach']) && intval($_GET['id_coach']) != 0) {
	$nameTools = get_lang("CoachStudents");
	$sql = 'SELECT lastname, firstname FROM '.Database::get_main_table(TABLE_MAIN_USER).' WHERE user_id='.intval($_GET['id_coach']);
	$rs = Database::query($sql, __FILE__, __LINE__);
	$coach_name = api_get_person_name(Database::result($rs, 0, 1), Database::result($rs, 0, 0));
	$title = get_lang('Probationers').' - '.$coach_name;
} else {
	$nameTools = get_lang("Students");
	$title = get_lang('Probationers');
}

$this_section = "session_my_space";

api_block_anonymous_users();

$interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('MySpace'));

if (isset($_GET["user_id"]) && $_GET["user_id"] != "" && !isset($_GET["type"])) {
	$interbreadcrumb[] = array ("url" => "teachers.php", "name" => get_lang('Teachers'));
}

if (isset($_GET["user_id"]) && $_GET["user_id"]!="" && isset($_GET["type"]) && $_GET["type"] == "coach") {
 	$interbreadcrumb[] = array ("url" => "coaches.php", "name" => get_lang('Tutors'));
}

$isCoach = api_is_coach();

Display :: display_header($nameTools);

// Database Table Definitions
$tbl_course 				= Database :: get_main_table(TABLE_MAIN_COURSE);
$tbl_user 					= Database :: get_main_table(TABLE_MAIN_USER);
$tbl_course_user 			= Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_session 				= Database :: get_main_table(TABLE_MAIN_SESSION);
$tbl_session_course 		= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_course_user 	= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
$tbl_session_rel_user 		= Database :: get_main_table(TABLE_MAIN_SESSION_USER);

/*
 ===============================================================================
 	FUNCTION
 ===============================================================================
 */

function count_student_coached() {
	global $students;
	return count($students);
}

function sort_users($a, $b) {
	global $tracking_column;
	if ($a[$tracking_column] > $b[$tracking_column]) {
		return 1;
	} else {
		return -1;
	}
}

function rsort_users($a, $b)
{
	global $tracking_column;
	if ($b[$tracking_column] > $a[$tracking_column]) {
		return 1;
	} else {
		return -1;
	}
}

/*
 ===============================================================================
 	MAIN CODE
 ===============================================================================
 */

if ($isCoach || api_is_platform_admin() || $_user['status'] == DRH) {

	if ($export_csv) {
		$is_western_name_order = api_is_western_name_order(PERSON_NAME_DATA_EXPORT);
	} else {
		$is_western_name_order = api_is_western_name_order();
	}
	$sort_by_first_name = api_sort_by_first_name();

	echo '<div align="left" style="float:left"><h4>'.$title.'</h4></div>
		  <div align="right">
			<a href="javascript: void(0);" onclick="javascript: window.print();"><img align="absbottom" src="../img/printmgr.gif">&nbsp;'.get_lang('Print').'</a>
			<a href="'.api_get_self().'?export=csv"><img align="absbottom" src="../img/excel.gif">&nbsp;'.get_lang('ExportAsCSV').'</a>
		  </div><div class="clear"></div>';

	if (isset($_GET['id_coach'])) {
		$coach_id = intval($_GET['id_coach']);
	} else {
		$coach_id = $_user['user_id'];
	}

	if (!isset($_GET['id_session'])) {
		if ($isCoach) {
			$courses = Tracking :: get_courses_followed_by_coach($coach_id);
			$students = Tracking :: get_student_followed_by_coach($coach_id);
		}
		elseif ($_user['status'] == DRH) {
			$students = Tracking :: get_student_followed_by_drh($_user['user_id']);
			$courses_of_the_platform = CourseManager :: get_real_course_list();
			foreach ($courses_of_the_platform as $course) {
				$courses[$course['code']] = $course['code'];
			}
		}
	} else {
		$students = Tracking :: get_student_followed_by_coach_in_a_session($_GET['id_session'], $coach_id);
	}

	$tracking_column = isset($_GET['tracking_column']) ? $_GET['tracking_column'] : ($is_western_name_order xor $sort_by_first_name) ? 1 : 0;
	$tracking_direction = isset($_GET['tracking_direction']) ? $_GET['tracking_direction'] : DESC;

	if (count($students) > 0) {
		$table = new SortableTable('tracking', 'count_student_coached', null, ($is_western_name_order xor $sort_by_first_name) ? 1 : 0);
		if ($is_western_name_order) {
			$table -> set_header(0, get_lang('FirstName'), true, 'align="center');
			$table -> set_header(1, get_lang('LastName'), true, 'align="center');
		} else {
			$table -> set_header(0, get_lang('LastName'), true, 'align="center');
			$table -> set_header(1, get_lang('FirstName'), true, 'align="center');
		}
		$table -> set_header(2, get_lang('Time'), false);
		$table -> set_header(3, get_lang('Progress'), false);
		$table -> set_header(4, get_lang('Score'), false);
		$table -> set_header(5, get_lang('Student_publication'), false);
		$table -> set_header(6, get_lang('Messages'), false);
		$table -> set_header(7, get_lang('FirstLogin'), false);
		$table -> set_header(8, get_lang('LatestLogin'), false);
		$table -> set_header(9, get_lang('Details'), false);

		if ($export_csv) {
			if ($is_western_name_order) {
				$csv_header[] = array (
					get_lang('FirstName', ''),
					get_lang('LastName', ''),
					get_lang('Time', ''),
					get_lang('Progress', ''),
					get_lang('Score', ''),
					get_lang('Student_publication', ''),
					get_lang('Messages', ''),
					get_lang('FirstLogin', ''),
					get_lang('LatestLogin', '')
				);
			} else {
				$csv_header[] = array (
					get_lang('LastName', ''),
					get_lang('FirstName', ''),
					get_lang('Time', ''),
					get_lang('Progress', ''),
					get_lang('Score', ''),
					get_lang('Student_publication', ''),
					get_lang('Messages', ''),
					get_lang('FirstLogin', ''),
					get_lang('LatestLogin', '')
				);
			}
		}

	    $all_datas = array();
		foreach ($students as $student_id) {
			$student_data = UserManager :: get_user_info_by_id($student_id);
			if(isset($_GET['id_session'])) {
				$courses = Tracking :: get_course_list_in_session_from_student($student_id, $_GET['id_session']);
			}

			$avg_time_spent = $avg_student_score = $avg_student_progress = $total_assignments = $total_messages = 0;
			$nb_courses_student = 0;
			foreach ($courses as $course_code) {
				if (CourseManager :: is_user_subscribed_in_course($student_id, $course_code, true)) {
					$avg_time_spent += Tracking :: get_time_spent_on_the_platform($student_id, $course_code);
					$avg_student_score += Tracking :: get_avg_student_score($student_id, $course_code);
					$avg_student_progress += Tracking :: get_avg_student_progress($student_id, $course_code);
					$total_assignments += Tracking :: count_student_assignments($student_id, $course_code);
					$total_messages += Tracking :: count_student_messages($student_id, $course_code);
					$nb_courses_student++;
				}
			}

			if ($nb_courses_student > 0) {
				$avg_time_spent = $avg_time_spent / $nb_courses_student;
				$avg_student_score = $avg_student_score / $nb_courses_student;
				$avg_student_progress = $avg_student_progress / $nb_courses_student;
			} else {
				$avg_time_spent = null;
				$avg_student_score = null;
				$avg_student_progress = null;
			}

			$row = array();
			if ($is_western_name_order) {
				$row[] = $student_data['firstname'];
				$row[] = $student_data['lastname'];
			} else {
				$row[] = $student_data['lastname'];
				$row[] = $student_data['firstname'];
			}
			$row[] = api_time_to_hms($avg_time_spent);
			$row[] = is_null($avg_student_progress) ? null : round($avg_student_progress, 2).'%';
			$row[] = is_null($avg_student_score) ? null : round($avg_student_score, 2).'%';
			$row[] = $total_assignments;
			$row[] = $total_messages;

			$string_date = Tracking :: get_last_connection_date($student_id, true);
			$first_date = Tracking :: get_first_connection_date($student_id);
			$row[] = $first_date;
			$row[] = $string_date;

			if ($export_csv) {
				$csv_content[] = $row;
			}

			if (isset($_GET['id_coach']) && intval($_GET['id_coach']) != 0) {
				$row[] = '<a href="myStudents.php?student='.$student_id.'&id_coach='.$coach_id.'&id_session='.$_GET['id_session'].'"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a>';
			} else {
				$row[] = '<a href="myStudents.php?student='.$student_id.'"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a>';
			}

			$all_datas[] = $row;
		}

		if ($tracking_direction == 'ASC') {
			usort($all_datas, 'rsort_users');
		} else {
			usort($all_datas, 'sort_users');
		}

		if ($export_csv) {
			usort($csv_content, 'sort_users');
			$csv_content = array_merge($csv_header, $csv_content);
		}

		foreach ($all_datas as $row) {
			$table -> addRow($row, 'align="right"');
		}
		$table -> updateColAttributes(0, array('align' => 'left'));
		$table -> updateColAttributes(1, array('align' => 'left'));
		$table -> updateColAttributes(7, array('align' => 'left'));
		$table -> updateColAttributes(8, array('align' => 'left'));
		$table -> setColAttributes(9, array('align' => 'center'));
		$table -> display();
	} else {
		echo get_lang('NoStudent');
	}

	// send the csv file if asked
	if ($export_csv) {
		ob_end_clean();
		Export :: export_table_csv($csv_content, 'reporting_student_list');
	}
}

/*
==============================================================================
		FOOTER
==============================================================================
*/

Display :: display_footer();
