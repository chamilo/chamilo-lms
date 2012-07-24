<?php
/* For licensing terms, see /license.txt */
/*
 * Created on 18 October 2006 by Elixir Interactive http://www.elixir-interactive.com
 */

ob_start();

// name of the language file that needs to be included
$language_file = array ('registration', 'index', 'tracking', 'admin');
$cidReset = true;

require '../inc/global.inc.php';
require_once api_get_path(SYS_CODE_PATH).'mySpace/myspace.lib.php';

$this_section = SECTION_TRACKING;

$nameTools = get_lang('Tutors');

api_block_anonymous_users();
$interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('MySpace'));

if (isset($_GET["id_student"])) {
	$interbreadcrumb[] = array ("url" => "student.php", "name" => get_lang('Students'));
}

Display :: display_header($nameTools);

api_display_tool_title($nameTools);

// Database Table Definitions
$tbl_course 						= Database :: get_main_table(TABLE_MAIN_COURSE);
$tbl_course_user 					= Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_user 							= Database :: get_main_table(TABLE_MAIN_USER);
$tbl_session 						= Database :: get_main_table(TABLE_MAIN_SESSION);
$tbl_session_rel_course 			= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_rel_course_rel_user 	= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
$tbl_session_rel_user 				= Database :: get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_track_login 					= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);


/*
  	FUNCTIONS
  */
 
/*Posible Deprecated*/

function is_coach() {
  	global $tbl_session_course;
	$sql = "SELECT course_code FROM $tbl_session_course WHERE id_coach='".intval($_SESSION["_uid"])."'";
	$result = Database::query($sql);
	if (Database::num_rows($result) > 0) {
		return true;
	}
	return false;
}


/**
 * MAIN PART
 */

if (isset($_POST['export'])) {
	$order_clause = api_is_western_name_order(PERSON_NAME_DATA_EXPORT) ? ' ORDER BY firstname, lastname' : ' ORDER BY lastname, firstname';
} else {
	$order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname' : ' ORDER BY lastname, firstname';
}
if (isset($_GET["id_student"])) {
	$id_student = intval($_GET["id_student"]);
	$sql_coachs = "SELECT DISTINCT srcru.id_user as id_coach" .
		"FROM $tbl_session_rel_course_rel_user as srcru " .
		"WHERE srcru.id_user='$id_student' AND srcru.status=2";
} else {
	if (api_is_platform_admin()) {
		$sql_coachs = "SELECT DISTINCT srcru.id_user as id_coach, user_id, lastname, firstname
			FROM $tbl_user, $tbl_session_rel_course_rel_user srcru
			WHERE srcru.id_user=user_id AND srcru.status=2 ".$order_clause;
	} else {
		$sql_coachs = "SELECT DISTINCT id_user as id_coach, $tbl_user.user_id, lastname, firstname
			FROM $tbl_user as user, $tbl_session_rel_course_user as srcu, $tbl_course_user as course_rel_user
			WHERE course_rel_user.course_code=srcu.course_code AND course_rel_user.status='1' AND course_rel_user.user_id='".intval($_SESSION["_uid"])."'
			AND srcu.id_user=user.user_id AND srcu.status=2 ".$order_clause;
	}
}

$result_coachs = Database::query($sql_coachs);

if (api_is_western_name_order()) {
	echo '<table class="data_table"><tr><th>'.get_lang('FirstName').'</th><th>'.get_lang('LastName').'</th><th>'.get_lang('ConnectionTime').'</th><th>'.get_lang('AdminCourses').'</th><th>'.get_lang('Students').'</th></tr>';
} else {
	echo '<table class="data_table"><tr><th>'.get_lang('LastName').'</th><th>'.get_lang('FirstName').'</th><th>'.get_lang('ConnectionTime').'</th><th>'.get_lang('AdminCourses').'</th><th>'.get_lang('Students').'</th></tr>';
}

