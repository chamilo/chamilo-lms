<?php
/* For licensing terms, see /license.txt */

/**
 * Show the achieved badges by an user
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.badge
 */

require_once __DIR__.'/../inc/global.inc.php';

if (api_get_setting('allow_skills_tool') !== 'true') {
    api_not_allowed(true);
}

$userId = isset($_GET['user']) ? intval($_GET['user']) : 0;
$courseId = api_get_course_int_id();
$sessionId = api_get_session_id();

if ($userId === 0) {
    exit;
}

$objSkillRelUser = new SkillRelUser();
$userSkills = $objSkillRelUser->get_user_skills($userId, $courseId, $sessionId);

if (empty($userSkills)) {
    exit;
}

$assertions = array();

foreach ($userSkills as $skill) {
    $skillId = current($skill);

    $assertionUrl = api_get_path(WEB_CODE_PATH)."badge/assertion.php?";
    $assertionUrl .= http_build_query(array(
        'user' => $userId,
        'skill' => $skillId,
        'course' => $courseId,
        'session' => $sessionId
    ));

    $assertions[] = $assertionUrl;
}

$backpack = 'https://backpack.openbadges.org/';

$configBackpack = api_get_setting('openbadges_backpack');
if (strcmp($backpack, $configBackpack) !== 0) {
    $backpack = $configBackpack;
}

$htmlHeadXtra[] = '<script src="'.$backpack.'issuer.js"></script>';

$tpl = new Template(get_lang('Badges'), false, false);

$tpl->assign(
    'content',
    "<script>
    $(document).on('ready', function (){ 
        OpenBadges.issue_no_modal(" . json_encode($assertions)."); 
    });
    </script>"
);

$tpl->display_one_col_template();
