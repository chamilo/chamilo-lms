<?php
/**
 * This script should be included by add_course.lib.inc.php when adding a new course
 * @package chamilo.plugin.bigbluebutton
 */
/**
 * Initialization
 */
//$cdb is defined inside the fillDbCourse() function which is calling this script
$t_course = Database::get_course_table(TABLE_COURSE_SETTING,$cdb);
$sql_course = "INSERT INTO $t_course (variable,value,category) VALUES ('big_blue_button_meeting_name','','plugins')";
$r = Database::query($sql_course);
$sql_course = "INSERT INTO $t_course (variable,value,category) VALUES ('big_blue_button_attendee_password','','plugins')";
$r = Database::query($sql_course);
$sql_course = "INSERT INTO $t_course (variable,value,category) VALUES ('big_blue_button_moderator_password','','plugins')";
$r = Database::query($sql_course);
$sql_course = "INSERT INTO $t_course (variable,value,category) VALUES ('big_blue_button_welcome_message','','plugins')";
$r = Database::query($sql_course);
$t_tool = Database::get_course_table(TABLE_TOOL_LIST,$cdb);
$sql_course = "INSERT INTO $t_tool VALUES (NULL, 'videoconference','../../plugin/bbb/start.php','visio.gif','".string2binary(api_get_setting('course_create_active_tools', 'videoconference'))."','0','squaregrey.gif','NO','_blank','plugin','0')";
$r = Database::query($sql_course);
