<?php
require '../../main/inc/global.inc.php';
$tables = array(
  'user',
  'course',
  'session',
  'course_rel_user',
  'session_rel_course_rel_user',
  'gradebook_category',
);
foreach ($tables as $table) {
  $sql = "SELECT count(*) FROM $table";
  $res = Database::query($sql);
  if ($res === false) {
    echo "SQL error in ".$sql."\n";
  } else {
    $row = Database::fetch_array($res);
    $count = $row[0];
    echo "Table $table has $count items after migration\n";
  }
}
