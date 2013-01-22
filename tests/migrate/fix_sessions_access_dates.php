<?php
/**
 * Finds the sessions without access date and defines (for all sessions without access date older than 30
 * days in the past from now) a new access date based on the period (which can be found in the session
 * name).
 * @package chamilo.migrate 
 */
require_once '../../main/inc/global.inc.php';
$log = 'sessions_dates_changes.txt';
$ts = Database::get_main_table(TABLE_MAIN_SESSION);
//$sql = "SELECT * FROM $ts WHERE access_start_date = '0000-00-00 00:00:00' AND access_end_date = '0000-00-00 00:00:00' ORDER BY name";
//$sql = "SELECT * FROM $ts WHERE access_start_date = '0000-00-00 00:00:00' OR access_end_date = '0000-00-00 00:00:00' ORDER BY name";
$sql = "SELECT * FROM $ts ORDER BY name";
echo $sql."\n";
$res = Database::query($sql);
if ($res !== false) {
  echo "Found ".Database::num_rows($res)." sessions matching empty start or end date\n";
  while ($row = Database::fetch_assoc($res)) {
    //echo "Session ".$row['name']." has no start/end date\n";
    $matches = array();
    $match = preg_match('/-\s(\d{4})(\d{2})\s-/',$row['name'],$matches);
    $now = new DateTime(null);
    $cy = $now->format('Y');
    $cm = $now->format('m');
    if (!empty($match)) {
      $ny = $y = $matches[1];
      $nm = 1 + $m = $matches[2];
      //ignore current month
      if ($y == $cy && $m == $cm) { continue; }
      if ($m == 12) {
        $ny = $y+1;
        $nm = 1;
      }
      $start = new DateTime();
      $end = new DateTime();
      $start->setDate($y, $m, 1);
      $end->setDate($ny, $nm, 1);
      $end->modify('-1 day');
      $vstart = $start->format('Y-m-d H:i:s');
      $vend = $end->format('Y-m-d H:i:s');
      echo "Original period = $y$m, converted to start/stop: $vstart/$vend\n";
      $sql2 = "UPDATE $ts SET ";
      $comma = false;
      //if ($row['access_start_date'] == '0000-00-00 00:00:00') {
        $sql2 .= " access_start_date = '$vstart', coach_access_start_date = '$vstart' ";
        $comma = true;
      //}
      //if ($row['access_end_date'] == '0000-00-00 00:00:00') {
        if ($comma) { $sql2 .= ', '; }
        $sql2 .= " access_end_date = '$vend', coach_access_end_date = '$vend' ";
      //}
      $sql2 .= " WHERE id = ".$row['id'];
      $res2 = Database::query($sql2);
      file_put_contents($log,$row['id']."\n",FILE_APPEND);
    }
  }
}
