<?php
/**
 * This script is included by main/admin/settings.lib.php when unselecting a plugin 
 * and is meant to remove things installed by the install.php script in both
 * the global database and the courses tables
 * @package chamilo.plugin.bigbluebutton
 */
/**
 * Queries
 */
$t_settings = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
$t_options  = Database::get_main_table(TABLE_MAIN_SETTINGS_OPTIONS);
$sql = "DELETE FROM $t_settings WHERE variable = 'bbb_plugin'";
Database::query($sql);
$sql = "DELETE FROM $t_options WHERE variable = 'bbb_plugin'";
Database::query($sql);
$sql = "DELETE FROM $t_settings WHERE variable = 'bbb_plugin_host'";
Database::query($sql);
$sql = "DELETE FROM $t_settings WHERE variable = 'bbb_plugin_salt'";
Database::query($sql);
$sql = "DROP TABLE plugin_bbb";
Database::query($sql);
// update existing courses to add conference settings
$t_courses = Database::get_main_table(TABLE_MAIN_COURSE);
$sql = "SELECT id, code, db_name FROM $t_courses ORDER BY id";
$res = Database::query($sql);
while ($row = Database::fetch_assoc($res)) {
    $t_course = Database::get_course_table(TABLE_COURSE_SETTING);
    $sql_course = "DELETE FROM $t_course  WHERE c_id = ".$row['id']." AND variable = 'big_blue_button_meeting_name'";
    $r = Database::query($sql_course);
    $sql_course = "DELETE FROM $t_course  WHERE c_id = ".$row['id']." AND variable = 'big_blue_button_attendee_password'";
    $r = Database::query($sql_course);
    $sql_course = "DELETE FROM $t_course  WHERE c_id = ".$row['id']." AND variable = 'big_blue_button_moderator_password'";
    $r = Database::query($sql_course);
    $sql_course = "DELETE FROM $t_course  WHERE c_id = ".$row['id']." AND variable = 'big_blue_button_welcome_message'";
    $r = Database::query($sql_course);
    $t_tool = Database::get_course_table(TABLE_TOOL_LIST);
    $sql_course = "DELETE FROM $t_tool WHERE  c_id = ".$row['id']." AND link = '../../plugin/bbb/start.php'";
    $r = Database::query($sql_course);
}
