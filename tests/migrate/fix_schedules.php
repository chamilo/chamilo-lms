<?php
/**
 * Finds the sessions without access date and defines (for all sessions without access date older than 30
 * days in the past from now) a new access date based on the period (which can be found in the session
 * name).
 * @package chamilo.migrate 
 */
require_once '../../main/inc/global.inc.php';
$log = 'sessions_dates_changes.txt';
$ts = Database::get_main_table(TABLE_MAIN_SESSION_FIELD_OPTIONS);
//$sql = "SELECT * FROM $ts WHERE access_start_date = '0000-00-00 00:00:00' AND access_end_date = '0000-00-00 00:00:00' ORDER BY name";
//$sql = "SELECT * FROM $ts WHERE access_start_date = '0000-00-00 00:00:00' OR access_end_date = '0000-00-00 00:00:00' ORDER BY name";
$sql = "SELECT id, option_display_text FROM $ts WHERE field_id = 4";
echo $sql."\n";
$res = Database::query($sql);
if ($res !== false) {
  while ($row = Database::fetch_assoc($res)) {
    //echo "Session ".$row['name']." has no start/end date\n";
    $name = $row['option_display_text'];
    $name = substr($name,5).' '.substr($name,0,4);
    $sql2 = "UPDATE $ts SET option_display_text = '$name' WHERE id = ".$row['id'];
    $res2 = Database::query($sql2);
  }
}
