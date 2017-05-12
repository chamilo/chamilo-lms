<?php
/* For licensing terms, see /license.txt */

/**
 * Show information about a new assertion
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.badge
 */
header('Content-Type: application/json');

require_once __DIR__.'/../inc/global.inc.php';

$userId = isset($_GET['user']) ? intval($_GET['user']) : 0;
$skillId = isset($_GET['skill']) ? intval($_GET['skill']) : 0;
$courseId = isset($_GET['course']) ? intval($_GET['course']) : 0;
$sessionId = isset($_GET['session']) ? intval($_GET['session']) : 0;

if ($userId === 0 || $skillId === 0) {
    exit;
}

$objSkill = new Skill();

if (!$objSkill->user_has_skill($userId, $skillId, $courseId, $sessionId)) {
    exit;
}

$objSkillRelUser = new SkillRelUser();
$userSkill = $objSkillRelUser->getByUserAndSkill($userId, $skillId, $courseId, $sessionId);

if ($userSkill == false) {
    exit;
}

$user = api_get_user_info($userSkill['user_id']);

$json = array(
    'uid' => $userSkill['id'],
    'recipient' => array(
        'type' => 'email',
        'hashed' => false,
        'identity' => $user['email']
    ),
    'issuedOn' => strtotime($userSkill['acquired_skill_at']),
    'badge' => api_get_path(WEB_CODE_PATH)."badge/class.php?id=$skillId",
    'verify' => array(
        'type' => 'hosted',
        'url' => api_get_path(WEB_CODE_PATH)."badge/assertion.php?".http_build_query(array(
            'user' => $userId,
            'skill' => $skillId,
            'course' => $courseId,
            'session' => $sessionId
        ))
    )
);

echo json_encode($json);
