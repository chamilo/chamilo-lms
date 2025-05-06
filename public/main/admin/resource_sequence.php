<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Sequence;
use Chamilo\CoreBundle\Entity\SequenceResource;
use ChamiloSession as Session;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_global_admin_script();

Session::erase('sr_vertex');

$httpRequest = HttpRequest::createFromGlobals();

// setting breadcrumbs
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Platform Admin')];

$type = $httpRequest->query->has('type')
    ? $httpRequest->query->getInt('type', SequenceResource::SESSION_TYPE)
    : $httpRequest->request->getInt('type', SequenceResource::SESSION_TYPE);

$tpl = new Template(get_lang('Resources Sequencing'));
$em = Database::getManager();
$sequenceRepository = $em->getRepository(Sequence::class);

$currentUrl = api_get_self().'?type='.$type;

$formSequence = new FormValidator('sequence_form', 'post', $currentUrl, null, null, FormValidator::LAYOUT_INLINE);
$formSequence->addText('name', get_lang('Sequence'), true, ['cols-size' => [3, 8, 1]]);
$formSequence->applyFilter('name', 'html_filter');
$formSequence->addButtonCreate(get_lang('Add sequence'), 'submit_sequence', false, ['cols-size' => [3, 8, 1]]);

$em = Database::getManager();

// Add sequence
if ($formSequence->validate()) {
    $values = $formSequence->exportValues();
    $sequence = new Sequence();
    $sequence->setTitle($values['name']);
    $em->persist($sequence);
    $em->flush();
    Display::addFlash(Display::return_message(get_lang('Saved')));
    header('Location: '.$currentUrl);
    exit;
}

$selectSequence = new FormValidator('frm_select_delete', 'post', $currentUrl);
$sequenceList = $sequenceRepository->findAllToSelect($type);
$currentSequenceName = '';
$selectedSequenceId = $selectSequence->getSubmitValue('sequence');

if (!empty($selectedSequenceId) && isset($sequenceList[$selectedSequenceId])) {
    $currentSequenceName = $sequenceList[$selectedSequenceId];
}

$sequenceElement = $selectSequence->addSelect(
    'sequence',
    get_lang('Sequence'),
    $sequenceList,
    ['id' => 'sequence_id', 'cols-size' => [3, 7, 2]]
);

if (!empty($sequenceList)) {
    $selectSequence->addButtonDelete(get_lang('Delete'));
    $sequenceElement->removeAttribute('disabled');
}

if ($selectSequence->validate()) {
    $values = $selectSequence->exportValues();
    $sequenceRepository->removeSequence($values['sequence']);

    Display::addFlash(
        Display::return_message(get_lang('Deleted'), 'success')
    );

    header('Location: '.$currentUrl);
    exit;
}

$list = $sequenceRepository->getItems($type);

switch ($type) {
    case SequenceResource::COURSE_TYPE:
        $label = get_lang('Courses');
        break;
    case SequenceResource::SESSION_TYPE:
        $label = get_lang('Sessions');
        break;
}

$form = new FormValidator('');

$form->addHidden('sequence_type', $type);
$form->addHtml('<div class="flex flex-col lg:flex-row gap-4 items-end">');

$form->addHtml('<div class="w-full lg:w-1/2">');
$form->addSelect(
    'sessions',
    $label,
    $list,
    ['id' => 'item', 'class' => 'w-full']
);
$form->addButtonNext(
    get_lang('Use as reference'),
    'use_as_reference',
    ['class' => 'mt-2']
);
$form->addHtml('</div>');

$form->addHtml('<div class="w-full lg:w-1/2">');
$form->addSelect(
    'requirements',
    get_lang('Requirements'),
    $list,
    ['id' => 'requirements', 'class' => 'w-full']
);

$form->addButtonCreate(
    get_lang('Set as requirement'),
    'set_requirement',
    false,
    ['class' => 'mt-2']
);
$form->addHtml('</div>');

$form->addHtml('</div>');

$formSave = new FormValidator('');
$formSave->addButton(
    'save_resource',
    get_lang('Save settings'),
    'floppy-o',
    'success',
    null,
    null,
    ['cols-size' => [1, 10, 1]]
);

$headers[] = [
    'url' => api_get_self().'?type='.SequenceResource::SESSION_TYPE,
    'content' => get_lang('Sessions'),
];

$headers[] = [
    'url' => api_get_self().'?type='.SequenceResource::COURSE_TYPE,
    'content' => get_lang('Courses'),
];

$tabs = Display::tabsOnlyLink($headers, SequenceResource::COURSE_TYPE === $type ? 2 : 1);

$tpl->assign('create_sequence', $formSequence->returnForm());
$tpl->assign('select_sequence', $selectSequence->returnForm());
$tpl->assign('configure_sequence', $form->returnForm());
$tpl->assign('save_sequence', $formSave->returnForm());
$tpl->assign('sequence_type', $type);
$tpl->assign('tabs', $tabs);
$tpl->assign('_p', ['web_ajax' => api_get_path(WEB_AJAX_PATH)]);
$tpl->assign('current_sequence_name', $currentSequenceName);
$layout = $tpl->get_template('admin/resource_sequence.tpl');
$tpl->display($layout);
