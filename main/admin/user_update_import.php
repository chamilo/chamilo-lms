<?php
/* For licensing terms, see /license.txt */

/**
 * This tool allows platform admins to add users by uploading a CSV or XML file.
 *
 * @package chamilo.admin
 */

/**
 * Validate the imported data.
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

// Set this option to true to enforce strict purification for usenames.
$purification_option_for_usernames = false;

function validate_data($users)
{
    global $defined_auth_sources;
    $errors = [];
    $usernames = [];

    // 1. Check if mandatory fields are set.
    $mandatory_fields = ['LastName', 'FirstName'];

    if (api_get_setting('registration', 'email') == 'true') {
        $mandatory_fields[] = 'Email';
    }
    $classExistList = [];
    $usergroup = new UserGroup();

    foreach ($users as $user) {
        foreach ($mandatory_fields as $field) {
            if (isset($user[$field])) {
                if (empty($user[$field])) {
                    $user['error'] = get_lang($field.'Mandatory');
                    $errors[] = $user;
                }
            }
        }

        // 2. Check username, first, check whether it is empty.

        if (isset($user['NewUserName'])) {
            if (!UserManager::is_username_empty($user['NewUserName'])) {
                // 2.1. Check whether username is too long.
                if (UserManager::is_username_too_long($user['NewUserName'])) {
                    $user['error'] = get_lang('UserNameTooLong');
                    $errors[] = $user;
                }
                // 2.2. Check whether the username was used twice in import file.
                if (isset($usernames[$user['NewUserName']])) {
                    $user['error'] = get_lang('UserNameUsedTwice');
                    $errors[] = $user;
                }
                $usernames[$user['UserName']] = 1;
                // 2.3. Check whether username is allready occupied.
                if (!UserManager::is_username_available($user['NewUserName']) && $user['NewUserName'] != $user['UserName']) {
                    $user['error'] = get_lang('UserNameNotAvailable');
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
        $user['UserName'] = UserManager::create_unique_username($user['FirstName'], $user['LastName']);
    } else {
        $user['UserName'] = UserManager::purify_username($user['UserName'], $purification_option_for_usernames);
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

    return $user;
}

/**
 * Update users from the imported data.
 *
 * @param array $users List of users
 *
 * @return false|null
 *
 * @uses \global variable $inserted_in_course, which returns the list of courses the user was inserted in
 */
