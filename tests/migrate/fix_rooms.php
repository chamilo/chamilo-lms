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
$tv = Database::get_main_table(TABLE_MAIN_SESSION_FIELD_VALUES);
//$sql = "SELECT * FROM $ts WHERE access_start_date = '0000-00-00 00:00:00' AND access_end_date = '0000-00-00 00:00:00' ORDER BY name";
//$sql = "SELECT * FROM $ts WHERE access_start_date = '0000-00-00 00:00:00' OR access_end_date = '0000-00-00 00:00:00' ORDER BY name";
$sql = "SELECT id, option_value, option_display_text FROM $ts WHERE field_id = 6";
echo $sql."\n";
$res = Database::query($sql);
if ($res !== false) {
  while ($row = Database::fetch_assoc($res)) {
    //echo "Session ".$row['name']." has no start/end date\n";
    $aid = $row['option_value'];
    $name = $row['option_display_text'];
    //$name = substr($name,5).' '.substr($name,0,4);
    // Get one session with this room
    $sql2 = "SELECT * FROM $tv WHERE field_id = 6 AND field_value = '$aid' LIMIT 1";
    //echo $sql2."\n";
    $res2 = Database::query($sql2);
    $row2 = Database::fetch_assoc($res2);
    if (empty($row2['session_id'])) { continue; }
    $session_id = $row2['session_id'];
    $sql3 = "SELECT field_value FROM $tv WHERE field_id = 3 AND session_id = $session_id";
    $res3 = Database::query($sql3);
    $row3 = Database::fetch_assoc($res3);
    if (empty($row3['field_value'])) { continue; }
    $branch_id = $row3['field_value'];
    $sql4 = "SELECT option_display_text FROM $ts WHERE field_id = 3 and option_value = '$branch_id'";
    $res4 = Database::query($sql4);
    $row4 = Database::fetch_assoc($res4);
    if (!empty($row4['option_display_text'])) {
      $name = substr($row4['option_display_text'],13).' - '.$name;
      echo $name."\n";
      $sql5 = "UPDATE $ts SET option_display_text = '$name' WHERE id = ".$row['id'];
      $res5 = Database::query($sql5);
    }
  }
}
