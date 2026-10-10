<?php
/* For licensing terms, see /license.txt */

/**
 * Create skill form.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Framework\Container;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script(false, 'true' === api_get_setting('allow_hr_skills_management'));
SkillModel::isAllowed();

/* Process data */
$skillParentId = isset($_GET['parent']) ? (int) $_GET['parent'] : 0;
$returnToSkillWheel = 'skill-wheel' === ($_REQUEST['origin'] ?? '');
$returnSkillId = isset($_REQUEST['return_skill']) ? (int) $_REQUEST['return_skill'] : $skillParentId;
$skillWheelUrl = Container::getRouter()->generate('skill_wheel');
if ($returnSkillId > 0) {
    $skillWheelUrl .= '?'.http_build_query(['skillId' => $returnSkillId]);
}

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = $returnToSkillWheel
    ? ['url' => $skillWheelUrl, 'name' => get_lang('Skills wheel')]
    : ['url' => 'skill_list.php', 'name' => get_lang('Manage skills')];

$formDefaultValues = [];

$objSkill = new SkillModel();
if ($skillParentId > 0) {
    $skillParentInfo = $objSkill->getSkillInfo($skillParentId);

    $formDefaultValues = [
        'parent_id' => $skillParentInfo['id'],
        'gradebook_id' => [],
    ];

    foreach ($skillParentInfo['gradebooks'] as $gradebook) {
        $formDefaultValues['gradebook_id'][] = (int) $gradebook['id'];
    }
}

/* Form */
$createForm = new FormValidator('skill_create');
$createForm->addHeader(get_lang('Create skill'));
$returnParams = $objSkill->setForm($createForm, []);
if ($returnToSkillWheel) {
    $createForm->addHidden('origin', 'skill-wheel');
    $createForm->addHidden('return_skill', $returnSkillId);
}

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
        $url = api_get_path(WEB_CODE_PATH).'skills/skill_edit.php?id='.$created;
        $link = Display::url($skillValues['title'], $url);
        Display::addFlash(
            Display::return_message(get_lang('The skill has been created').': '.$link, 'success', false)
        );
    } else {
        Display::addFlash(
            Display::return_message(get_lang('Cannot create skill'), 'error')
        );
    }

    if ($returnToSkillWheel) {
        $destinationSkillId = $created ? (int) $created : $returnSkillId;
        $redirectUrl = api_get_path(WEB_PATH).'skill/wheel';
        if ($destinationSkillId > 0) {
            $redirectUrl .= '?'.http_build_query(['skillId' => $destinationSkillId]);
        }
    } else {
        $redirectUrl = api_get_path(WEB_CODE_PATH).'skills/skill_list.php';
    }

    header('Location: '.$redirectUrl);
    exit;
}

if ($returnToSkillWheel) {
    $toolbar = Display::toolbarAction('toolbar', [
        Display::url(
            Display::getMdiIcon(
                ActionIcon::BACK,
                'ch-tool-icon',
                null,
                ICON_SIZE_MEDIUM,
                get_lang('Skills wheel')
            ),
            $skillWheelUrl,
            ['title' => get_lang('Skills wheel')]
        ),
    ]);
} else {
    $toolbar = $objSkill->getToolbar();
}

$tpl = new Template(get_lang('Create skill'));
$tpl->assign('content', $toolbar.$createForm->returnForm());
$tpl->display_one_col_template();
