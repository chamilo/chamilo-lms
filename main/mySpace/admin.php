<?php

/*
 * Created on 18 October 2006 by Elixir Interactive http://www.elixir-interactive.com
 */

ob_start();

// name of the language file that needs to be included 
$language_file = array ('registration', 'index','trad4all', 'tracking');
$cidReset = true;
require '../inc/global.inc.php';

$this_section = "session_my_space";

$nameTools = get_lang('Administrators');

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
$tbl_admin				= Database :: get_main_table(TABLE_MAIN_ADMIN);


/*
 ===============================================================================
 	FUNCTION
 ===============================================================================  
 */
 
function exportCsv($a_header, $a_data) {
 	global $archiveDirName;

	$fileName = 'administrators.csv';
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

		fwrite($open, $info);
		fclose($open);
		$perm = api_get_setting('permissions_for_new_files');
		$perm = octdec(!empty($perm) ? $perm : '0660');
		chmod($fileName, $perm);

		header("Location:".$archiveURL.$fileName);
	}
	return $message;
}


/**
 * MAIN PART
 */

/*
 * liste nominative avec coordonn�es et lien vers les cours et
les stagiaires dont il est le
responsable. 
 */

if (isset($_POST['export'])) {
	$order_clause = api_is_western_name_order(PERSON_NAME_DATA_EXPORT) ? ' ORDER BY firstname, lastname' : ' ORDER BY lastname, firstname';
} else {
	$order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname' : ' ORDER BY lastname, firstname';
}
$sqlAdmins = "SELECT user.user_id,lastname,firstname,email
				FROM $tbl_user as user, $tbl_admin as admin
				WHERE admin.user_id=user.user_id".$order_clause;
$resultAdmins = api_sql_query($sqlAdmins, __FILE__, __LINE__);

if (api_is_western_name_order()) {
	echo '<table class="data_table"><tr><th>'.get_lang('FirstName').'</th><th>'.get_lang('LastName').'</th><th>'.get_lang('Email').'</th></tr>';
} else {
	echo '<table class="data_table"><tr><th>'.get_lang('LastName').'</th><th>'.get_lang('FirstName').'</th><th>'.get_lang('Email').'</th></tr>';
}

if (api_is_western_name_order(PERSON_NAME_DATA_EXPORT)) {
	$a_header[] = get_lang('FirstName');
	$a_header[] = get_lang('LastName');
	$a_header[] = get_lang('Email');
} else {
	$a_header[] = get_lang('LastName');
	$a_header[] = get_lang('FirstName');
	$a_header[] = get_lang('Email');
}

if (mysql_num_rows($resultAdmins) > 0 ) {
	while($a_admins = mysql_fetch_array($resultAdmins)){

		$i_user_id = $a_admins["user_id"];
		$s_lastname = $a_admins["lastname"];
		$s_firstname = $a_admins["firstname"];
		$s_email = $a_admins["email"];

		if ($i%2 == 0) {
			$s_css_class = "row_odd";
			if ($i%20 == 0 && $i!=0) {
				if (api_is_western_name_order()) {
					echo '<tr><th>'.get_lang('FirstName').'</th><th>'.get_lang('LastName').'</th><th>'.get_lang('Email').'</th></tr>';
				} else {
					echo '<tr><th>'.get_lang('LastName').'</th><th>'.get_lang('FirstName').'</th><th>'.get_lang('Email').'</th></tr>';
				}
			}
		} else {
			$s_css_class = "row_even";
		}

		$i++;

		if (api_is_western_name_order()) {
			echo "<tr class=".$s_css_class."><td>$s_firstname</td><td>$s_lastname</td><td><a href='mailto:".$s_email."'>$s_email</a></td></tr>";
		} else {
			echo "<tr class=".$s_css_class."><td>$s_lastname</td><td>$s_firstname</td><td><a href='mailto:".$s_email."'>$s_email</a></td></tr>";
		}

		if (api_is_western_name_order(PERSON_NAME_DATA_EXPORT)) {
			$a_data[$i_user_id]["firstname"] = $s_firstname;
			$a_data[$i_user_id]["lastname"] = $s_lastname;
			$a_data[$i_user_id]["email"] = $s_email;
		} else {
			$a_data[$i_user_id]["lastname"] = $s_lastname;
			$a_data[$i_user_id]["firstname"] = $s_firstname;
			$a_data[$i_user_id]["email"] = $s_email;
		}
	}
}

//No results
else {
	echo '<tr><td colspan="3" "align=center">'.get_lang("NoResults").'</td></tr>';
}
echo '</table>';

if (isset($_POST['export'])) {
	exportCsv($a_header, $a_data);
}

echo "<br /><br />";
echo "<form method='post' action='admin.php'><button type='submit' class='save' name='export' value='".get_lang('exportExcel')."'>".get_lang('exportExcel')."</button><form>";

/*
==============================================================================
	FOOTER
==============================================================================
*/

Display::display_footer();