function updateUsers($users)
{
    global $insertedIn_course;
    // Not all scripts declare the $inserted_in_course array (although they should).
    if (!isset($inserted_in_course)) {
        $inserted_in_course = [];
    }
    $usergroup = new UserGroup();
    $send_mail = $_POST['sendMail'] ? true : false;
    if (is_array($users)) {
        foreach ($users as $user) {
            $user = complete_missing_data($user);
            $user['Status'] = api_status_key($user['Status']);
            $userName = $user['UserName'];
            $userInfo = api_get_user_info_from_username($userName);
            $user_id = $userInfo['user_id'];
            if ($user_id == 0) {
                return false;
            }
            $firstName = isset($user['FirstName']) ? $user['FirstName'] : $userInfo['firstname'];
            $lastName = isset($user['LastName']) ? $user['LastName'] : $userInfo['lastname'];
            $userName = isset($user['NewUserName']) ? $user['NewUserName'] : $userInfo['username'];
            $password = isset($user['Password']) ? $user['Password'] : $userInfo['password'];
            $authSource = isset($user['AuthSource']) ? $user['AuthSource'] : $userInfo['auth_source'];
            $email = isset($user['Email']) ? $user['Email'] : $userInfo['email'];
            $status = isset($user['Status']) ? $user['Status'] : $userInfo['status'];
            $officialCode = isset($user['OfficialCode']) ? $user['OfficialCode'] : $userInfo['official_code'];
            $phone = isset($user['PhoneNumber']) ? $user['PhoneNumber'] : $userInfo['phone'];
            $pictureUrl = isset($user['PictureUri']) ? $user['PictureUri'] : $userInfo['picture_uri'];
            $expirationDate = isset($user['ExpiryDate']) ? $user['ExpiryDate'] : $userInfo['expiration_date'];
            $active = isset($user['Active']) ? $user['Active'] : $userInfo['active'];
            $creatorId = $userInfo['creator_id'];
            $hrDeptId = $userInfo['hr_dept_id'];
            $language = isset($user['Language']) ? $user['Language'] : $userInfo['language'];
            $sendEmail = isset($user['SendEmail']) ? $user['SendEmail'] : $userInfo['language'];
            $userUpdated = UserManager :: update_user(
                $user_id,
                $firstName,
                $lastName,
                $userName,
                $password,
                $authSource,
                $email,
                $status,
                $officialCode,
                $phone,
                $pictureUrl,
                $expirationDate,
                $active,
                $creatorId,
                $hrDeptId,
                null,
                $language,
                '',
                '',
                ''
            );
            if (!is_array($user['Courses']) && !empty($user['Courses'])) {
                $user['Courses'] = [$user['Courses']];
            }
            if (is_array($user['Courses'])) {
                foreach ($user['Courses'] as $course) {
                    if (CourseManager::course_exists($course)) {
                        CourseManager::subscribeUser($user_id, $course, $user['Status']);
                        $course_info = CourseManager::get_course_information($course);
                        $inserted_in_course[$course] = $course_info['title'];
                    }
                }
            }
            if (!empty($user['ClassId'])) {
                $classId = explode('|', trim($user['ClassId']));
                foreach ($classId as $id) {
                    $usergroup->subscribe_users_to_usergroup(
                        $id,
                        [$user_id],
                        false
                    );
                }
            }

            // Saving extra fields.
            global $extra_fields;

            // We are sure that the extra field exists.
            foreach ($extra_fields as $extras) {
                if (isset($user[$extras[1]])) {
                    $key = $extras[1];
                    $value = $user[$extras[1]];
                    UserManager::update_extra_field_value(
                        $user_id,
                        $key,
                        $value
                    );
                }
            }
        }
    }
}

/**
 * Read the CSV-file.
 *
 * @param string $file Path to the CSV-file
 *
 * @return array All userinformation read from the file
 */
function parse_csv_data($file)
{
    $users = Import :: csvToArray($file);
    foreach ($users as $index => $user) {
        if (isset($user['Courses'])) {
            $user['Courses'] = explode('|', trim($user['Courses']));
        }
        $users[$index] = $user;
    }

    return $users;
}

function parse_xml_data($file)
{
    $crawler = new \Symfony\Component\DomCrawler\Crawler();
    $crawler->addXmlContent(file_get_contents($file));
    $crawler = $crawler->filter('Contacts > Contact ');
    $array = [];
    foreach ($crawler as $domElement) {
        $row = [];
        foreach ($domElement->childNodes as $node) {
            if ($node->nodeName != '#text') {
                $row[$node->nodeName] = $node->nodeValue;
            }
        }
        if (!empty($row)) {
            $array[] = $row;
        }
    }

    return $array;
}

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script(true, null);

$defined_auth_sources[] = PLATFORM_AUTH_SOURCE;

if (isset($extAuthSource) && is_array($extAuthSource)) {
    $defined_auth_sources = array_merge($defined_auth_sources, array_keys($extAuthSource));
}

$tool_name = get_lang('ImportUserListXMLCSV');
$interbreadcrumb[] = ["url" => 'index.php', "name" => get_lang('PlatformAdmin')];

set_time_limit(0);
$extra_fields = UserManager::get_extra_fields(0, 0, 5, 'ASC', true);
$user_id_error = [];
$error_message = '';

