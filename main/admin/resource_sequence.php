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

$formSequence = new FormValidator('sequence_form', 'post', api_get_self());
$formSequence->addText('name', get_lang('Sequence'));
$formSequence->addButtonCreate(get_lang('AddSequence'), 'submit_sequence');

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

$form = new FormValidator('');
$form->addHidden('sequence_type', 'session');
$em = Database::getManager();

$sequenceList = $em->getRepository('ChamiloCoreBundle:Sequence')->findAll();

$form->addSelect(
    'sequence',
    get_lang('Sequence'),
    $sequenceList,
    ['id' => 'sequence_id']
);

$form->addSelect(
    'sessions',
    get_lang('Sessions'),
    $sessionList,
    ['id' => 'item']
);
$form->addButtonNext(get_lang('UseAsReference'), 'use_as_reference');

$form->addSelect(
    'requirements',
    get_lang('Requirements'),
    $sessionList,
    ['id' => 'requirements', 'multiple' => 'multiple']
);

$form->addButtonCreate(get_lang('SetAsRequirementForSelected'), 'set_requirement');
$form->addButtonSave(get_lang('Save'), 'save_resource');

$tpl->assign('left_block', $formSequence->returnForm().$form->returnForm());
$layout = $tpl->get_template('admin/resource_sequence.tpl');
$tpl->display($layout);

