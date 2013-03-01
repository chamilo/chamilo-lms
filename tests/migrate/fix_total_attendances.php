<?php
require '../../main/inc/global.inc.php';
require '../../main/inc/lib/attendance.lib.php';
$a = new Attendance();
$sessions_list = SessionManager::get_sessions_list();
$min = 90000;
$max = 999999;
// Get sessions
foreach ($sessions_list as $session) {
    if ($session['id'] < $min) { continue; }
    echo "Session ".$session['id']."\n";
    // Get users in session to build users list
    $users = SessionManager::get_users_by_session($session['id']);
    $u = array();
    foreach ($users as $user) {
        $u[] = $user['user_id'];
    }
    // Get courses list to get the right course (only one in each session)
    $courses = SessionManager::get_course_list_by_session_id($session['id']);
    if (count($courses)>0) {
        foreach ($courses as $course) {
            $course_id = $course['id'];
            break;
        }
        echo "-- Course ".$course_id."\n";
        // Get attendances sheets from course (only one in each course)
        $att = $a->get_attendances_list($course_id,$session['id']);
        if (count($att)>0) {
            foreach ($att as $at) {
                $at_id = $at['id'];
                break; //get out after first result
            }
            echo "---- Attendance ".$at_id."\n";
            $a->set_course_int_id($course_id);
            $a->update_users_results($u,$at_id);
        } else {
            var_dump($att);
        }
    }
    if ($session['id']>$max) { die('Finished processing '.$max."\n"); }
}
