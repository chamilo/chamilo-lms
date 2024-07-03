<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Chamilo\CourseBundle\Entity\CItemProperty;
use Chamilo\PluginBundle\Entity\StudentFollowUp\CarePost;
use Fhaculty\Graph\Graph;
use Monolog\Handler\BufferHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

if (PHP_SAPI != 'cli') {
    exit('Run this script through the command line or comment this line in the code');
}

if (file_exists('multiple_url_fix.php')) {
    require 'multiple_url_fix.php';
}

require_once __DIR__.'/../inc/global.inc.php';

ini_set('memory_limit', -1);
ini_set('max_execution_time', 0);
ini_set('log_errors', '1');
ini_set('display_errors', '1');

/**
 * Class ImportCsv.
 */
class ImportCsv
{
    public $test;
    public $defaultLanguage = 'dutch';
    public $extraFieldIdNameList = [
        'session' => 'external_session_id',
        'session_career' => 'external_career_id',
        'course' => 'external_course_id',
        'user' => 'external_user_id',
        'calendar_event' => 'external_calendar_event_id',
        'career' => 'external_career_id',
        'career_urls' => 'career_urls',
        'career_diagram' => 'career_diagram',
    ];
    public $defaultAdminId = 1;
    public $defaultSessionVisibility = 1;

    /**
     * When creating a user the expiration date is set to registration date + this value.
     *
     * @var int number of years
     */
    public $expirationDateInUserCreation = 1;

    public $batchSize = 20;

    /**
     * When updating a user the expiration date is set to update date + this value.
     *
     * @var int number of years
     */
    public $expirationDateInUserUpdate = 1;
    public $daysCoachAccessBeforeBeginning = 14;
    public $daysCoachAccessAfterBeginning = 14;
    public $conditions;
    private $logger;
    private $dumpValues;
    private $updateEmailToDummy;

    /**
     * @param Monolog\Logger $logger
     * @param array
     */
    public function __construct($logger, $conditions)
    {
        $this->logger = $logger;
        $this->conditions = $conditions;
        $this->updateEmailToDummy = false;
    }

    /**
     * @param bool $dump
     */
    public function setDumpValues($dump)
    {
        $this->dumpValues = $dump;
    }

    /**
     * @return bool
     */
    public function getDumpValues()
    {
        return $this->dumpValues;
    }

    /**
     * Runs the import process.
     */
    public function run()
    {
        global $_configuration;

        $value = api_get_configuration_value('import_csv_custom_url_id');
        if (!empty($value)) {
            $_configuration['access_url'] = $value;
        }

        $path = api_get_path(SYS_CODE_PATH).'cron/incoming/';
        if (!is_dir($path)) {
            echo "The folder! $path does not exits";

            return 0;
        }

        if ($this->getDumpValues()) {
            $this->dumpDatabaseTables();
        }

        echo 'Reading files: '.PHP_EOL.PHP_EOL;

        $files = scandir($path);
        $fileToProcess = [];
        $fileToProcessStatic = [];
        $teacherBackup = [];
        $groupBackup = [];

        $this->prepareImport();

        if (!empty($files)) {
            foreach ($files as $file) {
                $fileInfo = pathinfo($file);
                if (isset($fileInfo['extension']) && $fileInfo['extension'] === 'csv') {
                    // Checking teachers_yyyymmdd.csv,
                    // courses_yyyymmdd.csv, students_yyyymmdd.csv and sessions_yyyymmdd.csv
                    $parts = explode('_', $fileInfo['filename']);
                    $preMethod = ucwords($parts[1]);
                    $preMethod = str_replace('-static', 'Static', $preMethod);
                    $method = 'import'.$preMethod;
                    $isStatic = strpos($method, 'Static');

                    if ($method == 'importSessionsextidStatic') {
                        $method = 'importSessionsExtIdStatic';
                    }

                    if ($method == 'importCourseinsertStatic') {
                        $method = 'importSubscribeUserToCourse';
                    }

                    if ($method == 'importUnsubsessionsextidStatic') {
                        $method = 'importUnsubsessionsExtidStatic';
                    }

                    if ($method == 'importCareersdiagram') {
                        $method = 'importCareersDiagram';
                    }

                    if ($method == 'importCareersresults') {
                        $method = 'importCareersResults';
                    }

                    if ($method == 'importCareersresultsremoveStatic') {
                        $method = 'importCareersResultsRemoveStatic';
                    }

                    if ($method == 'importOpensessions') {
                        $method = 'importOpenSessions';
                    }

                    if ($method == 'importOpensessions') {
                        $method = 'importOpenSessions';
                    }

                    if ($method === 'importSessionsall') {
                        $method = 'importSessionsUsersCareers';
                    }

                    if ($method === 'importSubsessionsextidStatic') {
                        $method = 'importSubscribeUserToCourseSessionExtStatic';
                    }

                    if (method_exists($this, $method)) {
                        if ((
                                $method == 'importSubscribeStatic' ||
                                $method == 'importSubscribeUserToCourse'
                            ) ||
                            empty($isStatic)
                        ) {
                            $fileToProcess[$parts[1]][] = [
                                'method' => $method,
                                'file' => $path.$fileInfo['basename'],
                            ];
                        } else {
                            $fileToProcessStatic[$parts[1]][] = [
                                'method' => $method,
                                'file' => $path.$fileInfo['basename'],
                            ];
                        }
                    } else {
                        echo "Error - This file '$file' can't be processed.".PHP_EOL;
                        echo "Trying to call $method".PHP_EOL;
                        echo "The file have to has this format:".PHP_EOL;
                        echo "prefix_students_ddmmyyyy.csv, prefix_teachers_ddmmyyyy.csv,
                        prefix_courses_ddmmyyyy.csv, prefix_sessions_ddmmyyyy.csv ".PHP_EOL;
                        exit;
                    }
                }
            }

            if (empty($fileToProcess) && empty($fileToProcessStatic)) {
                echo 'Error - no files to process.';

                return 0;
            }

            $sections = [
                'students',
                'teachers',
                'courses',
                'sessions',
                'sessionsall',
                'opensessions',
                'subscribe-static',
                'courseinsert-static',
                'unsubscribe-static',
                'care',
                'skillset',
                //'careers',
                //'careersdiagram',
                //'careersresults',
            ];

            foreach ($sections as $section) {
                if (isset($fileToProcess[$section]) && !empty($fileToProcess[$section])) {
                    $this->logger->addInfo("-- Import $section --");
                    $files = $fileToProcess[$section];
                    foreach ($files as $fileInfo) {
                        $method = $fileInfo['method'];
                        $file = $fileInfo['file'];
                        echo 'File: '.$file.PHP_EOL;
                        echo 'Method : '.$method.PHP_EOL;
                        echo PHP_EOL;

                        $this->logger->addInfo('====================================================');
                        $this->logger->addInfo("Reading file: $file");
                        $this->logger->addInfo("Loading method $method ");
                        if ($method == 'importSessions' || $method == 'importOpenSessions') {
                            $this->$method(
                                $file,
                                true,
                                $teacherBackup,
                                $groupBackup
                            );
                        } else {
                            $this->$method($file, true);
                        }
                        $this->logger->addInfo('--Finish reading file--');
                    }
                }
            }

            $sections = [
                'students-static',
                'teachers-static',
                'courses-static',
                'sessions-static',
                'sessionsextid-static',
                'unsubscribe-static',
                'unsubsessionsextid-static',
                'subsessionsextid-static',
                'calendar-static',
                //'careersresultsremove-static',
            ];

            foreach ($sections as $section) {
                if (isset($fileToProcessStatic[$section]) &&
                    !empty($fileToProcessStatic[$section])
                ) {
                    $this->logger->addInfo("-- Import static files $section --");
                    $files = $fileToProcessStatic[$section];
                    foreach ($files as $fileInfo) {
                        $method = $fileInfo['method'];

                        $file = $fileInfo['file'];
                        echo 'Static file: '.$file.PHP_EOL;
                        echo 'Method : '.$method.PHP_EOL;
                        echo PHP_EOL;
                        $this->logger->addInfo("Reading static file: $file");
                        $this->logger->addInfo("Loading method $method ");
                        $this->$method(
                            $file,
                            true,
                            $teacherBackup,
                            $groupBackup
                        );
                        $this->logger->addInfo('--Finish reading file--');
                    }
                }
            }

            $this->logger->addInfo('teacher backup');
            $this->logger->addInfo(print_r($teacherBackup, 1));

            // Careers at the end:
            $sections = [
                'careers',
                'careersdiagram',
                'careersresults',
            ];

            foreach ($sections as $section) {
                if (isset($fileToProcess[$section]) && !empty($fileToProcess[$section])) {
                    $this->logger->addInfo("-- Import $section --");
                    $files = $fileToProcess[$section];
                    foreach ($files as $fileInfo) {
                        $method = $fileInfo['method'];
                        $file = $fileInfo['file'];
                        echo 'File: '.$file.PHP_EOL;
                        echo 'Method : '.$method.PHP_EOL;
                        echo PHP_EOL;

                        $this->logger->addInfo('====================================================');
                        $this->logger->addInfo("Reading file: $file");
                        $this->logger->addInfo("Loading method $method ");
                        $this->$method($file, true);
                        $this->logger->addInfo('--Finish reading file--');
                    }
                }
            }

            $removeResults = 'careersresultsremove-static';
            if (isset($fileToProcessStatic[$removeResults]) &&
                !empty($fileToProcessStatic[$removeResults])
            ) {
                $files = $fileToProcessStatic[$removeResults];
                foreach ($files as $fileInfo) {
                    $method = $fileInfo['method'];
                    $file = $fileInfo['file'];
                    echo 'Static file: '.$file.PHP_EOL;
                    echo 'Method : '.$method.PHP_EOL;
                    echo PHP_EOL;
                    $this->logger->addInfo("Reading static file: $file");
                    $this->logger->addInfo("Loading method $method ");
                    $this->$method(
                        $file,
                        true
                    );
                    $this->logger->addInfo('--Finish reading file--');
                }
            }
        }
    }

