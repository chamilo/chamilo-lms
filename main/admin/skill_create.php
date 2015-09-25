<?php
/* For licensing terms, see /license.txt */
/**
 * Create skill form
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.admin
 */
use ChamiloSession as Session;

$cidReset = true;

require_once '../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

if (api_get_setting('allow_skills_tool') != 'true') {
    api_not_allowed();
}

$interbreadcrumb[] = array("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => 'skill_list.php', 'name' => get_lang('ManageSkills'));

/* Process data */
$skillParentId = isset($_GET['parent']) ? intval($_GET['parent']) : 0;

$formDefaultValues = [];

$objSkill = new Skill();
$objGradebook = new Gradebook();

if ($skillParentId > 0) {
    $skillParentInfo = $objSkill->get_skill_info($skillParentId);

    $formDefaultValues = [
        'parent_id' => $skillParentInfo['id'],
        'gradebook_id' => []
    ];

    foreach ($skillParentInfo['gradebooks'] as $gradebook) {
        $formDefaultValues['gradebook_id'][] = intval($gradebook['id']);
    }
}

$allSkills = $objSkill->get_all();
$allGradebooks = $objGradebook->find('all');

$skillList = [0 => get_lang('None')];
$gradebookList = [];

foreach ($allSkills as $skill) {
    $skillList[$skill['id']] = $skill['name'];
}

foreach ($allGradebooks as $gradebook) {
    $gradebookList[$gradebook['id']] = $gradebook['name'];
}

/* Form */
$createForm = new FormValidator('skill_create');
$createForm->addHeader(get_lang('CreateSkill'));
$createForm->addText('name', get_lang('Name'), true, ['id' => 'name']);
$createForm->addText('short_code', get_lang('ShortCode'), false, ['id' => 'short_code']);
$createForm->addSelect('parent_id', get_lang('Parent'), $skillList, ['id' => 'parent_id']);
$createForm->addSelect(
    'gradebook_id',
    [get_lang('Gradebook'), get_lang('WithCertificate')],
    $gradebookList,
    ['id' => 'gradebook_id', 'multiple' => 'multiple', 'size' => 10]
);
$createForm->addTextarea('description', get_lang('Description'), ['id' => 'description', 'rows' => 7]);
$createForm->addButtonSave(get_lang('Save'));
$createForm->addHidden('id', null);

$createForm->setDefaults($formDefaultValues);

if ($createForm->validate()) {
    $created = $objSkill->add($createForm->getSubmitValues());

    if ($created) {
        Session::write(
            'message',
            Display::return_message(get_lang('TheSkillHasBeenCreated'), 'success')
        );
    } else {
        Session::write(
            'message',
            Display::return_message(get_lang('CannotCreateSkill'), 'error')
        );
    }

    header('Location: '.api_get_path(WEB_CODE_PATH) . 'admin/skill_list.php');
    exit;
}

/* view */
$tpl = new Template(get_lang('CreateSkill'));
$tpl->assign('content', $createForm->returnForm());
$tpl->display_one_col_template();