if (isset($_POST['formSent']) && $_POST['formSent'] && $_FILES['import_file']['size'] !== 0) {
    $file_type = 'csv';
    Security::clear_token();
    $tok = Security::get_token();
    $allowed_file_mimetype = ['csv', 'xml'];
    $error_kind_file = false;

    $uploadInfo = pathinfo($_FILES['import_file']['name']);
    $ext_import_file = $uploadInfo['extension'];

    if (in_array($ext_import_file, $allowed_file_mimetype)) {
        if (strcmp($file_type, 'csv') === 0 && $ext_import_file == $allowed_file_mimetype[0]) {
            $users = parse_csv_data($_FILES['import_file']['tmp_name']);
            $errors = validate_data($users);
            $error_kind_file = false;
        } elseif (strcmp($file_type, 'xml') === 0 && $ext_import_file == $allowed_file_mimetype[1]) {
            $users = parse_xml_data($_FILES['import_file']['tmp_name']);
            $errors = validate_data($users);
            $error_kind_file = false;
        } else {
            $error_kind_file = true;
        }
    } else {
        $error_kind_file = true;
    }

    // List user id with error.
    $users_to_insert = $user_id_error = [];

    if (is_array($errors)) {
        foreach ($errors as $my_errors) {
            $user_id_error[] = $my_errors['UserName'];
        }
    }

    if (is_array($users)) {
        foreach ($users as $my_user) {
            if (!in_array($my_user['UserName'], $user_id_error)) {
                $users_to_insert[] = $my_user;
            }
        }
    }

    $inserted_in_course = [];
    if (strcmp($file_type, 'csv') === 0) {
        updateUsers($users_to_insert);
    }

    if (count($errors) > 0) {
        $see_message_import = get_lang('FileImportedJustUsersThatAreNotRegistered');
    } else {
        $see_message_import = get_lang('FileImported');
    }

    if (count($errors) != 0) {
        $warning_message = '<ul>';
        foreach ($errors as $index => $error_user) {
            $warning_message .= '<li><b>'.$error_user['error'].'</b>: ';
            $warning_message .=
                '<strong>'.$error_user['UserName'].'</strong>&nbsp;('.
                api_get_person_name($error_user['FirstName'], $error_user['LastName']).')';
            $warning_message .= '</li>';
        }
        $warning_message .= '</ul>';
    }

    // if the warning message is too long then we display the warning message trough a session
    Display::addFlash(Display::return_message($warning_message, 'warning', false));

    if ($error_kind_file) {
        Display::addFlash(Display::return_message(get_lang('YouMustImportAFileAccordingToSelectedOption'), 'error', false));
    } else {
        header('Location: '.api_get_path(WEB_CODE_PATH).'admin/user_list.php?sec_token='.$tok);
        exit;
    }
}
Display::display_header($tool_name);

if (!empty($error_message)) {
    echo Display::return_message($error_message, 'error');
}

$form = new FormValidator('user_update_import', 'post', api_get_self());
$form->addElement('header', $tool_name);
$form->addElement('hidden', 'formSent');
$form->addElement('file', 'import_file', get_lang('ImportFileLocation'));

$group = [];

$form->addButtonImport(get_lang('Import'));
$defaults['formSent'] = 1;
$defaults['sendMail'] = 0;
$defaults['file_type'] = 'csv';
$form->setDefaults($defaults);
$form->display();

$list = [];
$list_reponse = [];
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
        <b>UserName</b>;LastName;FirstName;Email;NewUserName;Password;AuthSource;OfficialCode;PhoneNumber;Status;ExpiryDate;Active;Language;Courses;ClassId;
        xxx;xxx;xxx;xxx;xxx;xxx;xxx;xxx;xxx;user/teacher/drh;YYYY-MM-DD 00:00:00;0/1;xxx;<span style="color:red;"><?php if (count($list_reponse) > 0) {
    echo implode(';', $list_reponse).';';
} ?></span>xxx1|xxx2|xxx3;1;<br />
    </pre>
</blockquote>
<p><?php

Display :: display_footer();
