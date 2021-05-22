<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_COURSES;

// notice for unauthorized people.
api_protect_course_script(true);

if ('false' == api_get_setting('allow_user_course_subscription_by_course_admin')) {
    if (!api_is_platform_admin()) {
        api_not_allowed(true);
    }
}

// Make sure we know if we're importing students or teachers into the course
$userType = STUDENT;
if (!empty($_REQUEST['type']) && COURSEMANAGER == $_REQUEST['type']) {
    $userType = COURSEMANAGER;
}

$tool_name = get_lang('Import users list');

$interbreadcrumb[] = ["url" => "user.php", "name" => get_lang("Users")];
$interbreadcrumb[] = ["url" => "#", "name" => get_lang("Import users list")];

$form = new FormValidator('user_import', 'post', 'user_import.php');
$form->addElement('header', $tool_name);
$form->addElement('file', 'import_file', get_lang('CSV file import location'));
$form->addElement('checkbox', 'unsubscribe_users', null, get_lang('Unsubscribe users already added'));
$form->addElement('hidden', 'type', $userType);
$form->addButtonImport(get_lang('Import'));

$course_code = api_get_course_id();
$courseId = api_get_course_int_id();

if (empty($course_code)) {
    api_not_allowed(true);
}

$session_id = api_get_session_id();
$message = '';
$user_to_show = [];
$type = '';

if ($form->validate()) {
    if (isset($_FILES['import_file']['size']) && 0 !== $_FILES['import_file']['size']) {
        $unsubscribe_users = isset($_POST['unsubscribe_users']) ? true : false;
        //@todo : csvToArray deprecated
        $users = Import::csvToArray($_FILES['import_file']['tmp_name']);
        $invalid_users = [];
        $clean_users = [];

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
                $message = get_lang('List of users subscribed to course');

                if ($unsubscribe_users) {
                    $current_user_list = CourseManager::get_user_list_from_course_code(
                        $course_code,
                        $session_id,
                        null,
                        null,
                        $userType
                    );
                    if (!empty($current_user_list)) {
                        $user_ids = [];
                        foreach ($current_user_list as $user) {
                            if (COURSEMANAGER == $userType) {
                                if (CourseManager::isCourseTeacher($user['user_id'], $courseId)) {
                                    $user_ids[] = $user['user_id'];
                                }
                            } else {
                                if (!CourseManager::isCourseTeacher($user['user_id'], $courseId)) {
                                    $user_ids[] = $user['user_id'];
                                }
                            }
                        }
                        CourseManager::unsubscribe_user($user_ids, $course_code, $session_id);
                    }
                }

                foreach ($clean_users as $userId) {
                    $userInfo = api_get_user_info($userId);
                    CourseManager::subscribeUser($userId, $courseId, $userType, $session_id);
                    if (empty($session_id)) {
                        //just to make sure
                        if (CourseManager::is_user_subscribed_in_course($userId, $course_code)) {
                            $user_to_show[] = $userInfo['complete_name'];
                        }
                    } else {
                        //just to make sure
                        if (CourseManager::is_user_subscribed_in_course($userId, $course_code, true, $session_id)) {
                            $user_to_show[] = $userInfo['complete_name'];
                        }
                    }
                }
            } else {
                $message = get_lang('Use user\'s IDs from the file to subscribe them');
                $type = 'warning';
                foreach ($invalid_users as $invalid_user) {
                    $user_to_show[] = $invalid_user;
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
                $user = [$user];
            }
            $user = array_filter($user);
            $userMessage .= implode(', ', $user)."<br />";
        }
        if ('confirmation' == $type) {
            echo Display::return_message($message.': <br />'.$userMessage, 'confirm', false);
        } else {
            echo Display::return_message($message.':  <br />'.$userMessage, 'warning', false);
        }
    } else {
        $empty_line_msg = (0 == $empty_line) ? get_lang('Errors when importing file') : get_lang('Errors when importing file').': '.get_lang('There are empty lines in the header of selected file');
        echo Display::return_message($empty_line_msg, 'error');
    }
}

$content = get_lang('The CSV file must look like this').
'<blockquote><pre>
    username
    jdoe
    jmontoya
</pre>
</blockquote>'.
get_lang('or').
    '<blockquote><pre>
    id
    23
    1337
</pre>
</blockquote>';

echo Display::prose($content);
$form->display();

Display::display_footer();
