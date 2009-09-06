<?php

/*
 * Created on 18 October 2006 by Elixir Interactive http://www.elixir-interactive.com
 */
 
ob_start(); 

// names of the language file that needs to be included 
$language_file = array ('registration', 'index','trad4all', 'tracking', 'admin');
$cidReset = true;
require '../inc/global.inc.php';

$this_section = "session_my_space";

$nameTools= get_lang('Teachers');

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


/*
===============================================================================
	FUNCTION
===============================================================================  
*/

function exportCsv($a_header, $a_data) {
 	global $archiveDirName;

	$fileName = 'teachers.csv';
	$archivePath = api_get_path(SYS_ARCHIVE_PATH);
	$archiveURL = api_get_path(WEB_CODE_PATH).'course_info/download.php?archive=';

	if (!$open = fopen($archivePath.$fileName, 'w+')) {
		$message = get_lang('noOpen');
	} else {
		$info = '';

		foreach ($a_header as $header) {
			$info .= $header.';';
		}
		$info .= "\r\n";

		foreach ($a_data as $data) {
			foreach ($data as $infos) {
				$info .= $infos.';';
			}
			$info .= "\r\n";
		}

		fwrite($open,$info);
		fclose($open);
		$perm = api_get_setting('permissions_for_new_files');
		$perm = octdec(!empty($perm)?$perm:'0660');
		chmod($fileName,$perm);

		header("Location:".$archiveURL.$fileName);
	}
	return $message;
}


/**
 * MAIN PART
 */


if (isset($_GET["teacher_id"]) && $_GET["teacher_id"] != 0) {
	$i_teacher_id=$_GET["teacher_id"];
	$sqlFormateurs = "SELECT user_id,lastname,firstname,email
		FROM $tbl_user
		WHERE user_id='$i_teacher_id' 
		ORDER BY lastname ASC";
} else {
	$sqlFormateurs = "SELECT user_id,lastname,firstname,email
		FROM $tbl_user
		WHERE status = 1
		ORDER BY lastname ASC";
}

$resultFormateurs = Database::query($sqlFormateurs, __FILE__, __LINE__);

echo '<table class="data_table"><tr><th>'.get_lang('FirstName').'</th><th>'.get_lang('LastName').'</th><th>'.get_lang('Email').'</th><th>'.get_lang('AdminCourses').'</th><th>'.get_lang('Students').'</th></tr>';

$a_header[] = get_lang('FirstName');
$a_header[] = get_lang('LastName');
$a_header[] = get_lang('Email');

$a_data = array();

if (Database::num_rows($resultFormateurs) > 0) {

	$i = 1;
	while ($a_formateurs = Database::fetch_array($resultFormateurs)) {

		$i_user_id = $a_formateurs["user_id"];
		$s_lastname = $a_formateurs["lastname"];
		$s_firstname = $a_formateurs["firstname"];
		$s_email = $a_formateurs["email"];

		if ($i%2 == 0) {
			$s_css_class = "row_odd";
			
			if ($i%20 == 0 && $i != 0){
				echo '<tr><th>'.get_lang('FirstName').'</th><th>'.get_lang('LastName').'</th><th>'.get_lang('Email').'</th><th>'.get_lang('AdminCourses').'</th><th>'.get_lang('Students').'</th></tr>';
			}
		} else {
			$s_css_class = "row_even";
		}

		$i++;

		$a_data[$i_user_id]["firstname"]=$s_firstname;
		$a_data[$i_user_id]["lastname"]=$s_lastname;
		$a_data[$i_user_id]["email"]=$s_email;

		echo '<tr class="'.$s_css_class.'"><td>'.$s_firstname.'</td><td>'.$s_lastname.'</td><td><a href="mailto:'.$s_email.'">'.$s_email.'</a></td><td><a href="course.php?user_id='.$i_user_id.'"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a></td><td><a href="student.php?user_id='.$i_user_id.'"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a></td></tr>';
	}
}

//No results
else {
	echo '<tr><td colspan="5" "align=center">'.get_lang("NoResults").'</td></tr>';
}
echo '</table>';

if (isset($_POST['export'])) {
	exportCsv($a_header,$a_data);
}

echo "<br /><br />";
echo "<form method='post' action='teachers.php'><input type='submit' name='export' value='".get_lang('exportExcel')."'/><form>";

/*
==============================================================================
	FOOTER
==============================================================================
*/

Display::display_footer();
