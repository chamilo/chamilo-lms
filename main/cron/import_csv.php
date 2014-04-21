<?php
/* For licensing terms, see /license.txt */

if (PHP_SAPI !='cli') {
    die('Run this script through the command line or comment this line in the code');
}

if (file_exists('multiple_url_fix.php')) {
    require 'multiple_url_fix.php';
}

require_once __DIR__.'/../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'log.class.php';

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
    );
    public $defaultAdminId = 1;
    public $defaultSessionVisibility = 1;

    /**
     * When creating a user the expiration date is set to registration date + this value
     * @var int number of years
     */
    public $expirationDateInUserCreation = 1;

    /**
     * When updating a user the expiration date is set to update date + this value
     * @var int number of years
     */
    public $expirationDateInUserUpdate = 1;
    public $daysCoachAccessBeforeBeginning = 30;
    public $daysCoachAccessAfterBeginning = 60;
    public $conditions;

    /**
     * @param Logger $logger
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
     * @return mixed
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

        if (!empty($files)) {
            foreach ($files as $file) {
                $fileInfo = pathinfo($file);
                if ($fileInfo['extension'] == 'csv') {
                    // Checking teachers_yyyymmdd.csv, courses_yyyymmdd.csv, students_yyyymmdd.csv and sessions_yyyymmdd.csv
                    $parts = explode('_', $fileInfo['filename']);
                    $preMethod = ucwords($parts[1]);
                    $preMethod = str_replace('-static', 'Static', $preMethod);
                    $method = 'import'.$preMethod;

                    $isStatic = strpos($method, 'Static');

                    if (method_exists($this, $method)) {
                        if ($method == 'importUnsubscribeStatic' || empty($isStatic)) {
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

            if (empty($fileToProcess)) {
                echo 'Error - no files to process.';
                return 0;
            }

            $this->prepareImport();

            $sections = array('students', 'teachers', 'courses', 'sessions', 'unsubscribe-static');
            foreach ($sections as $section) {
                $this->logger->addInfo("-- Import $section --");

                if (isset($fileToProcess[$section]) && !empty($fileToProcess[$section])) {
                    $files = $fileToProcess[$section];
                    foreach ($files as $fileInfo) {
                        $method = $fileInfo['method'];
                        $file = $fileInfo['file'];

                        echo 'File: '.$file.PHP_EOL;
                        $this->logger->addInfo("Reading file: $file");
                        $this->$method($file, true);
                    }
                }
            }

            $sections = array('students-static', 'teachers-static', 'courses-static', 'sessions-static');
            foreach ($sections as $section) {
                $this->logger->addInfo("-- Import static files $section --");

                if (isset($fileToProcessStatic[$section]) && !empty($fileToProcessStatic[$section])) {
                    $files = $fileToProcessStatic[$section];
                    foreach ($files as $fileInfo) {
                        $method = $fileInfo['method'];
                        $file = $fileInfo['file'];
                        echo 'Static file: '.$file.PHP_EOL;
                        $this->logger->addInfo("Reading static file: $file");
                        $this->$method($file, true);
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
        UserManager::create_extra_field($this->extraFieldIdNameList['user'], 1, 'External user id', null);
        // Create course extra field: extra_external_course_id
        CourseManager::create_course_extra_field($this->extraFieldIdNameList['course'], 1, 'External course id');
        // Create session extra field extra_external_session_id
        SessionManager::create_session_extra_field($this->extraFieldIdNameList['session'], 1, 'External session id');
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
            $this->logger->addError("Error - Cant move file to the treated folder: $file");
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
        $row['auth_source'] = $row['AuthSource'];
        $row['official_code'] = $row['OfficialCode'];
        $row['phone'] = $row['PhoneNumber'];

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
            $teachers = explode(',', $row['Teacher']);
            if (!empty($teachers)) {
                foreach ($teachers as $teacherUserName) {
                    $teacherUserName = trim($teacherUserName);
                    $userInfo = api_get_user_info_from_username($teacherUserName);
                    if (!empty($userInfo)) {
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
        $data = Import::csv_to_array($file);

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
            foreach ($data as $row) {
                $row = $this->cleanUserRow($row);

                $user_id = UserManager::get_user_id_from_original_id($row['extra_'.$this->extraFieldIdNameList['user']], $this->extraFieldIdNameList['user']);
                $userInfo  = array();
                $userInfoByOfficialCode  = null;

                if (!empty($user_id)) {
                    $userInfo = api_get_user_info($user_id);
                    //$userInfo = api_get_user_info_from_username($row['username']);
                    $userInfoByOfficialCode = api_get_user_info_from_official_code($row['official_code']);
                }

                $expirationDate = api_get_utc_datetime(strtotime("+".intval($this->expirationDateInUserCreation)."years"));

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
                        PLATFORM_AUTH_SOURCE, // ?
                        $expirationDate, //'0000-00-00 00:00:00', //$row['expiration_date'], //$expiration_date = '0000-00-00 00:00:00',
                        1, //active
                        0,
                        null, // extra
                        null, //$encrypt_method = '',
                        false //$send_mail = false
                    );

                    if ($userId) {
                        foreach ($row as $key => $value) {
                            if (substr($key, 0, 6) == 'extra_') { //an extra field
                                UserManager::update_extra_field_value($userId, substr($key, 6), $value);
                            }
                        }
                        $this->logger->addInfo("Teachers - User created: ".$row['username']);
                    } else {
                        $this->logger->addError("Teachers - User NOT created: ".$row['username']." ".$row['firstname']." ".$row['lastname']);
                    }
                } else {
                    if (empty($userInfo)) {
                        $this->logger->addError("Teachers - Can't update user :".$row['username']);
                        continue;
                    }

                    $expirationDate = api_get_utc_datetime(strtotime("+".intval($this->expirationDateInUserUpdate)."years"));

                    // Update user
                    $result = UserManager::update_user(
                        $userInfo['user_id'],
                        $row['firstname'], // <<-- changed
                        $row['lastname'],  // <<-- changed
                        $userInfo['username'],
                        null, //$password = null,
                        PLATFORM_AUTH_SOURCE,
                        $userInfo['email'],
                        COURSEMANAGER,
                        $userInfo['official_code'],
                        $userInfo['phone'],
                        $userInfo['picture_uri'],
                        $expirationDate,
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
                            if (substr($key, 0, 6) == 'extra_') { //an extra field
                                UserManager::update_extra_field_value($userInfo['user_id'], substr($key, 6), $value);
                            }
                        }
                        $this->logger->addInfo("Teachers - User updated: ".$row['username']);
                    } else {
                        $this->logger->addError("Teachers - User not updated: ".$row['username']);
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
        $data = Import::csv_to_array($file);

        /*
         * Another users import.
        Unique identifier: official code and username . ok
        Password should never get updated. ok
        If an update should need to occur (because it changed in the .csv), we’ll want that logged. We will handle this manually in that case.
        All other fields should be updateable, though passwords should of course not get updated. ok
        If a user gets deleted (not there anymore),
        He should be set inactive one year after the current date. So I presume you’ll just update the expiration date. We want to grant access to courses up to a year after deletion.
         */

        if (!empty($data)) {
            $language = $this->defaultLanguage;
            $this->logger->addInfo(count($data)." records found.");
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

                $expirationDate = api_get_utc_datetime(strtotime("+".intval($this->expirationDateInUserCreation)."years"));

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
                        PLATFORM_AUTH_SOURCE, // ?
                        $expirationDate, //'0000-00-00 00:00:00', //$row['expiration_date'], //$expiration_date = '0000-00-00 00:00:00',
                        1, //active
                        0,
                        null, // extra
                        null, //$encrypt_method = '',
                        false //$send_mail = false
                    );

                    if ($result) {
                        foreach ($row as $key => $value) {
                            if (substr($key, 0, 6) == 'extra_') { //an extra field
                                UserManager::update_extra_field_value($result, substr($key, 6), $value);
                            }
                        }
                        $this->logger->addInfo("Students - User created: ".$row['username']);
                    } else {
                        $this->logger->addError("Students - User NOT created: ".$row['username']." ".$row['firstname']." ".$row['lastname']);
                    }
                } else {

                    if (empty($userInfo)) {
                        $this->logger->addError("Students - Can't update user :".$row['username']);
                        continue;
                    }

                    if ($row['action'] == 'delete') {
                        // Inactive one year later
                        $userInfo['expiration_date'] = api_get_utc_datetime(api_strtotime(time() + 365*24*60*60));
                    }

                    $password = $row['password']; // change password
                    $email = $row['email']; // change email
                    $resetPassword = 2; // allow password change

                    // Conditions that disables the update of password and email:

                    if (isset($this->conditions['importStudents'])) {
                        if (isset($this->conditions['importStudents']['update']) && isset($this->conditions['importStudents']['update']['avoid'])) {
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
                            $avoidUsersWithPassword = $this->conditions['importStudents']['update']['avoid']['password'];

                            if ($userInfo['password'] != api_get_encrypted_password($row['password']) && in_array($row['password'], $avoidUsersWithPassword)) {
                                $this->logger->addInfo("Students - User password is not updated: ".$row['username']." because the avoid conditions (password).");
                                $password = null;
                                $resetPassword = 0; // disallow password change
                            }
                        }
                    }

                    $expirationDate = api_get_utc_datetime(strtotime("+".intval($this->expirationDateInUserUpdate)."years"));

                    // Update user
                    $result = UserManager::update_user(
                        $userInfo['user_id'],
                        $row['firstname'], // <<-- changed
                        $row['lastname'],  // <<-- changed
                        $row['username'],  // <<-- changed
                        $password, //$password = null,
                        PLATFORM_AUTH_SOURCE,
                        $email,
                        STUDENT,
                        $userInfo['official_code'],
                        $userInfo['phone'],
                        $userInfo['picture_uri'],
                        $expirationDate,
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
                            if (substr($key, 0, 6) == 'extra_') { //an extra field
                                UserManager::update_extra_field_value($userInfo['user_id'], substr($key, 6), $value);
                            }
                        }

                        $this->logger->addInfo("Students - User updated: ".$row['username']);
                    } else {
                        $this->logger->addError("Students - User NOT updated: ".$row['username']." ".$row['firstname']." ".$row['lastname']);
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
     */
    private function importCoursesStatic($file)
    {
        $this->importCourses($file, false);
    }

    /**
     * @param string $file
     * @param bool $moveFile
     */
    private function importCourses($file, $moveFile = true)
    {
        $data = Import::csv_to_array($file);

        //$language = $this->defaultLanguage;

        if (!empty($data)) {
            $this->logger->addInfo(count($data)." records found.");

            foreach ($data as $row) {
                $row = $this->cleanCourseRow($row);
                $courseCode = CourseManager::get_course_id_from_original_id($row['extra_'.$this->extraFieldIdNameList['course']], $this->extraFieldIdNameList['course']);
                $courseInfo = api_get_course_info($courseCode);
                if (empty($courseInfo)) {
                    // Create
                    $params = array();
                    $params['title']                = $row['title'];
                    $params['exemplary_content']    = false;
                    $params['wanted_code']          = $row['course_code'];
                    $params['course_category']      = $row['course_category'];
                    $params['course_language']      = $row['language'];
                    $params['teachers']             = $row['teachers'];

                    $courseInfo = CourseManager::create_course($params);

                    if (!empty($courseInfo)) {
                        CourseManager::update_course_extra_field_value($courseInfo['code'], 'external_course_id', $row['extra_'.$this->extraFieldIdNameList['course']]);
                        $this->logger->addInfo("Courses - Course created ".$courseInfo['code']);
                    } else {
                        $this->logger->addError("Courses - Can't create course:".$row['title']);
                    }
                } else {
                    // Update
                    $params = array(
                        'title' => $row['title'],
                    );

                    $result = CourseManager::update_attributes($courseInfo['real_id'], $params);

                    $addTeacherToSession = isset($courseInfo['add_teachers_to_sessions_courses']) && !empty($courseInfo['add_teachers_to_sessions_courses']) ? true : false;
                    if ($addTeacherToSession) {
                        CourseManager::updateTeachers($courseInfo['id'], $row['teachers'], false, true, false);
                    } else {
                        CourseManager::updateTeachers($courseInfo['id'], $row['teachers'], false, false);
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
     * @param string $file
     */
    private function importSessionsStatic($file)
    {
        $this->importSessions($file, false);
    }

    /**
     * @param string $file
     * @param bool $moveFile
     */
    private function importSessions($file, $moveFile = true)
    {
        $avoid =  null;
        if (isset($this->conditions['importSessions']) && isset($this->conditions['importSessions']['update'])) {
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
            false,
            false,
            true
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
    private function importUnsubscribeStatic($file)
    {
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

                $userId = Usermanager::get_user_id_from_username($chamiloUserName);

                if (empty($userId)) {
                    $this->logger->addError('User does not exists: '.$chamiloUserName);
                    continue;
                }

                CourseManager::unsubscribe_user($userId, $courseInfo['code'], $chamiloSessionId);
                $this->logger->addError("User '$chamiloUserName' was removed from session: #$chamiloSessionId, Course: ".$courseInfo['code']);
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
        //$sql = "DELETE FROM $table WHERE username NOT IN ('admin') AND lastname <> 'Anonymous' ";
        $sql = "DELETE FROM $table WHERE user_id not in (select user_id from $tableAdmin) and status <> ".ANONYMOUS;
        Database::query($sql);
        echo $sql.PHP_EOL;

        // Course
        $table = Database::get_main_table(TABLE_MAIN_COURSE);
        $sql = "DELETE FROM $table";
        Database::query($sql);
        echo $sql.PHP_EOL;

        $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $sql = "DELETE FROM $table";
        Database::query($sql);
        echo $sql.PHP_EOL;

        $table = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $sql = "DELETE FROM $table";
        Database::query($sql);
        echo $sql.PHP_EOL;

        $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $sql = "DELETE FROM $table";
        Database::query($sql);
        echo $sql.PHP_EOL;

        // Sessions
        $table = Database::get_main_table(TABLE_MAIN_SESSION);
        $sql = "DELETE FROM $table";
        Database::query($sql);
        echo $sql.PHP_EOL;

        $table = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);
        $sql = "DELETE FROM $table";
        Database::query($sql);
        echo $sql.PHP_EOL;

        $table = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $sql = "DELETE FROM $table";
        Database::query($sql);
        echo $sql.PHP_EOL;

        $table = Database::get_main_table(TABLE_MAIN_SESSION_USER);
        $sql = "DELETE FROM $table";
        Database::query($sql);
        echo $sql.PHP_EOL;

        $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
        $sql = "DELETE FROM $table";
        Database::query($sql);
        echo $sql.PHP_EOL;

        // Extra fields
        $table = Database::get_main_table(TABLE_MAIN_SESSION_FIELD_VALUES);
        $sql = "DELETE FROM $table";
        Database::query($sql);
        echo $sql.PHP_EOL;

        $table = Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
        $sql = "DELETE FROM $table";
        Database::query($sql);
        echo $sql.PHP_EOL;

        $table = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
        $sql = "DELETE FROM $table";
        Database::query($sql);
        echo $sql.PHP_EOL;
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

$stream = new StreamHandler(api_get_path(SYS_ARCHIVE_PATH).'import_csv.log', $minLevel);
$logger->pushHandler(new BufferHandler($stream, 0, $minLevel));
$logger->pushHandler(new RotatingFileHandler('import_csv', 5, $minLevel));

$cronImportCSVConditions = isset($_configuration['cron_import_csv_conditions']) ? $_configuration['cron_import_csv_conditions'] : null;

$import = new ImportCsv($logger, $cronImportCSVConditions);

if (isset($_configuration['default_admin_user_id_for_cron'])) {
    $import->defaultAdminId = $_configuration['default_admin_user_id_for_cron'];
}
// @todo in production disable the dump option
$dump = false;

if (isset($argv[1]) && $argv[1] = '--dump') {
    $dump = true;
}

if (isset($_configuration['import_csv_disable_dump']) && $_configuration['import_csv_disable_dump'] == true) {
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

$import->run();

if (isset($_configuration['import_csv_fix_permissions']) && $_configuration['import_csv_fix_permissions'] == true) {
    $command = "sudo find ".api_get_path(SYS_COURSE_PATH)." -type d -exec chmod 777 {} \; ";
    echo "Executing: ".$command.PHP_EOL;
    system($command);

    $command = "sudo find ".api_get_path(SYS_CODE_PATH)."upload/users  -type d -exec chmod 777 {} \;";
    echo "Executing: ".$command.PHP_EOL;
    system($command);
}
