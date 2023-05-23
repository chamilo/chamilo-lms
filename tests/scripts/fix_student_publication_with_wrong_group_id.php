<?php
/* For licensing terms, see /license.txt */
/*
 * Update all the student publication to have the same post_group_id 
 * as its parents if the parent has a post_group_id
*/
exit;

require_once '../../main/inc/global.inc.php';

$workTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION);

$sql = "SELECT * FROM $workTable WHERE parent_id = 0 AND post_group_id != 0 AND active = 1";
$res = Database::query($sql);
while ($data = Database::fetch_array($res)) {
    echo "revising c_student_publicaton (" . $data['title'] . ") with id = " . $data['id'] . " in group = " . $data['post_group_id'];
    $updatesql = "update $workTable set post_group_id = " . $data['post_group_id'] . " WHERE parent_id = " . $data['id'] . ";";
    echo " update sql = " . $updatesql;
    $subres = Database::query($updatesql);
}

