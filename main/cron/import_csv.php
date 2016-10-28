<?php
/* For licensing terms, see /license.txt */

if (PHP_SAPI != 'cli') {
    die('Run this script through the command line or comment this line in the code');
}

if (file_exists('multiple_url_fix.php')) {
    require 'multiple_url_fix.php';
}

require_once __DIR__.'/../inc/global.inc.php';

ini_set('memory_limit', -1);
ini_set('max_execution_time', 0);

/**
 * Class ImportCsv
 */
class ImportCsv
{
    private $logger;
    private $dumpValues;
    public $test;
    public $defaultLanguage = 'dutch';
    public $extraFieldIdNameList = array(
        'session' => 'external_session_id',
        'course' => 'external_course_id',
        'user' => 'external_user_id',
        'calendar_event' => 'external_calendar_event_id',
    );
    public $defaultAdminId = 1;
    public $defaultSessionVisibility = 1;

    /**
     * When creating a user the expiration date is set to registration date + this value
     * @var int number of years
     */
    public $expirationDateInUserCreation = 1;

    public $batchSize = 20;

    /**
     * When updating a user the expiration date is set to update date + this value
     * @var int number of years
     */
    public $expirationDateInUserUpdate = 1;
    public $daysCoachAccessBeforeBeginning = 14;
    public $daysCoachAccessAfterBeginning = 14;
    public $conditions;

    /**
     * @param Monolog\Logger $logger
     * @param array
     */
    public function __construct($logger, $conditions)
    {
        $this->logger = $logger;
        $this->conditions = $conditions;
    }

    /**
     * @param bool $dump
     */
    public function setDumpValues($dump)
    {
        $this->dumpValues = $dump;
    }

    /**
     * @return boolean
     */
    public function getDumpValues()
    {
        return $this->dumpValues;
    }

