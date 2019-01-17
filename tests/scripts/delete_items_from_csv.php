<?php
/* For licensing terms, see /license.txt */

exit;

require_once __DIR__ . '/../../main/inc/global.inc.php';

$file = 'delete.csv';
if (!file_exists($file)) {
    echo "File $file doesn't exists";
    exit;
}

$data = Import::csv_reader($file);
echo 'Number of lines '.count($data).PHP_EOL;
$counter = 1;
foreach ($data as $row) {
    if (isset($row['SessionID'])) {
        $sessionId = SessionManager::getSessionIdFromOriginalId($row['SessionID'], 'external_session_id');
        if (!empty($sessionId)) {
            $sessionInfo = api_get_session_info($sessionId);
            if (!empty($sessionInfo)) {
                $sessionId = $sessionInfo['id'];
                $sessionName = $sessionInfo['name'];
                echo "Line: $counter. Session will be deleted: $sessionName #$sessionId ".PHP_EOL;
                //SessionManager::delete($sessionId, true);
            } else {
                echo "Line: $counter. Session not found: $sessionName".PHP_EOL;
            }
        }
    }

    // Course
    $courseId = isset($row['CourseID']) ? $row['CourseID'] : '';
    if (!empty($courseId)) {
        $courseInfo = CourseManager::getCourseInfoFromOriginalId($courseId, 'external_course_id');
        if (!empty($courseInfo) && isset($courseInfo['id'])) {
            $courseCode = $courseInfo['code'];
            $courseId = $courseInfo['id'];
            CourseManager::delete_course($courseCode);
            echo "Line: $counter. Course will be deleted: $courseCode #$courseId".PHP_EOL;
        } else {
            echo "Line: $counter. Course not found: $courseCode".PHP_EOL;
        }
    }

    // User
    $userName = isset($row['UserName']) ? $row['UserName'] : '';
    if (!empty($userName)) {
        $userInfo = api_get_user_info_from_username($userName);
        if (!empty($userInfo) && isset($userInfo['id'])) {
            $userId = $userInfo['id'];
            //UserManager::delete_user($userId);
            echo "Line: $counter. User will be deleted: $userId".PHP_EOL;
        } else {
            echo "Line: $counter. User not found: $userName".PHP_EOL;
        }
    }

    $counter++;
}