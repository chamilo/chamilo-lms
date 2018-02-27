<?php
/* For licensing terms, see /license.txt */

use Chamilo\SkillBundle\Entity\SkillRelCourse;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

if (api_get_configuration_value('allow_skill_rel_items') == false) {
    api_not_allowed(true);
}

$courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;

$course = api_get_course_entity($courseId);
if (empty($course)) {
    api_not_allowed(true);
}

$sessionId = isset($_GET['session_id']) ? (int) $_GET['session_id'] : null;

$url = api_get_self().'?course_id='.$courseId.'&session_id='.$sessionId;
$form = new FormValidator('skills', 'post', $url);

$sessionName = $course->getTitleAndCode();
if (!empty($sessionId)) {
    $session = api_get_session_entity($sessionId);
    $courseExistsInSession = SessionManager::sessionHasCourse($sessionId, $course->getCode());
    if (!$courseExistsInSession) {
        api_not_allowed(true);
    }
    $sessionName = ' '.$session->getName().' - '.$course->getTitleAndCode();
}

$form->addHeader(get_lang('AddSkills').$sessionName);

$skillList = [];
$em = Database::getManager();
$items = $em->getRepository('ChamiloSkillBundle:SkillRelCourse')->findBy(
    ['course' => $courseId, 'session' => $sessionId]
);
/** @var SkillRelCourse $skillRelCourse */
foreach ($items as $skillRelCourse) {
    $skillList[$skillRelCourse->getSkill()->getId()] = $skillRelCourse->getSkill()->getName();
}

$form->addHidden('course_id', $courseId);
$form->addHidden('session_id', $sessionId);

$form->addSelectAjax(
    'skills',
    get_lang('Skills'),
    $skillList,
    [
        'url' => api_get_path(WEB_AJAX_PATH).'skill.ajax.php?a=search_skills',
        'multiple' => 'multiple',
    ]
);

$form->addButtonSave(get_lang('Save'));

$form->setDefaults(['skills' => array_keys($skillList)]);

if ($form->validate()) {
    $result = Skill::saveSkillsToCourseFromForm($form);
    if ($result) {
        Display::addFlash(Display::return_message(get_lang('Updated')));
    }
    header('Location: '.$url);
    exit;
}
$content = $form->returnForm();

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'session/session_list.php',
    'name' => get_lang('SessionList'),
];

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'session/resume_session.php?id_session='.$sessionId,
    'name' => get_lang('SessionOverview'),
];

$template = new Template(get_lang('SkillRelCourses'));
$template->assign('content', $content);
$template->display_one_col_template();