    /**
     * Runs the import process
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

        echo "Reading files: ".PHP_EOL.PHP_EOL;

        $files = scandir($path);
        $fileToProcess = array();
        $fileToProcessStatic = array();
        $teacherBackup = array();
        $groupBackup = array();

        if (!empty($files)) {
            foreach ($files as $file) {
                $fileInfo = pathinfo($file);
                if (isset($fileInfo['extension']) && $fileInfo['extension'] === 'csv') {
                    // Checking teachers_yyyymmdd.csv, courses_yyyymmdd.csv, students_yyyymmdd.csv and sessions_yyyymmdd.csv
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

                    if ($method == 'importSubsessionsextidStatic') {
                        $method = 'importSubscribeUserToCourseSessionExtStatic';
                    }

                    if (method_exists($this, $method)) {
                        if (
                            (
                                $method == 'importSubscribeStatic' ||
                                $method == 'importSubscribeUserToCourse'
                            ) ||
                            empty($isStatic)
                        ) {
                            $fileToProcess[$parts[1]][] = array(
                                'method' => $method,
                                'file' => $path.$fileInfo['basename']
                            );
                        } else {
                            $fileToProcessStatic[$parts[1]][] = array(
                                'method' => $method,
                                'file' => $path.$fileInfo['basename']
                            );
                        }
                    } else {
                        echo "Error - This file '$file' can't be processed.".PHP_EOL;
                        echo "Trying to call $method".PHP_EOL;

                        echo "The file have to has this format:".PHP_EOL;
                        echo "prefix_students_ddmmyyyy.csv, prefix_teachers_ddmmyyyy.csv, prefix_courses_ddmmyyyy.csv, prefix_sessions_ddmmyyyy.csv ".PHP_EOL;
                        exit;
                    }
                }
            }

            if (empty($fileToProcess) && empty($fileToProcessStatic)) {
                echo 'Error - no files to process.';

                return 0;
            }

            $this->prepareImport();

            $sections = array(
                'students',
                'teachers',
                'courses',
                'sessions',
                'subscribe-static',
                'courseinsert-static',
                'unsubscribe-static'
            );

            foreach ($sections as $section) {
                $this->logger->addInfo("-- Import $section --");

                if (isset($fileToProcess[$section]) && !empty($fileToProcess[$section])) {
                    $files = $fileToProcess[$section];
                    foreach ($files as $fileInfo) {
                        $method = $fileInfo['method'];
                        $file = $fileInfo['file'];

                        echo 'File: '.$file.PHP_EOL;
                        $this->logger->addInfo("Reading file: $file");
                        if ($method == 'importSessions') {
                            $this->$method(
                                $file,
                                true,
                                $teacherBackup,
                                $groupBackup
                            );
                        } else {
                            $this->$method($file, true);
                        }
                    }
                }
            }

            $sections = array(
                'students-static',
                'teachers-static',
                'courses-static',
                'sessions-static',
                'calendar-static',
                'sessionsextid-static',
                'unsubscribe-static',
                'unsubsessionsextid-static',
                'subsessionsextid-static'
            );

            foreach ($sections as $section) {
                $this->logger->addInfo("-- Import static files $section --");

                if (isset($fileToProcessStatic[$section]) &&
                    !empty($fileToProcessStatic[$section])
                ) {
                    $files = $fileToProcessStatic[$section];
                    foreach ($files as $fileInfo) {
                        $method = $fileInfo['method'];

                        $file = $fileInfo['file'];
                        echo 'Static file: '.$file.PHP_EOL;
                        $this->logger->addInfo("Reading static file: $file");
                        $this->$method(
                            $file,
                            true,
                            $teacherBackup,
                            $groupBackup
                        );
                    }
                }
            }
        }
    }

    /**
     * Prepares extra fields before the import
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

        // Create session extra field extra_external_session_id
        SessionManager::create_session_extra_field(
            $this->extraFieldIdNameList['session'],
            1,
            'External session id'
        );

        // Create calendar_event extra field extra_external_session_id
        $extraField = new ExtraField('calendar_event');
        $extraField->save(
            array(
                'field_type' => ExtraField::FIELD_TYPE_TEXT,
                'variable' => $this->extraFieldIdNameList['calendar_event'],
                'display_text' => 'External calendar event id',
            )
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

        $row['teachers'] = array();
        if (isset($row['Teacher']) && !empty($row['Teacher'])) {
            //$this->logger->addInfo("Teacher list found: ".$row['Teacher']);
            $teachers = explode(',', $row['Teacher']);
            if (!empty($teachers)) {
                foreach ($teachers as $teacherUserName) {
                    $teacherUserName = trim($teacherUserName);
                    $userInfo = api_get_user_info_from_username($teacherUserName);
                    if (!empty($userInfo)) {
                        //$this->logger->addInfo("Username found: $teacherUserName");
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
     * File to import
     * @param string $file
     */
    private function importTeachersStatic($file)
    {
        $this->importTeachers($file, false);
    }

    /**
     * File to import
     * @param string $file
     * @param bool $moveFile
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

                $user_id = UserManager::get_user_id_from_original_id(
                    $row['extra_'.$this->extraFieldIdNameList['user']],
                    $this->extraFieldIdNameList['user']
                );
                $userInfo = array();
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
                        $row['lastname'],  // <<-- changed
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
    }

    /**
     * @param string $file
     */
    private function importStudentsStatic($file)
    {
        $this->importStudents($file, false);
    }

