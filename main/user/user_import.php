<?php
/* For licensing terms, see /license.txt */

require_once '../inc/global.inc.php';
$this_section = SECTION_COURSES;

// notice for unauthorized people.
api_protect_course_script(true);

if (api_get_setting('allow_user_course_subscription_by_course_admin') == 'false') {
    if (!api_is_platform_admin()) {
        api_not_allowed(true);
    }
}

$tool_name = get_lang('ImportUsersToACourse');

$interbreadcrumb[] = array ("url" => "user.php", "name" => get_lang("Users"));
$interbreadcrumb[] = array ("url" => "#", "name" => get_lang("ImportUsersToACourse"));

$form = new FormValidator('user_import', 'post', 'user_import.php');
$form->addElement('header', $tool_name);
$form->addElement('file', 'import_file', get_lang('ImportCSVFileLocation'));
$form->addElement('checkbox', 'unsubscribe_users', null, get_lang('UnsubscribeUsersAlreadyAddedInCourse'));
$form->addButtonImport(get_lang('Import'));

$course_code = api_get_course_id();

if (empty($course_code)) {
    api_not_allowed(true);
}

$session_id = api_get_session_id();
$message = '';
$user_to_show = array();
$type = '';

if ($form->validate()) {
    if (isset($_FILES['import_file']['size']) && $_FILES['import_file']['size'] !== 0) {
        $unsubscribe_users = isset($_POST['unsubscribe_users']) ? true : false;
        //@todo : csvToArray deprecated
        $users  = Import::csvToArray($_FILES['import_file']['tmp_name']);
        $invalid_users  = array();
        $clean_users    = array();

        if (!empty($users)) {
            $empty_line = 0;
            foreach ($users as $user_data) {
                $user_id = null;
                $user_data = array_change_key_case($user_data, CASE_LOWER);

                // Checking "username" field
                if (isset($user_data['username']) && !empty($user_data['username'])) {
                    $user_id = UserManager::get_user_id_from_username($user_data['username']);
                }

                // Checking "id" field
                if (isset($user_data['id']) && !empty($user_data['id'])) {
                    $user_id = $user_data['id'];
                }

                if (UserManager::is_user_id_valid($user_id)) {
                    $clean_users[] = $user_id;
                } else {
                    $invalid_users[] = $user_data;
                }
            }

            if (empty($invalid_users)) {
                $type = 'confirmation';
                $message = get_lang('ListOfUsersSubscribedToCourse');

                if ($unsubscribe_users) {
                    $current_user_list = CourseManager::get_user_list_from_course_code($course_code, $session_id);
                    if (!empty($current_user_list)) {
                        $user_ids = array();
                        foreach ($current_user_list as $user) {
                            if (!CourseManager::is_course_teacher($user['user_id'], $course_code)) {
                                $user_ids[]= $user['user_id'];
                            }
                        }
                        CourseManager::unsubscribe_user($user_ids, $course_code, $session_id);
                    }
                }

                foreach ($clean_users as $userId) {
                    $userInfo = api_get_user_info($userId);
                    CourseManager::subscribe_user($userId, $course_code, STUDENT, $session_id);
                    if (empty($session_id)) {
                        //just to make sure
                        if (CourseManager :: is_user_subscribed_in_course($userId, $course_code)) {
                            $user_to_show[]= $userInfo['complete_name'];
                        }
                    } else {
                        //just to make sure
                        if (CourseManager::is_user_subscribed_in_course($userId, $course_code, true, $session_id)) {
                            $user_to_show[]= $userInfo['complete_name'];
                        }
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
        $userMessage = null;
        foreach ($user_to_show as $user) {
            if (!is_array($user)) {
                $user = array($user);
            }
            $user = array_filter($user);
            $userMessage .= implode(', ', $user)."<br />";
        }
        if ($type == 'confirmation') {
            Display::display_confirmation_message($message.': <br />'.$userMessage, false);
        } else {
            Display::display_warning_message($message.':  <br />'.$userMessage, false);
        }
    } else {
        $empty_line_msg = ($empty_line == 0) ? get_lang('ErrorsWhenImportingFile'): get_lang('ErrorsWhenImportingFile').': '.get_lang('EmptyHeaderLine');
        Display::display_error_message($empty_line_msg);
    }
}

$form->display();

echo get_lang('CSVMustLookLike');
echo '<blockquote><pre>
    username
    jdoe
    jmontoya
</pre>
</blockquote>';

echo get_lang('Or');
echo '<blockquote><pre>
    id
    23
    1337
</pre>
</blockquote>';

Display::display_footer();
