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
    $cleanedCode = api_replace_dangerous_char($values['code']);
    $code = Database::escape_string($cleanedCode);

    $sql = "SELECT * FROM justification_document WHERE code = '$code' AND id <> $id";
    $result = Database::query($sql);
    $data = Database::fetch_array($result);
    $message = Display::return_message(get_lang('ThisCodeAlradyExists'), 'warning');
    if (empty($data)) {
        $params = [
            'name' => $values['name'],
            'code' => $cleanedCode,
            'validity_duration' => $values['validity_duration'],
            'date_manual_on' => (int) $values['date_manual_on'],
            'comment' => $values['comment'],
        ];

        Database::update('justification_document', $params, ['id = ?' => $id]);
        $message = Display::return_message(get_lang('Saved'));
    }

    Display::addFlash($message);

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
