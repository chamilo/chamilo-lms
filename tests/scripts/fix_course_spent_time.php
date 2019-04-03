<?php
/* For licensing terms, see /license.txt */

/**
 * This script checks and updates (if you uncomment the query)
 * the records in track_e_course_access that is used to calculate the
 * total course time.
 */
exit;

require_once __DIR__.'/../../main/inc/global.inc.php';

$maxSeconds = 10 * 60 * 60; // Check records higher than X hours
$addSecondsToLogin = 2 * 60 * 60; // Update this abusive records with X hours
$limit = 10; // Only fix first 10
$sendMessage = true;
$userId = 1; // User id that will receive a report
$update = false; // Update and fix the record

$sql = "SELECT 
    course_access_id, 
    counter, 
    UNIX_TIMESTAMP(logout_course_date) - UNIX_TIMESTAMP(login_course_date) diff, 
    login_course_date, 
    logout_course_date 
    FROM track_e_course_access 
    WHERE UNIX_TIMESTAMP(logout_course_date) > UNIX_TIMESTAMP(login_course_date) 
    ORDER by diff DESC
    LIMIT $limit
";

// contidos
//SELECT course_access_id, counter, (UNIX_TIMESTAMP(logout_course_date) - UNIX_TIMESTAMP(login_course_date)) / 60 / 60 diff, login_course_date, logout_course_date FROM track_e_course_access WHERE UNIX_TIMESTAMP(logout_course_date) > UNIX_TIMESTAMP(login_course_date)  order by diff DESC limit 10;

$result = Database::query($sql);
$log = '';
while ($row = Database::fetch_array($result)) {
    if ($row['diff'] >= $maxSeconds) {
        $id = $row['course_access_id'];
        $loginDate = $row['login_course_date'];
        $logoutDate = $row['logout_course_date'];
        $diff = round($row['diff']/60/60);

        $login = api_strtotime($row['login_course_date'], 'UTC') + $addSecondsToLogin;
        $newLogout = api_get_utc_datetime($login);
        $sql = "UPDATE track_e_course_access SET logout_course_date = '$newLogout' WHERE course_access_id = $id;";

        if ($update) {
            Database::query($sql);
        }
        $report = "Login : $loginDate";
        $report .= PHP_EOL;
        $report .= "Logout: $logoutDate Diff in hours: $diff";
        $report .= PHP_EOL;
        $report .= $sql;
        $report .= PHP_EOL;
        $report .= PHP_EOL;
        $log .= $report;
        echo $report;
    }
}

if ($sendMessage && !empty($log)) {
    $log = nl2br($log);
    MessageManager::send_message_simple(
        $userId,
        'Course time spent fixes',
        $log
    );
}
