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
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];

$type = $httpRequest->query->has('type')
    ? $httpRequest->query->getInt('type', SequenceResource::SESSION_TYPE)
    : $httpRequest->request->getInt('type', SequenceResource::SESSION_TYPE);

$tpl = new Template(get_lang('ResourcesSequencing'));
$em = Database::getManager();
$sequenceRepository = $em->getRepository('ChamiloCoreBundle:Sequence');

$currentUrl = api_get_self().'?type='.$type;

$formSequence = new FormValidator('sequence_form', 'post', $currentUrl, null, null, FormValidator::LAYOUT_INLINE);
$formSequence->addText('name', get_lang('Sequence'), true, ['cols-size' => [3, 8, 1]]);
$formSequence->applyFilter('name', 'html_filter');
$formSequence->addButtonCreate(get_lang('AddSequence'), 'submit_sequence', false, ['cols-size' => [3, 8, 1]]);

$em = Database::getManager();

// Add sequence
if ($formSequence->validate()) {
    $values = $formSequence->exportValues();
    $sequence = new Sequence();
    $sequence->setName($values['name']);
    $em->persist($sequence);
    $em->flush();
    Display::addFlash(Display::return_message(get_lang('Saved')));
    header('Location: '.$currentUrl);
    exit;
}

$selectSequence = new FormValidator('frm_select_delete', 'post', $currentUrl);
$sequenceList = $sequenceRepository->findAllToSelect($type);

$sequenceElement = $selectSequence->addSelect(
    'sequence',
    get_lang('Sequence'),
    $sequenceList,
    ['id' => 'sequence_id', 'cols-size' => [3, 7, 2], 'disabled' => 'disabled']
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
$form->addHtml("<div class='col-md-6'>");
$form->addHidden('sequence_type', $type);
$form->addSelect(
    'sessions',
    $label,
    $list,
    ['id' => 'item', 'cols-size' => [4, 7, 1], 'disabled' => 'disabled']
);
$form->addButtonNext(
    get_lang('UseAsReference'),
    'use_as_reference',
    ['cols-size' => [4, 7, 1], 'disabled' => 'disabled']
);
$form->addHtml("</div>");
$form->addHtml("<div class='col-md-6'>");
$form->addSelect(
    'requirements',
    get_lang('Requirements'),
    $list,
    ['id' => 'requirements', 'cols-size' => [3, 7, 2], 'disabled' => 'disabled']
);

$form->addButtonCreate(
    get_lang('SetAsRequirement'),
    'set_requirement',
    false,
    ['cols-size' => [3, 7, 2], 'disabled' => 'disabled']
);
$form->addHtml('</div>');

$formSave = new FormValidator('');
$formSave->addButton(
    'save_resource',
    get_lang('SaveSettings'),
    'floppy-o',
    'success',
    null,
    null,
    ['cols-size' => [1, 10, 1], 'disabled' => 'disabled']
);

$headers[] = [
    'url' => api_get_self().'?type='.SequenceResource::SESSION_TYPE,
    'content' => get_lang('Sessions'),
];

$headers[] = [
    'url' => api_get_self().'?type='.SequenceResource::COURSE_TYPE,
    'content' => get_lang('Courses'),
];

$tabs = Display::tabsOnlyLink($headers, $type === SequenceResource::COURSE_TYPE ? 2 : 1);

$tpl->assign('create_sequence', $formSequence->returnForm());
$tpl->assign('select_sequence', $selectSequence->returnForm());
$tpl->assign('configure_sequence', $form->returnForm());
$tpl->assign('save_sequence', $formSave->returnForm());
$tpl->assign('sequence_type', $type);
$tpl->assign('tabs', $tabs);
$layout = $tpl->get_template('admin/resource_sequence.tpl');
$tpl->display($layout);
