<?php
/* For licensing terms, see /license.txt */

require_once '../inc/global.inc.php';

$allow = api_get_configuration_value('extra');
if (empty($allow)) {
    exit;
}

$table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
$ex_user_id = isset($_GET['ex_user_id']) ? (int) $_GET['ex_user_id'] : '';
$mod_no = isset($_GET['mod_no']) ? Database::escape_string($_GET['mod_no']) : '';
$score_ex = isset($_GET['score_ex']) ? Database::escape_string($_GET['score_ex']) : '';
$score_rep1 = isset($_GET['score_rep1']) ? Database::escape_string($_GET['score_rep1']) : '';
$score_rep2 = isset($_GET['score_rep2']) ? Database::escape_string($_GET['score_rep2']) : '';
$coment = isset($_GET['coment']) ? Database::escape_string($_GET['coment']) : '';

$sql = "INSERT INTO $table (exe_user_id, c_id, mod_no, score_ex, score_rep1, score_rep2, coment)
        VALUES ($ex_user_id, 0, '$mod_no', '$score_ex', '$score_rep1', '$score_rep2', '$coment')";
Database::query($sql);
header("location: myStudents.php?student=$ex_user_id");
exit;
