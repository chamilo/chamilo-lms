<?php
/**
 * This script moves all courses to a session in the past (closing the month before)
 * @package chamilo.tests.scripts
 */
/**
 * Init
 */
require __DIR__ . '/../../main/inc/global.inc.php';
// List of tables that will need an update
$tables = array(
    'c_announcement' => array('c' => 'c_id', 's' => 'session_id'),
    'c_attendance' => array('c' => 'c_id', 's' => 'session_id'),
    'c_blog' => array('c' => 'c_id', 's' => 'session_id'),
    'c_calendar_event' => array('c' => 'c_id', 's' => 'session_id'),
    'c_chat_connected' => array('c' => 'c_id', 's' => 'session_id'),
    'c_course_description' => array('c' => 'c_id', 's' => 'session_id'),
    'c_document' => array('c' => 'c_id', 's' => 'session_id'),
    'c_dropbox_category' => array('c' => 'c_id', 's' => 'session_id'),
    'c_dropbox_file' => array('c' => 'c_id', 's' => 'session_id'),
    'c_dropbox_post' => array('c' => 'c_id', 's' => 'session_id'),
    'c_forum_category' => array('c' => 'c_id', 's' => 'session_id'),
    'c_forum_forum' => array('c' => 'c_id', 's' => 'session_id'),
    'c_forum_thread' => array('c' => 'c_id', 's' => 'session_id'),
    'c_forum_thread_qualify' => array('c' => 'c_id', 's' => 'session_id'),
    'c_forum_thread_qualify_log' => array('c' => 'c_id', 's' => 'session_id'),
    'c_glossary' => array('c' => 'c_id', 's' => 'session_id'),
    'c_group_info' => array('c' => 'c_id', 's' => 'session_id'),
    'c_item_property' => array('c' => 'c_id', 's' => 'id_session'),
    'c_link' => array('c' => 'c_id', 's' => 'session_id'),
    'c_link_category' => array('c' => 'c_id', 's' => 'session_id'),
    'c_lp' => array('c' => 'c_id', 's' => 'session_id'),
    'c_lp_view' => array('c' => 'c_id', 's' => 'session_id'),
    'c_notebook' => array('c' => 'c_id', 's' => 'session_id'),
    'c_quiz' => array('c' => 'c_id', 's' => 'session_id'),
    'c_student_publication' => array('c' => 'c_id', 's' => 'session_id'),
    'c_survey' => array('c' => 'c_id', 's' => 'session_id'),
    'c_survey_invitation' => array('c' => 'c_id', 's' => 'session_id'),
    'c_thematic' => array('c' => 'c_id', 's' => 'session_id'),
    'c_tool' => array('c' => 'c_id', 's' => 'session_id'),
    'c_tool_intro' => array('c' => 'c_id', 's' => 'session_id'),
    'c_wiki' => array('c' => 'c_id', 's' => 'session_id'),
    'c_wiki_mailcue' => array('c' => 'c_id', 's' => 'session_id'),
    'gradebook_category' => array('c' => 'course_code', 's' => 'session_id'),
    //'session_rel_course',
    //'session_rel_course_rel_user',
    //'session_rel_user',
    'track_course_ranking' => array('c' => 'c_id', 's' => 'session_id'),
    'track_e_access' => array('access_cours_code' => 'c_id', 's' => 'access_session_id'),
    'track_e_attempt' => array('c' => 'course_code', 's' => 'session_id'),
    'track_e_course_access' => array('c' => 'course_code', 's' => 'session_id'),
    'track_e_downloads' => array('c' => 'down_cours_id', 's' => 'down_session_id'),
    'track_e_exercices' => array('c' => 'exe_cours_id', 's' => 'session_id'),
    'track_e_item_property' => array('c' => 'course_id', 's' => 'session_id'),
    'track_e_lastaccess' => array('c' => 'access_cours_code', 's' => 'access_session_id'),
    'track_e_links' => array('c' => 'links_cours_id', 's' => 'links_session_id'),
    'track_e_online' => array('c' => 'course', 's' => 'session_id'),
    'track_e_uploads' => array('c' => 'upload_cours_id', 's' => 'upload_session_id'),
    'user_rel_course_vote' => array('c' => 'c_id', 's' => 'session_id'),
);

/**
 * Create the sessions
 * For each existing course, create a session that ends on the last day of the
 * past month and starts 2 years before that.
 */
$year = date('Y');
$month = date('m');
$end = api_strtotime($year.'-'.$month.'-01 00:00:00') - 1;
$start = $end - (2*365*86400);

$res = Database::select('id, title, code', TABLE_MAIN_COURSE);
foreach ($res as $course) {
    echo $course['title'] . PHP_EOL;
    $sessionTitle = $course['title'] . $month . '-' . $year;
    $id = SessionManager::create_session($sessionTitle, $year, $month, 0, $year, $month, 28, 0, 0, 0, 'info@contidosdixitais.com', 0, SESSION_VISIBLE_READ_ONLY);
    if ($id === false) {
        echo "Could not create session $sessionTitle" . PHP_EOL;
    }
    SessionManager::add_courses_to_session($id, array($course['code']));
    $resultUsers = Database::select('user_id', TABLE_MAIN_COURSE_USER, array('where course_code = ?' => array($course['code'])));
    $users = array();
    foreach ($resultUsers as $user_id) {
        $users[] = $user_id;
    }
    SessionManager::subscribe_users_to_session_course($users, $id, $course['code']);
    foreach ($tables as $table => $fields) {
        //c_id + course_id = int, others = char
        if ($fields['c'] == 'c_id' or $fields['c'] == 'course_id') {
            $sql = "UPDATE $table SET " . $fields['s'] . " = $id WHERE " . $fields['c'] . " = " . $course['id'];
        } else {
            $sql = "UPDATE $table SET " . $fields['s'] . " = $id WHERE " . $fields['c'] . " = " . $course['code'];
        }
        $resultChange = Database::query($sql);
    }
    // Now clean up by unsubscribing the user from the course itself manually
    // to avoid deleting other stuff
    foreach ($users as $user) {
        $sql = "DELETE FROM course_rel_user WHERE user_id = $user AND course_code = " . $course['code'];
        $resultRemove = Database::query($sql);
    }
}