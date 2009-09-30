<?php
/*
 * Created on 28 juil. 2006 by Elixir Interactive http://www.elixir-interactive.com
 */

// TODO: Is this file used?

$nameTools = 'Reussite';
// name of the language file that needs to be included
$language_file = array ('registration', 'index', 'trad4all', 'tracking');
$cidReset = true;

require '../inc/global.inc.php';

$this_section = "session_my_space";

api_block_anonymous_users();
$interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('MySpace'));
Display :: display_header($nameTools);

// Database Table Definitions
$tbl_course 		= Database :: get_main_table(TABLE_MAIN_COURSE);
$tbl_user 			= Database :: get_main_table(TABLE_MAIN_USER);
$tbl_session_course = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session 		= Database :: get_main_table(TABLE_MAIN_SESSION);
$tbl_track_exercice = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);

/*
===============================================================================
	MAIN CODE
===============================================================================
*/
if (!empty($_GET['session'])) {
	$sql_session = "SELECT name,id
		FROM $tbl_session
		ORDER BY name ASC";
	$result_session = Database::query($sql_session, __FILE__, __LINE__);

	echo "<a href='".api_get_self()."'>".get_lang('MoyCourse')."</a>";
	echo "<br /><br />";

	if (Database::num_rows($result_session) > 0) {
		echo '<table class="data_table"><tr><th>'.get_lang('Session').'</th><th>'.get_lang('MoyenneTest').'</th><th>'.get_lang('MoyenneExamen').'</th></tr>';
		while ($session = Database::fetch_array($result_session)) {
			$sql_course = "SELECT title,code
				FROM $tbl_course as course
				INNER JOIN $tbl_session_course AS rel_course
				ON course.code = rel_course.course_code
				AND rel_course.id_session = ".$session['id']."
				ORDER BY title ASC";

			$result_course = Database::query($sql_course, __FILE__, __LINE__);
			$total_result = 0;
			$total_weighting = 0;
			while ($course = Database::fetch_array($result_course)) {
				$sql_moy_test = "SELECT exe_result,exe_weighting
					FROM $tbl_track_exercice
					WHERE exe_cours_id = '".$course['code']."'";
				$result_moy_test = Database::query($sql_moy_test);
				$result = 0;
				$weighting = 0;
				while ($moy_test = Database::fetch_array($result_moy_test)) {
					$result = $result + $moy_test['exe_result'];
					$weighting = $weighting + $moy_test['exe_weighting'];
				}
				$total_result = $total_result + $result;
				$total_weighting = $total_weighting + $weighting;
			}
			if ($total_weighting != 0) {
				$moyenne_test = round(($total_result * 100) / $total_weighting);
			} else {
				$moyenne_test = null;
			}

			echo '<tr><td>'.$session['name'].'</td><td align="center">'.(is_null($moyenne_test) ? '' : $moyenne_test.'%').'</td><td> </td></tr>';
		}
		echo '</table>';
	} else {
		echo get_lang('NoSession');
	}
} else {
	$sql_course = "SELECT 	title,code
		FROM $tbl_course as course
		ORDER BY title ASC";

	$result_course = Database::query($sql_course, __FILE__, __LINE__);

	echo "<a href='".api_get_self()."?session=true'>".get_lang('MoySession')."</a>";
	echo "<br /><br />";
	if (Database::num_rows($result_course) > 0) {
		echo '<table class="data_table"><tr><th>'.get_lang('Course').'</th><th>'.get_lang('MoyenneTest').'</th><th>'.get_lang('MoyenneExamen').'</th></tr>';
		while ($course= Database::fetch_array($result_course)) {
			$sql_moy_test = "SELECT exe_result,exe_weighting
				FROM $tbl_track_exercice
				WHERE exe_cours_id = '".$course['code']."'";
			$result_moy_test = Database::query($sql_moy_test, __FILE__, __LINE__);
			$result = 0;
			$weighting = 0;
			while ($moy_test = Database::fetch_array($result_moy_test)) {
				$result = $result + $moy_test['exe_result'];
				$weighting = $weighting + $moy_test['exe_weighting'];
			}
			if ($weighting != 0) {
				$moyenne_test = round(($result * 100) / $weighting);
			} else {
				$moyenne_test = null;
			}
			echo '<tr><td>'.$course['title'].'</td><td align="center">'.(is_null($moyenne_test) ? '' : $moyenne_test.'%').'</td><td> </td></tr>';
		}
		echo '</table>';
	} else {
		echo get_lang('NoCourse');
	}
}
/*
==============================================================================
		FOOTER
==============================================================================
*/

Display :: display_footer();
