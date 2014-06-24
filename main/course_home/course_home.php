<?php
/* For licensing terms, see /license.txt */
/**
*        HOME PAGE FOR EACH COURSE
*
*
* Edit visibility of tools
*
*   visibility = 1 - everybody
*   visibility = 0 - course admin (teacher) and platform admin
*
* Who can change visibility ?
*
*   admin = 0 - course admin (teacher) and platform admin
*   admin = 1 - platform admin
*
* Show message to confirm that a tools must be hide from available tools
*
*   visibility 0,1
*
*
*	@package chamilo.course_home
*/

/*
if (isset($_GET['action']) && $_GET['action'] == 'subscribe') {
    if (Security::check_token('get')) {
        Security::clear_token();
        $auth = new AuthLib();
        $msg = $auth->subscribe_user($course_code);
        if (!empty($msg)) {
            $show_message .= Display::return_message(get_lang($msg));
        }
    }
}*/

/*	Is the user allowed here? */
//api_protect_course_script(true);

/*  STATISTICS */
/*
if (!isset($coursesAlreadyVisited[$course_code])) {
    Event::accessCourse();
    $coursesAlreadyVisited[$course_code] = 1;
    Session::write('coursesAlreadyVisited', $coursesAlreadyVisited);
}*/

