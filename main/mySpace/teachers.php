<?php
/* For licensing terms, see /dokeos_license.txt */
/*
 * Created on 18 October 2006 by Elixir Interactive http://www.elixir-interactive.com
 */

ob_start();

// names of the language file that needs to be included
$language_file = array ('registration', 'index', 'trad4all', 'tracking', 'admin');
$cidReset = true;

require_once '../inc/global.inc.php';
require_once api_get_path(SYS_CODE_PATH).'mySpace/myspace.lib.php';

$this_section = "session_my_space";

$nameTools = get_lang('Teachers');

api_block_anonymous_users();
$interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('MySpace'));
Display :: display_header($nameTools);

api_display_tool_title($nameTools);

// Database Table Definitions
$tbl_course 			= Database :: get_main_table(TABLE_MAIN_COURSE);
$tbl_user 				= Database :: get_main_table(TABLE_MAIN_USER);
$tbl_session 			= Database :: get_main_table(TABLE_MAIN_SESSION);
$tbl_session_course 	= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_rel_user 	= Database :: get_main_table(TABLE_MAIN_SESSION_USER);


/**
 * MAIN PART
 */

if (isset($_POST['export'])) {
	$is_western_name_order = api_is_western_name_order(PERSON_NAME_DATA_EXPORT);
} else {
	$is_western_name_order = api_is_western_name_order();
}
$sort_by_first_name = api_sort_by_first_name();

$order_clause = $sort_by_first_name ? ' ORDER BY firstname, lastname' : ' ORDER BY lastname, firstname';
if (isset($_GET["teacher_id"]) && $_GET["teacher_id"] != 0) {
	$teacher_id = intval($_GET["teacher_id"]);
	$sql_formateurs = "SELECT user_id,lastname,firstname,email
		FROM $tbl_user
		WHERE user_id='$teacher_id'".$order_clause;
} else {
	$sql_formateurs = "SELECT user_id,lastname,firstname,email
		FROM $tbl_user
		WHERE status = 1".$order_clause;
}

$result_formateurs = Database::query($sql_formateurs, __FILE__, __LINE__);

if ($is_western_name_order) {
	echo '<table class="data_table"><tr><th>'.get_lang('FirstName').'</th><th>'.get_lang('LastName').'</th><th>'.get_lang('Email').'</th><th>'.get_lang('AdminCourses').'</th><th>'.get_lang('Students').'</th></tr>';
} else {
	echo '<table class="data_table"><tr><th>'.get_lang('LastName').'</th><th>'.get_lang('FirstName').'</th><th>'.get_lang('Email').'</th><th>'.get_lang('AdminCourses').'</th><th>'.get_lang('Students').'</th></tr>';
}

if ($is_western_name_order) {
	$header[] = get_lang('FirstName', '');
	$header[] = get_lang('LastName', '');
} else {
	$header[] = get_lang('LastName', '');
	$header[] = get_lang('FirstName', '');
}
$header[] = get_lang('Email', '');

$data = array();

if (Database::num_rows($result_formateurs) > 0) {

	$i = 1;
	while ($formateurs = Database::fetch_array($result_formateurs)) {

		$user_id = $formateurs["user_id"];
		$lastname = $formateurs["lastname"];
		$firstname = $formateurs["firstname"];
		$email = $formateurs["email"];

		if ($i % 2 == 0) {
			$css_class = "row_odd";

			if ($i % 20 == 0 && $i != 0) {
				if ($is_western_name_order) {
					echo '<tr><th>'.get_lang('FirstName').'</th><th>'.get_lang('LastName').'</th><th>'.get_lang('Email').'</th><th>'.get_lang('AdminCourses').'</th><th>'.get_lang('Students').'</th></tr>';
				} else {
					echo '<tr><th>'.get_lang('LastName').'</th><th>'.get_lang('FirstName').'</th><th>'.get_lang('Email').'</th><th>'.get_lang('AdminCourses').'</th><th>'.get_lang('Students').'</th></tr>';
				}
			}
		} else {
			$css_class = "row_even";
		}

		$i++;

		if ($is_western_name_order) {
			$data[$user_id]["firstname"] = $firstname;
			$data[$user_id]["lastname"] = $lastname;
		} else {
			$data[$user_id]["lastname"] = $lastname;
			$data[$user_id]["firstname"] = $firstname;
		}
		$data[$user_id]["email"] = $email;

		if ($is_western_name_order) {
			echo '<tr class="'.$css_class.'"><td>'.$firstname.'</td><td>'.$lastname.'</td><td><a href="mailto:'.$email.'">'.$email.'</a></td><td><a href="course.php?user_id='.$user_id.'"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a></td><td><a href="student.php?user_id='.$user_id.'"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a></td></tr>';
		} else {
			echo '<tr class="'.$css_class.'"><td>'.$lastname.'</td><td>'.$firstname.'</td><td><a href="mailto:'.$email.'">'.$email.'</a></td><td><a href="course.php?user_id='.$user_id.'"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a></td><td><a href="student.php?user_id='.$user_id.'"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a></td></tr>';
		}
	}
} else {
	// No results
	echo '<tr><td colspan="5" "align=center">'.get_lang("NoResults").'</td></tr>';
}
echo '</table>';

if (isset($_POST['export'])) {
	export_csv($header, $data, 'teachers.csv');
}

echo "<br /><br />";
echo "<form method='post' action='teachers.php'><input type='submit' name='export' value='".get_lang('exportExcel')."'/><form>";

/*
==============================================================================
	FOOTER
==============================================================================
*/

Display::display_footer();