    /**
     * @param string $file
     * @param bool $moveFile
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

            $expirationDateOnCreate = api_get_utc_datetime(strtotime("+".intval($this->expirationDateInUserCreation)."years"));
            $expirationDateOnUpdate = api_get_utc_datetime(strtotime("+".intval($this->expirationDateInUserUpdate)."years"));

            $counter = 1;

            foreach ($data as $row) {
                $row = $this->cleanUserRow($row);
                $user_id = UserManager::get_user_id_from_original_id(
                    $row['extra_'.$this->extraFieldIdNameList['user']],
                    $this->extraFieldIdNameList['user']
                );

                $userInfo = array();
                $userInfoByOfficialCode = null;
                if (!empty($user_id)) {
                    $userInfo = api_get_user_info($user_id, false, true);
                    $userInfoByOfficialCode = api_get_user_info_from_official_code($row['official_code']);
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
                        $expirationDateOnCreate, //'0000-00-00 00:00:00', //$row['expiration_date'], //$expiration_date = '0000-00-00 00:00:00',
                        1, //active
                        0,
                        null, // extra
                        null, //$encrypt_method = '',
                        false //$send_mail = false
                    );

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
                        $userInfo['expiration_date'] = api_get_utc_datetime(api_strtotime(time() + 365*24*60*60));
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
                                $email = $userInfo['email'];
                            }

                            // 3. Condition
                            if (in_array($userInfo['email'], $avoidUsersWithEmail) && !in_array($row['email'], $avoidUsersWithEmail)) {
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
                        $row['lastname'],  // <<-- changed
                        $row['username'],  // <<-- changed
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

                        $this->logger->addInfo("Students - User updated: ".$row['username']);
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
        $executionTime = round(($timeEnd - $timeStart)/60, 2);
        $this->logger->addInfo("Execution Time for process students: $executionTime Min");

        if ($moveFile) {
            $this->moveFile($file);
        }
    }

    /**
     * @param string $file
     */
    private function importCoursesStatic($file, $moveFile, &$teacherBackup = array(), &$groupBackup = array())
    {
        $this->importCourses($file, false, $teacherBackup, $groupBackup);
    }

