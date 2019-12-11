<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script();

$tool = 'justification';
$plugin = Justification::create();

$id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

if (empty($id)) {
    api_not_allowed();
}

$justification = $plugin->getJustification($id);

$tpl = new Template($tool);
$fields = [];

$form = new FormValidator('add', 'post', api_get_self().'?id='.$id);
$form->addText('name', get_lang('Name'));
$form->addText('code', $plugin->get_lang('JustificationCode'));
$form->addNumeric('validity_duration', $plugin->get_lang('ValidityDuration'));
$form->addCheckBox('date_manual_on', $plugin->get_lang('DateManualOn'));
$form->addTextarea('comment', get_lang('Comment'));
$form->addButtonSave(get_lang('Update'));

$form->setDefaults($justification);

if ($form->validate()) {
    $values = $form->getSubmitValues();
    $params = [
        'name' => $values['name'],
        'code' => $values['code'],
        'validity_duration' => $values['validity_duration'],
        'date_manual_on' => (int) $values['date_manual_on'],
        'comment' => $values['comment'],
    ];
    Database::update('justification_document', $params, ['id = ?' => $id]);
    Display::addFlash(get_lang('Saved'));
    $url = api_get_path(WEB_PLUGIN_PATH).'justification/list.php?';
    header('Location: '.$url);
    exit;
}

$actionLinks = Display::toolbarButton(
    $plugin->get_lang('Back'),
    api_get_path(WEB_PLUGIN_PATH).'justification/list.php',
    'arrow-left',
    'primary'
);

$tpl->assign(
    'actions',
    Display::toolbarAction('toolbar', [$actionLinks])
);


$content = $form->returnForm();

$tpl->assign('content', $content);
$tpl->display_one_col_template();
