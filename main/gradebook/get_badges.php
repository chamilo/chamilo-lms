<?php
/* For licensing terms, see /license.txt */
/**
 * Show the achieved badges by an user
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.badge
 */
$cidReset = true;

require_once '../inc/global.inc.php';

$userId = isset($_GET['user']) ? intval($_GET['user']) : 0;

if ($userId === 0) {
    exit;
}

$objSkillRelUser = new SkillRelUser();
$userSkills = $objSkillRelUser->get_user_skills($userId);

if (empty($userSkills)) {
    exit;
}

$assertions = array();

foreach ($userSkills as $skill) {
    $skillId = current($skill);

    $assertions[] = api_get_path(WEB_CODE_PATH) . "badge/assertion.php?user=$userId&skill=$skillId";
}

$backpack = 'https://backpack.openbadges.org/';

if (array_key_exists('openbadges_backpack', $_configuration)) {
    $backpack = $_configuration['openbadges_backpack'];
}

$htmlHeadXtra[] = '<script src="' . $backpack . 'issuer.js"></script>';

$tpl = new Template(get_lang('Badges'), false, false);

$tpl->assign(
    'content',
    "<script>"
    . "$(document).on('ready', function (){ OpenBadges.issue_no_modal(" . json_encode($assertions) . "); });"
    . "</script>"
);

$tpl->display_one_col_template();
