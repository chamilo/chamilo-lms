<?php

/* For licensing terms, see /license.txt */

/**
 * Move sessions from URL 1 to URL 2
 */
exit;

require_once __DIR__.'/../../main/inc/global.inc.php';

$test = true;
$sessionsToMove = [1];
$urlSourceId = 2;
$urlDestinationId = 3;

$urlSourceInfo = UrlManager::get_url_data_from_id($urlSourceId);
if (empty($urlSourceInfo)) {
    echo 'Portal not found';
    exit;
}

$urlDestination = UrlManager::get_url_data_from_id($urlDestinationId);
if (empty($urlDestination)) {
    echo 'Portal not found';
    exit;
}

if ($test) {
    echo '----'.PHP_EOL;
    echo '----No DB changes'.PHP_EOL;
    echo '----'.PHP_EOL;
}

foreach ($sessionsToMove as $sessionId) {
    $sessionInfo = api_get_session_info($sessionId);
    echo "Session: $sessionId ".PHP_EOL;

    if (empty($sessionInfo)) {
        echo "Session does not exists $sessionId ".PHP_EOL;
        continue;
    }

    $coachId = $sessionInfo['coach_id'];
    if (!empty($coachId)) {
        if ($test) {
            echo "Add coach: $coachId to URL: $urlDestinationId".PHP_EOL;
        } else {
            //UrlManager::delete_url_rel_user($coachId, $sourceId);
            UrlManager::add_user_to_url($coachId, $urlDestinationId);
        }
    }

    $sessionAdminId = $sessionInfo['session_admin_id'];
    if (!empty($sessionAdminId)) {
        if ($test) {
            echo "Add sessionAdminId: $sessionAdminId to URL: $urlDestinationId".PHP_EOL;
        } else {
            //UrlManager::delete_url_rel_user($coachId, $sourceId);
            UrlManager::add_user_to_url($sessionAdminId, $urlDestinationId);
        }
    }

    $courses = SessionManager::getCoursesInSession($sessionId);
    foreach ($courses as $courseId) {
        if ($test) {
            echo "Add course: $courseId to URL: $urlDestinationId".PHP_EOL;
        } else {
            UrlManager::add_course_to_url($courseId, $urlDestinationId);
        }

        $coaches = SessionManager::getCoachesByCourseSession($sessionId, $courseId);
        echo PHP_EOL.'Coaches: '.PHP_EOL;
        foreach ($coaches as $coachId) {
            if ($test) {
                echo "Add coach: $coachId to URL: $urlDestinationId".PHP_EOL;
            } else {
                UrlManager::add_user_to_url($coachId, $urlDestinationId);
            }
        }
    }

    $users = SessionManager::get_users_by_session($sessionId, null, false, $urlSourceId);
    echo PHP_EOL.'Students: '.PHP_EOL;
    foreach ($users as $user) {
        $userId = $user['user_id'];
        //UrlManager::delete_url_rel_user($userId, $sourceId);
        if ($test) {
            echo "Add user: $userId to URL: $urlDestinationId".PHP_EOL;
        } else {
            UrlManager::add_user_to_url($userId, $urlDestinationId);
        }
    }

    if ($test) {
        echo "Add session: $sessionId to URL: $urlDestinationId".PHP_EOL;
    } else {
        $sql = "DELETE FROM access_url_rel_session
                WHERE session_id = $sessionId AND access_url_id = $urlSourceId";
        Database::query($sql);
        UrlManager::add_session_to_url($sessionId, $urlDestinationId);
    }
}
