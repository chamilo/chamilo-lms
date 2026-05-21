<?php

/* For license terms, see /license.txt */

require_once __DIR__.'/config.php';

api_block_anonymous_users();

$legal = CourseLegalPlugin::create();

if (!$legal->isEnabled()) {
    api_not_allowed(true);
}

$courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;
$sessionId = isset($_GET['session_id']) ? (int) $_GET['session_id'] : 0;

if (empty($courseId)) {
    api_not_allowed(true);
}

$courseInfo = api_get_course_info_by_id($courseId);

if (empty($courseInfo)) {
    api_not_allowed(true);
}

$userId = api_get_user_id();
$canAccess = api_is_platform_admin();

if (!$canAccess) {
    if (empty($sessionId)) {
        $canAccess = CourseManager::is_user_subscribed_in_course($userId, $courseInfo['code'])
            || api_check_user_access_to_legal($courseInfo);
    } else {
        $userStatus = SessionManager::get_user_status_in_course_session($userId, $courseId, $sessionId);
        $canAccess = isset($userStatus) || api_check_user_access_to_legal($courseInfo);
    }
}

if (!$canAccess) {
    api_not_allowed(true);
}

$data = $legal->getData($courseId, $sessionId);

if (empty($data['filename'])) {
    api_not_allowed(true);
}

$filePath = $legal->getStoredFilePath($courseId, $sessionId, $data['filename']);

if (!is_file($filePath)) {
    api_not_allowed(true);
}

$fileName = basename($data['filename']);
$contentType = function_exists('mime_content_type') ? mime_content_type($filePath) : 'application/octet-stream';

if (empty($contentType)) {
    $contentType = 'application/octet-stream';
}

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: '.$contentType);
header('Content-Length: '.filesize($filePath));
header('Content-Disposition: attachment; filename="'.str_replace('"', '', $fileName).'"');
header('X-Content-Type-Options: nosniff');

readfile($filePath);
exit;
