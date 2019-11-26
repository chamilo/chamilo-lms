<?php
/* For licensing terms, see /license.txt */

/**
 * Automatic fix online time procedure. If a user has been idle for $timeLimit
 * or more then the procedure adds $extraTime to his logout_course_date.
 *
 * How to use it:
 *
 * By default the script will fix only users with teacher status (COURSEMANAGER)
 *
 * php main/cron/fix_online_time.php
 *
 * If you want to add an specific status you can call the file with:
 *
 *  php main/cron/fix_online_time.php status=5
 *
 * Where "5" is the value of the 'STUDENT' constant.
 *
 * @package chamilo.cron
 *
 * @author Imanol Losada <imanol.losada@beeznest.com>
 * @author Julio Montoya
 */
require_once __DIR__.'/../inc/global.inc.php';

if (php_sapi_name() != 'cli') {
    exit; //do not run from browser
}

/**
 * Get ids of users that are inside a course right now.
 *
 * @param int $status COURSEMANAGER|STUDENT constants
 *
 * @return array user id
 */
function getUsersInCourseIds($status)
{
    $status = (int) $status;
    $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ONLINE);
    $joinStatement = ' JOIN '.Database::get_main_table(TABLE_MAIN_USER).' ON login_user_id = user_id';

    return Database::select(
        'login_user_id',
        $table.$joinStatement,
        [
            'where' => [
                'c_id IS NOT NULL AND status = ?' => [
                    $status,
                ],
            ],
        ]
    );
}

/**
 * If a user has been idle for $timeLimit or more then
 * the procedure adds $extraTime to his logout_course_date.
 *
 * @param array $users user id list
 *
 * @return int
 */
function updateUsersInCourseIdleForTimeLimit($users)
{
    $timeLimit = '- 30 minute';
    $extraTime = '+ 5 minute';
    $utcResult = Database::fetch_array(Database::query('SELECT UTC_TIMESTAMP'));
    $dataBaseCurrentHour = array_shift($utcResult);
    $maximumIdleTimeInCourse = date(
        'Y-m-d H:i:s',
        strtotime($dataBaseCurrentHour.' '.$timeLimit)
    );
    $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
    $onLineTrackTable = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ONLINE);
    $count = 1;
    foreach ($users as $key => $value) {
        $value = array_shift($value);
        $logResult = Database::select(
            'course_access_id, logout_course_date',
            $table,
            [
                'where' => [
                    'user_id = ?' => [
                        $value,
                    ],
                ],
                'order' => 'course_access_id DESC',
                'limit' => '1',
            ]
        );
        $currentTeacherData = array_shift($logResult);
        Database::update(
            $table,
            [
                'logout_course_date' => date(
                    'Y-m-d H:i:s',
                    strtotime($currentTeacherData['logout_course_date'].' '.$extraTime)
                ),
            ],
            [
                'user_id = ? AND logout_course_date < ? AND course_access_id = ?' => [
                    $value,
                    $maximumIdleTimeInCourse,
                    $currentTeacherData['course_access_id'],
                ],
            ]
        );

        /*
         * (Avoid multiple updates)
         * When the user enters a course, this field is updated with the course code.
         * And when the user goes to another tool, returns to NULL
         */
        $userId = intval($value);
        $sql = "UPDATE $onLineTrackTable 
                SET c_id = NULL 
                WHERE login_user_id = $userId";
        Database::query($sql);
        $count++;
    }

    return $count;
}

// Default status when running script
$status = COURSEMANAGER;
if (!empty($argv) && isset($argv[1])) {
    // Get status from parameter
    parse_str($argv[1], $params);
    if (isset($params['status'])) {
        $status = (int) $params['status'];
    }
}

$statusToString = api_get_status_langvars();
if (isset($statusToString[$status])) {
    echo "Fixing users with status: '$status' = ".$statusToString[$status].PHP_EOL;
} else {
    echo "User status not '$status' not found. Try with status=1 or status=5";
    exit;
}

$users = getUsersInCourseIds($status);
if (!empty($users)) {
    $result = updateUsersInCourseIdleForTimeLimit($users);
    echo "# users updated: $result".PHP_EOL;
} else {
    echo "Nothing to update. No users found to be fixed.".PHP_EOL;
}
