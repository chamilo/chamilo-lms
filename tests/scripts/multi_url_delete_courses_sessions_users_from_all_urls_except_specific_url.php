<?php
/* For licensing terms, see /license.txt */
/*
 * Delete all courses, sessions and users that are not on a specific URL identified by $urlId.
 * 
 */

exit;
// Uncomment le following line and set the urlId of the URL to be kept by this script.
//$urlId = ;

if (empty($urlId)) {
    echo "You need to define a urlId at the begining of the script";
    exit;
}
require_once '../../main/inc/global.inc.php';

$accessUrlRelCourseTable = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
$accessUrlRelUserTable = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
$accessUrlRelSessionTable = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);

$sqlCoursesToDelete = "select c_id from $accessUrlRelCourseTable where access_url_id != $urlId and c_id not in (select c_id from $accessUrlRelCourseTable where access_url_id = $urlId)";
$sqlUsersToDelete = "select user_id from $accessUrlRelUserTable where access_url_id != $urlId and user_id not in (select user_id from $accessUrlRelUserTable where access_url_id = $urlId)";
$sqlSessionsToDelete = "select session_id from $accessUrlRelSessionTable where access_url_id != $urlId and session_id not in (select session_id from $accessUrlRelSessionTable where access_url_id = $urlId)";


echo "Initiating sessions deletion".PHP_EOL;
$resSessionsToDelete = Database::query($sqlSessionsToDelete);
while ($data = Database::fetch_array($resSessionsToDelete)) {
    echo "deleting session with id = " . $data['session_id'] . ".".PHP_EOL;
    if (!SessionManager::delete($data['session_id'],true)) {
        echo "Session " . $data['session_id'] . " not deleted".PHP_EOL;
    }
}

echo "Initiating courses deletion".PHP_EOL;
$resCoursesToDelete = Database::query($sqlCoursesToDelete);
while ($data = Database::fetch_array($resCoursesToDelete)) {
    $courseCode = CourseManager::get_course_code_from_course_id($data['c_id']);
    echo "deleting course " . $courseCode . " with c_id = " . $data['c_id'] . ".".PHP_EOL;
    if (!CourseManager::delete_course($courseCode, true)) {
        echo "Course " . $data['c_id'] . " not deleted".PHP_EOL;    
    }
}

echo "Initiating users deletion".PHP_EOL;
$resUsersToDelete = Database::query($sqlUsersToDelete);
while ($data = Database::fetch_array($resUsersToDelete)) {
    $courseCode = CourseManager::get_course_code_from_course_id($data['c_id']);
    echo "deleting user with user_id = " . $data['user_id'] . ".".PHP_EOL;
    if (!UserManager::delete_user($data['user_id'])) {
        echo "User " . $data['user_id'] . " not deleted".PHP_EOL;
    }
}

