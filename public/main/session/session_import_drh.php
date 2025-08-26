<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script(true);
api_protect_limit_for_session_admin();

$form_sent = 0;
$tool_name = get_lang('Import list of HR directors into sessions');

$interbreadcrumb[] = ['url' => 'session_list.php', 'name' => get_lang('Session list')];

set_time_limit(0);

$inserted_in_course = [];
$csvCustomError = '';
$topStaticErrorHtml = '';

$form = new FormValidator(
    'import_sessions',
    'post',
    api_get_self(),
    null,
    ['enctype' => 'multipart/form-data']
);

$form->addElement('file', 'import_file', get_lang('Import file'));
$form->addElement('checkbox', 'remove_old_relationships', null, get_lang('Remove previous relationships'));
$form->addButtonImport(get_lang('Import session(s)'));

if ($form->validate()) {
    if (isset($_FILES['import_file']['tmp_name']) && !empty($_FILES['import_file']['tmp_name'])) {
        $values = $form->exportValues();
        $sendMail = !empty($values['send_email']);
        $removeOldRelationships = !empty($values['remove_old_relationships']);
        $check = Import::assertCommaSeparated($_FILES['import_file']['tmp_name'], true);
        if (true !== $check) {
            $csvCustomError = $check;
            $topStaticErrorHtml = Display::return_message($csvCustomError, 'error', false);
        } else {
            $result = SessionManager::importSessionDrhCSV(
                $_FILES['import_file']['tmp_name'],
                $sendMail,
                $removeOldRelationships
            );
            Display::addFlash(Display::return_message($result, 'info', false));
            header('Location: '.api_get_self());
            exit;
        }
    } else {
        $error_message = get_lang('No file was sent');
    }
}

Display::display_header($tool_name);
$actions = '<a href="../session/session_list.php">'.
    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back to').' '.get_lang('Administration')).'</a>';

echo Display::toolbarAction('toolbar', [$actions]);
if (!empty($topStaticErrorHtml)) {
    echo $topStaticErrorHtml;
}
if (!empty($error_message)) {
    echo Display::return_message($error_message, 'normal', false);
}

$form->display();

$content = '
<p>'.get_lang('The CSV file must look like this').' ('.get_lang('Fields in <strong>bold</strong> are mandatory.').') :</p>
<blockquote>
<pre>
Username,SessionName
drh1,Session 1
drh2,Session 2
</pre>
</blockquote>';

echo Display::prose($content);

Display::display_footer();
