<?php
/**
 * This script is included by main/admin/settings.lib.php and generally 
 * includes things to execute in the main database (settings_current table)
 * @package chamilo.plugin.bigbluebutton
 */
/**
 * Initialization
 */

require 'config.php';

$t_settings = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
$t_options  = Database::get_main_table(TABLE_MAIN_SETTINGS_OPTIONS);
/**
 * Queries
 */
$sql = "INSERT INTO $t_settings (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable, access_url_locked) VALUES
		('bbb_plugin', '', 'radio', 'Extra', 'false', 'BigBlueButtonEnableTitle','BigBlueButtonEnableComment',NULL,NULL, 1, 1)";
Database::query($sql);
$sql = "INSERT INTO $t_options (variable, value, display_text) VALUES ('bbb_plugin', 'true', 'Yes')";
Database::query($sql);
$sql = "INSERT INTO $t_options (variable, value, display_text) VALUES ('bbb_plugin', 'false', 'No')";
Database::query($sql);
$sql = "INSERT INTO $t_settings (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable, access_url_locked) VALUES
    ('bbb_plugin_host', '', 'textfield', 'Extra', '192.168.0.100', 'BigBlueButtonHostTitle','BigBlueButtonHostComment',NULL,NULL, 1,1)";
Database::query($sql);
$sql = "INSERT INTO $t_settings (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable, access_url_locked) VALUES
    ('bbb_plugin_salt', '', 'textfield', 'Extra', '', 'BigBlueButtonSecuritySaltTitle','BigBlueButtonSecuritySaltComment',NULL,NULL, 1,1)";
Database::query($sql);

$table = Database::get_main_table('plugin_bbb_meeting');
$sql = "CREATE TABLE $table ( 
        id INT unsigned NOT NULL auto_increment PRIMARY KEY, 
        c_id INT unsigned NOT NULL DEFAULT 0,
        meeting_name VARCHAR(255) NOT NULL DEFAULT '', 
        attendee_pw VARCHAR(255) NOT NULL DEFAULT '',
        moderator_pw VARCHAR(255) NOT NULL DEFAULT '', 
        record INT NOT NULL DEFAULT 0,
        status INT NOT NULL DEFAULT 0,
        created_at VARCHAR(255) NOT NULL,
        calendar_id INT DEFAULT 0,
        welcome_msg VARCHAR(255) NOT NULL DEFAULT '')";
Database::query($sql);

// Update existing courses to add conference settings
$t_courses = Database::get_main_table(TABLE_MAIN_COURSE);
$sql = "SELECT id, code FROM $t_courses ORDER BY id";
$res = Database::query($sql);
while ($row = Database::fetch_assoc($res)) {
    $course_id = $row['id'];
            
    foreach ($variables as $variable) {
        $sql = "SELECT value FROM $t_course WHERE c_id = $course_id AND variable = '$variable' ";
        $result = Database::query($sql);
        if (!Database::num_rows($result)) {
            $sql_course = "INSERT INTO $t_course (c_id, variable,value,category) VALUES ($course_id, '$variable','','plugins')";
            $r = Database::query($sql_course);
        }
    }
    
    
    $t_tool = Database::get_course_table(TABLE_TOOL_LIST);
    $sql = "SELECT name FROM $t_tool WHERE c_id = $course_id AND name = 'videoconference' ";
    $result = Database::query($sql);
    if (!Database::num_rows($result)) {
        $sql_course = "INSERT INTO $t_tool VALUES ($course_id, NULL, 'videoconference','../../plugin/bbb/start.php','visio.gif','".string2binary(api_get_setting('course_create_active_tools', 'videoconference'))."','0','squaregrey.gif','NO','_self','plugin','0')";
        $r = Database::query($sql_course);    
    }    
}