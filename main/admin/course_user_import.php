<?php
/* For licensing terms, see /license.txt */

/**
 * This tool allows platform admins to update course-user relations by uploading
 * a CSV file.
 */

/**
 * Validates the imported data.
 */
function validate_data($users_courses)
{
    $errors = [];
    $coursecodes = [];
    foreach ($users_courses as $index => $user_course) {
        $user_course['line'] = $index + 1;
        // 1. Check whether mandatory fields are set.
        $mandatory_fields = ['UserName', 'CourseCode', 'Status'];
        foreach ($mandatory_fields as $key => $field) {
            if (!isset($user_course[$field]) || strlen($user_course[$field]) == 0) {
                $user_course['error'] = get_lang($field.'Mandatory');
                $errors[] = $user_course;
            }
        }

        // 2. Check whether coursecode exists.
        if (isset($user_course['CourseCode']) && strlen($user_course['CourseCode']) != 0) {
            // 2.1 Check whethher code has been allready used by this CVS-file.
            if (!isset($coursecodes[$user_course['CourseCode']])) {
                // 2.1.1 Check whether course with this code exists in the system.
                $courseInfo = api_get_course_info($user_course['CourseCode']);
                if (empty($courseInfo)) {
                    $user_course['error'] = get_lang('CodeDoesNotExists');
                    $errors[] = $user_course;
                } else {
                    $coursecodes[$user_course['CourseCode']] = 1;
                }
            }
        }

        // 3. Check whether username exists.
        if (isset($user_course['UserName']) && strlen($user_course['UserName']) != 0) {
            if (UserManager::is_username_available($user_course['UserName'])) {
                $user_course['error'] = get_lang('UnknownUser');
                $errors[] = $user_course;
            }
        }

        // 4. Check whether status is valid.
        if (isset($user_course['Status']) && strlen($user_course['Status']) != 0) {
            if ($user_course['Status'] != COURSEMANAGER && $user_course['Status'] != STUDENT) {
                $user_course['error'] = get_lang('UnknownStatus');
                $errors[] = $user_course;
            }
        }
    }

    return $errors;
}

/**
 * Saves imported data.
 */
function save_data($users_courses)
{
    $course_user_table = Database::get_main_table(TABLE_MAIN_COURSE_USER);
    $csv_data = [];
    $inserted_in_course = [];
    $courseListCache = [];
    $courseListById = [];
    foreach ($users_courses as $user_course) {
        if (!in_array($user_course['CourseCode'], array_keys($courseListCache))) {
            $courseInfo = api_get_course_info($user_course['CourseCode']);
            if ($courseInfo) {
                $courseListCache[$user_course['CourseCode']] = $courseInfo;
            }
        } else {
            $courseInfo = $courseListCache[$user_course['CourseCode']];
        }
        $courseListById[$courseInfo['real_id']] = $courseInfo;
        $csv_data[$user_course['UserName']][$courseInfo['real_id']] = $user_course['Status'];
    }

    foreach ($csv_data as $username => $csv_subscriptions) {
        $userInfo = api_get_user_info_from_username($username);
        if (empty($userInfo)) {
            continue;
        }

        $user_id = $userInfo['user_id'];
        $sql = "SELECT * FROM $course_user_table cu
                WHERE cu.user_id = $user_id AND cu.relation_type <> ".COURSE_RELATION_TYPE_RRHH." ";
        $res = Database::query($sql);
        $db_subscriptions = [];
        while ($obj = Database::fetch_object($res)) {
            $db_subscriptions[$obj->c_id] = $obj->status;
        }

        $to_subscribe = array_diff(array_keys($csv_subscriptions), array_keys($db_subscriptions));
        $to_unsubscribe = array_diff(array_keys($db_subscriptions), array_keys($csv_subscriptions));

        if (isset($_POST['subscribe']) && $_POST['subscribe']) {
            foreach ($to_subscribe as $courseId) {
                $courseInfo = $courseListById[$courseId];
                $courseCode = $courseInfo['code'];
                $result = CourseManager::subscribeUser(
                    $user_id,
                    $courseCode,
                    $csv_subscriptions[$courseId]
                );
                if ($result) {
                    $inserted_in_course[$courseInfo['code']] = $courseInfo['title'];
                }
            }
        }

        if (isset($_POST['unsubscribe']) && $_POST['unsubscribe']) {
            foreach ($to_unsubscribe as $courseId) {
                if (isset($courseListById[$courseId])) {
                    $courseInfo = $courseListById[$courseId];
                } else {
                    $courseInfo = api_get_course_info_by_id($courseId);
                }
                $courseCode = $courseInfo['code'];
                CourseManager::unsubscribe_user($user_id, $courseCode);
            }
        }
    }

    return $inserted_in_course;
}