    /**
     * @param $file
     * @param bool $moveFile
     */
    public function importCare($file, $moveFile = false)
    {
        $data = Import::csv_reader($file);
        $counter = 1;
        $batchSize = $this->batchSize;
        $em = Database::getManager();

        if (!empty($data)) {
            $this->logger->addInfo(count($data)." records found.");
            $items = [];
            foreach ($data as $list) {
                $post = [];
                foreach ($list as $key => $value) {
                    $key = (string) trim($key);
                    // Remove utf8 bom
                    $key = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $key);
                    $post[$key] = $value;
                }

                if (empty($post)) {
                    continue;
                }

                $externalId = $post['External_care_id'];
                $items[$externalId] = $post;
            }
            ksort($items);

            foreach ($items as $row) {
                // Insert user
                //$insertUserInfo = api_get_user_info_from_username($row['Added_by']);

                // User about the post
                $userId = UserManager::get_user_id_from_original_id(
                    $row['Added_by'],
                    $this->extraFieldIdNameList['user']
                );

                $insertUserInfo = api_get_user_info($userId);

                if (empty($insertUserInfo)) {
                    $this->logger->addInfo("User: '".$row['Added_by']."' doesn't exists. Skip this entry.");
                    continue;
                }
                $insertUserInfo = api_get_user_entity($insertUserInfo['user_id']);

                // User about the post
                $userId = UserManager::get_user_id_from_original_id(
                    $row['External_user_id'],
                    $this->extraFieldIdNameList['user']
                );

                if (empty($userId)) {
                    $this->logger->addInfo("User does '".$row['External_user_id']."' not exists skip this entry.");
                    continue;
                }

                $userInfo = api_get_user_entity($userId);

                if (empty($userInfo)) {
                    $this->logger->addInfo("Chamilo user does not found: #".$userId."' ");
                    continue;
                }

                // Dates
                $createdAt = $this->createDateTime($row['Added_On']);
                $updatedAt = $this->createDateTime($row['Edited_on']);

                // Parent
                $parent = null;
                if (!empty($row['Parent_id'])) {
                    $parentId = $items[$row['Parent_id']];
                    $criteria = [
                        'externalCareId' => $parentId,
                    ];
                    $parent = $em->getRepository('ChamiloPluginBundle:StudentFollowUp\CarePost')->findOneBy($criteria);
                }

                // Tags
                $tags = explode(',', $row['Tags']);

                // Check if post already was added:
                $criteria = [
                    'externalCareId' => $row['External_care_id'],
                ];
                $post = $em->getRepository('ChamiloPluginBundle:StudentFollowUp\CarePost')->findOneBy($criteria);

                if (empty($post)) {
                    $post = new CarePost();
                    $this->logger->addInfo("New post will be created no match for externalCareId = ".$row['External_care_id']);
                }

                $contentDecoded = utf8_encode(base64_decode($row['Article']));

                $post
                    ->setTitle($row['Title'])
                    ->setContent($contentDecoded)
                    ->setExternalCareId($row['External_care_id'])
                    ->setCreatedAt($createdAt)
                    ->setUpdatedAt($updatedAt)
                    ->setPrivate((int) $row['Private'])
                    ->setInsertUser($insertUserInfo)
                    ->setExternalSource((int) $row['Source_is_external'])
                    ->setParent($parent)
                    ->setTags($tags)
                    ->setUser($userInfo)
                    ->setAttachment($row['Attachement'])
                ;
                $em->persist($post);
                $em->flush();

                $this->logger->addInfo("Post id saved #".$post->getId());

                if (($counter % $batchSize) === 0) {
                    $em->flush();
                    $em->clear(); // Detaches all objects from Doctrine!
                }
                $counter++;
            }

            $em->clear(); // Detaches all objects from Doctrine!
        }
    }

    /**
     * @return mixed
     */
    public function getUpdateEmailToDummy()
    {
        return $this->updateEmailToDummy;
    }

    /**
     * @param mixed $updateEmailToDummy
     */
    public function setUpdateEmailToDummy($updateEmailToDummy)
    {
        $this->updateEmailToDummy = $updateEmailToDummy;
    }

    /**
     * Change emails of all users except admins.
     */
    public function updateUsersEmails()
    {
        if ($this->getUpdateEmailToDummy() === true) {
            $sql = "UPDATE user SET email = CONCAT(username,'@example.com') WHERE id NOT IN (SELECT user_id FROM admin)";
            Database::query($sql);
        }
    }

    /**
     * Prepares extra fields before the import.
     */
    private function prepareImport()
    {
        // Create user extra field: extra_external_user_id
        UserManager::create_extra_field(
            $this->extraFieldIdNameList['user'],
            1,
            'External user id',
            null
        );

        // Create course extra field: extra_external_course_id
        CourseManager::create_course_extra_field(
            $this->extraFieldIdNameList['course'],
            1,
            'External course id',
            ''
        );

        // Course skill set.
        CourseManager::create_course_extra_field(
            'skillset',
            1,
            'Skill set',
            ''
        );

        CourseManager::create_course_extra_field(
            'disable_import_calendar',
            13,
            'Disable import calendar',
            ''
        );

        // Create session extra field extra_external_session_id
        SessionManager::create_session_extra_field(
            $this->extraFieldIdNameList['session'],
            1,
            'External session id'
        );

        SessionManager::create_session_extra_field(
            $this->extraFieldIdNameList['session_career'],
            1,
            'Career id'
        );

        // Create calendar_event extra field extra_external_session_id
        $extraField = new ExtraField('calendar_event');
        $extraField->save(
            [
                'field_type' => ExtraField::FIELD_TYPE_TEXT,
                'variable' => $this->extraFieldIdNameList['calendar_event'],
                'display_text' => 'External calendar event id',
            ]
        );

        $extraField = new ExtraField('career');
        $extraField->save(
            [
                'visible_to_self' => 1,
                'field_type' => ExtraField::FIELD_TYPE_TEXT,
                'variable' => $this->extraFieldIdNameList['career'],
                'display_text' => 'External career id',
            ]
        );

        $extraField->save(
            [
                'visible_to_self' => 1,
                'field_type' => ExtraField::FIELD_TYPE_TEXTAREA,
                'variable' => $this->extraFieldIdNameList['career_diagram'],
                'display_text' => 'Career diagram',
            ]
        );

        $extraField->save(
            [
                'visible_to_self' => 1,
                'field_type' => ExtraField::FIELD_TYPE_TEXTAREA,
                'variable' => $this->extraFieldIdNameList['career_urls'],
                'display_text' => 'Career urls',
            ]
        );
    }

    /**
     * @param string $file
     */
    private function moveFile($file)
    {
        $moved = str_replace('incoming', 'treated', $file);

        if ($this->test) {
            $result = 1;
        } else {
            $result = rename($file, $moved);
        }

        if ($result) {
            $this->logger->addInfo("Moving file to the treated folder: $file");
        } else {
            $this->logger->addError(
                "Error - Cant move file to the treated folder: $file"
            );
        }
    }

    /**
     * @param array $row
     *
     * @return array
     */
    private function cleanUserRow($row)
    {
        $row['lastname'] = $row['LastName'];
        $row['firstname'] = $row['FirstName'];
        $row['email'] = $row['Email'];
        $row['username'] = $row['UserName'];
        $row['password'] = $row['Password'];
        $row['auth_source'] = isset($row['AuthSource']) ? $row['AuthSource'] : PLATFORM_AUTH_SOURCE;
        $row['official_code'] = $row['OfficialCode'];
        $row['phone'] = isset($row['PhoneNumber']) ? $row['PhoneNumber'] : '';

        if (isset($row['StudentID'])) {
            $row['extra_'.$this->extraFieldIdNameList['user']] = $row['StudentID'];
        }

        if (isset($row['TeacherID'])) {
            $row['extra_'.$this->extraFieldIdNameList['user']] = $row['TeacherID'];
        }

        return $row;
    }

    /**
     * @param array $row
     *
     * @return array
     */
    private function cleanCourseRow($row)
    {
        $row['title'] = $row['Title'];
        $row['course_code'] = $row['Code'];
        $row['course_category'] = $row['CourseCategory'];
        $row['email'] = $row['Teacher'];
        $row['language'] = $row['Language'];
        $row['visibility'] = isset($row['Visibility']) ? $row['Visibility'] : COURSE_VISIBILITY_REGISTERED;

        $row['teachers'] = [];
        if (isset($row['Teacher']) && !empty($row['Teacher'])) {
            $this->logger->addInfo("Teacher list found: ".$row['Teacher']);
            $teachers = explode(',', $row['Teacher']);
            if (!empty($teachers)) {
                foreach ($teachers as $teacherUserName) {
                    $teacherUserName = trim($teacherUserName);
                    $userInfo = api_get_user_info_from_username($teacherUserName);
                    if (!empty($userInfo)) {
                        $this->logger->addInfo("Username found: $teacherUserName");
                        $row['teachers'][] = $userInfo['user_id'];
                    }
                }
            }
        }

        if (isset($row['CourseID'])) {
            $row['extra_'.$this->extraFieldIdNameList['course']] = $row['CourseID'];
        }

        return $row;
    }

    /**
     * File to import.
     *
     * @param string $file
     */
    private function importTeachersStatic($file)
    {
        $this->importTeachers($file, true);
    }

    /**
     * File to import.
     *
     * @param string $file
     * @param bool   $moveFile
     */
    private function importTeachers($file, $moveFile = true)
    {
        $this->fixCSVFile($file);
        $data = Import::csvToArray($file);

        /* Unique identifier: official-code username.
        Email address and password should never get updated. *ok
        The only fields that I can think of that should update if the data changes in the csv file are FirstName and LastName. *ok
        A slight edit of these fields should be taken into account. ???
        Adding teachers is no problem, but deleting them shouldn’t be automated, but we should get a log of “to delete teachers”.
        We’ll handle that manually if applicable.
        No delete!
        */
        $language = $this->defaultLanguage;

        if (!empty($data)) {
            $this->logger->addInfo(count($data)." records found.");
            $expirationDateOnCreation = api_get_utc_datetime(strtotime("+".intval($this->expirationDateInUserCreation)."years"));
            $expirationDateOnUpdate = api_get_utc_datetime(strtotime("+".intval($this->expirationDateInUserUpdate)."years"));

            $batchSize = $this->batchSize;
            $em = Database::getManager();
            $counter = 1;
            foreach ($data as $row) {
                $row = $this->cleanUserRow($row);
                $externalUserId = $row['official_code'];
                $row['extra_'.$this->extraFieldIdNameList['user']] = $externalUserId;

                $user_id = UserManager::get_user_id_from_original_id(
                    $row['extra_'.$this->extraFieldIdNameList['user']],
                    $this->extraFieldIdNameList['user']
                );
                $userInfo = [];
                $userInfoByOfficialCode = null;

                if (!empty($user_id)) {
                    $userInfo = api_get_user_info($user_id);
                    $userInfoByOfficialCode = api_get_user_info_from_official_code($row['official_code']);
                }

                if (empty($userInfo) && empty($userInfoByOfficialCode)) {
                    // Create user
                    $userId = UserManager::create_user(
                        $row['firstname'],
                        $row['lastname'],
                        COURSEMANAGER,
                        $row['email'],
                        $row['username'],
                        $row['password'],
                        $row['official_code'],
                        $language, //$row['language'],
                        $row['phone'],
                        null, //$row['picture'], //picture
                        $row['auth_source'], // ?
                        $expirationDateOnCreation, //'0000-00-00 00:00:00', //$row['expiration_date'], //$expiration_date = '0000-00-00 00:00:00',
                        1, //active
                        0,
                        null, // extra
                        null, //$encrypt_method = '',
                        false //$send_mail = false
                    );

                    $row['extra_mail_notify_invitation'] = 1;
                    $row['extra_mail_notify_message'] = 1;
                    $row['extra_mail_notify_group_message'] = 1;

                    if ($userId) {
                        foreach ($row as $key => $value) {
                            if (substr($key, 0, 6) == 'extra_') {
                                //an extra field
                                UserManager::update_extra_field_value(
                                    $userId,
                                    substr($key, 6),
                                    $value
                                );
                            }
                        }
                        $this->logger->addInfo("Teachers - User created: ".$row['username']);
                    } else {
                        $this->logger->addError("Teachers - User NOT created: ".$row['username']." ".$row['firstname']." ".$row['lastname']);
                        $this->logger->addError(strip_tags(Display::getFlashToString()));
                        Display::cleanFlashMessages();
                    }
                } else {
                    if (empty($userInfo)) {
                        $this->logger->addError("Teachers - Can't update user :".$row['username']);
                        continue;
                    }

                    // Update user
                    $result = UserManager::update_user(
                        $userInfo['user_id'],
                        $row['firstname'], // <<-- changed
                        $row['lastname'], // <<-- changed
                        $userInfo['username'],
                        null, //$password = null,
                        $row['auth_source'],
                        $userInfo['email'],
                        COURSEMANAGER,
                        $userInfo['official_code'],
                        $userInfo['phone'],
                        $userInfo['picture_uri'],
                        $expirationDateOnUpdate,
                        $userInfo['active'],
                        null, //$creator_id = null,
                        0, //$hr_dept_id = 0,
                        null, // $extra = null,
                        null, //$language = 'english',
                        null, //$encrypt_method = '',
                        false, //$send_email = false,
                        0 //$reset_password = 0
                    );

                    $table = Database::get_main_table(TABLE_MAIN_USER);
                    $authSource = Database::escape_string($row['auth_source']);
                    $sql = "UPDATE $table SET auth_source = '$authSource' WHERE id = ".$userInfo['user_id'];
                    Database::query($sql);

                    $this->logger->addInfo(
                        'Teachers - #'.$userInfo['user_id']." auth_source was changed from '".$userInfo['auth_source']."' to '".$row['auth_source']."' "
                    );

                    if ($result) {
                        foreach ($row as $key => $value) {
                            if (substr($key, 0, 6) == 'extra_') {
                                //an extra field
                                UserManager::update_extra_field_value(
                                    $userInfo['user_id'],
                                    substr($key, 6),
                                    $value
                                );
                            }
                        }
                        $this->logger->addInfo("Teachers - User updated: ".$row['username']);
                    } else {
                        $this->logger->addError("Teachers - User not updated: ".$row['username']);
                    }
                }

                if (($counter % $batchSize) === 0) {
                    $em->flush();
                    $em->clear(); // Detaches all objects from Doctrine!
                }
                $counter++;
            }

            $em->clear(); // Detaches all objects from Doctrine!
        }

        if ($moveFile) {
            $this->moveFile($file);
        }

        $this->updateUsersEmails();
    }

    /**
     * @param string $file
     */
    private function importStudentsStatic($file)
    {
        $this->importStudents($file, true);
    }

    /**
     * @param string $file
     * @param bool   $moveFile
     */
    private function importStudents($file, $moveFile = true)
    {
        $this->fixCSVFile($file);
        $data = Import::csvToArray($file);

        /*
         * Another users import.
        Unique identifier: official code and username . ok
        Password should never get updated. ok
        If an update should need to occur (because it changed in the .csv),
        we’ll want that logged. We will handle this manually in that case.
        All other fields should be updateable, though passwords should of course not get updated. ok
        If a user gets deleted (not there anymore),
        He should be set inactive one year after the current date.
        So I presume you’ll just update the expiration date.
        We want to grant access to courses up to a year after deletion.
         */
        $timeStart = microtime(true);

        $batchSize = $this->batchSize;
        $em = Database::getManager();

        if (!empty($data)) {
            $language = $this->defaultLanguage;
            $this->logger->addInfo(count($data)." records found.");

            $expirationDateOnCreate = api_get_utc_datetime(
                strtotime("+".intval($this->expirationDateInUserCreation)."years")
            );
            $expirationDateOnUpdate = api_get_utc_datetime(
                strtotime("+".intval($this->expirationDateInUserUpdate)."years")
            );

            $counter = 1;
            $secondsInYear = 365 * 24 * 60 * 60;

            foreach ($data as $row) {
                $row = $this->cleanUserRow($row);
                $externalUserId = $row['official_code'];
                $row['extra_'.$this->extraFieldIdNameList['user']] = $externalUserId;

                $user_id = UserManager::get_user_id_from_original_id(
                    $row['extra_'.$this->extraFieldIdNameList['user']],
                    $this->extraFieldIdNameList['user']
                );

                $userInfo = [];
                $userInfoByOfficialCode = null;
                if (!empty($user_id)) {
                    $userInfo = api_get_user_info($user_id, false, true);
                    $userInfoByOfficialCode = api_get_user_info_from_official_code($row['official_code']);
                }

                $userInfoFromUsername = api_get_user_info_from_username($row['username']);
                if (!empty($userInfoFromUsername)) {
                    $extraFieldValue = new ExtraFieldValue('user');
                    $extraFieldValues = $extraFieldValue->get_values_by_handler_and_field_variable(
                        $userInfoFromUsername['user_id'],
                        $this->extraFieldIdNameList['user']
                    );

                    if (!empty($extraFieldValues)) {
                        $value = 0;
                        foreach ($extraFieldValues as $extraFieldValue) {
                            if (isset($extraFieldValue['value'])) {
                                $value = $extraFieldValue['value'];
                            }
                        }
                        if (!empty($user_id) && $value != $user_id) {
                            $emails = api_get_configuration_value('cron_notification_help_desk');
                            if (!empty($emails)) {
                                $this->logger->addInfo('Preparing email to users in configuration: "cron_notification_help_desk"');
                                $subject = 'User not added due to same username';
                                $body = 'Cannot add username: "'.$row['username'].'"
                                    with external_user_id: '.$row['extra_'.$this->extraFieldIdNameList['user']].'
                                    because '.$userInfoFromUsername['username'].' with external_user_id '.$value.' exists on the portal';
                                $this->logger->addInfo($body);
                                foreach ($emails as $email) {
                                    api_mail_html('', $email, $subject, $body);
                                }
                            }
                        }
                    }
                }

                if (empty($userInfo) && empty($userInfoByOfficialCode)) {
                    // Create user
                    $result = UserManager::create_user(
                        $row['firstname'],
                        $row['lastname'],
                        STUDENT,
                        $row['email'],
                        $row['username'],
                        $row['password'],
                        $row['official_code'],
                        $language, //$row['language'],
                        $row['phone'],
                        null, //$row['picture'], //picture
                        $row['auth_source'], // ?
                        $expirationDateOnCreate,
                        1, //active
                        0,
                        null, // extra
                        null, //$encrypt_method = '',
                        false //$send_mail = false
                    );

                    $row['extra_mail_notify_invitation'] = 1;
                    $row['extra_mail_notify_message'] = 1;
                    $row['extra_mail_notify_group_message'] = 1;

                    if ($result) {
                        foreach ($row as $key => $value) {
                            if (substr($key, 0, 6) === 'extra_') {
                                //an extra field
                                UserManager::update_extra_field_value(
                                    $result,
                                    substr($key, 6),
                                    $value
                                );
                            }
                        }
                        $this->logger->addInfo("Students - User created: ".$row['username']);
                    } else {
                        $this->logger->addError("Students - User NOT created: ".$row['username']." ".$row['firstname']." ".$row['lastname']);
                        $this->logger->addError(strip_tags(Display::getFlashToString()));
                        Display::cleanFlashMessages();
                    }
                } else {
                    if (empty($userInfo)) {
                        $this->logger->addError("Students - Can't update user :".$row['username']);
                        continue;
                    }

                    if (isset($row['action']) && $row['action'] === 'delete') {
                        // Inactive one year later
                        $userInfo['expiration_date'] = api_get_utc_datetime(api_strtotime(time() + $secondsInYear));
                    }

                    $password = $row['password']; // change password
                    $email = $row['email']; // change email
                    $resetPassword = 2; // allow password change

                    // Conditions that disables the update of password and email:
                    if (isset($this->conditions['importStudents'])) {
                        if (isset($this->conditions['importStudents']['update']) &&
                            isset($this->conditions['importStudents']['update']['avoid'])
                        ) {
                            // Blocking email update -
                            // 1. Condition
                            $avoidUsersWithEmail = $this->conditions['importStudents']['update']['avoid']['email'];
                            if ($userInfo['email'] != $row['email'] && in_array($row['email'], $avoidUsersWithEmail)) {
                                $this->logger->addInfo("Students - User email is not updated : ".$row['username']." because the avoid conditions (email).");
                                // Do not change email keep the old email.
                                $email = $userInfo['email'];
                            }

                            // 2. Condition
                            if (!in_array($userInfo['email'], $avoidUsersWithEmail) && !in_array($row['email'], $avoidUsersWithEmail)) {
                                $this->logger->addInfo("Students - User email is not updated from ".$userInfo['email']." to ".$row['email']." because the avoid conditions (email).");
                                $email = $userInfo['email'];
                            }

                            // 3. Condition
                            if (in_array($userInfo['email'], $avoidUsersWithEmail) && !in_array($row['email'], $avoidUsersWithEmail)) {
                                $this->logger->addInfo('Email to be updated from:'.$userInfo['email'].' to '.$row['email']);
                                $email = $row['email'];
                            }

                            // Blocking password update
                            //$avoidUsersWithPassword = $this->conditions['importStudents']['update']['avoid']['password'];

                            /*if (isset($row['password'])) {
                                $user = api_get_user_entity($userInfo['id']);
                                $encoded = UserManager::encryptPassword(
                                    $row['password'],
                                    $user
                                );

                                if ($userInfo['password'] != $encoded &&
                                    in_array($row['password'], $avoidUsersWithPassword)
                                ) {
                                    $this->logger->addInfo(
                                        "Students - User password is not updated: ".$row['username']." because the avoid conditions (password)."
                                    );
                                    $password = null;
                                    $resetPassword = 0; // disallow password change
                                }
                            }*/
                        }
                    }

                    // Always disallow password change during update
                    $password = null;
                    $resetPassword = 0; // disallow password change

                    // Update user
                    $result = UserManager::update_user(
                        $userInfo['user_id'],
                        $row['firstname'], // <<-- changed
                        $row['lastname'], // <<-- changed
                        $row['username'], // <<-- changed
                        $password, //$password = null,
                        $row['auth_source'],
                        $email,
                        STUDENT,
                        $userInfo['official_code'],
                        $userInfo['phone'],
                        $userInfo['picture_uri'],
                        $expirationDateOnUpdate,
                        $userInfo['active'],
                        null, //$creator_id = null,
                        0, //$hr_dept_id = 0,
                        null, // $extra = null,
                        null, //$language = 'english',
                        null, //$encrypt_method = '',
                        false, //$send_email = false,
                        $resetPassword //$reset_password = 0
                    );

                    $table = Database::get_main_table(TABLE_MAIN_USER);
                    $authSource = Database::escape_string($row['auth_source']);
                    $sql = "UPDATE $table SET auth_source = '$authSource' WHERE id = ".$userInfo['user_id'];
                    Database::query($sql);

                    $this->logger->addInfo(
                        "Students - #".$userInfo['user_id']." auth_source was changed from '".$userInfo['auth_source']."' to '".$row['auth_source']."' "
                    );

                    if ($result) {
                        if ($row['username'] != $userInfo['username']) {
                            $this->logger->addInfo("Students - Username was changes from '".$userInfo['username']."' to '".$row['username']."' ");
                        }
                        foreach ($row as $key => $value) {
                            if (substr($key, 0, 6) === 'extra_') {
                                //an extra field
                                UserManager::update_extra_field_value(
                                    $userInfo['user_id'],
                                    substr($key, 6),
                                    $value
                                );
                            }
                        }
                        $this->logger->addInfo(
                            'Students - User updated: username:'.$row['username'].' firstname:'.$row['firstname'].' lastname:'.$row['lastname'].' email:'.$email
                        );
                    } else {
                        $this->logger->addError("Students - User NOT updated: ".$row['username']." ".$row['firstname']." ".$row['lastname']);
                    }
                }

                if (($counter % $batchSize) === 0) {
                    $em->flush();
                    $em->clear(); // Detaches all objects from Doctrine!
                    $this->logger->addInfo("Detaches all objects");
                }
                $counter++;
            }
            $em->clear(); // Detaches all objects from Doctrine!
        }

        $timeEnd = microtime(true);
        $executionTime = round(($timeEnd - $timeStart) / 60, 2);
        $this->logger->addInfo("Execution Time for process students: $executionTime Min");

        if ($moveFile) {
            $this->moveFile($file);
        }

        $this->updateUsersEmails();
    }

    /**
     * @param string $file
     */
    private function importCoursesStatic($file, $moveFile, &$teacherBackup = [], &$groupBackup = [])
    {
        $this->importCourses($file, true, $teacherBackup, $groupBackup);
    }

    /**
     * @param string $file
     * @param bool   $moveFile
     *
     * @return int
     */
    private function importCalendarStatic($file, $moveFile = true)
    {
        $this->fixCSVFile($file);

        $this->updateUsersEmails();
        $data = Import::csvToArray($file);

        if (!empty($data)) {
            $this->logger->addInfo(count($data).' records found.');
            $eventsToCreate = [];
            $errorFound = false;

            $courseExtraFieldValue = new ExtraFieldValue('course');

            foreach ($data as $row) {
                $sessionId = null;
                $externalSessionId = null;
                if (isset($row['external_sessionID'])) {
                    $externalSessionId = $row['external_sessionID'];
                    $sessionId = SessionManager::getSessionIdFromOriginalId(
                        $externalSessionId,
                        $this->extraFieldIdNameList['session']
                    );
                }

                $courseCode = null;
                if (isset($row['coursecode'])) {
                    $courseCode = $row['coursecode'];
                }
                $courseInfo = api_get_course_info($courseCode);
                $courseId = $courseInfo['real_id'] ?? 0;

                $item = $courseExtraFieldValue->get_values_by_handler_and_field_variable(
                    $courseId,
                    'disable_import_calendar'
                );

                if (!empty($item) && isset($item['value']) && $item['value'] == 1) {
                    $this->logger->addInfo(
                        "Course '".$courseInfo['code']."' has 'disable_import_calendar' turn on. Skip"
                    );
                    $errorFound = true;
                }

                if (empty($courseInfo)) {
                    $this->logger->addInfo("Course '$courseCode' does not exists");
                } else {
                    if ($courseInfo['visibility'] == COURSE_VISIBILITY_HIDDEN) {
                        $this->logger->addInfo("Course '".$courseInfo['code']."' has hidden visibility. Skip");
                        $errorFound = true;
                    }
                }

                if (empty($sessionId)) {
                    $this->logger->addInfo("external_sessionID: $externalSessionId does not exists.");
                }
                $teacherId = null;
                $sessionInfo = [];
                if (!empty($sessionId) && !empty($courseInfo)) {
                    $sessionInfo = api_get_session_info($sessionId);
                    $courseIncluded = SessionManager::relation_session_course_exist($sessionId, $courseId);

                    if ($courseIncluded == false) {
                        $this->logger->addInfo(
                            "Course '$courseCode' is not included in session: $sessionId"
                        );
                        $errorFound = true;
                    } else {
                        $teachers = CourseManager::get_coach_list_from_course_code($courseInfo['code'], $sessionId);

                        // Getting first teacher.
                        if (!empty($teachers)) {
                            $teacher = current($teachers);
                            $teacherId = $teacher['user_id'];
                        } else {
                            $teacherId = $sessionInfo['id_coach'];
                        }
                    }
                } else {
                    $errorFound = true;
                }

                if (empty($teacherId)) {
                    $errorFound = true;
                    $this->logger->addInfo(
                        "No teacher found in course code : '$courseCode' and session: '$sessionId'"
                    );
                }

                $date = $row['date'];
                $startTime = $row['time_start'];
                $endTime = $row['time_end'];
                $title = $row['title'];
                $comment = $row['comment'] ?? '';
                $color = $row['color'] ?? '';

                $startDateYear = substr($date, 0, 4);
                $startDateMonth = substr($date, 4, 2);
                $startDateDay = substr($date, 6, 8);

                $startDate = $startDateYear.'-'.$startDateMonth.'-'.$startDateDay.' '.$startTime.':00';
                $endDate = $startDateYear.'-'.$startDateMonth.'-'.$startDateDay.' '.$endTime.':00';

                if (!api_is_valid_date($startDate) || !api_is_valid_date($endDate)) {
                    $this->logger->addInfo("Verify your dates: '$startDate' : '$endDate' ");
                    $errorFound = true;
                }

                // Check session dates.
                if ($sessionInfo && !empty($sessionInfo['access_start_date'])) {
                    $date = new \DateTime($sessionInfo['access_start_date']);
                    $intervalInput = '7';
                    if (!empty($row['dateinterval'])) {
                        if ((int) $row['dateinterval'] >= 0) {
                            $intervalInput = (int) $row['dateinterval'];
                        }
                    }
                    $interval = new \DateInterval('P'.$intervalInput.'D');
                    $date->sub($interval);
                    if ($date->getTimestamp() > time()) {
                        $this->logger->addInfo(
                            "Calendar event # ".$row['external_calendar_itemID']."
                            in session [$externalSessionId] was not added
                            because the startdate is more than $intervalInput days in the future: ".$sessionInfo['access_start_date']
                        );
                        $errorFound = true;
                    }
                }

                $sendAnnouncement = false;
                if (isset($row['sendmail']) && 1 === (int) $row['sendmail']) {
                    $sendAnnouncement = true;
                }

                // New condition.
                if ($errorFound == false) {
                    $eventsToCreate[] = [
                        'start' => $startDate,
                        'end' => $endDate,
                        'title' => $title,
                        'sender_id' => $teacherId,
                        'course_id' => $courseInfo['real_id'],
                        'session_id' => $sessionId,
                        'comment' => $comment,
                        'color' => $color,
                        'send_announcement' => $sendAnnouncement,
                        $this->extraFieldIdNameList['calendar_event'] => $row['external_calendar_itemID'],
                    ];
                }
                $errorFound = false;
            }

            if (empty($eventsToCreate)) {
                $this->logger->addInfo('No events to add');

                return 0;
            }

            $extraFieldValue = new ExtraFieldValue('calendar_event');
            $extraFieldName = $this->extraFieldIdNameList['calendar_event'];
            $externalEventId = null;

            $extraField = new ExtraField('calendar_event');
            $extraFieldInfo = $extraField->get_handler_field_info_by_field_variable($extraFieldName);

            if (empty($extraFieldInfo)) {
                $this->logger->addInfo(
                    "No calendar event extra field created: $extraFieldName"
                );

                return 0;
            }

            $this->logger->addInfo('Ready to insert # '.count($eventsToCreate).' events');
            $batchSize = $this->batchSize;
            $counter = 1;
            $em = Database::getManager();
            $eventStartDateList = [];
            $eventEndDateList = [];
            $report = [
                'mail_sent' => 0,
                'mail_not_sent_announcement_exists' => 0,
                'mail_not_sent_because_date' => 0,
                'mail_not_sent_because_setting' => 0,
            ];

            $eventsToCreateFinal = [];
            foreach ($eventsToCreate as $event) {
                $update = false;
                $item = null;
                if (!isset($event[$extraFieldName])) {
                    $this->logger->addInfo('No external_calendar_itemID found. Skipping ...');
                    continue;
                } else {
                    $externalEventId = $event[$extraFieldName];
                    if (empty($externalEventId)) {
                        $this->logger->addInfo('external_calendar_itemID was set but empty. Skipping ...');
                        continue;
                    }

                    $item = $extraFieldValue->get_item_id_from_field_variable_and_field_value(
                        $extraFieldName,
                        $externalEventId,
                        false,
                        false,
                        false
                    );

                    if (!empty($item)) {
                        $update = true;
                    }
                }

                $courseInfo = api_get_course_info_by_id($event['course_id']);
                $event['course_info'] = $courseInfo;
                $event['update'] = $update;
                $event['item'] = $item;

                $calendarEvent = null;
                /* Check if event changed of course code */
                if (!empty($item) && isset($item['item_id']) && !empty($item['item_id'])) {
                    /** @var CCalendarEvent $calendarEvent */
                    $calendarEvent = $em->getRepository('ChamiloCourseBundle:CCalendarEvent')->find($item['item_id']);
                }

                if ($calendarEvent) {
                    $this->logger->addInfo('Calendar event found '.$item['item_id']);
                    if ($calendarEvent->getCId() != $courseInfo['real_id']) {
                        $this->logger->addInfo('Move from course #'.$calendarEvent->getCId().' to #'.$courseInfo['real_id']);
                        // Seems that the course id changed in the csv
                        $calendarEvent->setCId($courseInfo['real_id']);
                        $em->persist($calendarEvent);
                        $em->flush();

                        $criteria = [
                            'tool' => 'calendar_event',
                            'ref' => $item['item_id'],
                        ];
                        /** @var CItemProperty $itemProperty */
                        $itemProperty = $em->getRepository('ChamiloCourseBundle:CItemProperty')->findOneBy($criteria);
                        $courseEntity = $em->getRepository('ChamiloCoreBundle:Course')->find($courseInfo['real_id']);
                        if ($itemProperty && $courseEntity) {
                            $itemProperty->setCourse($courseEntity);
                            $em->persist($itemProperty);
                            $em->flush();
                        }
                    }

                    // Checking if session still exists
                    $calendarSessionId = (int) $calendarEvent->getSessionId();
                    if (!empty($calendarSessionId)) {
                        $calendarSessionInfo = api_get_session_info($calendarSessionId);
                        if (empty($calendarSessionInfo)) {
                            $calendarId = (int) $calendarEvent->getIid();

                            // Delete calendar events because the session was deleted!
                            $this->logger->addInfo(
                                "Delete event # $calendarId because session # $calendarSessionId doesn't exist"
                            );

                            $sql = "DELETE FROM c_calendar_event
                                    WHERE iid = $calendarId AND session_id = $calendarSessionId";
                            Database::query($sql);
                            $this->logger->addInfo($sql);

                            $sql = "DELETE FROM c_item_property
                                    WHERE
                                        tool = 'calendar_event' AND
                                        ref = $calendarSessionId AND
                                        session_id = $calendarSessionId";
                            Database::query($sql);
                            $this->logger->addInfo($sql);
                        }
                    }
                } else {
                    $this->logger->addInfo('Calendar event not found '.$item['item_id']);
                }

                $event['external_event_id'] = $externalEventId;
                if (isset($eventStartDateList[$courseInfo['real_id']]) &&
                    isset($eventStartDateList[$courseInfo['real_id']][$event['session_id']])
                ) {
                    $currentItemDate = api_strtotime($event['start']);
                    $firstDate = $eventStartDateList[$courseInfo['real_id']][$event['session_id']];
                    if ($currentItemDate < api_strtotime($firstDate)) {
                        $eventStartDateList[$courseInfo['real_id']][$event['session_id']] = $event['start'];
                        $eventEndDateList[$courseInfo['real_id']][$event['session_id']] = $event['end'];
                    }
                } else {
                    // First time
                    $eventStartDateList[$courseInfo['real_id']][$event['session_id']] = $event['start'];
                    $eventEndDateList[$courseInfo['real_id']][$event['session_id']] = $event['end'];
                }
                $eventsToCreateFinal[] = $event;
            }

            $eventAlreadySent = [];

            $tpl = new Template(null, false, false, false, false, false, false);

            foreach ($eventsToCreateFinal as $event) {
                $courseInfo = $event['course_info'];
                $item = $event['item'];
                $update = $event['update'];
                $externalEventId = $event['external_event_id'];
                $info = 'Course: '.$courseInfo['real_id'].' ('.$courseInfo['code'].') - Session: '.$event['session_id'].' external event id: '.$externalEventId;

                $agenda = new Agenda(
                    'course',
                    $event['sender_id'],
                    $courseInfo['real_id'],
                    $event['session_id']
                );
                $agenda->set_course($courseInfo);
                $agenda->setSessionId($event['session_id']);
                $agenda->setSenderId($event['sender_id']);
                $agenda->setIsAllowedToEdit(true);
                $eventComment = $event['comment'];
                $color = $event['color'];

                // To use the event comment you need
                // ALTER TABLE c_calendar_event ADD COLUMN comment TEXT;
                // add in configuration.php allow_agenda_event_comment = true
                if (empty($courseInfo)) {
                    $this->logger->addInfo(
                        "No course found for event #$externalEventId Course #".$event['course_id']." Skipping ..."
                    );
                    continue;
                }

                if (empty($event['sender_id'])) {
                    $this->logger->addInfo(
                        "No sender found for event #$externalEventId Send #".$event['sender_id']." Skipping ..."
                    );
                    continue;
                }

                // Taking first element of course-session event
                $alreadyAdded = false;
                $firstDate = $eventStartDateList[$courseInfo['real_id']][$event['session_id']];
                $firstEndDate = $eventEndDateList[$courseInfo['real_id']][$event['session_id']];

                if (isset($eventAlreadySent[$courseInfo['real_id']]) &&
                    isset($eventAlreadySent[$courseInfo['real_id']][$event['session_id']])
                ) {
                    $alreadyAdded = true;
                } else {
                    $eventAlreadySent[$courseInfo['real_id']][$event['session_id']] = true;
                }

                // Working days (Mon-Fri) see BT#12156#note-16
                $days = 3;
                $startDatePlusDays = api_strtotime("$days weekdays");

                /*
                $timePart = date('H:i:s', api_strtotime('now'));
                $datePart = date('Y-m-d', api_strtotime("$days weekdays"));
                $startDatePlusDays = "$timePart $datePart";
                */
                $this->logger->addInfo(
                    'startDatePlusDays: '.api_get_utc_datetime($startDatePlusDays).' - First date: '.$firstDate
                );

                // Send.
                $sendMail = false;
                if ($startDatePlusDays > api_strtotime($firstDate)) {
                    $sendMail = true;
                }

                $allowAnnouncementSendEmail = false;
                if ($event['send_announcement']) {
                    $allowAnnouncementSendEmail = true;
                }

                // Send announcement to users
                if ($allowAnnouncementSendEmail) {
                    if ($sendMail && $alreadyAdded == false) {
                        $start = $firstDate;
                        $end = $firstEndDate;

                        if (!empty($end) &&
                            api_format_date($start, DATE_FORMAT_LONG) ==
                            api_format_date($end, DATE_FORMAT_LONG)
                        ) {
                            $date = api_format_date($start, DATE_FORMAT_LONG).' ('.
                                api_format_date($start, TIME_NO_SEC_FORMAT).' '.
                                api_format_date($end, TIME_NO_SEC_FORMAT).')';
                        } else {
                            $date = api_format_date($start, DATE_TIME_FORMAT_LONG_24H).' - '.
                                api_format_date($end, DATE_TIME_FORMAT_LONG_24H);
                        }

                        $sessionName = '';
                        $sessionId = isset($event['session_id']) && !empty($event['session_id']) ? $event['session_id'] : 0;
                        if (!empty($sessionId)) {
                            $sessionName = api_get_session_name($sessionId);
                        }

                        $courseTitle = $courseInfo['title'];

                        // Get the value of the "careerid" extra field of this
                        // session
                        $sessionExtraFieldValue = new ExtraFieldValue('session');
                        $externalCareerIdList = $sessionExtraFieldValue->get_values_by_handler_and_field_variable(
                            $event['session_id'],
                            'careerid'
                        );
                        $externalCareerIdList = $externalCareerIdList['value'];
                        if (substr($externalCareerIdList, 0, 1) === '[') {
                            $externalCareerIdList = substr($externalCareerIdList, 1, -1);
                            $externalCareerIds = preg_split('/,/', $externalCareerIdList);
                        } else {
                            $externalCareerIds = [$externalCareerIdList];
                        }

                        $careerExtraFieldValue = new ExtraFieldValue('career');
                        $career = new Career();
                        $careerName = '';

                        // Concat the names of each career linked to this session
                        foreach ($externalCareerIds as $externalCareerId) {
                            // Using the external_career_id field (from above),
                            // find the career ID
                            $careerValue = $careerExtraFieldValue->get_item_id_from_field_variable_and_field_value(
                                'external_career_id',
                                $externalCareerId
                            );
                            $career = $career->find($careerValue['item_id']);
                            $careerName .= $career['name'].', ';
                        }
                        // Remove trailing comma
                        $careerName = substr($careerName, 0, -2);
                        $subject = sprintf(
                            get_lang('WelcomeToPortalXInCourseSessionX'),
                            api_get_setting('Institution'),
                            $courseInfo['title']
                        );

                        $tpl->assign('course_title', $courseTitle);
                        $tpl->assign('career_name', $careerName);
                        $tpl->assign('first_lesson', $date);
                        $tpl->assign('location', $eventComment);
                        $tpl->assign('session_name', $sessionName);

                        if (empty($sessionId)) {
                            $teachersToString = CourseManager::getTeacherListFromCourseCodeToString(
                                $courseInfo['code'],
                                ','
                            );
                        } else {
                            $teachersToString = SessionManager::getCoachesByCourseSessionToString(
                                $sessionId,
                                $courseInfo['real_id'],
                                ','
                            );
                        }

                        $tpl->assign('teachers', $teachersToString);

                        $templateName = $this->getCustomMailTemplate();
                        $emailBody = $tpl->fetch($templateName);

                        $coaches = SessionManager::getCoachesByCourseSession(
                            $event['session_id'],
                            $courseInfo['real_id']
                        );

                        // Search if an announcement exists:
                        $announcementsWithTitleList = AnnouncementManager::getAnnouncementsByTitle(
                            $subject,
                            $courseInfo['real_id'],
                            $event['session_id'],
                            1
                        );

                        if (count($announcementsWithTitleList) === 0) {
                            $this->logger->addInfo(
                                'Mail to be sent because start date: '.$event['start'].' and no announcement found.'
                            );

                            $senderId = $this->defaultAdminId;
                            if (!empty($coaches) && isset($coaches[0]) && !empty($coaches[0])) {
                                $senderId = $coaches[0];
                            }

                            $announcementId = AnnouncementManager::add_announcement(
                                $courseInfo,
                                $event['session_id'],
                                $subject,
                                $emailBody,
                                [
                                    'everyone',
                                    'users' => $coaches,
                                ],
                                [],
                                null,
                                null,
                                false,
                                $senderId
                            );

                            if ($announcementId) {
                                $this->logger->addInfo("Announcement added: $announcementId in $info");
                                $this->logger->addInfo("<<--SENDING MAIL Sender id: $senderId-->>");
                                $report['mail_sent']++;
                                AnnouncementManager::sendEmail(
                                    $courseInfo,
                                    $event['session_id'],
                                    $announcementId,
                                    false,
                                    false,
                                    $this->logger,
                                    $senderId,
                                    true
                                );
                            } else {
                                $this->logger->addError(
                                    "Error when trying to add announcement with title $subject here: $info and SenderId = $senderId"
                                );
                            }
                        } else {
                            $report['mail_not_sent_announcement_exists']++;
                            $this->logger->addInfo(
                                "Mail NOT sent. An announcement seems to be already saved in '$info'"
                            );
                        }
                    } else {
                        $this->logger->addInfo(
                            "Send Mail: ".intval($sendMail).' - Already added: '.intval($alreadyAdded)
                        );
                        if ($sendMail == false) {
                            $report['mail_not_sent_because_date']++;
                        }
                    }
                } else {
                    $this->logger->addInfo("Announcement not sent because config 'sendmail' in CSV");
                    $report['mail_not_sent_because_setting']++;
                }

                $content = '';
                if ($update && isset($item['item_id'])) {
                    $eventInfo = $agenda->get_event($item['item_id']);
                    if (empty($eventInfo)) {
                        // Means that agenda external id exists but the event doesn't exist
                        $this->logger->addInfo("external event id exists: $externalEventId");
                        $this->logger->addInfo("but Chamilo event doesn't exists: ".$item['item_id']);

                        $eventId = $agenda->addEvent(
                            $event['start'],
                            $event['end'],
                            false,
                            $event['title'],
                            $content,
                            ['everyone'], // $usersToSend
                            false, //$addAsAnnouncement = false
                            null, //  $parentEventId
                            [], //$attachmentArray = array(),
                            [], //$attachmentCommentList
                            $eventComment,
                            $color
                        );

                        if (!empty($eventId)) {
                            $this->logger->addInfo("Chamilo event created: ".$eventId);
                            $extraFieldValueItem = $extraFieldValue->get_values_by_handler_and_field_id(
                                $item['item_id'],
                                $extraFieldInfo['id']
                            );

                            if (!empty($extraFieldValueItem) && isset($extraFieldValueItem['id'])) {
                                $params = [
                                    'id' => $extraFieldValueItem['id'],
                                    'item_id' => $eventId,
                                ];
                                $extraFieldValue->update($params);
                                $this->logger->addInfo(
                                    'Updating calendar extra field #'.$extraFieldValueItem['id'].'
                                    new item_id: '.$eventId.' old item_id: '.$item['item_id']
                                );
                            }
                        } else {
                            $this->logger->addInfo("Error while creating event external id: $externalEventId");
                        }
                    } else {
                        // The event already exists, just update
                        $eventResult = $agenda->editEvent(
                            $item['item_id'],
                            $event['start'],
                            $event['end'],
                            false,
                            $event['title'],
                            $content,
                            ['everyone'], // $usersToSend
                            [], //$attachmentArray = array(),
                            [], //$attachmentCommentList
                            $eventComment,
                            $color,
                            false,
                            false,
                            $this->defaultAdminId
                        );

                        if ($eventResult !== false) {
                            $this->logger->addInfo(
                                "Event updated #".$item['item_id']." External cal Id: (".$externalEventId.") $info"
                            );
                        } else {
                            $this->logger->addInfo(
                                "Error while updating event with external id: $externalEventId"
                            );
                        }
                    }
                } else {
                    // New event. Create it.
                    $eventId = $agenda->addEvent(
                        $event['start'],
                        $event['end'],
                        false,
                        $event['title'],
                        $content,
                        ['everyone'], // $usersToSend
                        false, //$addAsAnnouncement = false
                        null, //  $parentEventId
                        [], //$attachmentArray = array(),
                        [], //$attachmentCommentList
                        $eventComment,
                        $color
                    );

                    if (!empty($eventId)) {
                        $extraFieldValue->save(
                            [
                                'value' => $externalEventId,
                                'field_id' => $extraFieldInfo['id'],
                                'item_id' => $eventId,
                            ]
                        );
                        $this->logger->addInfo(
                            "Event added: #$eventId External cal id: (".$externalEventId.") $info"
                        );
                    } else {
                        $this->logger->addInfo(
                            "Error while creating event external id: $externalEventId"
                        );
                    }
                }

                if (($counter % $batchSize) === 0) {
                    $em->flush();
                    $em->clear(); // Detaches all objects from Doctrine!
                }
                $counter++;
            }

            $em->clear(); // Detaches all objects from Doctrine!
            $this->logger->addInfo('------Summary------');
            foreach ($report as $title => $count) {
                $this->logger->addInfo("$title: $count");
            }
            $this->logger->addInfo('------End Summary------');
        }

        if ($moveFile) {
            $this->moveFile($file);
        }
    }

    private function importSkillset(
        $file,
        $moveFile = true
    ) {
        $this->fixCSVFile($file);
        $data = Import::csvToArray($file);
        if (!empty($data)) {
            $this->logger->addInfo(count($data).' records found.');
            $extraFieldValues = new ExtraFieldValue('skill');
            $em = Database::getManager();
            $repo = $em->getRepository(\Chamilo\CoreBundle\Entity\Skill::class);
            $skillSetList = [];
            $urlId = api_get_current_access_url_id();

            foreach ($data as $row) {
                $skill = $repo->findOneBy(['shortCode' => $row['Code']]);
                $new = false;
                if ($skill === null) {
                    $new = true;
                    $skill = new \Chamilo\CoreBundle\Entity\Skill();
                    $skill
                        ->setShortCode($row['Code'])
                        ->setDescription('')
                        ->setAccessUrlId($urlId)
                        ->setIcon('')
                        ->setStatus(1)
                    ;
                }

                $skill
                    ->setName($row['Tekst'])
                    ->setUpdatedAt(new DateTime())
                ;
                $em->persist($skill);
                $em->flush();

                if ($new) {
                    $skillRelSkill = (new \Chamilo\CoreBundle\Entity\SkillRelSkill())
                        ->setRelationType(0)
                        ->setParentId(0)
                        ->setLevel(0)
                        ->setSkillId($skill->getId())
                    ;
                    $em->persist($skillRelSkill);
                    $em->flush();
                }

                /*
                $params = [
                    'item_id' => $skill->getId(),
                    'variable' => 'skillset',
                    'value' => $row['SkillsetID'],
                ];
                $extraFieldValues->save($params);*/
                $skillSetList[$row['SkillsetID']][] = $skill->getId();
            }

            //$courseRelSkills = [];
            foreach ($skillSetList as $skillSetId => $skillList) {
                $skillList = array_unique($skillList);
                if (empty($skillList)) {
                    continue;
                }

                $sql = "SELECT id FROM course WHERE code LIKE '%$skillSetId' ";
                $result = Database::query($sql);
                while ($row = Database::fetch_array($result, 'ASSOC')) {
                    $courseId = $row['id'];
                    //$courseRelSkills[$courseId] = $skillList;
                    Skill::saveSkillsToCourse($skillList, $courseId, null);
                }
            }
        }
    }

    /**
     * @param string $file
     * @param bool   $moveFile
     * @param array  $teacherBackup
     * @param array  $groupBackup
     */
    private function importCourses(
        $file,
        $moveFile = true,
        &$teacherBackup = [],
        &$groupBackup = []
    ) {
        $this->fixCSVFile($file);
        $data = Import::csvToArray($file);

        if (!empty($data)) {
            $this->logger->addInfo(count($data)." records found.");

            foreach ($data as $row) {
                $row = $this->cleanCourseRow($row);
                $courseId = CourseManager::get_course_id_from_original_id(
                    $row['extra_'.$this->extraFieldIdNameList['course']],
                    $this->extraFieldIdNameList['course']
                );

                $courseInfo = api_get_course_info_by_id($courseId);

                if (empty($courseInfo)) {
                    // Create
                    $params = [];
                    $params['title'] = $row['title'];
                    $params['exemplary_content'] = false;
                    $params['wanted_code'] = $row['course_code'];
                    $params['course_category'] = $row['course_category'];
                    $params['course_language'] = $row['language'];
                    $params['teachers'] = $row['teachers'];
                    $params['visibility'] = $row['visibility'];

                    $courseInfo = CourseManager::create_course(
                        $params,
                        $this->defaultAdminId
                    );

                    if (!empty($courseInfo)) {
                        CourseManager::update_course_extra_field_value(
                            $courseInfo['code'],
                            'external_course_id',
                            $row['extra_'.$this->extraFieldIdNameList['course']]
                        );

                        CourseManager::update_course_extra_field_value(
                            $courseInfo['code'],
                            'skillset',
                            $row['extra_courseskillset']
                        );

                        $this->logger->addInfo("Courses - Course created ".$courseInfo['code']);
                    } else {
                        $this->logger->addError("Courses - Can't create course:".$row['title']);
                    }
                } else {
                    // Update
                    $params = [
                        'title' => $row['title'],
                        'category_code' => $row['course_category'],
                        'visibility' => $row['visibility'],
                    ];

                    $result = CourseManager::update_attributes(
                        $courseInfo['real_id'],
                        $params
                    );

                    $addTeacherToSession = isset($courseInfo['add_teachers_to_sessions_courses']) && !empty($courseInfo['add_teachers_to_sessions_courses']) ? true : false;

                    $teachers = $row['teachers'];
                    if (!is_array($teachers)) {
                        $teachers = [$teachers];
                    }

                    if ($addTeacherToSession) {
                        $this->logger->addInfo("Add teacher to all course sessions");
                        CourseManager::updateTeachers(
                            $courseInfo,
                            $row['teachers'],
                            false,
                            true,
                            false,
                            $teacherBackup,
                            $this->logger
                        );
                    } else {
                        CourseManager::updateTeachers(
                            $courseInfo,
                            $row['teachers'],
                            true,
                            false,
                            false,
                            $teacherBackup,
                            $this->logger
                        );
                    }

                    CourseManager::update_course_extra_field_value(
                        $courseInfo['code'],
                        'skillset',
                        $row['extra_courseskillset']
                    );

                    foreach ($teachers as $teacherId) {
                        if (isset($groupBackup['tutor'][$teacherId]) &&
                            isset($groupBackup['tutor'][$teacherId][$courseInfo['code']])
                        ) {
                            foreach ($groupBackup['tutor'][$teacherId][$courseInfo['code']] as $data) {
                                $groupInfo = GroupManager::get_group_properties($data['group_id']);
                                GroupManager::subscribe_tutors(
                                    [$teacherId],
                                    $groupInfo,
                                    $data['c_id']
                                );
                            }
                        }

                        if (isset($groupBackup['user'][$teacherId]) &&
                            isset($groupBackup['user'][$teacherId][$courseInfo['code']]) &&
                            !empty($groupBackup['user'][$teacherId][$courseInfo['code']])
                        ) {
                            foreach ($groupBackup['user'][$teacherId][$courseInfo['code']] as $data) {
                                $groupInfo = GroupManager::get_group_properties($data['group_id']);
                                GroupManager::subscribe_users(
                                    [$teacherId],
                                    $groupInfo,
                                    $data['c_id']
                                );
                            }
                        }
                    }

                    if ($result) {
                        $this->logger->addInfo("Courses - Course updated ".$courseInfo['code']);
                    } else {
                        $this->logger->addError("Courses - Course NOT updated ".$courseInfo['code']);
                    }
                }
            }
        }

        if ($moveFile) {
            $this->moveFile($file);
        }
    }

    /**
     * Parse filename: encora_subsessionsextid-static_31082016.csv.
     *
     * @param string $file
     */
    private function importSubscribeUserToCourseSessionExtStatic($file, $moveFile = true)
    {
        $data = Import::csv_reader($file);
        if (!empty($data)) {
            $this->logger->addInfo(count($data)." records found.");
            $userIdList = [];
            foreach ($data as $row) {
                $chamiloUserName = $row['UserName'];
                $chamiloCourseCode = $row['CourseCode'];
                $externalSessionId = $row['ExtSessionID'];
                $status = $row['Status'];

                $chamiloSessionId = null;
                if (!empty($externalSessionId)) {
                    $chamiloSessionId = SessionManager::getSessionIdFromOriginalId(
                        $externalSessionId,
                        $this->extraFieldIdNameList['session']
                    );
                }

                $sessionInfo = api_get_session_info($chamiloSessionId);

                if (empty($sessionInfo)) {
                    $this->logger->addError('Session does not exists: '.$chamiloSessionId);
                    continue;
                }

                $courseInfo = api_get_course_info($chamiloCourseCode);
                if (empty($courseInfo)) {
                    $this->logger->addError('Course does not exists: '.$courseInfo);
                    continue;
                }

                $userId = UserManager::get_user_id_from_username($chamiloUserName);

                if (empty($userId)) {
                    $this->logger->addError('User does not exists: '.$chamiloUserName);
                    continue;
                }

                switch ($status) {
                    case 'student':
                        SessionManager::subscribe_users_to_session_course(
                            [$userId],
                            $chamiloSessionId,
                            $courseInfo['code']
                        );
                        break;
                    case 'teacher':
                        SessionManager::set_coach_to_course_session(
                            $userId,
                            $chamiloSessionId,
                            $courseInfo['code']
                        );
                        break;
                    case 'drh':
                        $removeAllSessionsFromUser = true;
                        if (in_array($userId, $userIdList)) {
                            $removeAllSessionsFromUser = false;
                        } else {
                            $userIdList[] = $userId;
                        }

                        $userInfo = api_get_user_info($userId);
                        SessionManager::subscribeSessionsToDrh(
                            $userInfo,
                            [$chamiloSessionId],
                            false,
                            $removeAllSessionsFromUser
                        );
                        break;
                }

                $this->logger->addError(
                    "User '$chamiloUserName' was added as '$status' to Session: #$chamiloSessionId - Course: ".$courseInfo['code']
                );
            }
        }

        if ($moveFile) {
            $this->moveFile($file);
        }
    }

    /**
     * @param $file
     * @param bool $moveFile
     */
    private function importUnsubSessionsExtIdStatic($file, $moveFile = true)
    {
        $data = Import::csv_reader($file);

        if (!empty($data)) {
            $this->logger->addInfo(count($data)." records found.");
            foreach ($data as $row) {
                $chamiloUserName = $row['UserName'];
                $chamiloCourseCode = $row['CourseCode'];
                $externalSessionId = $row['ExtSessionID'];
                $dateStop = $row['DateStop'];

                $chamiloSessionId = null;
                if (!empty($externalSessionId)) {
                    $chamiloSessionId = SessionManager::getSessionIdFromOriginalId(
                        $externalSessionId,
                        $this->extraFieldIdNameList['session']
                    );
                }

                $sessionInfo = api_get_session_info($chamiloSessionId);

                if (empty($sessionInfo)) {
                    $this->logger->addError('Session does not exists: '.$chamiloSessionId);
                    continue;
                }

                $courseInfo = api_get_course_info($chamiloCourseCode);
                if (empty($courseInfo)) {
                    $this->logger->addError('Course does not exists: '.$courseInfo);
                    continue;
                }

                $userId = UserManager::get_user_id_from_username($chamiloUserName);

                if (empty($userId)) {
                    $this->logger->addError('User does not exists: '.$chamiloUserName);
                    continue;
                }

                SessionManager::removeUsersFromCourseSession(
                    [$userId],
                    $chamiloSessionId,
                    $courseInfo
                );

                $this->logger->addError(
                    "User '$chamiloUserName' was remove from Session: #$chamiloSessionId - Course: ".$courseInfo['code']
                );
            }
        }

        if ($moveFile) {
            $this->moveFile($file);
        }
    }

    /**
     * @param string $file
     */
    private function importSessionsExtIdStatic($file, $moveFile = true)
    {
        $data = Import::csv_reader($file);

        if (!empty($data)) {
            $this->logger->addInfo(count($data)." records found.");
            foreach ($data as $row) {
                $chamiloUserName = $row['UserName'];
                $chamiloCourseCode = $row['CourseCode'];
                $externalSessionId = $row['ExtSessionID'];
                $type = $row['Type'];

                $chamiloSessionId = null;
                if (!empty($externalSessionId)) {
                    $chamiloSessionId = SessionManager::getSessionIdFromOriginalId(
                        $externalSessionId,
                        $this->extraFieldIdNameList['session']
                    );
                }

                $sessionInfo = api_get_session_info($chamiloSessionId);

                if (empty($sessionInfo)) {
                    $this->logger->addError('Session does not exists: '.$chamiloSessionId);
                    continue;
                }

                $courseInfo = api_get_course_info($chamiloCourseCode);
                if (empty($courseInfo)) {
                    $this->logger->addError('Course does not exists: '.$courseInfo);
                    continue;
                }

                $userId = UserManager::get_user_id_from_username($chamiloUserName);

                if (empty($userId)) {
                    $this->logger->addError('User does not exists: '.$chamiloUserName);
                    continue;
                }
                switch ($type) {
                    case 'student':
                        SessionManager::subscribe_users_to_session_course(
                            [$userId],
                            $chamiloSessionId,
                            $courseInfo['code'],
                            null,
                            false
                        );
                        break;
                    case 'teacher':
                        SessionManager::set_coach_to_course_session(
                            $userId,
                            $chamiloSessionId,
                            $courseInfo['code']
                        );
                        break;
                }

                $this->logger->addError(
                    "User '$chamiloUserName' with status $type was added to session: #$chamiloSessionId - Course: ".$courseInfo['code']
                );
            }
        }

        if ($moveFile) {
            $this->moveFile($file);
        }
    }

    /**
     * Updates the session synchronize with the csv file.
     *
     * @param bool   $moveFile
     * @param string $file
     */
    private function importSessionsStatic($file, $moveFile = true)
    {
        $content = file($file);
        $sessions = [];
        $tag_names = [];

        foreach ($content as $key => $enreg) {
            $enreg = explode(';', trim($enreg));
            if ($key) {
                foreach ($tag_names as $tag_key => $tag_name) {
                    if (isset($enreg[$tag_key])) {
                        $sessions[$key - 1][$tag_name] = $enreg[$tag_key];
                    }
                }
            } else {
                foreach ($enreg as $tag_name) {
                    $tag_names[] = api_preg_replace(
                        '/[^a-zA-Z0-9_\-]/',
                        '',
                        $tag_name
                    );
                }
                if (!in_array('SessionName', $tag_names) ||
                    !in_array('DateStart', $tag_names) || !in_array('DateEnd', $tag_names)
                ) {
                    $error_message = get_lang('NoNeededData');
                    break;
                }
            }
        }

        if (!empty($sessions)) {
            // Looping the sessions.
            foreach ($sessions as $session) {
                if (!empty($session['SessionID'])) {
                    $sessionId = SessionManager::getSessionIdFromOriginalId(
                        $session['SessionID'],
                        $this->extraFieldIdNameList['session']
                    );

                    $coachUserName = isset($session['Coach']) ? $session['Coach'] : null;
                    $categoryId = isset($session['category_id']) ? $session['category_id'] : null;

                    // 2014-06-30
                    $dateStart = explode('/', $session['DateStart']);
                    $dateEnd = explode('/', $session['DateEnd']);
                    $visibility = $this->defaultSessionVisibility;

                    $coachId = null;
                    if (!empty($coachUserName)) {
                        $coachInfo = api_get_user_info_from_username($coachUserName);
                        $coachId = $coachInfo['user_id'];
                    }

                    $dateStart = $dateStart[0].'-'.$dateStart[1].'-'.$dateStart[2].' 00:00:00';
                    $dateEnd = $dateEnd[0].'-'.$dateEnd[1].'-'.$dateEnd[2].' 23:59:59';

                    $date = new \DateTime($dateStart);
                    $interval = new DateInterval('P'.$this->daysCoachAccessBeforeBeginning.'D');
                    $date->sub($interval);
                    $coachBefore = $date->format('Y-m-d h:i');

                    $date = new \DateTime($dateEnd);
                    $interval = new DateInterval('P'.$this->daysCoachAccessAfterBeginning.'D');
                    $date->add($interval);
                    $coachAfter = $date->format('Y-m-d h:i');

                    /*$dateStart = api_get_utc_datetime($dateStart);
                    $dateEnd = api_get_utc_datetime($dateEnd);
                    $coachBefore = api_get_utc_datetime($coachBefore);
                    $coachAfter = api_get_utc_datetime($coachAfter);*/

                    if (empty($sessionId)) {
                        $result = SessionManager::create_session(
                            $session['SessionName'],
                            $dateStart,
                            $dateEnd,
                            $dateStart,
                            $dateEnd,
                            $coachBefore,
                            $coachAfter,
                            $coachId,
                            $categoryId,
                            $visibility
                        );

                        if (is_numeric($result)) {
                            $sessionId = $result;
                            $this->logger->addInfo("Session #$sessionId created: ".$session['SessionName']);
                            SessionManager::update_session_extra_field_value(
                                $sessionId,
                                $this->extraFieldIdNameList['session'],
                                $session['SessionID']
                            );
                        } else {
                            $this->logger->addInfo("Failed creating session: ".$session['SessionName']);
                        }
                    } else {
                        $sessionInfo = api_get_session_info($sessionId);
                        $accessBefore = null;
                        $accessAfter = null;

                        if (empty($sessionInfo['nb_days_access_before_beginning']) ||
                            (!empty($sessionInfo['nb_days_access_before_beginning']) &&
                                $sessionInfo['nb_days_access_before_beginning'] < $this->daysCoachAccessBeforeBeginning)
                        ) {
                            $accessBefore = $coachBefore;
                        }

                        $accessAfter = null;
                        if (empty($sessionInfo['nb_days_access_after_end']) ||
                            (!empty($sessionInfo['nb_days_access_after_end']) &&
                                $sessionInfo['nb_days_access_after_end'] < $this->daysCoachAccessAfterBeginning)
                        ) {
                            $accessAfter = $coachAfter;
                        }

                        $showDescription = isset($sessionInfo['show_description']) ? $sessionInfo['show_description'] : 1;

                        $result = SessionManager::edit_session(
                            $sessionId,
                            $session['SessionName'],
                            $dateStart,
                            $dateEnd,
                            $dateStart,
                            $dateEnd,
                            $accessBefore,
                            $accessAfter,
                            $coachId,
                            $categoryId,
                            $visibility,
                            null, //$description = null,
                            $showDescription
                        );

                        if (is_numeric($result)) {
                            $this->logger->addInfo("Session #$sessionId updated: ".$session['SessionName']);
                            $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
                            $params = [
                                'description' => $session['SessionDescription'],
                            ];
                            Database::update(
                                $tbl_session,
                                $params,
                                ['id = ?' => $sessionId]
                            );
                        }
                    }

                    if (!empty($sessionId)) {
                        // Courses
                        $courses = explode('|', $session['Courses']);
                        $courseList = [];
                        $courseListWithCoach = [];
                        foreach ($courses as $course) {
                            $courseArray = bracketsToArray($course);
                            $courseCode = $courseArray[0];
                            if (CourseManager::course_exists($courseCode)) {
                                $courseInfo = api_get_course_info($courseCode);
                                $courseList[] = $courseInfo['real_id'];
                                // Extracting course coaches
                                $courseCoaches = isset($courseArray[1]) ? $courseArray[1] : null;
                                $courseCoaches = explode(',', $courseCoaches);

                                // Extracting students
                                $courseUsers = isset($courseArray[2]) ? $courseArray[2] : null;
                                $courseUsers = explode(',', $courseUsers);

                                $courseListWithCoach[] = [
                                    'course_info' => $courseInfo,
                                    'coaches' => $courseCoaches,
                                    'course_users' => $courseUsers,
                                ];
                            }
                        }

                        SessionManager::add_courses_to_session(
                            $sessionId,
                            $courseList,
                            true
                        );

                        $this->logger->addInfo("Session #$sessionId: Courses added: '".implode(', ', $courseList)."'");

                        if (empty($courseListWithCoach)) {
                            $this->logger->addInfo("No users/coaches to update");
                            continue;
                        }

                        foreach ($courseListWithCoach as $courseData) {
                            $courseInfo = $courseData['course_info'];
                            $courseCode = $courseInfo['code'];
                            $courseId = $courseInfo['real_id'];
                            $courseCoaches = $courseData['coaches'];
                            $courseUsers = $courseData['course_users'];

                            // Coaches
                            if (!empty($courseCoaches)) {
                                $coachList = [];
                                foreach ($courseCoaches as $courseCoach) {
                                    $courseCoachId = UserManager::get_user_id_from_username(
                                        $courseCoach
                                    );
                                    if ($courseCoachId !== false) {
                                        // Just insert new coaches
                                        $coachList[] = $courseCoachId;
                                    }
                                }

                                $this->logger->addInfo("Session #$sessionId: course '$courseCode' coaches added: '".implode(', ', $coachList)."'");

                                SessionManager::updateCoaches(
                                    $sessionId,
                                    $courseId,
                                    $coachList,
                                    true
                                );
                            } else {
                                $this->logger->addInfo("No coaches added");
                            }

                            // Students
                            if (!empty($courseUsers)) {
                                $userList = [];
                                foreach ($courseUsers as $username) {
                                    $userInfo = api_get_user_info_from_username(trim($username));
                                    if (!empty($userInfo)) {
                                        $userList[] = $userInfo['user_id'];
                                    }
                                }

                                $this->logger->addInfo("Session #$sessionId: course '$courseCode': Students added '".implode(', ', $userList)."'");
                                SessionManager::subscribe_users_to_session_course(
                                    $userList,
                                    $sessionId,
                                    $courseCode,
                                    SESSION_VISIBLE_READ_ONLY,
                                    true
                                );
                            } else {
                                $this->logger->addInfo("No users to register.");
                            }
                        }
                    } else {
                        $this->logger->addInfo(
                            'SessionID not found in system.'
                        );
                    }
                } else {
                    $this->logger->addInfo('SessionID does not exists');
                }
            }
        } else {
            $this->logger->addInfo($error_message);
        }

        if ($moveFile) {
            $this->moveFile($file);
        }
    }

    /**
     * @param $file
     * @param bool  $moveFile
     * @param array $teacherBackup
     * @param array $groupBackup
     */
    private function importOpenSessions(
        $file,
        $moveFile = true,
        &$teacherBackup = [],
        &$groupBackup = []
    ) {
        $this->importSessions($file, $moveFile, $teacherBackup, $groupBackup);
    }

    private function importSessionsUsersCareers(
        $file,
        $moveFile = false,
        &$teacherBackup = [],
        &$groupBackup = []
    ) {
        $data = Import::csvToArray($file);
        if (!empty($data)) {
            $extraFieldValueCareer = new ExtraFieldValue('career');
            $sessionExtraFieldValue = new ExtraFieldValue('session');
            $career = new Career();

            $this->logger->addInfo(count($data)." records found.");
            foreach ($data as $row) {
                $users = $row['Users'];
                if (empty($users)) {
                    $this->logger->addError('No users found');
                    continue;
                }

                $users = explode('|', $users);
                $careerList = str_replace(['[', ']'], '', $row['extra_careerid']);
                $careerList = explode(',', $careerList);

                $finalCareerIdList = [];
                $careerListValidated = [];
                foreach ($careerList as $careerId) {
                    $realCareerIdList = $extraFieldValueCareer->get_item_id_from_field_variable_and_field_value(
                        'external_career_id',
                        $careerId
                    );
                    if (isset($realCareerIdList['item_id'])) {
                        $careerListValidated[] = $careerId;
                        $finalCareerIdList[] = $realCareerIdList['item_id'];
                    }
                }

                if (empty($finalCareerIdList)) {
                    $this->logger->addError('Careers not found: '.print_r($finalCareerIdList, 1));
                    continue;
                }

                //$chamiloSessionId = $row['SessionID'];

                $chamiloSessionId = SessionManager::getSessionIdFromOriginalId(
                    $row['SessionID'],
                    $this->extraFieldIdNameList['session']
                );

                $sessionInfo = api_get_session_info($chamiloSessionId);

                if (empty($sessionInfo)) {
                    $this->logger->addError('Session does not exists: '.$chamiloSessionId);
                    continue;
                } else {
                    $this->logger->addInfo("Session id: ".$sessionInfo['id']);
                }

                $sessionId = $sessionInfo['id'];

                // Add career to session.
                $externalCareerIdList = $sessionExtraFieldValue->get_values_by_handler_and_field_variable(
                    $sessionId,
                    'careerid'
                );

                if (empty($externalCareerIdList) ||
                    (isset($externalCareerIdList['value']) && empty($externalCareerIdList['value']))
                ) {
                    $careerItem = '['.implode(',', $careerListValidated).']';
                    $params = ['item_id' => $sessionId, 'extra_careerid' => $careerItem];
                    $this->logger->addInfo("Saving career: $careerItem to session: $sessionId");
                    $sessionExtraFieldValue->saveFieldValues($params, true);
                } else {
                    /*foreach ($finalCareerIdList as $careerId) {
                        if (empty($externalCareerIdList)) {
                            $params = ['item_id' => $sessionId, 'extra_careerid' => $careerId];
                            $sessionExtraFieldValue->saveFieldValues($params, true);
                        }
                    }*/
                }

                // Add career to users.
                foreach ($users as $username) {
                    $userInfo = api_get_user_info_from_username($username);
                    if (empty($userInfo)) {
                        $this->logger->addError('username not found: '.$username);
                        continue;
                    }

                    foreach ($finalCareerIdList as $careerId) {
                        $this->logger->addInfo("Adding Career $careerId: To user $username");
                        UserManager::addUserCareer($userInfo['id'], $careerId);
                    }
                }
            }
        }
    }

    /**
     * @param string $file
     * @param bool   $moveFile
     * @param array  $teacherBackup
     * @param array  $groupBackup
     */
    private function importSessions(
        $file,
        $moveFile = true,
        &$teacherBackup = [],
        &$groupBackup = []
    ) {
        $avoid = null;
        if (isset($this->conditions['importSessions']) &&
            isset($this->conditions['importSessions']['update'])
        ) {
            $avoid = $this->conditions['importSessions']['update'];
        }
        $result = SessionManager::importCSV(
            $file,
            true,
            $this->defaultAdminId,
            $this->logger,
            [
                'SessionID' => 'extra_'.$this->extraFieldIdNameList['session'],
                'CareerId' => 'extra_'.$this->extraFieldIdNameList['session_career'],
            ],
            $this->extraFieldIdNameList['session'],
            $this->daysCoachAccessBeforeBeginning,
            $this->daysCoachAccessAfterBeginning,
            $this->defaultSessionVisibility,
            $avoid,
            false, // deleteUsersNotInList
            false, // updateCourseCoaches
            true, // sessionWithCoursesModifier
            true, //$addOriginalCourseTeachersAsCourseSessionCoaches
            false, //$removeAllTeachersFromCourse
            1, // $showDescription,
            $teacherBackup,
            $groupBackup
        );

        if (!empty($result['error_message'])) {
            $this->logger->addError($result['error_message']);
        }
        $this->logger->addInfo("Sessions - Sessions parsed: ".$result['session_counter']);

        if ($moveFile) {
            $this->moveFile($file);
        }
    }

    /**
     * @param string $file
     * @param bool   $moveFile
     */
    private function importSubscribeStatic($file, $moveFile = true)
    {
        $data = Import::csv_reader($file);

        if (!empty($data)) {
            $this->logger->addInfo(count($data)." records found.");
            foreach ($data as $row) {
                $chamiloUserName = $row['UserName'];
                $chamiloCourseCode = $row['CourseCode'];
                $chamiloSessionId = $row['SessionID'];
                $type = $row['Type'];

                $sessionInfo = api_get_session_info($chamiloSessionId);

                if (empty($sessionInfo)) {
                    $this->logger->addError('Session does not exists: '.$chamiloSessionId);
                    continue;
                }

                $courseInfo = api_get_course_info($chamiloCourseCode);
                if (empty($courseInfo)) {
                    $this->logger->addError('Course does not exists: '.$courseInfo);
                    continue;
                }

                $userId = UserManager::get_user_id_from_username($chamiloUserName);

                if (empty($userId)) {
                    $this->logger->addError('User does not exists: '.$chamiloUserName);
                    continue;
                }

                switch ($type) {
                    case 'student':
                        SessionManager::subscribe_users_to_session_course(
                            [$userId],
                            $chamiloSessionId,
                            $courseInfo['code'],
                            null,
                            false
                        );
                        break;
                    case 'teacher':
                        SessionManager::set_coach_to_course_session(
                            $userId,
                            $chamiloSessionId,
                            $courseInfo['real_id']
                        );
                        break;
                }

                $this->logger->addError(
                    "User '$chamiloUserName' with status $type was added to session: #$chamiloSessionId - Course: ".$courseInfo['code']
                );
            }
        }

        if ($moveFile) {
            $this->moveFile($file);
        }
    }

    /**
     * @param $file
     * @param bool $moveFile
     */
    private function importSubscribeUserToCourse($file, $moveFile = false, &$teacherBackup = [])
    {
        $data = Import::csv_reader($file);

        if (!empty($data)) {
            $this->logger->addInfo(count($data)." records found.");
            foreach ($data as $row) {
                $chamiloUserName = $row['UserName'];
                $chamiloCourseCode = $row['CourseCode'];
                $status = $row['Status'];

                $courseInfo = api_get_course_info($chamiloCourseCode);

                if (empty($courseInfo)) {
                    $this->logger->addError(
                        'Course does not exists: '.$chamiloCourseCode
                    );
                    continue;
                }

                $userId = UserManager::get_user_id_from_username(
                    $chamiloUserName
                );

                if (empty($userId)) {
                    $this->logger->addError(
                        'User does not exists: '.$chamiloUserName
                    );
                    continue;
                }

                $userCourseCategory = '';
                if (isset($teacherBackup[$userId]) &&
                    isset($teacherBackup[$userId][$courseInfo['code']])
                ) {
                    $courseUserData = $teacherBackup[$userId][$courseInfo['code']];
                    $userCourseCategory = $courseUserData['user_course_cat'];
                }

                $result = CourseManager::subscribeUser(
                    $userId,
                    $courseInfo['code'],
                    $status,
                    0,
                    $userCourseCategory
                );

                if ($result) {
                    $this->logger->addInfo(
                        "User $userId added to course ".$courseInfo['code']." with status '$status' with course category: '$userCourseCategory'"
                    );
                } else {
                    $this->logger->addInfo(
                        "User $userId was NOT ADDED to course ".$courseInfo['code']." with status '$status' with course category: '$userCourseCategory'"
                    );
                }
            }
        }

        if ($moveFile) {
            $this->moveFile($file);
        }
    }

    /**
     * 23/4/2017 to datetime.
     *
     * @param $string
     *
     * @return mixed
     */
    private function createDateTime($string)
    {
        if (empty($string)) {
            return null;
        }

        $date = DateTime::createFromFormat('j/m/Y', $string);
        if ($date) {
            return $date;
        }

        return null;
    }

    /**
     * @param $file
     * @param bool  $moveFile
     * @param array $teacherBackup
     * @param array $groupBackup
     *
     * @return bool
     */
    private function importCareers(
        $file,
        $moveFile = false,
        &$teacherBackup = [],
        &$groupBackup = []
    ) {
        $data = Import::csv_reader($file);

        if (!empty($data)) {
            $this->logger->addInfo(count($data).' records found.');
            $extraFieldValue = new ExtraFieldValue('career');
            $extraFieldName = $this->extraFieldIdNameList['career'];
            $externalEventId = null;

            $extraField = new ExtraField('career');
            $extraFieldInfo = $extraField->get_handler_field_info_by_field_variable($extraFieldName);

            if (empty($extraFieldInfo)) {
                $this->logger->addInfo("Extra field doesn't exists: $extraFieldName");

                return false;
            }

            foreach ($data as $row) {
                foreach ($row as $key => $value) {
                    $key = (string) trim($key);
                    // Remove utf8 bom
                    $key = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $key);
                    $row[$key] = $value;
                }

                $itemId = $row['CareerId'];
                $item = $extraFieldValue->get_item_id_from_field_variable_and_field_value(
                    $extraFieldName,
                    $itemId,
                    false,
                    false,
                    false
                );

                $career = new Career();
                if (empty($item)) {
                    $params = [
                        'status' => 1,
                        'name' => $row['CareerName'],
                    ];
                    $careerId = $career->save($params);
                    if ($careerId) {
                        $this->logger->addInfo('Career saved: '.print_r($params, 1));
                        $params = [
                            'item_id' => $careerId,
                            'extra_'.$extraFieldName => $itemId,
                        ];
                        $links = isset($row['HLinks']) ? $row['HLinks'] : [];
                        if (!empty($links)) {
                            $extraFieldUrlName = $this->extraFieldIdNameList['career_urls'];
                            $extraFieldInfo = $extraField->get_handler_field_info_by_field_variable(
                                $extraFieldUrlName
                            );
                            if (!empty($extraFieldInfo)) {
                                $params['extra_'.$extraFieldUrlName] = $links;
                            }
                        }
                        $extraFieldValue->saveFieldValues($params);
                    }
                } else {
                    if (isset($item['item_id'])) {
                        $params = [
                            'id' => $item['item_id'],
                            'name' => $row['CareerName'],
                        ];
                        $career->update($params);
                        $this->logger->addInfo('Career updated: '.print_r($params, 1));
                        $links = isset($row['HLinks']) ? $row['HLinks'] : [];

                        if (!empty($links)) {
                            $extraFieldUrlName = $this->extraFieldIdNameList['career_urls'];
                            $extraFieldInfo = $extraField->get_handler_field_info_by_field_variable(
                                $extraFieldUrlName
                            );
                            if (!empty($extraFieldInfo)) {
                                $params = [
                                    'item_id' => $item['item_id'],
                                    'extra_'.$extraFieldName => $itemId,
                                    'extra_'.$extraFieldUrlName => $links,
                                ];
                                $extraFieldValue->saveFieldValues($params);
                            }
                        }
                    }
                }
            }
        }

        if ($moveFile) {
            $this->moveFile($file);
        }
    }

    /**
     * @param $file
     * @param bool  $moveFile
     * @param array $teacherBackup
     * @param array $groupBackup
     */
    private function importCareersResultsRemoveStatic(
        $file,
        $moveFile = false
    ) {
        $data = Import::csv_reader($file);

        $careerIdList = [];
        $userIdList = [];

        if (!empty($data)) {
            $totalCount = count($data);
            $this->logger->addInfo($totalCount.' records found.');

            $extraFieldValue = new ExtraFieldValue('career');
            $extraFieldName = $this->extraFieldIdNameList['career'];
            $rowCounter = 0;
            foreach ($data as $row) {
                $this->logger->addInfo("---------- Row: # $rowCounter");
                $rowCounter++;
                if (empty($row)) {
                    continue;
                }

                foreach ($row as $key => $value) {
                    $key = (string) trim($key);
                    // Remove utf8 bom
                    $key = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $key);
                    $row[$key] = $value;
                }

                $rowStudentId = $row['StudentId'];

                if (isset($userIdList[$rowStudentId])) {
                    $studentId = $userIdList[$rowStudentId];
                } else {
                    $studentId = UserManager::get_user_id_from_original_id(
                        $rowStudentId,
                        $this->extraFieldIdNameList['user']
                    );
                    $userIdList[$rowStudentId] = $studentId;
                }

                $careerId = $row['CareerId'];
                if (isset($careerIdList[$careerId])) {
                    $careerChamiloId = $careerIdList[$careerId];
                } else {
                    $item = $extraFieldValue->get_item_id_from_field_variable_and_field_value(
                        $extraFieldName,
                        $careerId
                    );

                    if (empty($item)) {
                        $careerIdList[$careerId] = 0;
                        continue;
                    } else {
                        if (isset($item['item_id'])) {
                            $careerChamiloId = $item['item_id'];
                            $careerIdList[$careerId] = $careerChamiloId;
                        } else {
                            $careerIdList[$careerId] = 0;
                            continue;
                        }
                    }
                }

                if (empty($careerChamiloId)) {
                    $this->logger->addInfo("Career not found: $careerId ");
                    continue;
                }

                $userCareerData = UserManager::getUserCareer($studentId, $careerChamiloId);

                if (empty($userCareerData)) {
                    $this->logger->addInfo(
                        "User chamilo id # $studentId (".$row['StudentId'].") has no career #$careerChamiloId (ext #$careerId)"
                    );
                    continue;
                }

                $extraData = isset($userCareerData['extra_data']) && !empty($userCareerData['extra_data']) ? unserialize($userCareerData['extra_data']) : [];
                unset($extraData[$row['CourseId']][$row['ResultId']]);
                $serializedValue = serialize($extraData);

                UserManager::updateUserCareer($userCareerData['id'], $serializedValue);

                $this->logger->addInfo('Deleting: result id'.$row['ResultId']);
                $this->logger->addInfo(
                    "Saving graph for user chamilo # $studentId (".$row['StudentId'].") with career #$careerChamiloId (ext #$careerId)"
                );
            }
        }

        if ($moveFile) {
            $this->moveFile($file);
        }
    }

    /**
     * @param $file
     * @param bool  $moveFile
     * @param array $teacherBackup
     * @param array $groupBackup
     */
    private function importCareersResults(
        $file,
        $moveFile = false,
        &$teacherBackup = [],
        &$groupBackup = []
    ) {
        $data = Import::csv_reader($file);

        $userTable = Database::get_main_table(TABLE_MAIN_USER);
        $careerIdList = [];
        $userIdList = [];
        if (!empty($data)) {
            $totalCount = count($data);
            $this->logger->addInfo($totalCount.' records found.');

            $extraFieldValue = new ExtraFieldValue('career');
            $extraFieldName = $this->extraFieldIdNameList['career'];
            $rowCounter = 0;
            foreach ($data as $row) {
                $this->logger->addInfo("---------- Row: # $rowCounter");
                $rowCounter++;
                if (empty($row)) {
                    continue;
                }

                foreach ($row as $key => $value) {
                    $key = (string) trim($key);
                    // Remove utf8 bom
                    $key = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $key);
                    $row[$key] = $value;
                }

                $rowStudentId = $row['StudentId'];

                if (isset($userIdList[$rowStudentId])) {
                    $studentId = $userIdList[$rowStudentId];
                } else {
                    $studentId = UserManager::get_user_id_from_original_id(
                        $rowStudentId,
                        $this->extraFieldIdNameList['user']
                    );

                    $userIdList[$rowStudentId] = $studentId;
                }

                $careerId = $row['CareerId'];
                if (isset($careerIdList[$careerId])) {
                    $careerChamiloId = $careerIdList[$careerId];
                } else {
                    $item = $extraFieldValue->get_item_id_from_field_variable_and_field_value(
                        $extraFieldName,
                        $careerId
                    );

                    if (empty($item)) {
                        //$this->logger->addInfo("Career not found: $careerId case 1");
                        $careerIdList[$careerId] = 0;
                        continue;
                    } else {
                        if (isset($item['item_id'])) {
                            $careerChamiloId = $item['item_id'];
                            $careerIdList[$careerId] = $careerChamiloId;
                        } else {
                            $careerIdList[$careerId] = 0;
                            //$this->logger->addInfo("Career not found: $careerId case 2");
                            continue;
                        }
                    }
                }

                if (empty($careerChamiloId)) {
                    $this->logger->addInfo("Career not found: $careerId ");
                    continue;
                }

                $userCareerData = UserManager::getUserCareer($studentId, $careerChamiloId);

                if (empty($userCareerData)) {
                    $this->logger->addInfo(
                        "User chamilo id # $studentId (".$row['StudentId'].") has no career #$careerChamiloId (ext #$careerId)"
                    );
                    continue;
                }

                $extraData = isset($userCareerData['extra_data']) && !empty($userCareerData['extra_data']) ? unserialize($userCareerData['extra_data']) : [];

                $sql = "SELECT firstname, lastname FROM $userTable
                        WHERE username='".Database::escape_string($row['TeacherUsername'])."'";
                $result = Database::query($sql);

                $teacherName = $row['TeacherUsername'];
                if (Database::num_rows($result)) {
                    $teacherInfo = Database::fetch_array($result);
                    $teacherName = $teacherInfo['firstname'].' '.$teacherInfo['lastname'];
                }

                $extraData[$row['CourseId']][$row['ResultId']] = [
                    'Description' => $row['Description'],
                    'Period' => $row['Period'],
                    'TeacherText' => $row['TeacherText'],
                    'TeacherUsername' => $teacherName,
                    'ScoreText' => $row['ScoreText'],
                    'ScoreValue' => $row['ScoreValue'],
                    'Info' => $row['Info'],
                    'BgColor' => $row['BgColor'],
                    'Color' => $row['Color'],
                    'BorderColor' => $row['BorderColor'],
                    'Icon' => $row['Icon'],
                    'IconColor' => $row['IconColor'],
                    'SortDate' => $row['SortDate'] ?? '',
                ];
                $serializedValue = serialize($extraData);

                UserManager::updateUserCareer($userCareerData['id'], $serializedValue);

                $this->logger->addInfo(
                    "Saving graph for user chamilo # $studentId (".$row['StudentId'].") with career #$careerChamiloId (ext #$careerId)"
                );
            }
        }

        if ($moveFile) {
            $this->moveFile($file);
        }
    }

    /**
     * @param $file
     * @param bool  $moveFile
     * @param array $teacherBackup
     * @param array $groupBackup
     */
    private function importCareersDiagram(
        $file,
        $moveFile = false,
        &$teacherBackup = [],
        &$groupBackup = []
    ) {
        $data = Import::csv_reader($file);

        $extraFieldValue = new ExtraFieldValue('career');
        $extraFieldName = $this->extraFieldIdNameList['career'];

        $extraField = new ExtraField('career');
        $extraFieldInfo = $extraField->get_handler_field_info_by_field_variable($extraFieldName);

        $careerDiagramExtraFieldName = $this->extraFieldIdNameList['career_diagram'];
        $extraFieldDiagramInfo = $extraField->get_handler_field_info_by_field_variable(
            $careerDiagramExtraFieldName
        );

        if (empty($extraFieldInfo) || empty($extraFieldDiagramInfo)) {
            return false;
        }

        if (!empty($data)) {
            $this->logger->addInfo(count($data).' records found.');
            $values = [];
            foreach ($data as $row) {
                if (empty($row)) {
                    continue;
                }
                foreach ($row as $key => $value) {
                    $key = (string) trim($key);
                    // Remove utf8 bom
                    $key = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $key);
                    $row[$key] = $value;
                }
                $values[$row['Column']][] = $row;
            }

            $careerList = [];
            $careerNameList = [];
            ksort($values);
            $careerChamiloIdList = [];
            // 1. First create all items
            foreach ($values as $column => $rowList) {
                foreach ($rowList as $row) {
                    $careerId = $row['CareerId'];
                    $item = $extraFieldValue->get_item_id_from_field_variable_and_field_value(
                        $extraFieldName,
                        $careerId,
                        false,
                        false,
                        false
                    );

                    if (empty($item)) {
                        $this->logger->addInfo("Career not found: $careerId");
                        continue;
                    } else {
                        if (isset($item['item_id'])) {
                            $careerChamiloId = $item['item_id'];
                            $career = new Career();
                            $career = $career->find($careerChamiloId);
                            $chamiloCareerName = $career['name'];
                            $careerNameList[$careerId] = $chamiloCareerName;
                            $careerChamiloIdList[$careerId] = $careerChamiloId;
                        } else {
                            continue;
                        }
                    }

                    if (empty($chamiloCareerName)) {
                        $this->logger->addInfo("Career not found: $careerId");
                        continue;
                    }

                    if (isset($careerList[$careerId])) {
                        $graph = $careerList[$careerId];
                    } else {
                        $graph = new Graph($careerId);
                        $graph->setAttribute('graphviz.graph.rankdir', 'LR');
                        $careerList[$careerId] = $graph;
                    }

                    $currentCourseId = $row['CourseId'];
                    $name = $row['CourseName'];
                    $notes = $row['Notes'];
                    $groupValue = $row['Group'];
                    $boxColumn = $row['Column'];
                    $rowValue = $row['Row'];
                    $color = isset($row['DefinedColor']) ? $row['DefinedColor'] : '';
                    $arrow = isset($row['DrawArrowFrom']) ? $row['DrawArrowFrom'] : '';
                    $subGroup = isset($row['SubGroup']) ? $row['SubGroup'] : '';
                    $connections = isset($row['Connections']) ? $row['Connections'] : '';
                    $linkedElement = isset($row['LinkedElement']) ? $row['LinkedElement'] : '';

                    if ($graph->hasVertex($currentCourseId)) {
                        // Avoid double insertion
                        continue;
                    } else {
                        $current = $graph->createVertex($currentCourseId);
                        $current->setAttribute('graphviz.label', $name);
                        $current->setAttribute('DefinedColor', $color);
                        $current->setAttribute('Notes', $notes);
                        $current->setAttribute('Row', $rowValue);
                        $current->setAttribute('Group', $groupValue);
                        $current->setAttribute('Column', $boxColumn);
                        $current->setAttribute('DrawArrowFrom', $arrow);
                        $current->setAttribute('SubGroup', $subGroup);
                        $current->setAttribute('Connections', $connections);
                        $current->setAttribute('LinkedElement', $linkedElement);
                        $current->setAttribute('graphviz.shape', 'box');
                        $current->setGroup($column);
                    }
                }
            }

            // 2. Create connections
            // $column start with 1 (depending in Column row)
            foreach ($values as $column => $rowList) {
                foreach ($rowList as $row) {
                    $careerId = $row['CareerId'];
                    if (isset($careerList[$careerId])) {
                        $graph = $careerList[$careerId];
                    } else {
                        continue;
                    }

                    $currentCourseId = $row['CourseId'];
                    if ($graph->hasVertex($currentCourseId)) {
                        $current = $graph->getVertex($currentCourseId);
                    } else {
                        continue;
                    }

                    if (isset($row['DependedOn']) && !empty($row['DependedOn'])) {
                        $parentList = explode(',', $row['DependedOn']);
                        foreach ($parentList as $parentId) {
                            $parentId = (int) $parentId;
                            if ($graph->hasVertex($parentId)) {
                                /** @var Vertex $parent */
                                $parent = $graph->getVertex($parentId);
                                /*$parent->setAttribute('graphviz.color', 'red');
                                $parent->setAttribute('graphviz.label', $name);
                                $parent->setAttribute('graphviz.shape', 'square');*/
                                $parent->createEdgeTo($current);
                            }
                        }
                    }
                }
            }

            /** @var Graph $graph */
            foreach ($careerList as $id => $graph) {
                if (isset($careerChamiloIdList[$id])) {
                    $params = [
                        'item_id' => $careerChamiloIdList[$id],
                        'extra_'.$careerDiagramExtraFieldName => serialize($graph),
                        'extra_'.$extraFieldName => $id,
                    ];
                    $extraFieldValue->saveFieldValues($params, true);
                }
            }
        }

        if ($moveFile) {
            $this->moveFile($file);
        }
    }

    /**
     * @param string $file
     * @param bool   $moveFile
     * @param array  $teacherBackup
     * @param array  $groupBackup
     */
    private function importUnsubscribeStatic(
        $file,
        $moveFile = false,
        &$teacherBackup = [],
        &$groupBackup = []
    ) {
        $data = Import::csv_reader($file);

        if (!empty($data)) {
            $this->logger->addInfo(count($data)." records found.");
            foreach ($data as $row) {
                $chamiloUserName = $row['UserName'];
                $chamiloCourseCode = $row['CourseCode'];
                $chamiloSessionId = $row['SessionID'];

                $sessionInfo = api_get_session_info($chamiloSessionId);

                if (empty($sessionInfo)) {
                    $this->logger->addError('Session does not exists: '.$chamiloSessionId);
                    continue;
                }

                $courseInfo = api_get_course_info($chamiloCourseCode);
                if (empty($courseInfo)) {
                    $this->logger->addError('Course does not exists: '.$courseInfo);
                    continue;
                }

                $userId = UserManager::get_user_id_from_username($chamiloUserName);

                if (empty($userId)) {
                    $this->logger->addError('User does not exists: '.$chamiloUserName);
                    continue;
                }

                $sql = "SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
                        WHERE
                            user_id = ".$userId." AND
                            c_id = '".$courseInfo['real_id']."'
                        ";
                $result = Database::query($sql);
                $rows = Database::num_rows($result);
                if ($rows > 0) {
                    $userCourseData = Database::fetch_array($result, 'ASSOC');
                    if (!empty($userCourseData)) {
                        $teacherBackup[$userId][$courseInfo['code']] = $userCourseData;
                    }
                }

                $sql = "SELECT * FROM ".Database::get_course_table(TABLE_GROUP_USER)."
                        WHERE
                            user_id = ".$userId." AND
                            c_id = '".$courseInfo['real_id']."'
                        ";

                $result = Database::query($sql);
                while ($groupData = Database::fetch_array($result, 'ASSOC')) {
                    $groupBackup['user'][$userId][$courseInfo['code']][$groupData['group_id']] = $groupData;
                }

                $sql = "SELECT * FROM ".Database::get_course_table(TABLE_GROUP_TUTOR)."
                        WHERE
                            user_id = ".$userId." AND
                            c_id = '".$courseInfo['real_id']."'
                        ";

                $result = Database::query($sql);
                while ($groupData = Database::fetch_array($result, 'ASSOC')) {
                    $groupBackup['tutor'][$userId][$courseInfo['code']][$groupData['group_id']] = $groupData;
                }

                CourseManager::unsubscribe_user(
                    $userId,
                    $courseInfo['code'],
                    $chamiloSessionId
                );

                $this->logger->addError(
                    "User '$chamiloUserName' was removed from session: #$chamiloSessionId, Course: ".$courseInfo['code']
                );
            }
        }

        if ($moveFile) {
            $this->moveFile($file);
        }
    }

    /**
     *  Dump database tables.
     */
    private function dumpDatabaseTables()
    {
        echo 'Dumping tables'.PHP_EOL;

        // User
        $table = Database::get_main_table(TABLE_MAIN_USER);
        $tableAdmin = Database::get_main_table(TABLE_MAIN_ADMIN);
        $sql = "DELETE FROM $table
                WHERE user_id not in (select user_id from $tableAdmin) and status <> ".ANONYMOUS;
        Database::query($sql);
        echo $sql.PHP_EOL;

        // Truncate tables
        $truncateTables = [
            Database::get_main_table(TABLE_MAIN_COURSE),
            Database::get_main_table(TABLE_MAIN_COURSE_USER),
            Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE),
            Database::get_main_table(TABLE_MAIN_CATEGORY),
            Database::get_main_table(TABLE_MAIN_COURSE_MODULE),
            Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER),
            Database::get_main_table(TABLE_MAIN_SESSION),
            Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY),
            Database::get_main_table(TABLE_MAIN_SESSION_COURSE),
            Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER),
            Database::get_main_table(TABLE_MAIN_SESSION_USER),
            Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION),
            Database::get_main_table(TABLE_MAIN_SESSION_FIELD_VALUES),
            Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES),
            Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES),
            Database::get_main_table(TABLE_MAIN_USER_FIELD),
            Database::get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS),
            Database::get_main_table(TABLE_MAIN_COURSE_FIELD),
            Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES),
            Database::get_main_table(TABLE_MAIN_SESSION_FIELD),
            Database::get_main_table(TABLE_MAIN_SESSION_FIELD_VALUES),
            Database::get_course_table(TABLE_AGENDA),
            Database::get_course_table(TABLE_AGENDA_ATTACHMENT),
            Database::get_course_table(TABLE_AGENDA_REPEAT),
            Database::get_course_table(TABLE_AGENDA_REPEAT_NOT),
            Database::get_main_table(TABLE_PERSONAL_AGENDA),
            Database::get_main_table(TABLE_PERSONAL_AGENDA_REPEAT_NOT),
            Database::get_main_table(TABLE_PERSONAL_AGENDA_REPEAT),
            Database::get_main_table(TABLE_MAIN_CALENDAR_EVENT_VALUES),
            Database::get_main_table(TABLE_STATISTIC_TRACK_E_LASTACCESS),
            Database::get_main_table(TABLE_STATISTIC_TRACK_E_ACCESS),
            Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN),
            Database::get_main_table(TABLE_STATISTIC_TRACK_E_DOWNLOADS),
            Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCICES),
            Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT),
            Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING),
            Database::get_main_table(TABLE_STATISTIC_TRACK_E_DEFAULT),
            Database::get_main_table(TABLE_STATISTIC_TRACK_E_UPLOADS),
            Database::get_main_table(TABLE_STATISTIC_TRACK_E_HOTSPOT),
            Database::get_main_table(TABLE_STATISTIC_TRACK_E_ITEM_PROPERTY),
            Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY),
            Database::get_main_table(TABLE_MAIN_GRADEBOOK_EVALUATION),
            Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINKEVAL_LOG),
            Database::get_main_table(TABLE_MAIN_GRADEBOOK_RESULT),
            Database::get_main_table(TABLE_MAIN_GRADEBOOK_RESULT_LOG),
            Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK),
            Database::get_main_table(TABLE_MAIN_GRADEBOOK_SCORE_DISPLAY),
            Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE),
            Database::get_course_table(TABLE_STUDENT_PUBLICATION),
            Database::get_course_table(TABLE_QUIZ_QUESTION),
            Database::get_course_table(TABLE_QUIZ_TEST),
            Database::get_course_table(TABLE_QUIZ_ORDER),
            Database::get_course_table(TABLE_QUIZ_ANSWER),
            Database::get_course_table(TABLE_QUIZ_TEST_QUESTION),
            Database::get_course_table(TABLE_QUIZ_QUESTION_OPTION),
            Database::get_course_table(TABLE_QUIZ_QUESTION_CATEGORY),
            Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY),
            Database::get_course_table(TABLE_LP_MAIN),
            Database::get_course_table(TABLE_LP_ITEM),
            Database::get_course_table(TABLE_LP_VIEW),
            Database::get_course_table(TABLE_LP_ITEM_VIEW),
            Database::get_course_table(TABLE_DOCUMENT),
            Database::get_course_table(TABLE_ITEM_PROPERTY),
            Database::get_course_table(TABLE_TOOL_LIST),
            Database::get_course_table(TABLE_TOOL_INTRO),
            Database::get_course_table(TABLE_COURSE_SETTING),
            Database::get_course_table(TABLE_SURVEY),
            Database::get_course_table(TABLE_SURVEY_QUESTION),
            Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION),
            Database::get_course_table(TABLE_SURVEY_INVITATION),
            Database::get_course_table(TABLE_SURVEY_ANSWER),
            Database::get_course_table(TABLE_SURVEY_QUESTION_GROUP),
            Database::get_course_table(TABLE_SURVEY_REPORT),
            Database::get_course_table(TABLE_GLOSSARY),
            Database::get_course_table(TABLE_LINK),
            Database::get_course_table(TABLE_LINK_CATEGORY),
            Database::get_course_table(TABLE_GROUP),
            Database::get_course_table(TABLE_GROUP_USER),
            Database::get_course_table(TABLE_GROUP_TUTOR),
            Database::get_course_table(TABLE_GROUP_CATEGORY),
            Database::get_course_table(TABLE_DROPBOX_CATEGORY),
            Database::get_course_table(TABLE_DROPBOX_FEEDBACK),
            Database::get_course_table(TABLE_DROPBOX_POST),
            Database::get_course_table(TABLE_DROPBOX_FILE),
            Database::get_course_table(TABLE_DROPBOX_PERSON),
        ];

        foreach ($truncateTables as $table) {
            $sql = "TRUNCATE $table";
            Database::query($sql);
            echo $sql.PHP_EOL;
        }

        $table = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $sql = "DELETE FROM $table WHERE tool = 'calendar_event'";
        Database::query($sql);
        echo $sql.PHP_EOL;
    }

    /**
     * If csv file ends with '"' character then a '";' is added.
     *
     * @param string $file
     */
    private function fixCSVFile($file)
    {
        /*$f = fopen($file, 'r+');
        $cursor = -1;

        fseek($f, $cursor, SEEK_END);
        $char = fgetc($f);
        while ($char === "\n" || $char === "\r") {
            fseek($f, $cursor--, SEEK_END);
            $char = fgetc($f);
        }

        if ($char === "\"") {
            fseek($f, -1, SEEK_CUR);
            fwrite($f, '";');
        }*/
    }

    /**
     * Get custom tpl for mail welcome.
     */
    private function getCustomMailTemplate(): string
    {
        $name = 'mail/custom_calendar_welcome.tpl';
        $sysTemplatePath = api_get_path(SYS_TEMPLATE_PATH);
        if (is_readable($sysTemplatePath.'overrides/'.$name)) {
            return 'overrides/'.$name;
        }
        $customThemeFolder = api_get_configuration_value('default_template');
        if (is_readable($sysTemplatePath.$customThemeFolder.'/'.$name)) {
            return $customThemeFolder.'/'.$name;
        }
        if (is_readable($sysTemplatePath.'default/'.$name)) {
            return 'default/'.$name;
        }
        // If none has been found, it means we don't have a custom mail
        // welcome message, so use the .dist version
        $alternateName = 'mail/custom_calendar_welcome.dist.tpl';

        return 'default/'.$alternateName;
    }
}

