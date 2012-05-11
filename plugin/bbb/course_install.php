<?php
/**
 * This script is executed when a new course is created
 * @package chamilo.plugin.bigbluebutton
 */
/**
 * Initialization
 */
require 'config.php';

// $course_id is set in the add_course.lib.inc.php
if (!empty($course_id)) {    
    BBBPlugin::create()->course_install($course_id);
}