if (api_is_western_name_order(PERSON_NAME_DATA_EXPORT)) {
	$header[] = get_lang('FirstName', '');
	$header[] = get_lang('LastName', '');
} else {
	$header[] = get_lang('LastName', '');
	$header[] = get_lang('FirstName', '');
}
$header[] = get_lang('ConnectionTime', '');

if (Database::num_rows($result_coachs) > 0) {
	while ($coachs = Database::fetch_array($result_coachs)) {
		$id_coach = $coachs["id_coach"];

		if (isset($_GET["id_student"])) {
			$sql_infos_coach = "SELECT lastname, firstname FROM $tbl_user WHERE user_id='$id_coach'";
			$result_coachs_infos = Database::query($sql_infos_coach);
			$lastname = Database::result($result_coachs_infos, 0, "lastname");
			$firstname = Database::result($result_coachs_infos, 0, "firstname");
		} else {
			$lastname = $coachs["lastname"];
			$firstname = $coachs["firstname"];
		}

		$sql_connection_time = "SELECT login_date, logout_date FROM $tbl_track_login WHERE login_user_id ='$id_coach' AND logout_date <> 'null'";
		$result_connection_time = Database::query($sql_connection_time);

		$nb_seconds = 0;
		while ($connections = Database::fetch_array($result_connection_time)) {
			$login_date = $connections["login_date"];
			$logout_date = $connections["logout_date"];
			$timestamp_login_date = strtotime($login_date);
			$timestamp_logout_date = strtotime($logout_date);
			$nb_seconds += ($timestamp_logout_date - $timestamp_login_date);
		}

		if ($nb_seconds == 0) {
			$s_connection_time = '';
		} else {
			$s_connection_time = api_time_to_hms($nb_seconds);
		}

		if ($i % 2 == 0) {
			$css_class = "row_odd";
			if ($i % 20 == 0 && $i != 0) {
				if (api_is_western_name_order()) {
					echo '<tr><th>'.get_lang('FirstName').'</th><th>'.get_lang('LastName').'</th><th>'.get_lang('ConnectionTime').'</th><th>'.get_lang('AdminCourses').'</th><th>'.get_lang('Students').'</th></tr>';
				} else {
					echo '<tr><th>'.get_lang('LastName').'</th><th>'.get_lang('FirstName').'</th><th>'.get_lang('ConnectionTime').'</th><th>'.get_lang('AdminCourses').'</th><th>'.get_lang('Students').'</th></tr>';
				}
			}
		} else {
			$css_class = "row_even";
		}

		$i++;

		if (api_is_western_name_order()) {
			echo '<tr class="'.$css_class.'"><td>'.$firstname.'</td><td>'.$lastname.'</td><td>'.$s_connection_time.'</td><td><a href="course.php?type=coach&user_id='.$id_coach.'"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a></td><td><a href="student.php?type=coach&user_id='.$id_coach.'"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a></td></tr>';
		} else {
			echo '<tr class="'.$css_class.'"><td>'.$lastname.'</td><td>'.$firstname.'</td><td>'.$s_connection_time.'</td><td><a href="course.php?type=coach&user_id='.$id_coach.'"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a></td><td><a href="student.php?type=coach&user_id='.$id_coach.'"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a></td></tr>';
		}

		if (api_is_western_name_order(PERSON_NAME_DATA_EXPORT)) {
			$data[$id_coach]["firstname"] = $firstname;
			$data[$id_coach]["lastname"] = $lastname;
		} else {
			$data[$id_coach]["lastname"] = $lastname;
			$data[$id_coach]["firstname"] = $firstname;
		}
		$data[$id_coach]["connection_time"] = $s_connection_time;
	}
} else {
	// No results
	echo '<tr><td colspan="5">'.get_lang("NoResult").'</td></tr>';
}
echo '</table>';

if (isset($_POST['export'])){
	export_csv($header, $data, 'coaches.csv');
}

echo "<br /><br />";
echo "<form method='post' action='coaches.php'><button type='submit' class='save' name='export' value='".get_lang('exportExcel')."'>".get_lang('exportExcel')."</button><form>";
Display::display_footer();