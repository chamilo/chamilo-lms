<?php

/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script(true);
api_protect_limit_for_session_admin();

$form_sent = 0;
$tool_name = get_lang('ImportSessionDrhList');
$interbreadcrumb[] = ['url' => 'session_list.php', 'name' => get_lang('SessionList')];

set_time_limit(0);

$inserted_in_course = [];

$form = new FormValidator(
    'import_sessions',
    'post',
    api_get_self(),
    null,
    ['enctype' => 'multipart/form-data']
);

$form->addElement('file', 'import_file', get_lang('ImportFileLocation'));
$form->addCheckbox('remove_old_relationships', [get_lang('RemoveOldRelationships'), get_lang('RemoveOldRelationshipsHelp')]);
$form->addButtonImport(get_lang('ImportSession'));

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
        $error_message = get_lang('NoInputFile');
    }
}

Display::display_header($tool_name);
echo '<div class="actions">';
echo '<a href="../session/session_list.php">'.
    Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('PlatformAdmin'), '', ICON_SIZE_MEDIUM).'</a>';
echo '</div>';
if (!empty($error_message)) {
    echo Display::return_message($error_message, 'normal', false);
}
$form->display();

?>
<p><?php echo get_lang('CSVMustLookLike').' ('.get_lang('MandatoryFields').')'; ?> :</p>

<blockquote>
<pre>
Username;SessionName;
drh1;Session 1;
drh2;Session 2;
</pre>
</blockquote>

<p><?php echo get_lang('Or'); ?> </p>

<blockquote>
<pre>
Username;SessionId;
drh1,drh2;100;
drh3;102;
</pre>
</blockquote>

<?php

Display::display_footer();
