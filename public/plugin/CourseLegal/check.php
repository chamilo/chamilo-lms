<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/config.php';

header('Content-Type: application/json; charset=utf-8');

function course_legal_json_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

api_block_anonymous_users();

$plugin = CourseLegalPlugin::create();

if (!$plugin->isEnabled()) {
    course_legal_json_response([
        'required' => false,
        'accepted' => true,
        'url' => null,
    ]);
}

$courseId = isset($_GET['cid']) ? (int) $_GET['cid'] : 0;
if (empty($courseId) && isset($_GET['course_id'])) {
    $courseId = (int) $_GET['course_id'];
}

$sessionId = isset($_GET['sid']) ? (int) $_GET['sid'] : 0;
if (isset($_GET['session_id'])) {
    $sessionId = (int) $_GET['session_id'];
}

if (empty($courseId)) {
    course_legal_json_response([
        'required' => false,
        'accepted' => true,
        'url' => null,
    ]);
}

$courseInfo = api_get_course_info_by_id($courseId);
if (empty($courseInfo) || empty($courseInfo['code'])) {
    course_legal_json_response([
        'required' => false,
        'accepted' => true,
        'url' => null,
    ]);
}

$userId = api_get_user_id();

if (api_is_platform_admin() || api_is_allowed_to_edit()) {
    course_legal_json_response([
        'required' => false,
        'accepted' => true,
        'url' => null,
    ]);
}

$canAccess = false;
if (empty($sessionId)) {
    $canAccess = CourseManager::is_user_subscribed_in_course($userId, $courseInfo['code'])
        || api_check_user_access_to_legal($courseInfo);
} else {
    $userStatus = SessionManager::get_user_status_in_course_session($userId, $courseId, $sessionId);
    $canAccess = isset($userStatus) || api_check_user_access_to_legal($courseInfo);
}

if (!$canAccess) {
    course_legal_json_response([
        'required' => false,
        'accepted' => true,
        'url' => null,
    ]);
}

$courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
$courseRow = Database::fetch_assoc(
    Database::query(
        "SELECT activate_legal FROM $courseTable WHERE id = $courseId"
    )
);

$activateLegal = isset($courseRow['activate_legal']) ? (int) $courseRow['activate_legal'] : 0;
if (1 !== $activateLegal) {
    course_legal_json_response([
        'required' => false,
        'accepted' => true,
        'url' => null,
    ]);
}

$legalData = $plugin->getData($courseId, $sessionId);
if (empty($legalData) || empty(trim((string) ($legalData['content'] ?? '')))) {
    course_legal_json_response([
        'required' => false,
        'accepted' => true,
        'url' => null,
    ]);
}

$userAgreement = $plugin->getUserAcceptedLegal($userId, $courseId, $sessionId);
$accepted = !empty($userAgreement) && 1 === (int) ($userAgreement['web_agreement'] ?? 0);

$legalUrl = api_get_path(WEB_CODE_PATH).'course_info/legal.php?course_code='.
    urlencode($courseInfo['code']).'&session_id='.$sessionId;

course_legal_json_response([
    'required' => true,
    'accepted' => $accepted,
    'url' => $accepted ? null : $legalUrl,
]);
