<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Sequence;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_global_admin_script();

// setting breadcrumbs
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];

$tpl = new Template(get_lang('Resources sequencing'));

$sessionListFromDatabase = SessionManager::get_sessions_list();
$sessionList = [];
if (!empty($sessionListFromDatabase)) {
    foreach ($sessionListFromDatabase as $sessionItem) {
        $sessionList[$sessionItem['id']] = $sessionItem['name'].' ('.$sessionItem['id'].')';
    }
}

$formSequence = new FormValidator('sequence_form', 'post', api_get_self(), null, null, 'inline');
$formSequence->addText('name', get_lang('Sequence'), true, ['cols-size' => [3, 8, 1]]);
$formSequence->addButtonCreate(get_lang('Add new sequence'), 'submit_sequence', false, ['cols-size' => [3, 8, 1]]);

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

$selectSequence = new FormValidator('frm_select_delete');
$selectSequence->addHidden('sequence_type', 'session');
$em = Database::getManager();

$sequenceList = $em->getRepository('ChamiloCoreBundle:Sequence')->findAll();

$slcSequences = $selectSequence->addSelect(
    'sequence',
    get_lang('Sequence'),
    $sequenceList,
    ['id' => 'sequence_id', 'cols-size' => [3, 7, 2], 'disabled' => 'disabled']
);

if (!empty($sequenceList)) {
    $selectSequence->addButtonDelete(get_lang('Delete'));
    $slcSequences->removeAttribute('disabled');
}

if ($selectSequence->validate()) {
    $values = $selectSequence->exportValues();

    $sequence = $em->find('ChamiloCoreBundle:Sequence', $values['sequence']);

    $em
        ->createQuery('DELETE FROM ChamiloCoreBundle:SequenceResource sr WHERE sr.sequence = :seq')
        ->execute(['seq' => $sequence]);

    $em->remove($sequence);
    $em->flush();

    Display::addFlash(
        Display::return_message(get_lang('Deleted'), 'success')
    );

    header('Location: '.api_get_self());
    exit;
}

$form = new FormValidator('');
$form->addHtml("<div class='col-md-6'>");
$form->addHidden('sequence_type', 'session');
$form->addSelect(
    'sessions',
    get_lang('Course sessions'),
    $sessionList,
    ['id' => 'item', 'cols-size' => [4, 7, 1], 'disabled' => 'disabled']
);
$form->addButtonNext(get_lang('Use as reference'), 'use_as_reference', ['cols-size' => [4, 7, 1], 'disabled' => 'disabled']);
$form->addHtml("</div>");
$form->addHtml("<div class='col-md-6'>");
$form->addSelect(
    'requirements',
    get_lang('Requirements'),
    $sessionList,
    ['id' => 'requirements', 'cols-size' => [3, 7, 2], 'disabled' => 'disabled']
);

$form->addButtonCreate(get_lang('Set as a requirement'), 'set_requirement', false, ['cols-size' => [3, 7, 2], 'disabled' => 'disabled']);
$form->addHtml("</div>");

$formSave = new FormValidator('');
$formSave->addHidden('sequence_type', 'session');
$formSave->addButton(
    'save_resource',
    get_lang('Save settings'),
    'floppy-o',
    'success',
    null,
    null,
    ['cols-size' => [1, 10, 1], 'disabled' => 'disabled']
);

$tpl->assign('create_sequence', $formSequence->returnForm());
$tpl->assign('select_sequence', $selectSequence->returnForm());
$tpl->assign('configure_sequence', $form->returnForm());
$tpl->assign('save_sequence', $formSave->returnForm());
$layout = $tpl->get_template('admin/resource_sequence.tpl');
$tpl->display($layout);