    /**
     * @param string $file
     * @param bool $moveFile
     *
     * @return int
     */
    private function importCalendarStatic($file, $moveFile = true)
    {
        $this->fixCSVFile($file);
        $data = Import::csvToArray($file);

        if (!empty($data)) {
            $this->logger->addInfo(count($data)." records found.");
            $eventsToCreate = array();
            $errorFound = false;
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

                if (empty($courseInfo)) {
                    $this->logger->addInfo("Course '$courseCode' does not exists");
                }

                if (empty($sessionId)) {
                    $this->logger->addInfo("external_sessionID: ".$externalSessionId." does not exists.");
                }
                $teacherId = null;

                if (!empty($sessionId) && !empty($courseInfo)) {
                    $courseIncluded = SessionManager::relation_session_course_exist(
                        $sessionId,
                        $courseInfo['real_id']
                    );

                    if ($courseIncluded == false) {
                        $this->logger->addInfo(
                            "Course '$courseCode' is not included in session: $sessionId"
                        );
                        $errorFound = true;
                    } else {
                        $teachers = CourseManager::get_coach_list_from_course_code(
                            $courseInfo['code'],
                            $sessionId
                        );

                        // Getting first teacher.
                        if (!empty($teachers)) {
                            $teacher = current($teachers);
                            $teacherId = $teacher['user_id'];
                        } else {
                            $sessionInfo = api_get_session_info($sessionId);
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
                $comment = $row['comment'];
                $color = isset($row['color']) ? $row['color'] : '';

                $startDateYear = substr($date, 0, 4);
                $startDateMonth = substr($date, 4, 2);
                $startDateDay = substr($date, 6, 8);

                $startDate = $startDateYear.'-'.$startDateMonth.'-'.$startDateDay.' '.$startTime.":00";
                $endDate = $startDateYear.'-'.$startDateMonth.'-'.$startDateDay.' '.$endTime.":00";

                if (!api_is_valid_date($startDate) || !api_is_valid_date($endDate)) {
                    $this->logger->addInfo(
                        "Verify your dates:  '$startDate' : '$endDate' "
                    );
                    $errorFound = true;
                }

                // If old events do nothing.
                /*if (api_strtotime($startDate) < time()) {
                    continue;
                }*/

                if ($errorFound == false) {
                    $eventsToCreate[] = array(
                        'start' => $startDate,
                        'end' => $endDate,
                        'title' => $title,
                        'sender_id' => $teacherId,
                        'course_id' => $courseInfo['real_id'],
                        'session_id' => $sessionId,
                        'comment' => $comment,
                        'color' => $color,
                        $this->extraFieldIdNameList['calendar_event'] => $row['external_calendar_itemID'],
                    );
                }
                $errorFound = false;
            }

            if (empty($eventsToCreate)) {
                $this->logger->addInfo(
                    "No events to add"
                );

                return 0;
            }

            $this->logger->addInfo(
                "Ready to insert events"
            );

            $agenda = new Agenda();

            $extraFieldValue = new ExtraFieldValue('calendar_event');
            $extraFieldName = $this->extraFieldIdNameList['calendar_event'];
            $externalEventId = null;

            $extraField = new ExtraField('calendar_event');
            $extraFieldInfo = $extraField->get_handler_field_info_by_field_variable(
                $extraFieldName
            );

            if (empty($extraFieldInfo)) {
                $this->logger->addInfo(
                    "No calendar event extra field created: $extraFieldName"
                );

                return 0;
            }

            foreach ($eventsToCreate as $event) {
                $update = false;
                $item = null;
                if (!isset($event[$extraFieldName])) {
                    $this->logger->addInfo(
                        "No external_calendar_itemID found. Skipping ..."
                    );
                    continue;
                } else {
                    $externalEventId = $event[$extraFieldName];
                    if (empty($externalEventId)) {
                        $this->logger->addInfo(
                            "external_calendar_itemID was set but empty. Skipping ..."
                        );
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
                        $this->logger->addInfo(
                            "Event #$externalEventId was already added. Updating ..."
                        );
                        $update = true;
                        //continue;
                    } else {
                        $this->logger->addInfo(
                            "Event #$externalEventId was not added. Preparing to be created ..."
                        );
                    }
                }

                $courseInfo = api_get_course_info_by_id($event['course_id']);
                $agenda->set_course($courseInfo);
                $agenda->setType('course');
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
                        "No course found for added: #".$event['course_id']." Skipping ..."
                    );
                    continue;
                }

                if (empty($event['sender_id'])) {
                    $this->logger->addInfo(
                        "No sender found: #".$event['sender_id']." Skipping ..."
                    );
                    continue;
                }

                $content = '';
                if ($update && isset($item['item_id'])) {
                    //the event already exists, just update
                    $eventResult = $agenda->editEvent(
                        $item['item_id'],
                        $event['start'],
                        $event['end'],
                        false,
                        $event['title'],
                        $content,
                        array('everyone'), // $usersToSend
                        array(), //$attachmentArray = array(),
                        [], //$attachmentCommentList
                        null, //$attachmentComment = null,
                        $eventComment,
                        $color
                    );

                    if ($eventResult !== false) {
                        $this->logger->addInfo(
                            "Event updated: #".$item['item_id']
                        );
                    } else {
                        $this->logger->addInfo(
                            "Error while updating event."
                        );
                    }
                } else {
                    // New event. Create it.
                    $eventId = $agenda->addEvent(
                        $event['start'],
                        $event['end'],
                        false,
                        $event['title'],
                        $content,
                        array('everyone'), // $usersToSend
                        false, //$addAsAnnouncement = false
                        null, //  $parentEventId
                        array(), //$attachmentArray = array(),
                        [], //$attachmentCommentList
                        null, //$attachmentComment = null,
                        $eventComment,
                        $color
                    );

                    if (!empty($eventId)) {
                        //$extraFieldValue->is_course_model = true;
                        $extraFieldValue->save(
                            array(
                                'value' => $externalEventId,
                                'field_id' => $extraFieldInfo['id'],
                                'item_id' => $eventId
                            )
                        );
                        $this->logger->addInfo(
                            "Event added: #$eventId"
                        );
                    } else {
                        $this->logger->addInfo(
                            "Error while creating event."
                        );
                    }
                }
            }
        }

        if ($moveFile) {
            $this->moveFile($file);
        }
    }

