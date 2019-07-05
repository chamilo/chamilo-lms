<?php
/* For licensing terms, see /license.txt */

/**
 * Create skill form.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 *
 * @package chamilo.admin
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
Skill::isAllowed();

$interbreadcrumb[] = ["url" => 'index.php', "name" => get_lang('PlatformAdmin')];
$interbreadcrumb[] = ['url' => 'skill_list.php', 'name' => get_lang('ManageSkills')];

/* Process data */
$skillParentId = isset($_GET['parent']) ? intval($_GET['parent']) : 0;
$formDefaultValues = [];

$objSkill = new Skill();
if ($skillParentId > 0) {
    $skillParentInfo = $objSkill->getSkillInfo($skillParentId);

    $formDefaultValues = [
        'parent_id' => $skillParentInfo['id'],
        'gradebook_id' => [],
    ];

    foreach ($skillParentInfo['gradebooks'] as $gradebook) {
        $formDefaultValues['gradebook_id'][] = intval($gradebook['id']);
    }
}

/* Form */
$createForm = new FormValidator('skill_create');
$createForm->addHeader(get_lang('CreateSkill'));
$returnParams = $objSkill->setForm($createForm, []);
$jquery_ready_content = $returnParams['jquery_ready_content'];

// the $jquery_ready_content variable collects all functions that will be load in the $(document).ready javascript function
if (!empty($jquery_ready_content)) {
    $htmlHeadXtra[] = '<script>
    $(function () {
        '.$jquery_ready_content.'
    });
    </script>';
}

$createForm->setDefaults($formDefaultValues);

if ($createForm->validate()) {
    $skillValues = $createForm->getSubmitValues();
    $created = $objSkill->add($skillValues);

    $skillValues['item_id'] = $created;
    $extraFieldValue = new ExtraFieldValue('skill');
    $extraFieldValue->saveFieldValues($skillValues);
    if ($created) {
        $url = api_get_path(WEB_CODE_PATH).'admin/skill_edit.php?id='.$created;
        $link = Display::url($skillValues['name'], $url);
        Display::addFlash(
            Display::return_message(get_lang('TheSkillHasBeenCreated').': '.$link, 'success', false)
        );
    } else {
        Display::addFlash(
            Display::return_message(get_lang('CannotCreateSkill'), 'error')
        );
    }

    header('Location: '.api_get_path(WEB_CODE_PATH).'admin/skill_list.php');
    exit;
}

$toolbar = $objSkill->getToolbar();

$tpl = new Template(get_lang('CreateSkill'));
$tpl->assign('content', $toolbar.$createForm->returnForm());
$tpl->display_one_col_template();
