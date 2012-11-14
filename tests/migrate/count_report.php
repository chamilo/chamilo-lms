<?php
require '../../main/inc/global.inc.php';
$eol = "<br />\n";
if (PHP_SAPI == 'cli') {
  $eol = "\n";
}
$tables = array(
  'user',
  'course',
  'session',
  'course_rel_user',
  'session_rel_course_rel_user',
  'gradebook_category',
);
echo "$eol--- Post-migration count report ---$eol";
foreach ($tables as $table) {
  $sql = "SELECT count(*) FROM $table";
  $res = Database::query($sql);
  if ($res === false) {
    echo "SQL error in ".$sql.$eol;
  } else {
    $row = Database::fetch_array($res);
    $count = $row[0];
    echo "Table $table has $count items after migration$eol";
  }
}
echo 'The end.'.$eol;
