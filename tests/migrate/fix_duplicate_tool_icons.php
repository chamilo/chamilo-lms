<?php

require_once dirname(__FILE__).'/../../main/inc/global.inc.php';
$course_table = Database::get_main_table(TABLE_MAIN_COURSE);
$tool_table = Database::get_course_table(TABLE_TOOL_LIST);

$sql = "SELECT * FROM $course_table ";
$result = Database::query($sql);

$course_list = Database::store_result($result);
if (!empty($course_list)) {
    foreach ($course_list as $course) {
        $sql = "SELECT id, name FROM $tool_table WHERE c_id = {$course['id']} ";
        $tool_result = Database::query($sql);
        $all_tools = Database::store_result($tool_result);
        
        $tools_added = array();
        $deleted = false;
        if (!empty($all_tools)) {
            foreach ($all_tools as $tool) {            
                if (isset($tools_added[$tool['name']])) {
                    $sql = "DELETE FROM $tool_table WHERE  c_id = {$course['id']} AND id = {$tool['id']}";
                    Database::query($sql);
                    $deleted = true;
                } else {
                    $tools_added[$tool['name']] = true;
                }
            }
            if ($deleted) {
                error_log("Removing doubles tools from course {$course['id']}");
            } else {
                error_log("Nothing to delete from course {$course['id']}");
            }
        }        
    }
}