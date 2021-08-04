<?php
/* For licensing terms, see /license.txt */

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script();
if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}
$course_info = api_get_course_info();
$course_code = $course_info['code'];

echo '<form action="tutor.php" name="assign" id ="assign">';
echo '<div id="confirmation"></div>';
$id = (int) $_GET['id'];
$tblWeeklyReport = Database::get_main_table('rp_reporte_semanas');
$sql = "SELECT * FROM $tblWeeklyReport WHERE id = $id";
$sql_tasks = "SELECT id AS colid, title as coltitle
    FROM ".Database::get_course_table(TABLE_STUDENT_PUBLICATION)."
    WHERE parent_id = 0
        AND id NOT IN (
            SELECT work_id
            FROM $tblWeeklyReport
            WHERE
                course_code = '$course_code' AND
                id != $id
        )";
$sql_forum = "SELECT thread_id AS colid, thread_title AS coltitle
    FROM ".Database::get_course_table(TABLE_FORUM_THREAD)."
    WHERE thread_id NOT IN (
        SELECT forum_id
            FROM $tblWeeklyReport
            WHERE
                course_code = '$course_code' AND
                id != $id
    )";
$rs = Database::fetch_object(Database::query($sql));
$result_tareas = Database::query($sql_tasks);
$result_forum = Database::query($sql_forum);

echo '<div class="row">
        <input type="hidden" id="rs_id" name ="rs_id" value="'.$id.'">
        <div class="formw">'.get_lang('PleaseSelectTasks').'</div>
    </div>';
echo '<div class="row"><div class="formw"><select name ="work_id" id="work_id">';
echo '<option value="0"'.(($row['colid'] == $rs->work_id) ? "selected" : "").'>'.get_lang('PleaseSelect').'</option>';
while ($row = Database::fetch_assoc($result_tasks)) {
    echo '<option value="'.$row['colid'].'"'.(($row['colid'] == $rs->work_id) ? "selected" : "").'>'.
        $row['coltitle'].'</option>';
}
echo '</select></div><div>';
echo '<div class="row">
        <div class="formw">'.get_lang('PleaseSelectThread').'</div>
    </div>';
echo '<div class="row"><div class="formw"><select name ="forum_id" id="forum_id">';
echo '<option value="0"'.(($row['colid'] == $rs->work_id) ? "forum_id" : "").'>'.get_lang('PleaseSelect').'</option>';
while ($row = Database::fetch_assoc($result_forum)) {
    echo '<option value="'.$row['colid'].'"'.(($row['colid'] == $rs->forum_id) ? "selected" : "").'>'.
        $row['coltitle'].'</option>';
}
echo '</select></div><div>';
echo '<div class="row">
        <div class="formw">
        <button class="save" name="edit" type="button" value="'.get_lang('Edit').'" onClick="save('.$id.');">'.
            get_lang('Edit').'</button>
        </div>
    </div>';
echo '</form>';
