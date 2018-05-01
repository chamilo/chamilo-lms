<?php
/* For licensing terms, see /license.txt */

require_once '../inc/global.inc.php';

$allow = api_get_configuration_value('extra');
if (empty($allow)) {
    exit;
}

$tbl_stats_exercices = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);

foreach ($_POST as $index => $valeur) {
    $$index = Database::escape_string(trim($valeur));
}

$sql4 = "INSERT INTO $tbl_stats_exercices "."(exe_user_id,c_id,level,exe_date,inter_coment) ".
    "VALUES "."('$ex_user_id','0','$level','$date', '$inter_coment' )";
Database::query($sql4);

header("location:../extra/myStudents.php?student=$ex_user_id");
exit;
