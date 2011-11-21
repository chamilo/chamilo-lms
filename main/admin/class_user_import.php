<?php
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.admin
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
    $errors = array ();
    $classcodes = array ();
    foreach ($user_classes as $index => $user_class) {
        $user_class['line'] = $index + 1;
        // 1. Check whether mandatory fields are set.
        $mandatory_fields = array ('UserName', 'ClassName');
        foreach ($mandatory_fields as $key => $field) {
            if (!isset ($user_class[$field]) || strlen($user_class[$field]) == 0) {
                $user_class['error'] = get_lang($field.'Mandatory');
                $errors[] = $user_class;
            }
        }
        // 2. Check whether classcode exists.
        if (isset ($user_class['ClassName']) && strlen($user_class['ClassName']) != 0) {
            // 2.1 Check whether code has been allready used in this CVS-file.
            if (!isset ($classcodes[$user_class['ClassName']])) {
                // 2.1.1 Check whether code exists in DB.
                $class_table = Database :: get_main_table(TABLE_MAIN_CLASS);
                $sql = "SELECT * FROM $class_table WHERE name = '".Database::escape_string($user_class['ClassName'])."'";
                $res = Database::query($sql);
                if (Database::num_rows($res) == 0) {
                    $user_class['error'] = get_lang('CodeDoesNotExists');
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
                $user_class['error'] = get_lang('UserNameTooLong').': '.$user_class['UserName'];
                $errors[] = $user_class;
            }
            $username = UserManager::purify_username($user_class['UserName'], $purification_option_for_usernames);
            // 3.2. Check whether username exists.
            if (UserManager::is_username_available($username)) {
                $user_class['error'] = get_lang('UnknownUser').': '.$username;
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
    $user_table 		= Database :: get_main_table(TABLE_MAIN_USER);
    $class_user_table 	= Database :: get_main_table(TABLE_MAIN_CLASS_USER);
    $class_table 		= Database :: get_main_table(TABLE_MAIN_CLASS);

    // Data parsing: purification + conversion (UserName, ClassName) --> (user_is, class_id)
    $csv_data = array ();
    foreach ($users_classes as $index => $user_class) {
        $sql1 = "SELECT user_id FROM $user_table WHERE username = '".Database::escape_string(UserManager::purify_username($user_class['UserName'], $purification_option_for_usernames))."'";
        $res1 = Database::query($sql1);
        $obj1 = Database::fetch_object($res1);
        $sql2 = "SELECT id FROM $class_table WHERE name = '".Database::escape_string(trim($user_class['ClassName']))."'";
        $res2 = Database::query($sql2);
        $obj2 = Database::fetch_object($res2);
        if ($obj1 && $obj2) {
            $csv_data[$obj1->user_id][$obj2->id] = 1;
        }
    }

    // Logic for processing the request (data + UI options).
    $db_subscriptions = array();
    foreach ($csv_data as $user_id => $csv_subscriptions) {
        $sql = "SELECT class_id FROM $class_user_table cu WHERE cu.user_id = $user_id";
        $res = Database::query($sql);
        while ($obj = Database::fetch_object($res)) {
            $db_subscriptions[$obj->class_id] = 1;
        }
        $to_subscribe = array_diff(array_keys($csv_subscriptions), array_keys($db_subscriptions));
        $to_unsubscribe = array_diff(array_keys($db_subscriptions), array_keys($csv_subscriptions));
        // Subscriptions for new classes.
        if ($_POST['subscribe']) {
            foreach ($to_subscribe as $class_id) {
                ClassManager::add_user($user_id, $class_id);
            }
        }
        // Unsubscription from previous classes.
        if ($_POST['unsubscribe']) {
            foreach ($to_unsubscribe as $class_id) {
                ClassManager::unsubscribe_user($user_id, $class_id);
            }
        }
    }
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

include '../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script(true);

require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH).'import.lib.php';
require_once api_get_path(LIBRARY_PATH).'classmanager.lib.php';

$tool_name = get_lang('AddUsersToAClass').' CSV';

$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));

// Set this option to true to enforce strict purification for usenames.
$purification_option_for_usernames = false;

set_time_limit(0);

$form = new FormValidator('class_user_import');
$form->addElement('file', 'import_file', get_lang('ImportFileLocation'));
$form->addElement('checkbox', 'subscribe', get_lang('Action'), get_lang('SubscribeUserIfNotAllreadySubscribed'));
$form->addElement('checkbox', 'unsubscribe', '', get_lang('UnsubscribeUserIfSubscriptionIsNotInFile'));
$form->addElement('style_submit_button', 'submit', get_lang('Import'), 'class="save"');
if ($form->validate()) {
    $users_classes = parse_csv_data($_FILES['import_file']['tmp_name']);
    $errors = validate_data($users_classes);
    if (count($errors) == 0) {
        save_data($users_classes);
        header('Location: class_list.php?action=show_message&message='.urlencode(get_lang('FileImported')));
        exit();
    }
}

Display :: display_header($tool_name);
api_display_tool_title($tool_name);

if (count($errors) != 0) {
    $error_message = "\n";
    foreach ($errors as $index => $error_class_user) {
        $error_message .= get_lang('Line').' '.$error_class_user['line'].': '.$error_class_user['error'].'</b>: ';
        $error_message .= "\n";
    }
    $error_message .= "\n";
    Display :: display_error_message($error_message);
}

$form->display();
?>
<p><?php echo get_lang('CSVMustLookLike').' ('.get_lang('MandatoryFields').')'; ?> :</p>
<blockquote>
<pre>
<b>UserName</b>;<b>ClassName</b>
jdoe;class01
adam;class01
</pre>
</blockquote>
<?php

/*
==============================================================================
        FOOTER
==============================================================================
*/
Display :: display_footer();
