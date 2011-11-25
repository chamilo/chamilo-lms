<?php
/* For licensing terms, see /license.txt */

$language_file = array('registration', 'admin', 'userInfo');

require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'import.lib.php';

$this_section = SECTION_COURSES;

// notice for unauthorized people.
api_protect_course_script(true);

$tool_name = get_lang('ImportUsersToACourse');

$interbreadcrumb[] = array ("url" => "user.php", "name" => get_lang("Users"));
$interbreadcrumb[] = array ("url" => "#", "name" => get_lang("ImportUsersToACourse"));

$form = new FormValidator('user_import','post','user_import.php');
$form->addElement('header', '', $tool_name);
$form->addElement('file', 'import_file', get_lang('ImportCSVFileLocation'));
$form->addElement('style_submit_button', 'submit', get_lang('Import'), 'class="save"');

$course_code = api_get_course_id();

if (api_get_session_id()) {
    api_not_allowed();
}

if (empty($course_code)) {
    api_not_allowed();
}

$message = '';
$user_to_show = array();
$type = '';

if ($form->validate()) {
    if (isset($_FILES['import_file']['size']) && $_FILES['import_file']['size'] !== 0) {
        
        $users  = Import::csv_to_array($_FILES['import_file']['tmp_name']);
        
        $invalid_users  = array();
        $clean_users    = array();
        $users_in_file  = array();
        
        if (!empty($users)) {            
            foreach ($users as $user) {
                $user_info = api_get_user_info($user['id']);
                if (!empty($user_info)) {
                    $clean_users[$user['id']] = $user_info;
                    $users_in_file[$user['id']] = $user;
                } else {
                    $invalid_users[] = $user['id'];
                }
            }
            if (empty($invalid_users)) {
                $type = 'confirmation';
                $message = get_lang('ListOfUsersSubscribedToCourse');
                foreach ($users as $user) {                                     
                    $result = CourseManager :: subscribe_user($user['id'], $course_code, STUDENT);                    
                    //just to make sure
                    if (CourseManager :: is_user_subscribed_in_course($user['id'], $course_code)) {
                        $user_to_show[]= $clean_users[$user['id']]['complete_name'];
                    }                    
                }   
            } else {
                $message = get_lang('CheckUsersWithId');
                $type = 'warning';                
                foreach ($invalid_users as $invalid_user) {                    
                    $user_to_show[]= $invalid_user;
                }        
            }            
        }
    }
}

Display::display_header();

if (!empty($message)) {
    if (!empty($user_to_show)) {
        if ($type == 'confirmation') {
            Display::display_confirmation_message($message.': <br />'.implode(', ', $user_to_show), false);
        } else {
            Display::display_warning_message($message.': '.implode(', ', $user_to_show));
        }
    } else {
        Display::display_error_message(get_lang('ErrorsWhenImportingFile'));
    }
}
    
$form->display();
Display::display_footer();