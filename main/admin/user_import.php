<?php
/* For licensing terms, see /license.txt */
/**
 * This tool allows platform admins to add users by uploading a CSV or XML file
 * @package chamilo.admin
 */

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

// Set this option to true to enforce strict purification for usenames.
$purification_option_for_usernames = false;

/**
 * @param array $users
 * @param bool  $checkUniqueEmail
 * @return array
 */
function validate_data($users, $checkUniqueEmail = false)
{
    global $defined_auth_sources;
    $errors = array();
    $usernames = array();

    // 1. Check if mandatory fields are set.
    $mandatory_fields = array('LastName', 'FirstName');

    if (api_get_setting('registration', 'email') == 'true' || $checkUniqueEmail) {
        $mandatory_fields[] = 'Email';
    }

    $classExistList = array();
    $usergroup = new UserGroup();

    foreach ($users as $user) {
        foreach ($mandatory_fields as $field) {
            if (empty($user[$field])) {
                $user['error'] = get_lang($field.'Mandatory');
                $errors[] = $user;
            }
        }
        $username = $user['UserName'];
        // 2. Check username, first, check whether it is empty.
        if (!UserManager::is_username_empty($username)) {
            // 2.1. Check whether username is too long.
            if (UserManager::is_username_too_long($username)) {
                $user['error'] = get_lang('UserNameTooLong');
                $errors[] = $user;
            }
            // 2.1.1
            $hasDash = strpos($username, '-');
            if ($hasDash !== false) {
                $user['error'] = get_lang('UserNameHasDash');
                $errors[] = $user;
            }
            // 2.2. Check whether the username was used twice in import file.
            if (isset($usernames[$user['UserName']])) {
                $user['error'] = get_lang('UserNameUsedTwice');
                $errors[] = $user;
            }
            $usernames[$user['UserName']] = 1;
            // 2.3. Check whether username is already occupied.
            if (!UserManager::is_username_available($user['UserName'])) {
                $user['error'] = get_lang('UserNameNotAvailable');
                $errors[] = $user;
            }
        }

        if ($checkUniqueEmail) {
            if (isset($user['Email'])) {
                $userFromEmail = api_get_user_info_from_email($user['Email']);
                if (!empty($userFromEmail)) {
                    $user['error'] = get_lang('EmailUsedTwice');
                    $errors[] = $user;
                }
            }
        }

        // 3. Check status.
        if (isset($user['Status']) && !api_status_exists($user['Status'])) {
            $user['error'] = get_lang('WrongStatus');
            $errors[] = $user;
        }

        // 4. Check ClassId
        if (!empty($user['ClassId'])) {
            $classId = explode('|', trim($user['ClassId']));
            foreach ($classId as $id) {
                if (in_array($id, $classExistList)) {
                    continue;
                }
                $info = $usergroup->get($id);
                if (empty($info)) {
                    $user['error'] = sprintf(get_lang('ClassIdDoesntExists'), $id);
                    $errors[] = $user;
                } else {
                    $classExistList[] = $info['id'];
                }
            }
        }

        // 5. Check authentication source
        if (!empty($user['AuthSource'])) {
            if (!in_array($user['AuthSource'], $defined_auth_sources)) {
                $user['error'] = get_lang('AuthSourceNotAvailable');
                $errors[] = $user;
            }
        }
    }

    return $errors;
}

/**
 * Add missing user-information (which isn't required, like password, username etc).
 */
function complete_missing_data($user)
{
    global $purification_option_for_usernames;

    // 1. Create a username if necessary.
    if (UserManager::is_username_empty($user['UserName'])) {
        $user['UserName'] = UserManager::create_unique_username(
            $user['FirstName'],
            $user['LastName']
        );
    } else {
        $user['UserName'] = UserManager::purify_username(
            $user['UserName'],
            $purification_option_for_usernames
        );
    }

    // 2. Generate a password if necessary.
    if (empty($user['Password'])) {
        $user['Password'] = api_generate_password();
    }
    // 3. Set status if not allready set.
    if (empty($user['Status'])) {
        $user['Status'] = 'user';
    }
    // 4. Set authsource if not allready set.
    if (empty($user['AuthSource'])) {
        $user['AuthSource'] = PLATFORM_AUTH_SOURCE;
    }

    if (empty($user['ExpiryDate'])) {
        $user['ExpiryDate'] = '';
    }

    if (!isset($user['OfficialCode'])) {
        $user['OfficialCode'] = '';
    }

    if (!isset($user['language'])) {
        $user['language'] = '';
    }

    if (!isset($user['PhoneNumber'])) {
        $user['PhoneNumber'] = '';
    }

    if (!isset($user['OfficialCode'])) {
        $user['OfficialCode'] = '';
    }

    return $user;
}

