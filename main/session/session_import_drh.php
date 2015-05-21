<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.admin
 */
$cidReset = true;

require_once '../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script(true);
api_protect_limit_for_session_admin();

$form_sent = 0;
$tool_name = get_lang('ImportSessionDrhList');

//$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[]=array('url' => 'session_list.php','name' => get_lang('SessionList'));

set_time_limit(0);

$inserted_in_course = array();

// Display the header.
Display::display_header($tool_name);

echo '<div class="actions">';
echo '<a href="../session/session_list.php">'.
    Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('PlatformAdmin'),'',ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

if (!empty($error_message)) {
    Display::display_normal_message($error_message, false);
}

$form = new FormValidator('import_sessions', 'post', api_get_self(), null, array('enctype' => 'multipart/form-data'));
$form->addElement('file', 'import_file', get_lang('ImportFileLocation'));
$form->addElement('checkbox', 'remove_old_relationships', null, get_lang('RemoveOldRelationships'));
//$form->addElement('checkbox', 'send_email', null, get_lang('SendMailToUsers'));
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
        echo Display::return_message($result, 'info', false);
    } else {
        $error_message = get_lang('NoInputFile');
    }
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
<?php

/* FOOTER */
Display::display_footer();
