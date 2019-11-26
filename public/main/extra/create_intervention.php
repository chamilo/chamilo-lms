<?php
/* For licensing terms, see /license.txt */

require_once '../inc/global.inc.php';

$allow = api_get_configuration_value('extra');
if (empty($allow)) {
    exit;
}

$table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
$comment = isset($_POST['inter_coment']) ? Database::escape_string($_POST['inter_coment']) : '';
$date = isset($_POST['date']) ? Database::escape_string($_POST['date']) : '';
$level = isset($_POST['level']) ? Database::escape_string($_POST['level']) : '';
$ex_user_id = isset($_POST['ex_user_id']) ? Database::escape_string($_POST['ex_user_id']) : '';

$sql = "INSERT INTO $table (exe_user_id,c_id,level,exe_date,inter_coment)
        VALUES ($ex_user_id, 0, '$level', '$date', '$comment')";
Database::query($sql);
header("location: myStudents.php?student=$ex_user_id");
exit;