$logger = new Logger('cron');
$emails = isset($_configuration['cron_notification_mails']) ? $_configuration['cron_notification_mails'] : null;

$minLevel = Logger::DEBUG;

if (!is_array($emails)) {
    $emails = [$emails];
}
$subject = "Cron main/cron/import_csv.php ".date('Y-m-d h:i:s');
$from = api_get_setting('emailAdministrator');
/*
if (!empty($emails)) {
    foreach ($emails as $email) {
        $stream = new NativeMailerHandler($email, $subject, $from, $minLevel);
        $logger->pushHandler(new BufferHandler($stream, 0, $minLevel));
    }
}*/

$stream = new StreamHandler(
    api_get_path(SYS_ARCHIVE_PATH).'import_csv.log',
    $minLevel
);
$logger->pushHandler(new BufferHandler($stream, 0, $minLevel));
$logger->pushHandler(new RotatingFileHandler('import_csv', 5, $minLevel));

$verbose = false;
if (isset($argv[1]) && $argv[1] === '--verbose') {
    $verbose = true;
}
if ($verbose) {
    $logger->pushHandler(new ErrorLogHandler());
}

$cronImportCSVConditions = isset($_configuration['cron_import_csv_conditions']) ? $_configuration['cron_import_csv_conditions'] : null;

