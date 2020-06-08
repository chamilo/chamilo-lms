<?php
/* For licensing terms, see /license.txt */

/**
 * Show the achieved badges by an user.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
require_once __DIR__.'/../inc/global.inc.php';

$userId = isset($_GET['user']) ? (int) $_GET['user'] : 0;

if (empty($userId)) {
    api_not_allowed(true);
}

Skill::isAllowed($userId);

$courseId = api_get_course_int_id();
$sessionId = api_get_session_id();

$objSkillRelUser = new SkillRelUser();
$userSkills = $objSkillRelUser->getUserSkills($userId, $courseId, $sessionId);

if (empty($userSkills)) {
    api_not_allowed(true);
}

$assertions = [];
foreach ($userSkills as $skill) {
    $skillId = current($skill);
    $assertionUrl = api_get_path(WEB_CODE_PATH).'badge/assertion.php?';
    $assertionUrl .= http_build_query([
        'user' => $userId,
        'skill' => $skillId,
        'course' => $courseId,
        'session' => $sessionId,
    ]);

    $assertions[] = $assertionUrl;
}

$backpack = 'https://backpack.openbadges.org/';

$configBackpack = api_get_setting('openbadges_backpack');
if (0 !== strcmp($backpack, $configBackpack)) {
    $backpack = $configBackpack;
    if (substr($backpack, -1) !== '/') {
        $backpack .= '/';
    }
}

$htmlHeadXtra[] = '<script src="'.$backpack.'issuer.js"></script>';

$tpl = new Template(get_lang('Badges'), false, false);

$tpl->assign(
    'content',
    "<script>
    $(function() {
        OpenBadges.issue_no_modal(".json_encode($assertions)."); 
    });
    </script>"
);

$tpl->display_one_col_template();
