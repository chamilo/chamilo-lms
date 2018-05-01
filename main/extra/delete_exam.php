<?php
/* For licensing terms, see /license.txt */

require_once '../inc/global.inc.php';

$allow = api_get_configuration_value('extra');
if (empty($allow)) {
    exit;
}

$is_allowedToTrack = $is_courseAdmin || $is_platformAdmin;
api_block_anonymous_users();

$ex_user_id = isset($_GET['student_id']) ? $_GET['student_id'] : "";
$num = isset($_GET['num']) ? (int) $_GET['num'] : 0;

$tbl_stats_exercices = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
$sql = "DELETE FROM $tbl_stats_exercices WHERE exe_id ='$num'";
Database::query($sql);

header("location:../extra/myStudents.php?student=$ex_user_id");
exit;
	
