<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraFieldOptions;
use ChamiloSession as Session;
use Ddeboer\DataImport\Writer\CsvWriter;

/**
 * This tool allows platform admins to add users by uploading a CSV or XML file.
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

// Set this option to true to enforce strict purification for usenames.
$purification_option_for_usernames = false;
$userId = api_get_user_id();
api_protect_admin_script(true, null);
api_protect_limit_for_session_admin();
set_time_limit(0);

/**
 * Create directories and subdirectories for backup import csv data.
 *
 * @param null $path
 *
 * @return string|null
 */
function createDirectory($path = null)
{
    if ($path == null) {
        return $path;
    }
    $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    $realPath = explode(DIRECTORY_SEPARATOR, $path);
    $data = '';
    foreach ($realPath as $pth) {
        if (strlen($pth) > 0) {
            $data .= DIRECTORY_SEPARATOR.$pth;
            if (!is_dir($data)) {
                mkdir($data, api_get_permissions_for_new_directories());
                $block =
                    '<FilesMatch "\.(csv|xml)$">
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
    Order allow,deny
    Deny from all
</IfModule>
</FilesMatch>
Options -Indexes';

                $fp = fopen($data.'/.htaccess', 'w');
                if ($fp) {
                    fwrite($fp, $block);
                }
                fclose($fp);
            }
        }
    }

    return $data;
}

/**
 * @param array $users
 * @param bool  $checkUniqueEmail
 *
 * @return array
 */
function validate_data($users, $checkUniqueEmail = false)
{
    global $defined_auth_sources;
    $usernames = [];
    $previousMails = [];

    // 1. Check if mandatory fields are set.
    $mandatory_fields = ['LastName', 'FirstName'];
    if (api_get_setting('registration', 'email') == 'true' || $checkUniqueEmail) {
        $mandatory_fields[] = 'Email';
    }

    $classExistList = [];
    $usergroup = new UserGroup();
    foreach ($users as &$user) {
        $user['has_error'] = false;
        $user['message'] = '';

        foreach ($mandatory_fields as $field) {
            if (empty($user[$field])) {
                $user['message'] .= Display::return_message(get_lang($field.'Mandatory'), 'warning');
                $user['has_error'] = true;
            }
        }

        $username = isset($user['UserName']) ? $user['UserName'] : '';
        // 2. Check username, first, check whether it is empty.
        if (!UserManager::is_username_empty($username)) {
            // 2.1. Check whether username is too long.
            if (UserManager::is_username_too_long($username)) {
                $user['message'] .= Display::return_message(get_lang('UserNameTooLong'), 'warning');
                $user['has_error'] = true;
            }
            // 2.1.1
            /*$hasDash = strpos($username, '-');
            if ($hasDash !== false) {
                $user['message'] .= Display::return_message(get_lang('UserNameHasDash'), 'warning');
                $user['has_error'] = true;
            }*/
            // 2.2. Check whether the username was used twice in import file.
            if (isset($usernames[$username])) {
                $user['message'] .= Display::return_message(get_lang('UserNameUsedTwice'), 'warning');
                $user['has_error'] = true;
            }
            $usernames[$username] = 1;
            // 2.3. Check whether username is already occupied.
            if (!UserManager::is_username_available($username)) {
                $user['message'] .= Display::return_message(get_lang('UserNameNotAvailable'), 'warning');
                $user['has_error'] = true;
            }

            if ('true' === api_get_setting('login_is_email')) {
                if (false === api_valid_email($username)) {
                    $user['message'] .= Display::return_message(get_lang('PleaseEnterValidEmail'), 'warning');
                    $user['has_error'] = true;
                }
            } else {
                if (!UserManager::is_username_valid($username)) {
                    $user['message'] .= Display::return_message(get_lang('UsernameWrong'), 'warning');
                    $user['has_error'] = true;
                }
            }
        }

        // When e-mail is not a required field, e-mail is left empty
        if (api_get_setting('registration', 'email') === 'false' && empty($user['Email'])) {
            $user['Email'] = '';
        }
        if (!empty($user['Email'])) {
            $result = api_valid_email($user['Email']);
            if ($result === false) {
                $user['message'] .= Display::return_message(get_lang('PleaseEnterValidEmail'), 'warning');
                $user['has_error'] = true;
            }
        }

        if ($checkUniqueEmail) {
            if (!empty($user['Email'])) {
                if (in_array($user['Email'], $previousMails)) {
                    $user['message'] .= Display::return_message(get_lang('EmailUsedTwiceInImportFile'), 'warning');
                    $user['has_error'] = true;
                } else {
                    $userFromEmail = api_get_user_info_from_email($user['Email']);
                    if (!empty($userFromEmail)) {
                        $user['message'] .= Display::return_message(get_lang('EmailUsedTwice'), 'warning');
                        $user['has_error'] = true;
                    } else {
                        $previousMails[] = $user['Email'];
                    }
                }
            }
        }

        // 3. Check status.
        if (isset($user['Status']) && !api_status_exists($user['Status'])) {
            $user['message'] .= Display::return_message(get_lang('WrongStatus'), 'warning');
            $user['has_error'] = true;
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
                    $user['message'] .= Display::return_message(
                        sprintf(get_lang('ClassIdDoesntExists'), $id),
                        'warning'
                    );
                    $user['has_error'] = true;
                } else {
                    $classExistList[] = $info['id'];
                }
            }
        }

        // 5. Check authentication source
        if (!empty($user['AuthSource'])) {
            if (!in_array($user['AuthSource'], $defined_auth_sources)) {
                $user['message'] .= Display::return_message(get_lang('AuthSourceNotAvailable'), 'warning');
                $user['has_error'] = true;
            }
        }
    }

    return $users;
}

