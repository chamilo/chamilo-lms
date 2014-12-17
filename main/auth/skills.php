<?php
/* For licensing terms, see /license.txt */
/**
 * Skills reporting
 * @package chamilo.reporting
 */
require_once '../inc/global.inc.php';

if (!api_is_student()) {
    api_not_allowed();
}

$this_section = SECTION_TRACKING;

$toolName = get_lang('Skills');

$tableRows = array();

$objSkill = new Skill();

$objSkillRelUser = new SkillRelUser();
$userSkills = $objSkillRelUser->get_all(array(
    'where' => array(
        'user_id = ?' => api_get_user_id()
    )
));

foreach ($userSkills as $achievedSkill) {
    $skill = $objSkill->get($achievedSkill['skill_id']);
    $course = api_get_course_info_by_id($achievedSkill['course_id']);

    $tableRows[] = array(
        'skillName' => $skill['name'],
        'achievedAt' => api_format_date($achievedSkill['acquired_skill_at'], DATE_FORMAT_NUMBER),
        'courseImage' => $course['course_image'],
        'courseName' => $course['name']
    );
}

/*
 * View
 */
$tpl = new Template($toolName);

$tpl->assign('rows', $tableRows);

$contentTemplate = $tpl->get_template('auth/skills.tpl');

$tpl->display($contentTemplate);
