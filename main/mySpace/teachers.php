<?php
/* For licensing terms, see /license.txt */

ob_start();

// names of the language file that needs to be included
$language_file = array ('registration', 'index', 'trad4all', 'tracking', 'admin');
$cidReset = true;

require_once '../inc/global.inc.php';
require_once 'myspace.lib.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'tracking.lib.php';

$this_section = SECTION_TRACKING;

$nameTools = get_lang('Teachers');

api_block_anonymous_users();
$interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('MySpace'));
Display :: display_header($nameTools);

$formateurs = array();
if (api_is_drh() || api_is_platform_admin()) {

	// followed teachers by drh
	$formateurs = UserManager::get_users_followed_by_drh($_user['user_id'], COURSEMANAGER);
	 
	$menu_items[] = '<a href="index.php?view=drh_students&amp;display=yourstudents">'.get_lang('Students').'</a>';
	$menu_items[] = get_lang('Trainers');
	$menu_items[] = '<a href="course.php">'.get_lang('Courses').'</a>';
	$menu_items[] = '<a href="session.php">'.get_lang('Sessions').'</a>';
		
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
	if (count($formateurs) > 0) {
		echo '&nbsp;&nbsp;<a href="javascript: void(0);" onclick="javascript: window.print()"><img align="absbottom" src="../img/printmgr.gif">&nbsp;'.get_lang('Print').'</a> ';
		echo '<a href="'.api_get_self().'?export=xls"><img align="absbottom" src="../img/csv.gif">&nbsp;'.get_lang('ExportAsCSV').'</a>';	
	}
	echo '</div>';
	echo '<h4>'.get_lang('YourTeachers').'</h4>';
	echo '<br />';
}

if (!api_is_drh()) {
	api_display_tool_title($nameTools);
}

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

if (!api_is_drh() && !api_is_platform_admin()) {
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
	
	$result_formateurs = Database::query($sql_formateurs);
	if (Database::num_rows($result_formateurs) > 0) {
		while ($row_formateurs = Database::fetch_array($result_formateurs)) {
			$formateurs[] = $row_formateurs;	
		}
	}
}

$a_last_week = get_last_week();
$last_week 	 = date('Y-m-d',$a_last_week[0]).' '.get_lang('To').' '.date('Y-m-d', $a_last_week[6]);

if ($is_western_name_order) {
	echo '<table class="data_table"><tr><th>'.get_lang('FirstName').'</th><th>'.get_lang('LastName').'</th><th>'.get_lang('TimeSpentLastWeek').'<br />'.$last_week.'</th><th>'.get_lang('Email').'</th><th>'.get_lang('AdminCourses').'</th><th>'.get_lang('Students').'</th></tr>';
} else {
	echo '<table class="data_table"><tr><th>'.get_lang('LastName').'</th><th>'.get_lang('FirstName').'</th><th>'.get_lang('TimeSpentLastWeek').'<br />'.$last_week.'</th><th>'.get_lang('Email').'</th><th>'.get_lang('AdminCourses').'</th><th>'.get_lang('Students').'</th></tr>';
}

if ($is_western_name_order) {
	$header[] = get_lang('FirstName');
	$header[] = get_lang('LastName');
} else {
	$header[] = get_lang('LastName');
	$header[] = get_lang('FirstName');
}

$header[] = get_lang('TimeSpentLastWeek');
$header[] = get_lang('Email');

$data = array();

if (count($formateurs) > 0) {

	$i = 1;
	foreach ($formateurs as $formateur) {
		$user_id = $formateur["user_id"];
		$lastname = $formateur["lastname"];
		$firstname = $formateur["firstname"];
		$email = $formateur["email"];

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
		
		$time_on_platform = api_time_to_hms(Tracking :: get_time_spent_on_the_platform($user_id,true));
		$data[$user_id]["timespentlastweek"] = $time_on_platform;
		$data[$user_id]["email"] = $email;

		if ($is_western_name_order) {
			echo '<tr class="'.$css_class.'"><td>'.$firstname.'</td><td>'.$lastname.'</td><td align="right">'.$time_on_platform.'</td><td align="right"><a href="mailto:'.$email.'">'.$email.'</a></td><td align="right"><a href="course.php?user_id='.$user_id.'"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a></td><td align="right"><a href="student.php?user_id='.$user_id.'&amp;display=yourstudents"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a></td></tr>';
		} else {
			echo '<tr class="'.$css_class.'"><td>'.$lastname.'</td><td>'.$firstname.'</td><td align="right">'.$time_on_platform.'</td><td align="right"><a href="mailto:'.$email.'">'.$email.'</a></td><td align="right"><a href="course.php?user_id='.$user_id.'"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a></td><td align="right"><a href="student.php?user_id='.$user_id.'&amp;display=yourstudents"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a></td></tr>';
		}
	}
} else {
	// No results
	echo '<tr><td colspan="6" "align=center">'.get_lang("NoResults").'</td></tr>';
}
echo '</table>';

if (isset($_POST['export']) || (api_is_drh() && isset($_GET['export']))) {
	MySpace::export_csv($header, $data, 'teachers.csv');
}

echo "<br /><br />";
if (!api_is_drh()) {
	echo "<form method='post' action='teachers.php'><input type='submit' name='export' value='".get_lang('exportExcel')."'/><form>";
}

/*
==============================================================================
	FOOTER
==============================================================================
*/

Display::display_footer();
