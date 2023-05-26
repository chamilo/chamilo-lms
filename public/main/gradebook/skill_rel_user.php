<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\SkillRelItem;
use Chamilo\CoreBundle\Entity\SkillRelItemRelUser;

require_once __DIR__.'/../inc/global.inc.php';

if ('true' !== api_get_setting('skill.allow_skill_rel_items')) {
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

$skills = SkillModel::getSkillRelItemsPerCourse($courseId, $sessionId);
$uniqueSkills = [];
$itemsPerSkill = [];
$uniqueSkillsConclusion = [];
$skillRelUser = new SkillRelUserModel();
$userSkills = $skillRelUser->getUserSkills($userId, api_get_course_int_id(), api_get_session_id());
$userSkillsList = [];
if (!empty($userSkills)) {
    foreach ($userSkills as $userSkill) {
        $userSkillsList[] = $userSkill['skill_id'];
    }
}

$em = Database::getManager();
$codePath = api_get_path(WEB_CODE_PATH);
/** @var SkillRelItem $skill */
foreach ($skills as $skill) {
    $skillId = $skill->getSkill()->getId();
    $uniqueSkills[$skillId] = $skill->getSkill();
    $itemInfo = SkillModel::getItemInfo($skill->getItemId(), $skill->getItemType());

    $criteria = [
        'user' => $userId,
        'skillRelItem' => $skill,
    ];
    /** @var SkillRelItemRelUser $skillRelItemRelUser */
    $skillRelItemRelUser = $em->getRepository(SkillRelItemRelUser::class)->findOneBy($criteria);
    $itemInfo['status'] = $skillRelItemRelUser ? true : false;
    $itemInfo['url_activity'] = $codePath.$skill->getItemResultList(api_get_cidreq());
    if ($skillRelItemRelUser) {
        $itemInfo['url_activity'] = $codePath.$skillRelItemRelUser->getUserItemResultUrl(api_get_cidreq());
    }

    $itemsPerSkill[$skillId][]['info'] = $itemInfo;
}
foreach ($itemsPerSkill as $skillId => $skillList) {
    $uniqueSkillsConclusion[$skillId] = in_array($skillId, $userSkillsList);
}

$interbreadcrumb[] = [
    'url' => Category::getUrl(),
    'name' => get_lang('Assessments'),
];
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'gradebook/gradebook_display_summary.php?'.api_get_cidreq().'&selectcat='.$categoryId,
    'name' => get_lang('AssessmentsListOfStudentsReports'),
];

$url = api_get_path(WEB_AJAX_PATH).'skill.ajax.php?a=assign_user_to_skill';

$template = new Template(get_lang('Skills and users list'));
$template->assign('conclusion_list', $uniqueSkillsConclusion);
$template->assign('skills', $uniqueSkills);
$template->assign('items', $itemsPerSkill);
$template->assign('user', $userInfo);
$template->assign('course_id', api_get_course_int_id());
$template->assign('session_id', api_get_session_id());
$template->assign('assign_user_url', $url);

$templateName = $template->get_template('gradebook/skill_rel_user.tpl');
$content = $template->fetch($templateName);
$template->assign('content', $content);
$template->display_one_col_template();
