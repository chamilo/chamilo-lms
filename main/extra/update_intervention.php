<?php
/* For licensing terms, see /license.txt */

require_once '../inc/global.inc.php';

$allow = api_get_configuration_value('extra');
if (empty($allow)) {
    exit;
}

$table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
$ex_id = isset($_POST['exe_id']) ? (int) $_POST['exe_id'] : '';
$student_id = isset($_POST['student_id']) ? (int) $_POST['student_id'] : '';
$inter_coment = isset($_POST['inter_coment']) ? Database::escape_string($_POST['inter_coment']) : '';
$sql = "UPDATE $table SET inter_coment='$inter_coment' WHERE exe_id = $ex_id";
Database::query($sql);
header("location:../extra/myStudents.php?student=$student_id");
exit;
