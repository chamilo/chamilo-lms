<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.admin
 */

$language_file = array('admin', 'registration');
$cidReset = true;

require '../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script(true);

$form_sent = 0;
$error_message = ''; // Avoid conflict with the global variable $error_msg (array type) in add_course.conf.php.
if (isset($_GET['action']) && $_GET['action'] == 'show_message') {
    $error_message = Security::remove_XSS($_GET['message']);
}

$tool_name = get_lang('ImportUsers');
$session_id = isset($_GET['id_session']) ? intval($_GET['id_session']) : null;

if (empty($session_id)) {
    api_not_allowed(true);    
}

$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => "session_list.php","name" => get_lang('SessionList'));
$interbreadcrumb[] = array('url' => "resume_session.php?id_session=".$session_id,"name" => get_lang('SessionOverview'));

set_time_limit(0);

if ($_POST['formSent']) {    
    if (isset($_FILES['import_file']['tmp_name']) && !empty($_FILES['import_file']['tmp_name'])) {        
        $form_sent = $_POST['formSent'];        
        $send_mail = $_POST['sendMail'] ? 1 : 0;
        
        // CSV
        $users = Import::csv_to_array($_FILES['import_file']['tmp_name']);
        $user_list = array();
        foreach ($users as $user_data) {
            $username = $user_data['username'];
            $user_id = UserManager::get_user_id_from_username($username);
            if ($user_id) {
                $user_list[] = $user_id;
            }
        }
        
        if (!empty($user_list)) {            
            SessionManager::suscribe_users_to_session($session_id, $user_list, null, false, $send_mail);
            foreach ($user_list as & $user_id) {
                $user_info = api_get_user_info($user_id);
                $user_id = $user_info['complete_name'];
            }
            $error_message  = get_lang('UsersAdded').' : '.implode(', ', $user_list);
        }  
    } else {
        $error_message = get_lang('NoInputFile');
    }
}

// Display the header.
Display::display_header($tool_name);

if (count($inserted_in_course) > 1) {
    $msg = get_lang('SeveralCoursesSubscribedToSessionBecauseOfSameVisualCode').': ';
    foreach ($inserted_in_course as $code => $title) {
        $msg .= ' '.$title.' ('.$title.'),';
    }
    $msg = substr($msg, 0, -1);
    Display::display_warning_message($msg);
}

echo '<div class="actions">';
echo '<a href="resume_session.php?id_session='.$session_id.'">'.Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('PlatformAdmin'),'',ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

if (!empty($error_message)) {
    Display::display_normal_message($error_message, false);
}

$form = new FormValidator('import_sessions', 'post', api_get_self().'?id_session='.$session_id, null, array('enctype' => 'multipart/form-data'));
$form->addElement('hidden', 'formSent', 1);
$form->addElement('file', 'import_file', get_lang('ImportCSVFileLocation'));

$form->addElement('checkbox', 'sendMail', null, get_lang('SendMailToUsers'));
$form->addElement('button', 'submit', get_lang('Import'));

$defaults = array('sendMail' => 'true');
$form->setDefaults($defaults);

$form->display();

?>
<font color="gray">
<p><?php echo get_lang('CSVMustLookLike'); ?> :</p>

<blockquote>
<pre>
username;
jdoe;
jmontoya;
</pre>
</blockquote>

<?php

/* FOOTER */
Display::display_footer();