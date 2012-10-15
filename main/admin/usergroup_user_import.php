<?php
/* For licensing terms, see /license.txt */
/**
 * 	@package chamilo.admin
 */
/**
 * Code
 * This tool allows platform admins to update class-user relations by uploading
 * a CSVfile
 */

/**
 * Validates imported data.
 */
function validate_data($user_classes) {
    global $purification_option_for_usernames;
    $errors = array();
    $classcodes = array();

    /* if (!isset($_POST['subscribe']) && !isset($_POST['subscribe']))  {
      $user_class['error'] = get_lang('SelectAnAction');
      $errors[] = $user_class;
      return $errors;
      } */

    $usergroup = new UserGroup();

    foreach ($user_classes as $index => $user_class) {
        $user_class['line'] = $index + 1;
        // 1. Check whether mandatory fields are set.
        $mandatory_fields = array('UserName', 'ClassName');

        foreach ($mandatory_fields as $key => $field) {
            if (!isset($user_class[$field]) || strlen($user_class[$field]) == 0) {
                $user_class['error'] = get_lang($field . 'Mandatory');
                $errors[] = $user_class;
            }
        }

        // 2. Check whether classcode exists.
        if (isset($user_class['ClassName']) && strlen($user_class['ClassName']) != 0) {
            // 2.1 Check whether code has been allready used in this CVS-file.
            if (!isset($classcodes[$user_class['ClassName']])) {
                // 2.1.1 Check whether code exists in DB
                $exists = $usergroup->usergroup_exists($user_class['ClassName']);
                if (!$exists) {
                    $user_class['error'] = get_lang('CodeDoesNotExists') . ': ' . $user_class['ClassName'];
                    $errors[] = $user_class;
                } else {
                    $classcodes[$user_class['CourseCode']] = 1;
                }
            }
        }

        // 3. Check username, first, check whether it is empty.
        if (!UserManager::is_username_empty($user_class['UserName'])) {
            // 3.1. Check whether username is too long.
            if (UserManager::is_username_too_long($user_class['UserName'])) {
                $user_class['error'] = get_lang('UserNameTooLong') . ': ' . $user_class['UserName'];
                $errors[] = $user_class;
            }

            $username = UserManager::purify_username($user_class['UserName'], $purification_option_for_usernames);
            // 3.2. Check whether username exists.
            if (UserManager::is_username_available($username)) {
                $user_class['error'] = get_lang('UnknownUser') . ': ' . $username;
                $errors[] = $user_class;
            }
        }
    }
    return $errors;
}

/**
 * Saves imported data.
 */
function save_data($users_classes) {

    global $purification_option_for_usernames;

    // Table definitions.
    $user_table = Database :: get_main_table(TABLE_MAIN_USER);

    $usergroup = new UserGroup();

    // Data parsing: purification + conversion (UserName, ClassName) --> (user_is, class_id)
    $csv_data = array();
    if (!empty($users_classes)) {
        
        foreach ($users_classes as $user_class) {
            $sql1 = "SELECT user_id FROM $user_table WHERE username = '" . Database::escape_string(UserManager::purify_username($user_class['UserName'], $purification_option_for_usernames)) . "'";
            $res1 = Database::query($sql1);
            $obj1 = Database::fetch_object($res1);

            $usergroup = new UserGroup();
            $id = $usergroup->get_id_by_name($user_class['ClassName']);

            if ($obj1 && $id) {
                $csv_data[$id]['user_list'][] = $obj1->user_id;
                $csv_data[$id]['user_list_name'][] = $user_class['UserName'];
                $csv_data[$id]['class_name'] = $user_class['ClassName'];
            }
        }
    }

    // Logic for processing the request (data + UI options).
    $message = null;
    if (!empty($csv_data)) {        
        foreach ($csv_data as $class_id => $user_data) {
            $user_list = $user_data['user_list'];
            $class_name = $user_data['class_name'];
            $user_list_name = $user_data['user_list_name'];
            $usergroup->subscribe_users_to_usergroup($class_id, $user_list);
            $message .= Display::return_message(get_lang('Class') . ': ' . $class_name . '<br />', 'normal', false);
            $message .= Display::return_message(get_lang('Users') . ': ' . implode(', ', $user_list_name));
        }
    }    
    return $message;
}

/**
 * Reads a CSV-file.
 * @param string $file Path to the CSV-file
 * @return array All course-information read from the file
 */
function parse_csv_data($file) {
    $courses = Import::csv_to_array($file);
    return $courses;
}

$language_file = array('admin', 'registration');
$cidReset = true;

require_once '../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script(true);

require_once api_get_path(LIBRARY_PATH) . 'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH) . 'import.lib.php';

$tool_name = get_lang('AddUsersToAClass') . ' CSV';

$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => 'usergroups.php', 'name' => get_lang('Classes'));

// Set this option to true to enforce strict purification for usenames.
$purification_option_for_usernames = false;

set_time_limit(0);

$form = new FormValidator('class_user_import');
$form->addElement('header', $tool_name);
$form->addElement('file', 'import_file', get_lang('ImportCSVFileLocation'));
//$form->addElement('checkbox', 'subscribe', get_lang('Action'), get_lang('SubscribeUserIfNotAllreadySubscribed'));
//$form->addElement('checkbox', 'unsubscribe', '', get_lang('UnsubscribeUserIfSubscriptionIsNotInFile'));
$form->addElement('style_submit_button', 'submit', get_lang('Import'), 'class="save"');

if ($form->validate()) {
    $users_classes = parse_csv_data($_FILES['import_file']['tmp_name']);
    $errors = validate_data($users_classes);
    if (count($errors) == 0) {
        $return = save_data($users_classes);
    }
}

Display :: display_header($tool_name);

if (isset($return) && $return) {
    echo $return;
}

if (count($errors) != 0) {
    $error_message = "\n";
    foreach ($errors as $index => $error_class_user) {
        $error_message .= get_lang('Line') . ' ' . $error_class_user['line'] . ': ' . $error_class_user['error'] . '</b>';
        $error_message .= "<br />";
    }
    $error_message .= "\n";
    Display :: display_error_message($error_message, false);
}
$form->display();
?>
<p><?php echo get_lang('CSVMustLookLike') . ' (' . get_lang('MandatoryFields') . ')'; ?> :</p>
<pre>
<b>UserName</b>;<b>ClassName</b>
jdoe;class01
adam;class01
</pre>
<?php
Display :: display_footer();
