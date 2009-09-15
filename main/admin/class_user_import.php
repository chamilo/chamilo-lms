<?php

// $Id: class_user_import.php 9018 2006-06-28 15:11:16Z evie_em $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2005 Bart Mollet <bart.mollet@hogent.be>

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
* This tool allows platform admins to update class-user relations by uploading
* a CSVfile
* @package dokeos.admin
==============================================================================
*/

/**
 * Validates imported data.
 */
function validate_data($user_classes) {
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
				$res = Database::query($sql, __FILE__, __LINE__);
				if (Database::num_rows($res) == 0) {
					$user_class['error'] = get_lang('CodeDoesNotExists');
					$errors[] = $user_class;
				} else {
					$classcodes[$user_class['CourseCode']] = 1;
				}
			}
		}
		// 3. Check whether username exists.
		if (isset ($user_class['UserName']) && strlen($user_class['UserName']) != 0) {
			if (UserManager :: is_username_available($user_class['UserName'])) {
				$user_class['error'] = get_lang('UnknownUser').': '.$user_class['UserName'];
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
	// Table definitions.
	$user_table 		= Database :: get_main_table(TABLE_MAIN_USER);
	$class_user_table 	= Database :: get_main_table(TABLE_MAIN_CLASS_USER);
	$class_table 		= Database :: get_main_table(TABLE_MAIN_CLASS);

	$csv_data = array ();
	foreach ($users_classes as $index => $user_class) {
		$sql = "SELECT * FROM $class_table WHERE name = '".Database::escape_string($user_class['ClassName'])."'";
		$res = Database::query($sql, __FILE__, __LINE__);
		$obj = Database::fetch_object($res);
		$csv_data[$user_class['UserName']][$obj->id] = 1;
	}
	foreach ($csv_data as $username => $csv_subscriptions) {
		$user_id = 0;
		$sql = "SELECT * FROM $user_table u WHERE u.username = '".Database::escape_string($username)."'";
		$res = Database::query($sql, __FILE__, __LINE__);
		$obj = Database::fetch_object($res);
		$user_id = $obj->user_id;
		$sql = "SELECT * FROM $class_user_table cu WHERE cu.user_id = $user_id";
		$res = Database::query($sql, __FILE__, __LINE__);
		$db_subscriptions = array ();
		while ($obj = Database::fetch_object($res)) {
			$db_subscriptions[$obj->class_id] = 1;
		}
		$to_subscribe = array_diff(array_keys($csv_subscriptions), array_keys($db_subscriptions));
		$to_unsubscribe = array_diff(array_keys($db_subscriptions), array_keys($csv_subscriptions));
		if ($_POST['subscribe']) {
			foreach ($to_subscribe as $index => $class_id) {
				ClassManager :: add_user($user_id, $class_id);
				//echo get_lang('Subscription').' : '.$course_code.'<br />';
			}
		}
		if ($_POST['unsubscribe']) {
			foreach ($to_unsubscribe as $index => $class_id) {
				ClassManager :: unsubscribe_user($user_id, $class_id);
				//echo get_lang('Unsubscription').' : '.$course_code.'<br />';
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

// Names of the language file that needs to be included.
$language_file = array('admin', 'registration');

$cidReset = true;

include '../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script(true);

require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH).'import.lib.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'classmanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';

$tool_name = get_lang('AddUsersToAClass').' CSV';

$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));

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
a.dam;class01
</pre>
</blockquote>
<?php

/*
==============================================================================
		FOOTER
==============================================================================
*/
Display :: display_footer();
