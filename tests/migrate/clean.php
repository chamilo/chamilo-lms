<?php
die();
require '../../main/inc/global.inc.php';
$tables = array('user','course','session','session_rel_course','session_rel_course_rel_user','session_rel_user','c_attendances','track_e_default','user_field_values','course_field_values','session_field_values','course_field','session_field','user_field', 'user_field_options','course_field_options','session_field_options');
foreach ($tables as $table) {
  $sql = "TRUNCATE $table";
  if ($table == 'user') {
    $sql = "DELETE FROM $table WHERE user_id > 2";
  }
  mysql_query($sql);
}
@system('rm -rf ../../courses/*');
