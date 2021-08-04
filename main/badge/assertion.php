<?php
/* For licensing terms, see /license.txt */

/**
 * Show information about a new assertion.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
require_once __DIR__.'/../inc/global.inc.php';

$userId = isset($_GET['user']) ? (int) $_GET['user'] : 0;
$skillId = isset($_GET['skill']) ? (int) $_GET['skill'] : 0;
$courseId = isset($_GET['course']) ? (int) $_GET['course'] : 0;
$sessionId = isset($_GET['session']) ? (int) $_GET['session'] : 0;

if (0 === $userId || 0 === $skillId) {
    exit;
}

$objSkill = new Skill();
if (!$objSkill->userHasSkill($userId, $skillId, $courseId, $sessionId)) {
    exit;
}

$objSkillRelUser = new SkillRelUser();
$userSkill = $objSkillRelUser->getByUserAndSkill(
    $userId,
    $skillId,
    $courseId,
    $sessionId
);

if (false == $userSkill) {
    exit;
}

$user = api_get_user_info($userSkill['user_id']);

$json = [
    'uid' => $userSkill['id'],
    'recipient' => [
        'type' => 'email',
        'hashed' => false,
        'identity' => $user['email'],
    ],
    'issuedOn' => strtotime($userSkill['acquired_skill_at']),
    'badge' => api_get_path(WEB_CODE_PATH)."badge/class.php?id=$skillId",
    'verify' => [
        'type' => 'hosted',
        'url' => api_get_path(WEB_CODE_PATH)."badge/assertion.php?".http_build_query([
            'user' => $userId,
            'skill' => $skillId,
            'course' => $courseId,
            'session' => $sessionId,
        ]),
    ],
];

header('Content-Type: application/json');

echo json_encode($json);
