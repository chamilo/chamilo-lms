<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Sequence;

$cidReset = true;

require_once '../inc/global.inc.php';

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

$formSequence = new FormValidator('sequence_form', 'post', api_get_self(),null,null,'horizontal');
$formSequence->addText('name', get_lang('Sequence'), true, ['cols-size' => [4, 4, 2]]);
$formSequence->addButtonCreate(get_lang('AddSequence'), 'submit_sequence', false, ['cols-size' => [4, 4, 2]]);

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

$saveForm = new FormValidator('');
$saveForm->addHidden('sequence_type', 'session');
$em = Database::getManager();

$sequenceList = $em->getRepository('ChamiloCoreBundle:Sequence')->findAll();

$saveForm->addSelect(
    'sequence',
    get_lang('Sequence'),
    $sequenceList,
    ['id' => 'sequence_id', 'cols-size' => [4, 4, 2]]
);

$saveForm->addSelect(
    'sessions',
    get_lang('Sessions'),
    $sessionList,
    ['id' => 'item', 'cols-size' => [4, 4, 2]]
);
$saveForm->addButtonNext(get_lang('UseAsReference'), 'use_as_reference');

$form = new FormValidator('');
$form->addSelect(
    'requirements',
    get_lang('Requirements'),
    $sessionList,
    ['id' => 'requirements', 'multiple' => 'multiple', 'cols-size' => [4, 4, 2]]
);

$form->addButtonCreate(get_lang('SetAsRequirementForSelected'), 'set_requirement');
$form->addButtonSave(get_lang('Save'), 'save_resource');

$tpl->assign('create_sequence', $formSequence->returnForm());
$tpl->assign('save_sequence', $saveForm->returnForm());
$tpl->assign('left_block', $form->returnForm());
$layout = $tpl->get_template('admin/resource_sequence.tpl');
$tpl->display($layout);

