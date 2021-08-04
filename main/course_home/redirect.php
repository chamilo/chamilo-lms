<?php

/* For licensing terms, see /license.txt */

/**
 * Redirects a session name to a correct session id.
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$sessionName = isset($_GET['session_name']) ? $_GET['session_name'] : '';
$courseCode = isset($_GET['cidReq']) ? $_GET['cidReq'] : '';

if (!empty($sessionName) && !empty($courseCode)) {
    $sessionInfo = SessionManager::get_session_by_name($sessionName);
    $courseInfo = api_get_course_info($courseCode);
    if (!empty($sessionInfo) && !empty($courseInfo)) {
        $url = api_get_path(WEB_COURSE_PATH).$courseInfo['directory'].'/index.php?id_session='.$sessionInfo['id'];
        header('Location: '.$url);
        exit;
    }
}

api_not_allowed(true);
