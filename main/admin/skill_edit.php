<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Skill edit form
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.admin
 */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

if (api_get_setting('allow_skills_tool') != 'true') {
    api_not_allowed();
}

$interbreadcrumb[] = array("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => 'skill_list.php', 'name' => get_lang('ManageSkills'));

/* Process data */
$skillId = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

$objSkill = new Skill();
$objGradebook = new Gradebook();

$skillInfo = $objSkill->get_skill_info($skillId);

$allSkills = $objSkill->get_all();
$allGradebooks = $objGradebook->find('all');

$skillDefaultInfo = [
    'id' => $skillInfo['id'],
    'name' => $skillInfo['name'],
    'short_code' => $skillInfo['short_code'],
    'description' => $skillInfo['description'],
    'parent_id' => $skillInfo['extra']['parent_id'],
    'gradebook_id' => []
];

foreach ($skillInfo['gradebooks'] as $gradebook) {
    $skillDefaultInfo['gradebook_id'][] = $gradebook['id'];
}

$skillList = [0 => get_lang('None')];
$gradebookList = [];

foreach ($allSkills as $skill) {
    if ($skill['id'] == $skillInfo['id']) {
        continue;
    }

    $skillList[$skill['id']] = $skill['name'];
}

foreach ($allGradebooks as $gradebook) {
    $gradebookList[$gradebook['id']] = $gradebook['name'];
}

/* Form */
$editForm = new FormValidator('skill_edit');
$editForm->addHeader(get_lang('SkillEdit'));
$editForm->addText('name', get_lang('Name'), true, ['id' => 'name']);
$editForm->addText('short_code', get_lang('ShortCode'), false, ['id' => 'short_code']);
$editForm->addSelect('parent_id', get_lang('Parent'), $skillList, ['id' => 'parent_id']);
$editForm->addSelect(
    'gradebook_id',
    [get_lang('Gradebook'), get_lang('WithCertificate')],
    $gradebookList,
    ['id' => 'gradebook_id', 'multiple' => 'multiple', 'size' => 10]
);
$editForm->addTextarea('description', get_lang('Description'), ['id' => 'description', 'rows' => 7]);
// EXTRA FIELDS
$extraField = new ExtraField('skill');
$returnParams = $extraField->addElements($editForm, $skillId);
$jquery_ready_content = $returnParams['jquery_ready_content'];

// the $jquery_ready_content variable collects all functions that will be load in the $(document).ready javascript function
if (!empty($jquery_ready_content)) {
    $htmlHeadXtra[] = '<script>
    $(document).ready(function(){
        ' . $jquery_ready_content.'
    });
    </script>';
}

$editForm->addButtonSave(get_lang('Save'));
$editForm->addHidden('id', null);

$editForm->setDefaults($skillDefaultInfo);

if ($editForm->validate()) {
    $skillValues = $editForm->getSubmitValues();
    $updated = $objSkill->edit($skillValues);

    $extraFieldValue = new ExtraFieldValue('skill');
    $extraFieldValue->saveFieldValues($skillValues);

    if ($updated) {
        Session::write(
            'message',
            Display::return_message(get_lang('TheSkillHasBeenUpdated'), 'success')
        );
    } else {
        Session::write(
            'message',
            Display::return_message(get_lang('CannotUpdateSkill'), 'error')
        );
    }

    header('Location: '.api_get_path(WEB_CODE_PATH).'admin/skill_list.php');
    exit;
}

/* view */
$tpl = new Template(get_lang('SkillEdit'));
$tpl->assign('content', $editForm->returnForm());
$tpl->display_one_col_template();
