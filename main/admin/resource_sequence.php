<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Sequence;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_global_admin_script();

// setting breadcrumbs
$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));

$tpl = new Template(get_lang('ResourcesSequencing'));

$sessionListFromDatabase = SessionManager::get_sessions_list();
$sessionList = [];
if (!empty($sessionListFromDatabase)) {
    foreach ($sessionListFromDatabase as $sessionItem) {
        $sessionList[$sessionItem['id']] = $sessionItem['name'].' ('.$sessionItem['id'].')';
    }
}

$formSequence = new FormValidator('sequence_form', 'post', api_get_self(), null, null, 'inline');
$formSequence->addText('name', get_lang('Sequence'), true, ['cols-size' => [3, 8, 1]]);
$formSequence->addButtonCreate(get_lang('AddSequence'), 'submit_sequence', false, ['cols-size' => [3, 8, 1]]);

$em = Database::getManager();

// Add sequence
if ($formSequence->validate()) {
    $values = $formSequence->exportValues();
    $sequence = new Sequence();
    $sequence->setName($values['name']);
    $em->persist($sequence);
    $em->flush();
    header('Location: '.api_get_self());
    exit;
}

$selectSequence = new FormValidator('');
$selectSequence ->addHidden('sequence_type', 'session');
$em = Database::getManager();

$sequenceList = $em->getRepository('ChamiloCoreBundle:Sequence')->findAll();

$selectSequence->addSelect(
    'sequence',
    get_lang('Sequence'),
    $sequenceList,
    ['id' => 'sequence_id', 'cols-size' => [3, 7, 2]]
);

$form = new FormValidator('');
$form->addHtml("<div class='col-md-6'>");
$form->addHidden('sequence_type', 'session');
$form->addSelect(
    'sessions',
    get_lang('Sessions'),
    $sessionList,
    ['id' => 'item', 'cols-size' => [4, 7, 1]]
);
$form->addButtonNext(get_lang('UseAsReference'), 'use_as_reference', ['cols-size' => [4, 7, 1]]);
$form->addHtml("</div>");
$form->addHtml("<div class='col-md-6'>");
$form->addSelect(
    'requirements',
    get_lang('Requirements'),
    $sessionList,
    ['id' => 'requirements', 'cols-size' => [3, 7, 2]]
);

$form->addButtonCreate(get_lang('SetAsRequirement'), 'set_requirement', false, ['cols-size' => [3, 7, 2]]);
$form->addHtml("</div>");

$formSave = new FormValidator('');
$formSave->addHidden('sequence_type', 'session');
$formSave->addButton(
    'save_resource',
    get_lang('SaveSettings'),
    'floppy-o',
    'success',
    null,
    null,
    ['cols-size' => [1, 10, 1]]
);

$tpl->assign('create_sequence', $formSequence->returnForm());
$tpl->assign('select_sequence', $selectSequence->returnForm());
$tpl->assign('configure_sequence', $form->returnForm());
$tpl->assign('save_sequence', $formSave->returnForm());
$layout = $tpl->get_template('admin/resource_sequence.tpl');
$tpl->display($layout);

