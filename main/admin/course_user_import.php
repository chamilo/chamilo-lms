<?php
/* For licensing terms, see /license.txt */
/**
* This tool allows platform admins to update course-user relations by uploading
* a CSVfile
* @package chamilo.admin
*/
/**
 * Validates the imported data.
 */
function validate_data($users_courses) {
	$errors = array ();
	$coursecodes = array ();
	foreach ($users_courses as $index => $user_course) {
		$user_course['line'] = $index +1;
		// 1. Check whether mandatory fields are set.
		$mandatory_fields = array ('UserName', 'CourseCode', 'Status');
		foreach ($mandatory_fields as $key => $field) {
			if (!isset($user_course[$field]) || strlen($user_course[$field]) == 0) {
				$user_course['error'] = get_lang($field.'Mandatory');
				$errors[] = $user_course;
			}
		}
		// 2. Check whether coursecode exists.
		if (isset ($user_course['CourseCode']) && strlen($user_course['CourseCode']) != 0) {
			// 2.1 Check whethher code has been allready used by this CVS-file.
			if (!isset($coursecodes[$user_course['CourseCode']])) {
				// 2.1.1 Check whether course with this code exists in the system.
				$course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
				$sql = "SELECT * FROM $course_table WHERE code = '".Database::escape_string($user_course['CourseCode'])."'";
				$res = Database::query($sql);
				if (Database::num_rows($res) == 0) {
					$user_course['error'] = get_lang('CodeDoesNotExists');
					$errors[] = $user_course;
				} else {
					$coursecodes[$user_course['CourseCode']] = 1;
				}
			}
		}
		// 3. Check whether username exists.
		if (isset ($user_course['UserName']) && strlen($user_course['UserName']) != 0)
		{
			if (UserManager::is_username_available($user_course['UserName'])) {
				$user_course['error'] = get_lang('UnknownUser');
				$errors[] = $user_course;
			}
		}
		// 4. Check whether status is valid.
		if (isset ($user_course['Status']) && strlen($user_course['Status']) != 0) {
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
function save_data($users_courses) {
	$user_table= Database::get_main_table(TABLE_MAIN_USER);
	$course_user_table= Database::get_main_table(TABLE_MAIN_COURSE_USER);
	$csv_data = array();
	foreach ($users_courses as $index => $user_course) {
		$csv_data[$user_course['UserName']][$user_course['CourseCode']] = $user_course['Status'];
	}
	foreach($csv_data as $username => $csv_subscriptions) {
		$user_id = 0;
		$sql = "SELECT * FROM $user_table u WHERE u.username = '".Database::escape_string($username)."'";
		$res = Database::query($sql);
		$obj = Database::fetch_object($res);
		$user_id = $obj->user_id;
		$sql = "SELECT * FROM $course_user_table cu WHERE cu.user_id = $user_id AND cu.relation_type<>".COURSE_RELATION_TYPE_RRHH." ";
		$res = Database::query($sql);
		$db_subscriptions = array();
		while($obj = Database::fetch_object($res)) {
			$db_subscriptions[$obj->course_code] = $obj->status;
		}

		$to_subscribe = array_diff(array_keys($csv_subscriptions),array_keys($db_subscriptions));
		$to_unsubscribe = array_diff(array_keys($db_subscriptions),array_keys($csv_subscriptions));

        global $inserted_in_course;
        if (!isset($inserted_in_course)) {
        	$inserted_in_course = array();
        }
		if($_POST['subscribe'])	{
			foreach($to_subscribe as $index => $course_code) {
                if(CourseManager :: course_exists($course_code)) {
                    CourseManager::add_user_to_course($user_id,$course_code,$csv_subscriptions[$course_code]);
                    $course_info = CourseManager::get_course_information($course_code);
                    $inserted_in_course[$course_code] = $course_info['title'];
                }
                if (CourseManager :: course_exists($course_code,true)) {
                    // Also subscribe to virtual courses through check on visual code.
                    $list = CourseManager :: get_courses_info_from_visual_code($course_code);
                    foreach ($list as $vcourse) {
                        if ($vcourse['code'] == $course_code) {
                            // Ignore, this has already been inserted.
                        } else {
                            CourseManager::add_user_to_course($user_id,$vcourse['code'],$csv_subscriptions[$course_code]);
                            $inserted_in_course[$vcourse['code']] = $vcourse['title'];
                        }
                    }
                }
			}
		}
		if($_POST['unsubscribe']) {
			foreach($to_unsubscribe as $index => $course_code) {
                if(CourseManager :: course_exists($course_code)) {
                    CourseManager::unsubscribe_user($user_id,$course_code);
                    $course_info = CourseManager::get_course_information($course_code);
                    $inserted_in_course[$course_code] = $course_info['title'];
                }
                if (CourseManager :: course_exists($course_code,true)) {
                    // also subscribe to virtual courses through check on visual code
                    $list = CourseManager :: get_courses_info_from_visual_code($course_code);
                    foreach ($list as $vcourse) {
                        if ($vcourse['code'] == $course_code) {
                            // Ignore, this has already been inserted.
                        } else {
                            CourseManager::unsubscribe_user($user_id,$vcourse['code']);
                            $inserted_in_course[$vcourse['code']] = $vcourse['title'];
                        }
                    }
                }
			}
		}
	}
}

/**
 * Reads CSV-file.
 * @param string $file Path to the CSV-file
 * @return array All course-information read from the file
 */
function parse_csv_data($file) {
	$courses = Import :: csv_to_array($file);
	return $courses;
}

// Language files that should be included,
$language_file = array ('admin', 'registration');

$cidReset = true;

// Including the global Dokeos file.
include '../inc/global.inc.php';

// Setting the section (for the tabs).
$this_section = SECTION_PLATFORM_ADMIN;

// Protecting the admin section.
api_protect_admin_script();

// Including additional libraries.
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH).'import.lib.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';

$tool_name = get_lang('AddUsersToACourse').' CSV';

$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));