/**
 * Add missing user-information (which isn't required, like password, username etc).
 *
 * @param array $user
 */
function complete_missing_data($user)
{
    global $purification_option_for_usernames;

    $username = isset($user['UserName']) ? $user['UserName'] : '';

    // 1. Create a username if necessary.
    if (UserManager::is_username_empty($username)) {
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
 * Save the imported data.
 *
 * @param array $users    List of users
 * @param bool  $sendMail
 *
 * @uses \global variable $inserted_in_course, which returns the list of
 * courses the user was inserted in
 */
function save_data(
    $users,
    $sendMail = false,
    $targetFolder = null
) {
    global $inserted_in_course, $extra_fields;

    // Not all scripts declare the $inserted_in_course array (although they should).
    if (!isset($inserted_in_course)) {
        $inserted_in_course = [];
    }

    if (!empty($targetFolder)) {
        $userSaved = [];
        $userError = [];
        $userWarning = [];
    }

    $usergroup = new UserGroup();
    if (is_array($users)) {
        $efo = new ExtraFieldOption('user');

        $optionsByField = [];

        foreach ($users as &$user) {
            if ($user['has_error']) {
                $userError[] = $user;
                continue;
            }

            $user = complete_missing_data($user);
            $user['Status'] = api_status_key($user['Status']);
            $redirection = isset($user['Redirection']) ? $user['Redirection'] : '';

            $user_id = UserManager::create_user(
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
                $sendMail,
                false,
                '',
                false,
                null,
                null,
                null,
                $redirection
            );

            if ($user_id) {
                $returnMessage = Display::return_message(get_lang('UserAdded'), 'success');

                if (isset($user['Courses']) && is_array($user['Courses'])) {
                    foreach ($user['Courses'] as $course) {
                        if (CourseManager::course_exists($course)) {
                            $result = CourseManager::subscribeUser($user_id, $course, $user['Status']);
                            if ($result) {
                                $course_info = api_get_course_info($course);
                                $inserted_in_course[$course] = $course_info['title'];
                            }
                        }
                    }
                }

                if (isset($user['Sessions']) && is_array($user['Sessions'])) {
                    foreach ($user['Sessions'] as $sessionId) {
                        $sessionInfo = api_get_session_info($sessionId);
                        if (!empty($sessionInfo)) {
                            SessionManager::subscribeUsersToSession(
                                $sessionId,
                                [$user_id],
                                SESSION_VISIBLE_READ_ONLY,
                                false
                            );
                        }
                    }
                }

                if (!empty($user['ClassId'])) {
                    $classId = explode('|', trim($user['ClassId']));
                    foreach ($classId as $id) {
                        $usergroup->subscribe_users_to_usergroup($id, [$user_id], false);
                    }
                }

                // We are sure that the extra field exists.
                foreach ($extra_fields as $extras) {
                    if (!isset($user[$extras[1]])) {
                        continue;
                    }

                    $key = $extras[1];
                    $value = $user[$key];

                    if (!array_key_exists($key, $optionsByField)) {
                        $optionsByField[$key] = $efo->getOptionsByFieldVariable($key);
                    }

                    /** @var ExtraFieldOptions $option */
                    foreach ($optionsByField[$key] as $option) {
                        if ($option->getDisplayText() === $value) {
                            $value = $option->getValue();
                        }
                    }

                    UserManager::update_extra_field_value($user_id, $key, $value);
                }
                $userSaved[] = $user;
            } else {
                $returnMessage = Display::return_message(get_lang('Error'), 'warning');
                $userWarning[] = $user;
            }
            $user['message'] = $returnMessage;
        }
    }

    // Save with success, error and warning users
    if (!empty($targetFolder)) {
        $header = [
            'id',
            'FirstName',
            'LastName',
            'Status',
            'Email',
            'UserName',
            'message',
        ];
        // Save user with success
        if (count($userSaved) != 0) {
            $csv_content = [];
            $csv_row = $header;
            $csv_content[] = $csv_row;
            foreach ($userSaved as $userItem) {
                $csv_row = [];
                $csv_row[] = isset($userItem['id']) ? $userItem['id'] : '';
                $csv_row[] = isset($userItem['FirstName']) ? $userItem['FirstName'] : '';
                $csv_row[] = isset($userItem['LastName']) ? $userItem['LastName'] : '';
                $csv_row[] = isset($userItem['Status']) ? $userItem['Status'] : '';
                $csv_row[] = isset($userItem['Email']) ? $userItem['Email'] : '';
                $csv_row[] = isset($userItem['UserName']) ? $userItem['UserName'] : '';
                $csv_row[] = isset($userItem['message']) ? strip_tags($userItem['message']) : '';
                $csv_content[] = $csv_row;
            }
            saveCsvFile($csv_content, $targetFolder.'user_success_'.count($userSaved));
        }
        unset($userItem);
        if (count($userError) != 0) {
            // Save user with error
            $csv_content = [];
            $csv_row = $header;
            $csv_content[] = $csv_row;
            foreach ($userError as $userItem) {
                $csv_row = [];
                $csv_row[] = isset($userItem['id']) ? $userItem['id'] : '';
                $csv_row[] = isset($userItem['FirstName']) ? $userItem['FirstName'] : '';
                $csv_row[] = isset($userItem['LastName']) ? $userItem['LastName'] : '';
                $csv_row[] = isset($userItem['Status']) ? $userItem['Status'] : '-';
                $csv_row[] = isset($userItem['Email']) ? $userItem['Email'] : '';
                $csv_row[] = isset($userItem['UserName']) ? $userItem['UserName'] : '';
                $csv_row[] = isset($userItem['message']) ? strip_tags($userItem['message']) : '';
                $csv_content[] = $csv_row;
            }
            saveCsvFile($csv_content, $targetFolder.'user_error_'.count($userError));
        }
        unset($userItem);
        if (count($userWarning) != 0) {
            // Save user with warning
            $csv_content = [];
            $csv_row = $header;
            $csv_content[] = $csv_row;
            foreach ($userWarning as $userItem) {
                $csv_row = [];
                $csv_row[] = isset($userItem['id']) ? $userItem['id'] : '';
                $csv_row[] = isset($userItem['FirstName']) ? $userItem['FirstName'] : '';
                $csv_row[] = isset($userItem['LastName']) ? $userItem['LastName'] : '';
                $csv_row[] = isset($userItem['Status']) ? $userItem['Status'] : '';
                $csv_row[] = isset($userItem['Email']) ? $userItem['Email'] : '';
                $csv_row[] = isset($userItem['UserName']) ? $userItem['UserName'] : '';
                $csv_row[] = isset($userItem['message']) ? strip_tags($userItem['message']) : '';
                $csv_content[] = $csv_row;
            }
            saveCsvFile($csv_content, $targetFolder.'user_warning_'.count($userWarning));
        }
        unset($userItem);
    }

    return $users;
}

/**
 * Save array to a specific file.
 *
 * @param array $data
 * @param $file
 * @param string $enclosure
 */
function saveCsvFile($data = [], $file = 'example', $enclosure = '"')
{
    $filePath = $file.'.csv';
    $stream = fopen($filePath, 'w');
    $writer = new CsvWriter(';', $enclosure, $stream, true);
    $writer->prepare();

    foreach ($data as $item) {
        if (empty($item)) {
            $writer->writeItem([]);
            continue;
        }
        $item = array_map('trim', $item);
        $writer->writeItem($item);
    }
    $writer->finish();

    return null;
}

/**
 * @param array  $users
 * @param string $fileName
 * @param int    $sendEmail
 * @param bool   $checkUniqueEmail
 * @param bool   $resumeImport
 *
 * @return array
 */
function parse_csv_data($users, $fileName, $sendEmail = 0, $checkUniqueEmail = true, $resumeImport = false)
{
    $usersFromOrigin = $users;
    $allowRandom = api_get_configuration_value('generate_random_login');
    if ($allowRandom) {
        $factory = new RandomLib\Factory();
        $generator = $factory->getLowStrengthGenerator();
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    }

    $readMax = 50;
    $userId = api_get_user_id();
    $logMessages = '';
    $importData = Session::read('user_import_data_'.$userId);
    if (!empty($importData)) {
        $counter = $importData['counter'];
        $users = $importData['complete_list'];
        $users = array_splice($users, $counter, $readMax);
        $logMessages = $importData['log_messages'];
    } else {
        $users = array_splice($users, 0, $readMax);
    }

    if ($resumeImport === false) {
        $users = $usersFromOrigin;
    }

    $counter = 0;
    foreach ($users as $index => $user) {
        if ($resumeImport) {
            if ($counter >= $readMax) {
                $users = array_splice($users, $counter, $readMax);
                break;
            }
        }
        $counter++;
        if (empty($user['UserName'])) {
            if ($allowRandom) {
                $username = $generator->generateString(10, $chars);
                $user['UserName'] = $username;
            }
        }
        if (isset($user['Courses'])) {
            $user['Courses'] = explode('|', trim($user['Courses']));
        }

        if (isset($user['Sessions'])) {
            $user['Sessions'] = explode('|', trim($user['Sessions']));
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

    $globalCounter = $counter;
    if (!empty($importData)) {
        $globalCounter = $importData['counter'] + $counter;
    }

    $importData = [
        'complete_list' => $usersFromOrigin,
        'filename' => $fileName,
        'counter' => $globalCounter,
        'check_unique_email' => $checkUniqueEmail,
        'send_email' => $sendEmail,
        'date' => api_get_utc_datetime(),
        'log_messages' => $logMessages,
        'resume' => $resumeImport,
    ];

    Session::write('user_import_data_'.$userId, $importData);

    return $users;
}

/**
 * Read the XML-file.
 *
 * @param string $file Path to the XML-file
 *
 * @return array All user information read from the file
 */
function parse_xml_data($file, $sendEmail = 0, $checkUniqueEmail = true)
{
    $crawler = Import::xml($file);
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

    Session::write(
        'user_import_data_'.api_get_user_id(),
        [
            'check_unique_email' => $checkUniqueEmail,
            'send_email' => $sendEmail,
            'date' => api_get_utc_datetime(),
            'log_messages' => '',
        ]
    );

    return $array;
}

/**
 * @param array  $users
 * @param bool   $sendMail
 * @param string $targetFolder
 */
function processUsers(&$users, $sendMail, $targetFolder = null)
{
    $users = save_data($users, $sendMail, $targetFolder);

    $warningMessage = '';
    if (!empty($users)) {
        $table = new HTML_Table(['class' => 'table table-responsive']);
        $headers = [
            get_lang('User'),
            get_lang('Status'),
        ];
        $row = 0;
        $column = 0;
        foreach ($headers as $header) {
            $table->setHeaderContents($row, $column, $header);
            $column++;
        }
        $row++;
        foreach ($users as $user) {
            $column = 0;
            $email = isset($user['Email']) ? ' - '.$user['Email'] : null;
            $userData =
                '<strong>'.$user['UserName'].'</strong> - '.
                api_get_person_name(
                    $user['FirstName'],
                    $user['LastName']
                ).' '.$email;
            $table->setCellContents($row, $column, $userData);
            $table->setCellContents($row, ++$column, $user['message']);
            $row++;
        }
        $warningMessage = $table->toHtml();
    }

    // if the warning message is too long then we display the warning message trough a session
    //Display::addFlash(Display::return_message(get_lang('FileImported'), 'confirmation', false));

    $importData = Session::read('user_import_data_'.api_get_user_id());
    if (!empty($importData)) {
        if (isset($importData['log_messages'])) {
            $importData['log_messages'] .= $warningMessage;
        } else {
            $importData['log_messages'] = $warningMessage;
        }
        Session::write('user_import_data_'.api_get_user_id(), $importData);
    }
}

$this_section = SECTION_PLATFORM_ADMIN;
$defined_auth_sources[] = PLATFORM_AUTH_SOURCE;
if (isset($extAuthSource) && is_array($extAuthSource)) {
    $defined_auth_sources = array_merge($defined_auth_sources, array_keys($extAuthSource));
}

$tool_name = get_lang('ImportUserListXMLCSV');
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];
$reloadImport = (isset($_REQUEST['reload_import']) && (int) $_REQUEST['reload_import'] === 1);

$extra_fields = UserManager::get_extra_fields(0, 0, 5, 'ASC', true);

if (isset($_POST['formSent']) && $_POST['formSent'] && $_FILES['import_file']['size'] !== 0) {
    $file_type = $_POST['file_type'];
    Security::clear_token();
    $tok = Security::get_token();
    $allowed_file_mimetype = ['csv', 'xml'];
    $error_kind_file = true;

    $checkUniqueEmail = isset($_POST['check_unique_email']) ? $_POST['check_unique_email'] : null;
    $sendMail = $_POST['sendMail'] ? true : false;
    $resume = isset($_POST['resume_import']) ? true : false;
    $askNewPassword = isset($_POST['ask_new_password']) ? true : false;
    $uploadInfo = pathinfo($_FILES['import_file']['name']);
    $ext_import_file = $uploadInfo['extension'];
    $targetFolder = null;
    $users = [];
    if (in_array($ext_import_file, $allowed_file_mimetype)) {
        if (strcmp($file_type, 'csv') === 0 &&
            $ext_import_file == $allowed_file_mimetype[0]
        ) {
            $user = api_get_user_info();
            $userId = (int) $user['id'];
            $today = new DateTime();
            $today = $today->format('YmdHis');
            $targetFolder = api_get_configuration_value('root_sys').'app/cache/backup/import_users';
            $targetFolder .= DIRECTORY_SEPARATOR.$userId.DIRECTORY_SEPARATOR.$today;
            $targetFolder = createDirectory($targetFolder).DIRECTORY_SEPARATOR;
            $originalFile = $targetFolder.$_FILES['import_file']['name'];
            // save original file
            if (!file_exists($originalFile)) {
                touch($originalFile);
                file_put_contents($originalFile, file_get_contents($_FILES['import_file']['tmp_name']));
            }

            Session::erase('user_import_data_'.$userId);
            $users = Import::csvToArray($_FILES['import_file']['tmp_name']);
            $users = parse_csv_data(
                $users,
                $_FILES['import_file']['name'],
                $sendMail,
                $checkUniqueEmail,
                $resume
            );
            $users = validate_data($users, $checkUniqueEmail);
            $error_kind_file = false;
        } elseif (strcmp($file_type, 'xml') === 0 && $ext_import_file == $allowed_file_mimetype[1]) {
            $users = parse_xml_data(
                $_FILES['import_file']['tmp_name'],
                $sendMail,
                $checkUniqueEmail
            );
            $users = validate_data($users, $checkUniqueEmail);
            $error_kind_file = false;
        }
        if ($askNewPassword) {
            foreach ($users as $userId => $user) {
                $users[$userId]['ask_new_password'] = 1;
            }
        }

        // processUsers() triggers save_data() which uses the $extra_fields
        // variable defined above as a global list of fields to treat
        processUsers($users, $sendMail, $targetFolder);

        if ($error_kind_file) {
            Display::addFlash(
                Display::return_message(
                    get_lang('YouMustImportAFileAccordingToSelectedOption'),
                    'error',
                    false
                )
            );
        } else {
            $reload = '';
            if ($resume) {
                $reload = '?reload_import=1';
            }
            header('Location: '.api_get_self().$reload);
            exit;
        }
    } else {
        Display::addFlash(
            Display::return_message(
                get_lang('YouMustImportAFileAccordingToSelectedOption'),
                'error',
                false
            )
        );

        header('Location: '.api_get_self());
        exit;
    }
}

$importData = Session::read('user_import_data_'.$userId);

$formContinue = false;
$resumeStop = true;
if (!empty($importData)) {
    $isResume = $importData['resume'] ?? false;

    $formContinue = new FormValidator('user_import_continue', 'post', api_get_self());
    $label = get_lang('Results');
    if ($isResume) {
        $label = get_lang('ContinueLastImport');
    }
    $formContinue->addHeader($label);
    if (isset($importData['filename'])) {
        $formContinue->addLabel(get_lang('File'), $importData['filename'] ?? '');
    }

    $resumeStop = true;
    if ($isResume) {
        $totalUsers = isset($importData['complete_list']) ? count($importData['complete_list']) : 0;
        $counter = isset($importData['counter']) ? $importData['counter'] : 0;
        $bar = '';
        if (!empty($totalUsers)) {
            $bar = Display::bar_progress($counter / $totalUsers * 100);
        }
        $formContinue->addLabel(get_lang('Status'), $bar);
        $formContinue->addLabel(
            get_lang('UsersAdded'),
            $importData['counter'].' / '.count($importData['complete_list'])
        );
    } else {
        if (!empty($importData['complete_list'])) {
            $formContinue->addLabel(
                get_lang('Users'),
                count($importData['complete_list'])
            );
        }
    }

    $formContinue->addLabel(
        get_lang('CheckUniqueEmail'),
        $importData['check_unique_email'] ? get_lang('Yes') : get_lang('No')
    );
    $formContinue->addLabel(get_lang('SendMailToUsers'), $importData['send_email'] ? get_lang('Yes') : get_lang('No'));
    $formContinue->addLabel(get_lang('Date'), Display::dateToStringAgoAndLongDate($importData['date']));

    if ($isResume) {
        $resumeStop = $importData['counter'] >= count($importData['complete_list']);
        if ($resumeStop == false) {
            $formContinue->addButtonImport(get_lang('ContinueImport'), 'import_continue');
        }
    }

    $formContinue->addHtml('<br />'.$importData['log_messages']);

    if ($formContinue->validate()) {
        $users = parse_csv_data(
            $importData['complete_list'],
            $importData['filename'],
            $importData['send_email'],
            $importData['check_unique_email'],
            true
        );
        $users = validate_data($users, $importData['check_unique_email']);

        processUsers($users, $importData['send_email']);

        $reload = '';
        if ($isResume && $resumeStop === false) {
            $reload = '?reload_import=1';
        }

        header('Location: '.api_get_self().$reload);
        exit;
    }
}

Display::display_header($tool_name);

$form = new FormValidator('user_import', 'post', api_get_self());
$form->addHeader($tool_name);
$form->addElement('hidden', 'formSent');
$form->addElement('file', 'import_file', get_lang('ImportFileLocation'));
$group = [
    $form->createElement(
        'radio',
        'file_type',
        '',
        'CSV (<a href="example.csv" target="_blank" download>'.get_lang('ExampleCSVFile').'</a>)',
        'csv'
    ),
    $form->createElement(
        'radio',
        'file_type',
        null,
        'XML (<a href="example.xml" target="_blank" download>'.get_lang('ExampleXMLFile').'</a>)',
        'xml'
    ),
];

$form->addGroup($group, '', get_lang('FileType'));

$group = [
    $form->createElement('radio', 'sendMail', '', get_lang('Yes'), 1),
    $form->createElement('radio', 'sendMail', null, get_lang('No'), 0),
];
$form->addGroup($group, '', get_lang('SendMailToUsers'));

$form->addElement(
    'checkbox',
    'check_unique_email',
    '',
    get_lang('CheckUniqueEmail')
);

$form->addElement(
    'checkbox',
    'resume_import',
    '',
    get_lang('ResumeImport')
);

if (api_get_configuration_value('force_renew_password_at_first_login') == true) {
    $form->addElement(
        'checkbox',
        'ask_new_password',
        '',
        get_lang('FirstLoginForceUsersToChangePassword')
    );
}

$form->addButtonImport(get_lang('Import'));

$defaults['formSent'] = 1;
$defaults['sendMail'] = 0;
$defaults['file_type'] = 'csv';

$extraSettings = api_get_configuration_value('user_import_settings');
if (!empty($extraSettings) && isset($extraSettings['options']) &&
    isset($extraSettings['options']['send_mail_default_option'])
) {
    $defaults['sendMail'] = $extraSettings['options']['send_mail_default_option'];
}

$form->setDefaults($defaults);
if (is_dir(api_get_path(SYS_ARCHIVE_PATH).'backup/import_users')) {
    // only show if backup exist
    $form->addHtml("<a href='./download_import_users.php'>".get_lang('ViewHistoryChange')."</a>");
}
$form->display();

if ($formContinue) {
    $formContinue->display();
}

if ($reloadImport) {
    echo '<script>
        $(function() {
            function reload() {
                $("#user_import_continue").submit();
            }
            setTimeout(reload, 3000);
        });
    </script>';
}

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

if (api_get_configuration_value('plugin_redirection_enabled')) {
    $list[] = 'Redirection';
    $list_reponse[] = api_get_path(WEB_PATH);
}

?>
<p><?php echo get_lang('CSVMustLookLike').' ('.get_lang('MandatoryFields').')'; ?> :</p>
<blockquote>
<pre>
<b>LastName</b>;<b>FirstName</b>;<b>Email</b>;UserName;Password;AuthSource;OfficialCode;language;PhoneNumber;Status;ExpiryDate;<span style="color:red;"><?php if (count($list) > 0) {
    echo implode(';', $list).';';
} ?></span>Courses;Sessions;ClassId;
<b>xxx</b>;<b>xxx</b>;<b>xxx</b>;xxx;xxx;<?php echo implode('/', $defined_auth_sources); ?>;xxx;english/spanish/(other);xxx;user/teacher/drh;0000-00-00 00:00:00;<span style="color:red;"><?php if (count($list_reponse) > 0) {
    echo implode(';', $list_reponse).';';
} ?></span>xxx1|xxx2|xxx3;sessionId|sessionId|sessionId;1;<br />
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
        &lt;language&gt;english/spanish/(other)&lt;/language&gt;
        &lt;PhoneNumber&gt;xxx&lt;/PhoneNumber&gt;
        &lt;Status&gt;user/teacher/drh&lt;/Status&gt;<?php if ($result_xml != '') {
    echo '<br /><span style="color:red;">', $result_xml;
    echo '</span><br />';
} ?>
        &lt;Courses&gt;xxx1|xxx2|xxx3&lt;/Courses&gt;
        &lt;Sessions&gt;sessionId|sessionId|sessionId&lt;/Sessions&gt;
        &lt;ClassId&gt;1&lt;/ClassId&gt;
    &lt;/Contact&gt;
&lt;/Contacts&gt;
</pre>
</blockquote>
<?php
Display::display_footer();