/**
 * Save the imported data
 * @param   array   $users List of users
 * @return  void
 * @uses global variable $inserted_in_course, which returns the list of
 * courses the user was inserted in
 */
function save_data($users)
{
    global $inserted_in_course;
    // Not all scripts declare the $inserted_in_course array (although they should).
    if (!isset($inserted_in_course)) {
        $inserted_in_course = array();
    }
    $usergroup = new UserGroup();
    $send_mail = $_POST['sendMail'] ? true : false;

    if (is_array($users)) {
        foreach ($users as $user) {
            $user = complete_missing_data($user);
            $user['Status'] = api_status_key($user['Status']);
            $user_id = UserManager :: create_user(
                $user['FirstName'],
                $user['LastName'],
                $user['Status'],
                $user['Email'],
                $user['UserName'],
                $user['Password'],
                $user['OfficialCode'],
                $user['language'],
                $user['PhoneNumber'],
                '',
                $user['AuthSource'],
                $user['ExpiryDate'],
                1,
                0,
                null,
                null,
                $send_mail
            );

            if (isset($user['Courses']) && is_array($user['Courses'])) {
                foreach ($user['Courses'] as $course) {
                    if (CourseManager::course_exists($course)) {
                        CourseManager::subscribe_user($user_id, $course, $user['Status']);
                        $course_info = CourseManager::get_course_information($course);
                        $inserted_in_course[$course] = $course_info['title'];
                    }
                }
            }
            if (!empty($user['ClassId'])) {
                $classId = explode('|', trim($user['ClassId']));
                foreach ($classId as $id) {
                    $usergroup->subscribe_users_to_usergroup($id, array($user_id), false);
                }
            }

            // Saving extra fields.
            global $extra_fields;

            // We are sure that the extra field exists.
            foreach ($extra_fields as $extras) {
                if (isset($user[$extras[1]])) {
                    $key = $extras[1];
                    $value = $user[$extras[1]];
                    UserManager::update_extra_field_value($user_id, $key, $value);
                }
            }
        }
    }
}

/**
 * Read the CSV-file
 * @param string $file Path to the CSV-file
 * @return array All userinformation read from the file
 */
function parse_csv_data($file)
{
    $users = Import :: csvToArray($file);
    foreach ($users as $index => $user) {
        if (isset($user['Courses'])) {
            $user['Courses'] = explode('|', trim($user['Courses']));
        }

        // Lastname is needed.
        if (!isset($user['LastName']) || (isset($user['LastName']) && empty($user['LastName']))) {
            unset($users[$index]);
            continue;
        }

        // FirstName is needed.
        if (!isset($user['FirstName']) || (isset($user['FirstName']) && empty($user['FirstName']))) {
            unset($users[$index]);
            continue;
        }

        $users[$index] = $user;
    }

    return $users;
}
/**
 * XML-parser: handle start of element
 * @param   string  $parser Deprecated?
 * @param   string  $data The data to be parsed
 */
function element_start($parser, $data)
{
    $data = api_utf8_decode($data);
    global $user;
    global $current_tag;
    switch ($data) {
        case 'Contact':
            $user = array();
            break;
        default:
            $current_tag = $data;
    }
}

/**
 * XML-parser: handle end of element
 * @param   string  $parser Deprecated?
 * @param   string  $data   The data to be parsed
 */
function element_end($parser, $data)
{
    $data = api_utf8_decode($data);
    global $user;
    global $users;
    global $current_value;
    switch ($data) {
        case 'Contact':
            if ($user['Status'] == '5') {
                $user['Status'] = STUDENT;
            }
            if ($user['Status'] == '1') {
                $user['Status'] = COURSEMANAGER;
            }
            $users[] = $user;
            break;
        default:
            $user[$data] = $current_value;
            break;
    }
}

/**
 * XML-parser: handle character data
 * @param   string  $parser Parser (deprecated?)
 * @param   string  $data The data to be parsed
 * @return  void
 */
function character_data($parser, $data)
{
    $data = trim(api_utf8_decode($data));
    global $current_value;
    $current_value = $data;
}

/**
 * Read the XML-file
 * @param string $file Path to the XML-file
 * @return array All user information read from the file
 */
