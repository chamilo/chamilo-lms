<?php
/**
 * This script should be included by add_course.lib.inc.php when adding a new course
 * @package chamilo.plugin.bigbluebutton
 */
/**
 * Initialization
 */

$t_course = Database::get_course_table(TABLE_COURSE_SETTING);
$course_id = api_get_course_int_id(); 
$sql_course = "INSERT INTO $t_course (c_id, variable,value,category) VALUES ($course_id, 'big_blue_button_meeting_name','','plugins')";
$r = Database::query($sql_course);
$sql_course = "INSERT INTO $t_course (c_id, variable,value,category) VALUES ($course_id, 'big_blue_button_attendee_password','','plugins')";
$r = Database::query($sql_course);
$sql_course = "INSERT INTO $t_course (c_id, variable,value,category) VALUES ($course_id, 'big_blue_button_moderator_password','','plugins')";
$r = Database::query($sql_course);
$sql_course = "INSERT INTO $t_course (c_id, variable,value,category) VALUES ($course_id, 'big_blue_button_welcome_message','','plugins')";
$r = Database::query($sql_course);
$t_tool = Database::get_course_table(TABLE_TOOL_LIST);
$sql_course = "INSERT INTO $t_tool VALUES ($course_id, NULL, 'videoconference','../../plugin/bbb/start.php','visio.gif','".string2binary(api_get_setting('course_create_active_tools', 'videoconference'))."','0','squaregrey.gif','NO','_blank','plugin','0')";
$r = Database::query($sql_course);
