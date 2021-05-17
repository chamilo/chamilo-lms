<?php
/* For licensing terms, see /license.txt */
/**
 * This tool allows platform admins to add users by uploading a CSV or XML file
 * This code is inherited from admin/user_import.php.
 *
 * Created on 26 julio 2008  by Julio Montoya gugli100@gmail.com
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN; // TODO: Platform admin section?

$tool_name = get_lang('Import users list');
api_block_anonymous_users();

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Reporting')];
$id_session = '';
if (isset($_GET['id_session']) && '' != $_GET['id_session']) {
    $id_session = intval($_GET['id_session']);
    $interbreadcrumb[] = ['url' => 'session.php', 'name' => get_lang('Course sessions')];
    $interbreadcrumb[] = ['url' => 'course.php?id_session='.$id_session.'', 'name' => get_lang('Course')];
}

// Set this option to true to enforce strict purification for usenames.
$purification_option_for_usernames = false;

// Checking whether the current coach is the admin coach.
if ('true' === api_get_setting('add_users_by_coach')) {
    if (!api_is_platform_admin()) {
        if (isset($_REQUEST['id_session'])) {
            $id_session = intval($_REQUEST['id_session']);
            $sql = 'SELECT id_coach FROM '.Database::get_main_table(TABLE_MAIN_SESSION).'
                    WHERE id='.$id_session;
            $rs = Database::query($sql);
            if (Database::result($rs, 0, 0) != api_get_user_id()) {
                api_not_allowed(true);
            }
        } else {
            api_not_allowed(true);
        }
    }
} else {
    api_not_allowed(true);
}

set_time_limit(0);
$errors = [];
if (isset($_POST['formSent']) && $_POST['formSent'] && 0 !== $_FILES['import_file']['size']) {
    $file_type = $_POST['file_type'];
    $id_session = intval($_POST['id_session']);
    if ('csv' === $file_type) {
        $users = MySpace::parse_csv_data($_FILES['import_file']['tmp_name']);
    } else {
        $users = MySpace::parse_xml_data($_FILES['import_file']['tmp_name']);
    }
    if (count($users) > 0) {
        $results = MySpace::validate_data($users);
        $errors = $results['errors'];
        $users = $results['users'];

        if (0 == count($errors)) {
            if (!empty($id_session)) {
                $tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
                // Selecting all the courses from the session id requested.
                $sql = "SELECT c_id FROM $tbl_session_rel_course WHERE session_id ='$id_session'";
                $result = Database::query($sql);
                $course_list = [];
                while ($row = Database::fetch_array($result)) {
                    $course_list[] = $row['c_id'];
                }
                $errors = MySpace::get_user_creator($users);
                $users = MySpace::check_all_usernames($users, $course_list, $id_session);
                if (0 == count($errors)) {
                    MySpace::save_data($users, $course_list, $id_session);
                }
            } else {
                Display::addFlash(Display::return_message(get_lang('The session was not identified'), 'warning'));
                header('Location: course.php?id_session='.$id_session);
                exit;
            }
        }
    } else {
        Display::addFlash(Display::return_message(get_lang('Please verify your XML/CVS file'), 'warning'));
        header('Location: course.php?id_session='.$id_session);
        exit;
    }
}

Display::display_header($tool_name);

if (isset($_FILES['import_file']) && 0 == $_FILES['import_file']['size'] && $_POST) {
    echo Display::return_message(get_lang('Required field'), 'error');
}

if (0 != count($errors)) {
    $error_message = '<ul>';
    foreach ($errors as $index => $error_user) {
        $error_message .= '<li><strong>'.$error_user['error'].'</strong>: ';
        $error_message .= api_get_person_name($error_user['FirstName'], $error_user['LastName']);
        $error_message .= '</li>';
    }
    $error_message .= '</ul>';
    echo Display::return_message($error_message, 'error', false);
}

$form = new FormValidator('user_import');
$form->addElement('hidden', 'formSent');
$form->addElement('hidden', 'id_session', $id_session);
$form->addElement('file', 'import_file', get_lang('Import marks in an assessment'));
$form->addRule('import_file', get_lang('Required field'), 'required');
$allowed_file_types = ['xml', 'csv'];
$form->addRule('import_file', get_lang('Invalid extension').' ('.implode(',', $allowed_file_types).')', 'filetype', $allowed_file_types);
$form->addElement(
    'radio',
    'file_type',
    get_lang('File type'),
    'XML (<a href="../admin/example.xml" target="_blank" download>'.get_lang('Example XML file').'</a>)',
    'xml'
);
$form->addElement(
    'radio',
    'file_type',
    null,
    'CSV (<a href="../admin/example.csv" target="_blank" download>'.get_lang('Example CSV file').'</a>)',
    'csv'
);
$form->addElement('radio', 'sendMail', get_lang('Send a mail to users'), get_lang('Yes'), 1);
$form->addElement('radio', 'sendMail', null, get_lang('No'), 0);
$form->addButtonSave(get_lang('Validate'));
$defaults['formSent'] = 1;
$defaults['sendMail'] = 0;
$defaults['file_type'] = 'xml';
$form->setDefaults($defaults);
$form->display();

$content = '<p>'.get_lang('The CSV file must look like this').' ('.get_lang('Fields in <strong>bold</strong> are mandatory.').') :</p>
<blockquote>
<pre>
<b>LastName</b>;<b>FirstName</b>;<b>Email</b>;UserName;Password;OfficialCode;PhoneNumber;
<b>Montoya</b>;<b>Julio</b>;<b>info@localhost</b>;jmontoya;123456789;code1;3141516
<b>Doewing</b>;<b>Johny</b>;<b>info@localhost</b>;jdoewing;123456789;code2;3141516
</pre>
</blockquote>
<p>'.get_lang('The XML file must look like this').' ('.get_lang('Fields in <strong>bold</strong> are mandatory.').') :</p>
<blockquote>
<pre>
&lt;?xml version=&quot;1.0&quot; encoding=&quot;'.api_refine_encoding_id(api_get_system_encoding()).'&quot;?&gt;
&lt;Contacts&gt;
    &lt;Contact&gt;
        <b>&lt;LastName&gt;Montoya&lt;/LastName&gt;</b>
        <b>&lt;FirstName&gt;Julio&lt;/FirstName&gt;</b>
        <b>&lt;Email&gt;info@localhost&lt;/Email&gt;</b>
        &lt;UserName&gt;jmontoya&lt;/UserName&gt;
        &lt;Password&gt;123456&lt;/Password&gt;
        &lt;OfficialCode&gt;code1&lt;/OfficialCode&gt;
        &lt;PhoneNumber&gt;3141516&lt;/PhoneNumber&gt;
    &lt;/Contact&gt;
&lt;/Contacts&gt;
</pre>
</blockquote>';

echo Display::prose($content);
Display::display_footer();
