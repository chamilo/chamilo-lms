<?php

/* For licensing terms, see /license.txt */
/**
 * Progress report.
 *
 * @deprecated seems there's no link to this page
 * Created on 28 juil. 2006 by Elixir Interactive http://www.elixir-interactive.com
 */

// TODO: This file seems to be unfinished and unused.
require_once __DIR__.'/../inc/global.inc.php';

$nameTools = get_lang('Progression');

$cidReset = true;

$this_section = SECTION_TRACKING;

api_block_anonymous_users();
$interbreadcrumb[] = ["url" => "index.php", "name" => get_lang('MySpace')];
Display::display_header($nameTools);

// Database Table Definitions
$tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_user = Database::get_main_table(TABLE_MAIN_USER);
$tbl_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_track_exercice = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);

/*
    MAIN CODE
*/
$sql_course = "SELECT title, code, id
               FROM $tbl_course as course
               ORDER BY title ASC";
$result_course = Database::query($sql_course);

if (Database::num_rows($result_course) > 0) {
    if (isset($_POST['export'])) {
        $export_result = export_csv($header, $data, 'test.csv'); // TODO: There is no data for exporting yet.
        echo Display::return_message($export_result, 'error');
    }
    echo '<table class="table table-hover table-striped data_table">
            <tr>
                <th>'.get_lang('Course').'</th>
                <th>'.get_lang('TempsFrequentation').'</th>
                <th>'.get_lang('Progression').'</th>
                <th>'.get_lang('MoyenneTest').'</th>
            </tr>';
    $header = [get_lang('Course'), get_lang('TempsFrequentation'), get_lang('Progression'), get_lang('MoyenneTest')];
    while ($a_course = Database::fetch_array($result_course)) {
        // TODO: This query is to be checked, there are no HotPotatoes tests results.
        $sql_moy_test = "SELECT exe_result,exe_weighting
			FROM $tbl_track_exercice
			WHERE c_id = ".$a_course['id'];
        $result_moy_test = Database::query($sql_moy_test);
        $result = 0;
        $weighting = 0;
        while ($moy_test = Database::fetch_array($result_moy_test)) {
            $result = $result + $moy_test['exe_result'];
            $weighting = $weighting + $moy_test['exe_weighting'];
        }
        if (0 != $weighting) {
            $moyenne_test = round(($result * 100) / $weighting);
        } else {
            $moyenne_test = null;
        }
        echo '<tr><td>'.$a_course['title'].'</td><td> </td><td> </td><td>'.(is_null($moyenne_test) ? '' : $moyenne_test.'%').'</td> </tr>';
    }
    echo '</table>';
    echo "<br /><br />";
    echo "<form method='post'><input type='submit' name='export' value='".get_lang('ExportExcel')."'/><form>";
} else {
    echo get_lang('NoCourse');
}

Display::display_footer();