/**
 * Reads CSV-file.
 *
 * @param string $file Path to the CSV-file
 *
 * @return array All course-information read from the file
 */
function parse_csv_data($file)
{
    return Import::csvToArray($file);
}

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

// Setting the section (for the tabs).
$this_section = SECTION_PLATFORM_ADMIN;

// Protecting the admin section.
api_protect_admin_script();

$tool_name = get_lang('AddUsersToACourse').' CSV';

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];

set_time_limit(0);

// Creating the form.
$form = new FormValidator('course_user_import');
$form->addElement('header', '', $tool_name);
$form->addElement('file', 'import_file', get_lang('ImportFileLocation'));
$form->addElement('checkbox', 'subscribe', get_lang('Action'), get_lang('SubscribeUserIfNotAllreadySubscribed'));
$form->addElement('checkbox', 'unsubscribe', '', get_lang('UnsubscribeUserIfSubscriptionIsNotInFile'));
$form->addButtonImport(get_lang('Import'));
$form->setDefaults(['subscribe' => '1', 'unsubscribe' => 0]);
$errors = [];

if ($form->validate()) {
    $users_courses = parse_csv_data($_FILES['import_file']['tmp_name']);
    $errors = validate_data($users_courses);
    if (count($errors) == 0) {
        $inserted_in_course = save_data($users_courses);
        // Build the alert message in case there were visual codes subscribed to.
        if ($_POST['subscribe']) {
            //$warn = get_lang('UsersSubscribedToBecauseVisualCode').': ';
        } else {
            $warn = get_lang('UsersUnsubscribedFromBecauseVisualCode').': ';
        }

        if (!empty($inserted_in_course)) {
            $warn = get_lang('FileImported');
        } else {
            $warn = get_lang('ErrorsWhenImportingFile');
        }

        Display::addFlash(Display::return_message($warn));

        Security::clear_token();
        $tok = Security::get_token();
        header('Location: '.api_get_self());
        exit();
    }
}

// Displaying the header.
Display::display_header($tool_name);

if (count($errors) != 0) {
    $error_message = '<ul>';
    foreach ($errors as $index => $error_course) {
        $error_message .= '<li>'.get_lang('Line').' '.$error_course['line'].': <strong>'.$error_course['error'].'</strong>: ';
        $error_message .= $error_course['Code'].' '.$error_course['Title'];
        $error_message .= '</li>';
    }
    $error_message .= '</ul>';
    echo Display::return_message($error_message, 'error', false);
}

// Displaying the form.
$form->display();
?>
    <p><?php echo get_lang('CSVMustLookLike').' ('.get_lang('MandatoryFields').')'; ?> :</p>
    <blockquote>
<pre>
<b>UserName</b>;<b>CourseCode</b>;<b>Status</b>
jdoe;course01;<?php echo COURSEMANAGER; ?>

adam;course01;<?php echo STUDENT; ?>
</pre>
        <?php
        echo COURSEMANAGER.': '.get_lang('Teacher').'<br />';
        echo STUDENT.': '.get_lang('Student').'<br />';
        ?>
    </blockquote>
<?php

Display::display_footer();
