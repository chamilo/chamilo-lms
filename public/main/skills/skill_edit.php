<?php
/* For licensing terms, see /license.txt */

/**
 * Skill edit form.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */

use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\CoreBundle\Framework\Container;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
SkillModel::isAllowed();

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => 'skill_list.php', 'name' => get_lang('Manage skills')];

/* Process data */
$skillId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

$objSkill = new SkillModel();
$objGradebook = new Gradebook();
$skillInfo = $objSkill->getSkillInfo($skillId);

$em = Database::getManager();
$skill = $em->find(Skill::class, $skillId);
$skill->setLocale(Container::getParameter('locale'));
$em->refresh($skill);

if (empty($skillInfo)) {
    api_not_allowed(true);
}

$allGradebooks = $objGradebook->find('all');

$skillDefaultInfo = [
    'id' => $skillInfo['id'],
    'title' => $skill->getTitle(),
    'short_code' => $skill->getShortCode(),
    'description' => $skillInfo['description'],
    'parent_id' => $skillInfo['extra']['parent_id'],
    'criteria' => $skillInfo['criteria'],
    'gradebook_id' => [],
];

foreach ($skillInfo['gradebooks'] as $gradebook) {
    $skillDefaultInfo['gradebook_id'][] = $gradebook['id'];
}

$gradebookList = [];
foreach ($allGradebooks as $gradebook) {
    $gradebookList[$gradebook['id']] = $gradebook['title'];
}

/* Form */
$editForm = new FormValidator('skill_edit');
$editForm->addHeader(get_lang('Edit skill'));
$returnParams = $objSkill->setForm($editForm, $skillInfo);

$jquery_ready_content = $returnParams['jquery_ready_content'];

// the $jquery_ready_content variable collects all functions that will be load
// in the $(document).ready javascript function
if (!empty($jquery_ready_content)) {
    $htmlHeadXtra[] = '<script>
    $(function () {
        '.$jquery_ready_content.'
    });
    </script>';
}

$editForm->setDefaults($skillDefaultInfo);
if ($editForm->validate()) {
    $skillValues = $editForm->getSubmitValues();
    $updated = $objSkill->edit($skillValues);

    $extraFieldValue = new ExtraFieldValue('skill');
    $extraFieldValue->saveFieldValues($skillValues);

    if ($updated) {
        Display::addFlash(
            Display::return_message(
                get_lang('The skill has been updated'),
                'success'
            )
        );
    } else {
        Display::addFlash(
            Display::return_message(
                get_lang('Cannot update skill'),
                'error'
            )
        );
    }

    header('Location: '.api_get_path(WEB_CODE_PATH).'skills/skill_list.php');
    exit;
}

$toolbar = $objSkill->getToolBar();

/* view */
$tpl = new Template(get_lang('Edit skill'));
$tpl->assign('content', $toolbar.$editForm->returnForm());
$tpl->display_one_col_template();