function parse_xml_data($file)
{
    global $users;
    $users = array();
    $parser = xml_parser_create('UTF-8');
    xml_set_element_handler($parser, 'element_start', 'element_end');
    xml_set_character_data_handler($parser, 'character_data');
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, false);
    xml_parse($parser, api_utf8_encode_xml(file_get_contents($file)));
    xml_parser_free($parser);

    return $users;
}

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script(true, null, 'login');
api_protect_limit_for_session_admin();

$defined_auth_sources[] = PLATFORM_AUTH_SOURCE;

if (isset($extAuthSource) && is_array($extAuthSource)) {
    $defined_auth_sources = array_merge($defined_auth_sources, array_keys($extAuthSource));
}

$tool_name = get_lang('ImportUserListXMLCSV');
$interbreadcrumb[] = array("url" => 'index.php', "name" => get_lang('PlatformAdmin'));

set_time_limit(0);
$extra_fields = UserManager::get_extra_fields(0, 0, 5, 'ASC', true);
$user_id_error = array();
$error_message = '';

if (isset($_POST['formSent']) && $_POST['formSent'] AND
    $_FILES['import_file']['size'] !== 0
) {
    $file_type = $_POST['file_type'];
    Security::clear_token();
    $tok = Security::get_token();
    $allowed_file_mimetype = array('csv', 'xml');
    $error_kind_file = false;

    $checkUniqueEmail = isset($_POST['check_unique_email']) ? $_POST['check_unique_email'] : null;

    $uploadInfo = pathinfo($_FILES['import_file']['name']);
    $ext_import_file = $uploadInfo['extension'];
    $users = array();
    if (in_array($ext_import_file, $allowed_file_mimetype)) {
        if (strcmp($file_type, 'csv') === 0 &&
            $ext_import_file == $allowed_file_mimetype[0]
        ) {
            $users = parse_csv_data($_FILES['import_file']['tmp_name']);
            $errors = validate_data($users, $checkUniqueEmail);
            $error_kind_file = false;
        } elseif (strcmp($file_type, 'xml') === 0 && $ext_import_file == $allowed_file_mimetype[1]) {
            $users = parse_xml_data($_FILES['import_file']['tmp_name']);
            $errors = validate_data($users, $checkUniqueEmail);
            $error_kind_file = false;
        } else {
            $error_kind_file = true;
        }
    } else {
        $error_kind_file = true;
    }

    // List user id with error.
    $users_to_insert = array();

    $keyToCheck = 'UserName';
    if ($checkUniqueEmail || api_get_setting('registration', 'email') == 'true') {
        $keyToCheck = 'Email';
    }

    if (is_array($errors)) {
        foreach ($errors as $my_errors) {
            $user_id_error[] = $my_errors[$keyToCheck];
        }
    }

    if (is_array($users)) {
        foreach ($users as $my_user) {
            if (!in_array($my_user[$keyToCheck], $user_id_error)) {
                $users_to_insert[] = $my_user;
            }
        }
    }

    $inserted_in_course = array();
    if (strcmp($file_type, 'csv') === 0) {
        save_data($users_to_insert);
    } elseif (strcmp($file_type, 'xml') === 0) {
        save_data($users_to_insert);
    } else {
        $error_message = get_lang('YouMustImportAFileAccordingToSelectedOption');
    }

    if (count($errors) > 0) {
        $see_message_import = get_lang('FileImportedJustUsersThatAreNotRegistered');
    } else {
        $see_message_import = get_lang('FileImported');
    }

    $warning_message = '';
    if (count($errors) != 0) {
        $warning_message = '<ul>';
        foreach ($errors as $index => $error_user) {
            $email = isset($error_user['Email']) ? ' - '.$error_user['Email'] : null;
            $warning_message .= '<li><b>'.$error_user['error'].'</b>: ';
            $warning_message .=
                '<strong>'.$error_user['UserName'].'</strong> - '.
                api_get_person_name(
                    $error_user['FirstName'],
                    $error_user['LastName']
                ).' '.$email;
            $warning_message .= '</li>';
        }
        $warning_message .= '</ul>';
    }

    // if the warning message is too long then we display the warning message trough a session
    Display::addFlash(Display::return_message($warning_message, 'warning', false));
    Display::addFlash(Display::return_message($see_message_import, 'confirmation', false));

    if ($error_kind_file) {
        Display::addFlash(
            Display::return_message(
                get_lang('YouMustImportAFileAccordingToSelectedOption'),
                'error',
                false
            )
        );
    } else {
        header('Location: '.api_get_path(WEB_CODE_PATH).'admin/user_list.php?sec_token='.$tok);
        exit;
    }
}

