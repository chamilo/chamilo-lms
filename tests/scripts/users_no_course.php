<?php

/* For licensing terms, see /license.txt */

/**
 * This script select all users with no course subscriptions and
 * add it to a selected course.
 */
exit;

require __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script();

// Course that users with no course will be registered:
$courseCode = '';

$user = Database::get_main_table(TABLE_MAIN_USER);
$userCourse = Database::get_main_table(TABLE_MAIN_COURSE_USER);

$sql = "SELECT * FROM $user WHERE user_id NOT IN (
            SELECT user_id FROM $userCourse
        ) AND status <> ".ANONYMOUS."
        ";
$result = Database::query($sql);
$students = Database::store_result($result);

if (!empty($students)) {
    foreach ($students as $student) {
        var_dump($student['username'].'- '.$student['user_id']);
        $result = CourseManager::subscribeUser($student['user_id'], $courseCode);
        var_dump($result);
        echo '<br />';
    }
}