set_time_limit(0);

// Creating the form.
$form = new FormValidator('course_user_import');
$form->addElement('header', '', $tool_name);
$form->addElement('file', 'import_file', get_lang('ImportFileLocation'));
$form->addElement('checkbox', 'subscribe', get_lang('Action'), get_lang('SubscribeUserIfNotAllreadySubscribed'));
$form->addElement('checkbox', 'unsubscribe', '', get_lang('UnsubscribeUserIfSubscriptionIsNotInFile'));
$form->addElement('style_submit_button', 'submit',get_lang('Import'),'class="save"');
if ($form->validate()) {
	$users_courses = parse_csv_data($_FILES['import_file']['tmp_name']);
	$errors = validate_data($users_courses);
	if (count($errors) == 0) {
        $inserted_in_course = array();
		save_data($users_courses);
        // Build the alert message in case there were visual codes subscribed to.
        if ($_POST['subscribe']) {
            $warn = get_lang('UsersSubscribedToBecauseVisualCode').': ';
        } else {
            $warn = get_lang('UsersUnsubscribedFromBecauseVisualCode').': ';
        }
        if (count($inserted_in_course) > 1) {
        	// The users have been inserted in more than one course.
            foreach ($inserted_in_course as $code => $info) {
            	$warn .= ' '.$info.' ('.$code.'),';
            }
            $warn = substr($warn,0,-1);
        }
        Security::clear_token();
        $tok = Security::get_token();
		header('Location: user_list.php?action=show_message&message='.urlencode(get_lang('FileImported')).'&warn='.urlencode($warn).'&sec_token='.$tok);
		exit ();
	}
}

// Displaying the header.
Display :: display_header($tool_name);

// Displaying the tool title.
// api_display_tool_title($tool_name);

if (count($errors) != 0) {
	$error_message = '<ul>';
	foreach ($errors as $index => $error_course) {
		$error_message .= '<li>'.get_lang('Line').' '.$error_course['line'].': <strong>'.$error_course['error'].'</strong>: ';
		$error_message .= $error_course['Code'].' '.$error_course['Title'];
		$error_message .= '</li>';
	}
	$error_message .= '</ul>';
	Display :: display_error_message($error_message);
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
// Footer.
Display :: display_footer();
