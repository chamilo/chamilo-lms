<?php
/* For licensing terms, see /license.txt */

use Chamilo\SkillBundle\Entity\SkillRelItem;

require_once __DIR__.'/../inc/global.inc.php';

if (api_get_configuration_value('allow_skill_rel_items') == false) {
    api_not_allowed(true);
}

api_protect_course_script();
GradebookUtils::block_students();
$courseId = api_get_course_int_id();
$sessionId = api_get_session_id();

$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
$categoryId = isset($_GET['selectcat']) ? (int) $_GET['selectcat'] : 0;
$userInfo = api_get_user_info($userId);

if (empty($userInfo)) {
    api_not_allowed(true);
}

$skills = Skill::getSkillRelItemsPerCourse($courseId, $sessionId);
$uniqueSkills = [];
$itemsPerSkill = [];
$uniqueSkillsConclusion = [];

$skillRelUser = new SkillRelUser();
$userSkills = $skillRelUser->getUserSkills($userId, api_get_course_int_id(), api_get_session_id());
$userSkillsList = [];
if (!empty($userSkills)) {
    foreach ($userSkills as $userSkill) {
        $userSkillsList[] = $userSkill['skill_id'];
    }
}

$em = Database::getManager();

/** @var SkillRelItem $skill */
foreach ($skills as $skill) {
    $skillId = $skill->getSkill()->getId();
    $uniqueSkills[$skillId] = $skill->getSkill();
    $itemInfo = Skill::getItemInfo($skill->getItemId(), $skill->getItemType());

    $criteria = [
        'user' => $userId,
        'skillRelItem' => $skill
    ];
    $skillRelItemRelUser = $em->getRepository('ChamiloSkillBundle:SkillRelItemRelUser')->findOneBy($criteria);
    $itemInfo['status'] = $skillRelItemRelUser ? true : false;

    $itemsPerSkill[$skillId][]['info'] = $itemInfo;
}

foreach ($itemsPerSkill as $skillId => $skillList) {
    $allSkillsCompleted = true;
    foreach ($skillList as $itemInfo) {
        if ($itemInfo['info']['status'] === false) {
            $allSkillsCompleted = false;
            break;
        }
    }
    $uniqueSkillsConclusion[$skillId] = $allSkillsCompleted;
}

$interbreadcrumb[] = [
    'url' => Category::getUrl(),
    'name' => get_lang('Gradebook')
];
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'gradebook/gradebook_display_summary.php?'.api_get_cidreq().'&selectcat='.$categoryId,
    'name' => get_lang('GradebookListOfStudentsReports')
];

$template = new Template(get_lang('SkillUserList'));
$template->assign('conclusion_list', $uniqueSkillsConclusion);
$template->assign('skills', $uniqueSkills);
$template->assign('items', $itemsPerSkill);
$template->assign('user', $userInfo);

$templateName = $template->get_template('gradebook/skill_rel_user.tpl');
$content = $template->fetch($templateName);
$template->assign('content', $content);
$template->display_one_col_template();
