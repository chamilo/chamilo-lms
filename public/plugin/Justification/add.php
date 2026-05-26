<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script();

$tool = 'justification';
$plugin = Justification::create();

$tpl = new Template($tool);
$fields = [];

$form = new FormValidator('add');
$form->addText('name', get_lang('Name'));
$form->addText('code', $plugin->get_lang('JustificationCode'));
$form->addNumeric('validity_duration', $plugin->get_lang('ValidityDuration'));
$form->addCheckBox('date_manual_on', $plugin->get_lang('DateManualOn'));
$form->addTextarea('comment', get_lang('Comment'));
$form->addButtonSave(get_lang('Save'));

if ($form->validate()) {
    $values = $form->getSubmitValues();
    $dateManual = isset($values['date_manual_on']) ? 1 : 0;

    $cleanedCode = api_replace_dangerous_char($values['code']);
    $code = Database::escape_string($cleanedCode);

    $sql = "SELECT * FROM justification_document WHERE code = '$code' ";
    $result = Database::query($sql);
    $data = Database::fetch_array($result);
    $message = Display::return_message(get_lang('This code already exists'), 'warning');

    if (empty($data)) {
        $params = [
            'name' => $values['name'],
            'code' => $cleanedCode,
            'validity_duration' => $values['validity_duration'],
            'date_manual_on' => $dateManual,
            'comment' => $values['comment'],
        ];
        Database::insert('justification_document', $params);
        $message = Display::return_message(get_lang('Saved'));
    }

    Display::addFlash($message);

    $url = api_get_path(WEB_PLUGIN_PATH).'Justification/list.php?';
    header('Location: '.$url);
    exit;
}

$actionLinks = Display::toolbarButton(
    $plugin->get_lang('Back'),
    api_get_path(WEB_PLUGIN_PATH).'Justification/list.php',
    'arrow-left',
    'primary'
);

$tpl->assign(
    'actions',
    Display::toolbarAction('toolbar', [$actionLinks])
);

$content = '
<section class="w-full space-y-6">
    <div class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="flex items-start gap-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                <span class="mdi mdi-file-plus-outline text-2xl"></span>
            </div>
            <div>
                <h2 class="text-2xl font-semibold text-gray-90">'.$plugin->get_lang('AddJustificationDocument').'</h2>
                <p class="text-sm text-gray-50">'.$plugin->get_lang('AddJustificationDocumentHelp').'</p>
            </div>
        </div>
    </div>
    <div class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        '.$form->returnForm().'
    </div>
</section>';

$tpl->assign('content', $content);
$tpl->display_one_col_template();
