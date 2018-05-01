<?php
/* For licensing terms, see /license.txt */

require_once '../inc/global.inc.php';

$allow = api_get_configuration_value('extra');
if (empty($allow)) {
    exit;
}

$tbl_stats_exercices = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);

$ex_id = isset($_POST['exe_id']) ? $_POST['exe_id'] : "";
$student_id = isset($_POST['student_id']) ? $_POST['student_id'] : "";
$inter_coment = isset($_POST['inter_coment']) ? $_POST['inter_coment'] : "";

foreach ($_POST as $index => $valeur) {
    $$index = Database::escape_string(trim($valeur));
}
$sql4 = "UPDATE $tbl_stats_exercices SET inter_coment='$inter_coment'
		WHERE exe_id = '$ex_id'
    ";
Database::query($sql4);
header("location:../extra/myStudents.php?student=$student_id");
exit;