Display :: display_header($tool_name);

$form = new FormValidator('user_import', 'post', api_get_self());
$form->addElement('header', '', $tool_name);
$form->addElement('hidden', 'formSent');
$form->addElement('file', 'import_file', get_lang('ImportFileLocation'));
$group = array(
    $form->createElement(
        'radio',
        'file_type',
        '',
        'CSV (<a href="example.csv" target="_blank">'.get_lang('ExampleCSVFile').'</a>)',
        'csv'
    ),
    $form->createElement(
        'radio',
        'file_type',
        null,
        'XML (<a href="example.xml" target="_blank">'.get_lang('ExampleXMLFile').'</a>)',
        'xml'
    )
);

$form->addGroup($group, '', get_lang('FileType'));

$group = array(
    $form->createElement('radio', 'sendMail', '', get_lang('Yes'), 1),
    $form->createElement('radio', 'sendMail', null, get_lang('No'), 0)
);
$form->addGroup($group, '', get_lang('SendMailToUsers'));

$form->addElement(
    'checkbox',
    'check_unique_email',
    '',
    get_lang('CheckUniqueEmail')
);

$form->addButtonImport(get_lang('Import'));
$defaults['formSent'] = 1;
$defaults['sendMail'] = 0;
$defaults['file_type'] = 'csv';
$form->setDefaults($defaults);
$form->display();

$list = array();
$list_reponse = array();
$result_xml = '';
$i = 0;
$count_fields = count($extra_fields);
if ($count_fields > 0) {
    foreach ($extra_fields as $extra) {
        $list[] = $extra[1];
        $list_reponse[] = 'xxx';
        $spaces = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $result_xml .= $spaces.'&lt;'.$extra[1].'&gt;xxx&lt;/'.$extra[1].'&gt;';
        if ($i != $count_fields - 1) {
            $result_xml .= '<br/>';
        }
        $i++;
    }
}
?>
<p><?php echo get_lang('CSVMustLookLike').' ('.get_lang('MandatoryFields').')'; ?> :</p>
<blockquote>
<pre>
<b>LastName</b>;<b>FirstName</b>;<b>Email</b>;UserName;Password;AuthSource;OfficialCode;PhoneNumber;Status;ExpiryDate;<span style="color:red;"><?php if (count($list) > 0) echo implode(';', $list).';'; ?></span>Courses;ClassId;
<b>xxx</b>;<b>xxx</b>;<b>xxx</b>;xxx;xxx;<?php echo implode('/', $defined_auth_sources); ?>;xxx;xxx;user/teacher/drh;0000-00-00 00:00:00;<span style="color:red;"><?php if (count($list_reponse) > 0) echo implode(';', $list_reponse).';'; ?></span>xxx1|xxx2|xxx3;1;<br />
</pre>
</blockquote>
<p><?php echo get_lang('XMLMustLookLike').' ('.get_lang('MandatoryFields').')'; ?> :</p>
<blockquote>
<pre>
&lt;?xml version=&quot;1.0&quot; encoding=&quot;UTF-8&quot;?&gt;
&lt;Contacts&gt;
    &lt;Contact&gt;
        <b>&lt;LastName&gt;xxx&lt;/LastName&gt;</b>
        <b>&lt;FirstName&gt;xxx&lt;/FirstName&gt;</b>
        &lt;UserName&gt;xxx&lt;/UserName&gt;
        &lt;Password&gt;xxx&lt;/Password&gt;
        &lt;AuthSource&gt;<?php echo implode('/', $defined_auth_sources); ?>&lt;/AuthSource&gt;
        <b>&lt;Email&gt;xxx&lt;/Email&gt;</b>
        &lt;OfficialCode&gt;xxx&lt;/OfficialCode&gt;
        &lt;PhoneNumber&gt;xxx&lt;/PhoneNumber&gt;
        &lt;Status&gt;user/teacher/drh<?php if ($result_xml != '') { echo '<br /><span style="color:red;">', $result_xml; echo '</span>'; } ?>&lt;/Status&gt;
        &lt;Courses&gt;xxx1|xxx2|xxx3&lt;/Courses&gt;
        &lt;ClassId&gt;1&lt;/ClassId&gt;
        &lt;/Contact&gt;
&lt;/Contacts&gt;
</pre>
    </blockquote>
<?php
Display :: display_footer();