    /**
     * @param string $file
     * @param bool $moveFile
     * @param array $teacherBackup
     * @param array $groupBackup
     */
    private function importCourses(
        $file,
        $moveFile = true,
        &$teacherBackup = array(),
        &$groupBackup = array()
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
                    $params = array();
                    $params['title'] = $row['title'];
                    $params['exemplary_content'] = false;
                    $params['wanted_code'] = $row['course_code'];
                    $params['course_category'] = $row['course_category'];
                    $params['course_language'] = $row['language'];
                    $params['teachers'] = $row['teachers'];

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

                        $this->logger->addInfo("Courses - Course created ".$courseInfo['code']);
                    } else {
                        $this->logger->addError("Courses - Can't create course:".$row['title']);
                    }
                } else {
                    // Update
                    $params = array(
                        'title' => $row['title'],
                        'category_code' => $row['course_category']
                    );

                    $result = CourseManager::update_attributes(
                        $courseInfo['real_id'],
                        $params
                    );

                    $addTeacherToSession = isset($courseInfo['add_teachers_to_sessions_courses']) && !empty($courseInfo['add_teachers_to_sessions_courses']) ? true : false;

                    $teachers = $row['teachers'];
                    if (!is_array($teachers)) {
                        $teachers = array($teachers);
                    }

                    if ($addTeacherToSession) {
                        CourseManager::updateTeachers(
                            $courseInfo,
                            $row['teachers'],
                            false,
                            true,
                            false,
                            $teacherBackup
                        );
                    } else {
                        CourseManager::updateTeachers(
                            $courseInfo,
                            $row['teachers'],
                            true,
                            false,
                            false,
                            $teacherBackup
                        );
                    }

                    foreach ($teachers as $teacherId) {
                        if (isset($groupBackup['tutor'][$teacherId]) &&
                            isset($groupBackup['tutor'][$teacherId][$courseInfo['code']])
                        ) {
                            foreach ($groupBackup['tutor'][$teacherId][$courseInfo['code']] as $data) {
                                GroupManager::subscribe_tutors(
                                    array($teacherId),
                                    $data['group_id'],
                                    $data['c_id']
                                );
                            }
                        }

                        if (isset($groupBackup['user'][$teacherId]) &&
                            isset($groupBackup['user'][$teacherId][$courseInfo['code']]) &&
                            !empty($groupBackup['user'][$teacherId][$courseInfo['code']])
                        ) {
                            foreach ($groupBackup['user'][$teacherId][$courseInfo['code']] as $data) {
                                GroupManager::subscribe_users(
                                    array($teacherId),
                                    $data['group_id'],
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
     * Parse filename: encora_subsessionsextid-static_31082016.csv
     * @param string $file
     */
    private function importSubscribeUserToCourseSessionExtStatic($file)
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
                            array($userId),
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
    }

    /**
     * @param string $file
     */
    private function importUnsubSessionsExtIdStatic($file)
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
                    array($userId),
                    $chamiloSessionId,
                    $courseInfo
                );

                $this->logger->addError(
                    "User '$chamiloUserName' was remove from Session: #$chamiloSessionId - Course: " . $courseInfo['code']
                );

            }
        }

    }

    /**
     *
     * @param string $file
     */
    private function importSessionsExtIdStatic($file)
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
                            array($userId),
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
                    "User '$chamiloUserName' with status $type was added to session: #$chamiloSessionId - Course: " . $courseInfo['code']
                );
            }
        }
    }

    /**
     * Updates the session synchronize with the csv file.
     * @param string $file
     */
    private function importSessionsStatic($file)
    {
        $content = file($file);
        $sessions = array();
        $tag_names = array();

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
                if (!in_array('SessionName', $tag_names) || !in_array(
                        'DateStart',
                        $tag_names
                    ) || !in_array('DateEnd', $tag_names)
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

                    $date = new \DateTime($dateStart);
                    $interval = new DateInterval('P'.$this->daysCoachAccessAfterBeginning.'D');
                    $date->add($interval);
                    $coachAfter = $date->format('Y-m-d h:i');

                    $dateStart = api_get_utc_datetime($dateStart);
                    $dateEnd = api_get_utc_datetime($dateEnd);
                    $coachBefore = api_get_utc_datetime($coachBefore);
                    $coachAfter = api_get_utc_datetime($coachAfter);

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
                            $params = array(
                                'description' => $session['SessionDescription']
                            );
                            Database::update(
                                $tbl_session,
                                $params,
                                array('id = ?' => $sessionId)
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
                                    'course_users' => $courseUsers
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
                                $coachList = array();
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
                                $userList = array();
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
    }

    /**
     * @param string $file
     * @param bool $moveFile
     * @param array $teacherBackup
     * @param array $groupBackup
     */
    private function importSessions(
        $file,
        $moveFile = true,
        &$teacherBackup = array(),
        &$groupBackup = array()
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
            array('SessionID' => 'extra_'.$this->extraFieldIdNameList['session']),
            $this->extraFieldIdNameList['session'],
            $this->daysCoachAccessBeforeBeginning,
            $this->daysCoachAccessAfterBeginning,
            $this->defaultSessionVisibility,
            $avoid,
            false, // deleteUsersNotInList
            false, // updateCourseCoaches
            true, // sessionWithCoursesModifier
            true, //$addOriginalCourseTeachersAsCourseSessionCoaches
            true, //$removeAllTeachersFromCourse
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
     */
    private function importSubscribeStatic($file)
    {
        $data = Import::csv_reader($file);

        if (!empty($data)) {
            $this->logger->addInfo(count($data) . " records found.");
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
                            array($userId),
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
                    "User '$chamiloUserName' with status $type was added to session: #$chamiloSessionId - Course: " . $courseInfo['code']
                );
            }
        }
    }

    /**
     * @param $file
     * @param bool $moveFile
     */
    private function importSubscribeUserToCourse($file, $moveFile = false)
    {
        $data = Import::csv_reader($file);

        if (!empty($data)) {
            $this->logger->addInfo(count($data) . " records found.");
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

                CourseManager::subscribe_user(
                    $userId,
                    $courseInfo['code'],
                    $status
                );
                $this->logger->addInfo(
                    "User $userId added to course $chamiloCourseCode as $status"
                );
            }
        }
    }

    /**
     * @param string $file
     */
    private function importUnsubscribeStatic(
        $file,
        $moveFile = false,
        &$teacherBackup = array(),
        &$groupBackup = array()
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
                            course_code = '".$courseInfo['code']."'
                        ";

                $result = Database::query($sql);
                $userCourseData = Database::fetch_array($result, 'ASSOC');
                $teacherBackup[$userId][$courseInfo['code']] = $userCourseData;

                $sql = "SELECT * FROM ".Database::get_course_table(
                        TABLE_GROUP_USER
                    )."
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
    }

    /**
     *  Dump database tables
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
        $truncateTables = array(
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
            Database::get_course_table(TABLE_DROPBOX_PERSON)
        );

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
     * If csv file ends with '"' character then a '";' is added
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
}

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\NativeMailerHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\BufferHandler;

$logger = new Logger('cron');
$emails = isset($_configuration['cron_notification_mails']) ? $_configuration['cron_notification_mails'] : null;

$minLevel = Logger::DEBUG;

if (!is_array($emails)) {
    $emails = array($emails);
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

$cronImportCSVConditions = isset($_configuration['cron_import_csv_conditions']) ? $_configuration['cron_import_csv_conditions'] : null;

echo 'See the error log here: '.api_get_path(SYS_ARCHIVE_PATH).'import_csv.log'."\n";

$import = new ImportCsv($logger, $cronImportCSVConditions);

if (isset($_configuration['default_admin_user_id_for_cron'])) {
    $import->defaultAdminId = $_configuration['default_admin_user_id_for_cron'];
}
// @todo in production disable the dump option
$dump = false;

if (isset($argv[1]) && $argv[1] = '--dump') {
    $dump = true;
}

if (isset($_configuration['import_csv_disable_dump']) &&
    $_configuration['import_csv_disable_dump'] == true
) {
    $import->setDumpValues(false);
} else {
    $import->setDumpValues($dump);
}

// Do not moves the files to treated
if (isset($_configuration['import_csv_test'])) {
    $import->test = $_configuration['import_csv_test'];
} else {
    $import->test = true;
}

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