echo 'See the error log here: '.api_get_path(SYS_ARCHIVE_PATH).'import_csv.log'."\n";

$import = new ImportCsv($logger, $cronImportCSVConditions);

if (isset($_configuration['default_admin_user_id_for_cron'])) {
    $import->defaultAdminId = $_configuration['default_admin_user_id_for_cron'];
}
// @todo in production disable the dump option
$dump = false;
if (isset($argv[1]) && $argv[1] === '--dump') {
    $dump = true;
}

if (isset($_configuration['import_csv_disable_dump']) &&
    $_configuration['import_csv_disable_dump'] == true
) {
    $import->setDumpValues(false);
} else {
    $import->setDumpValues($dump);
}

$import->setUpdateEmailToDummy(api_get_configuration_value('update_users_email_to_dummy_except_admins'));

// Do not moves the files to treated
if (isset($_configuration['import_csv_test'])) {
    $import->test = $_configuration['import_csv_test'];
} else {
    $import->test = true;
}

$languageFilesToLoad = api_get_language_files_to_load($import->defaultLanguage);

foreach ($languageFilesToLoad as $languageFile) {
    include $languageFile;
}

// Set default language to be loaded
$language = $import->defaultLanguage;
global $language_interface;
$language_interface = $language;
global $language_interface_initial_value;
$language_interface_initial_value = $language;

$timeStart = microtime(true);
$import->run();
$timeEnd = microtime(true);
$executionTime = round(($timeEnd - $timeStart) / 60, 2);
$logger->addInfo("Total execution Time $executionTime Min");

if (isset($_configuration['import_csv_fix_permissions']) &&
    $_configuration['import_csv_fix_permissions'] == true
) {
    $command = "sudo find ".api_get_path(SYS_COURSE_PATH)." -type d -exec chmod 777 {} \; ";
    echo "Executing: ".$command.PHP_EOL;
    system($command);

    $command = "sudo find ".api_get_path(SYS_CODE_PATH)."upload/users  -type d -exec chmod 777 {} \;";
    echo "Executing: ".$command.PHP_EOL;
    system($command);
}
