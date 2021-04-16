<?php

/* For licensing terms, see /license.txt */

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

Display::display_header($tool_name);

$actions = '<a href="../session/session_list.php">'.
    Display::return_icon('back.png', get_lang('Back to').' '.get_lang('Administration'), '', ICON_SIZE_MEDIUM).'</a>';

echo Display::toolbarAction('toolbar', [$actions]);

if (!empty($error_message)) {
    echo Display::return_message($error_message, 'normal', false);
}

$form = new FormValidator(
    'import_sessions',
    'post',
    api_get_self(),
    null,
    ['enctype' => 'multipart/form-data']
);

$form->addElement('file', 'import_file', get_lang('Import marks in an assessment'));
$form->addElement('checkbox', 'remove_old_relationships', null, get_lang('Remove previous relationships'));
//$form->addElement('checkbox', 'send_email', null, get_lang('Send a mail to users'));
$form->addButtonImport(get_lang('Import session(s)'));

if ($form->validate()) {
    if (isset($_FILES['import_file']['tmp_name']) && !empty($_FILES['import_file']['tmp_name'])) {
        $values = $form->exportValues();
        $sendMail = isset($values['send_email']) ? true : false;
        $removeOldRelationships = isset($values['remove_old_relationships']) ? true : false;

        $result = SessionManager::importSessionDrhCSV(
            $_FILES['import_file']['tmp_name'],
            $sendMail,
            $removeOldRelationships
        );
        Display::addFlash(Display::return_message($result, 'info', false));
        header('Location: '.api_get_self());
        exit;
    } else {
        $error_message = get_lang('No file was sent');
    }
}

$form->display();

?>
<p><?php echo get_lang('The CSV file must look like this').' ('.get_lang('Fields in <strong>bold</strong> are mandatory.').')'; ?> :</p>

<blockquote>
<pre>
Username;SessionName;
drh1;Session 1;
drh2;Session 2;
</pre>
</blockquote>
<?php

/* FOOTER */
Display::display_footer();
