<?php
/* For licensing terms, see /license.txt */

if (PHP_SAPI!='cli') {
    die('Run this script through the command line or comment this line in the code');
}

require_once __DIR__.'/../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'log.class.php';

class ImportCsv
{
    private $logger;

    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    public function run()
    {
        $path = api_get_path(SYS_CODE_PATH).'cron/incoming/';
        $files = scandir($path);
        if (!empty($files)) {
            foreach ($files as $file) {
                $fileInfo = pathinfo($file);
                if ($fileInfo['extension'] == 'csv') {
                    // teachers_yyyymmdd.csv, courses_yyyymmdd.csv, students_yyyymmdd.csv and sessions_yyyymmdd.csv
                    $parts = explode('_', $fileInfo['filename']);
                    $method = 'import'.ucwords($parts[0]);

                    if (method_exists($this, $method)) {
                        $this->$method($path.$fileInfo['basename']);
                    } else {
                        echo "Error - This file can't be processed.";
                    }
                }
            }
        }
    }

    /**
     * @param string $file
     */
    private function moveFile($file)
    {
        $moved = str_replace('incoming', 'treated', $file);
        // $result = rename($file, $moved);
        $result = 1;
        if ($result) {
            $this->logger->addInfo("Moving file to the treated folder: $file");
        } else {
            $this->logger->addError("Error - Cant move file to the treated folder: $file");
        }
    }

    /**
     * File to import
     * @param string $file
     */
    private function importTeachers($file)
    {
        $data = Import::csv_to_array($file);
        $this->logger->addInfo("-- Import Teachers --");
        $this->logger->addInfo("Reading file: $file");

        /* Unique identifier: official-code username.
        Email address and password should never get updated. *ok
        The only fields that I can think of that should update if the data changes in the csv file are FirstName and LastName. *ok
        A slight edit of these fields should be taken into account. ???
        Adding teachers is no problem, but deleting them shouldn’t be automated, but we should get a log of “to delete teachers”.
        We’ll handle that manually if applicable.
        No delete!
        */

        if (!empty($data)) {
            foreach ($data as $row) {
                $userInfo = api_get_user_info_from_username($row['username']);
                $userInfoByOfficialCode = api_get_user_info_from_official_code($row['official_code']);

                if (empty($userInfo) && empty($userInfoByOfficialCode)) {
                    // Create user
                    $result = UserManager::create_user(
                        $row['firstname'],
                        $row['lastname'],
                        COURSEMANAGER,
                        $row['email'],
                        $row['username'],
                        $row['password'],
                        $row['official_code'],
                        $row['language'],
                        $row['phone'],
                        $row['picture'], //picture
                        PLATFORM_AUTH_SOURCE, // ?
                        $row['expiration_date'], //$expiration_date = '0000-00-00 00:00:00',
                        1, //active
                        0,
                        null, // extra
                        null, //$encrypt_method = '',
                        false //$send_mail = false
                    );

                    if ($result) {
                        $this->logger->addInfo("Info - Teachers - User created: ".$row['username']);
                    } else {
                        $this->logger->addError("Error - Teachers - User NOT created: ".$row['username']." ".$row['firstname']." ".$row['lastname']);
                    }
                } else {
                    if (empty($userInfo)) {
                        $this->logger->addError("Error - Teachers - Can't update user :".$row['username']);
                        continue;
                    }

                    // Update user
                    $result = UserManager::update_user(
                        $userInfo['user_id'],
                        $row['firstname'], // <<-- changed
                        $row['lastname'],  // <<-- changed
                        $userInfo['username'],
                        null, //$password = null,
                        $auth_source = null,
                        $userInfo['email'],
                        COURSEMANAGER,
                        $userInfo['official_code'],
                        $userInfo['phone'],
                        $userInfo['picture_uri'],
                        $userInfo['expiration_date'],
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
                        $this->logger->addInfo("Teachers - User updated: ".$row['username']);
                    } else {
                        $this->logger->addError("Teachers - User not updated: ".$row['username']);
                    }
                }

                // UserManager::delete_user();
                $this->moveFile($file);
            }
        }
    }

    /**
     * @param string $file
     */
    private function importStudents($file)
    {
        $data = Import::csv_to_array($file);
        $this->logger->addInfo("-- Import Students --");
        $this->logger->addInfo("Reading file: $file");

        /*
         * Another users import.
        Unique identifier: official code and username . ok
        Username and password should never get updated. ok
        If an update should need to occur (because it changed in the .csv), we’ll want that logged. We will handle this manually in that case.
        All other fields should be updateable, though passwords should of course not get updated. ok
        If a user gets deleted (not there anymore),
        He should be set inactive one year after the current date. So I presume you’ll just update the expiration date. We want to grant access to courses up to a year after deletion.
         */
        if (!empty($data)) {
            foreach ($data as $row) {
                $userInfo = api_get_user_info_from_username($row['username']);
                $userInfoByOfficialCode = api_get_user_info_from_official_code($row['official_code']);

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
                        $row['language'],
                        $row['phone'],
                        $row['picture'], //picture
                        PLATFORM_AUTH_SOURCE, // ?
                        $row['expiration_date'], //$expiration_date = '0000-00-00 00:00:00',
                        1, //active
                        0,
                        null, // extra
                        null, //$encrypt_method = '',
                        false //$send_mail = false
                    );

                    if ($result) {
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
                        // INactive one year later
                        $userInfo['expiration_date'] = api_get_utc_datetime(api_strtotime(time() + 365*24*60*60));
                    }

                    // Update user
                    $result = UserManager::update_user(
                        $userInfo['user_id'],
                        $row['firstname'], // <<-- changed
                        $row['lastname'],  // <<-- changed
                        $userInfo['username'],
                        null, //$password = null,
                        $auth_source = null,
                        $userInfo['email'],
                        STUDENT,
                        $userInfo['official_code'],
                        $userInfo['phone'],
                        $userInfo['picture_uri'],
                        $userInfo['expiration_date'],
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
                        $this->logger->addInfo("Students - User updated: ".$row['username']);
                    } else {
                        $this->logger->addError("Students - User NOT updated: ".$row['username']." ".$row['firstname']." ".$row['lastname']);
                    }
                }

                // UserManager::delete_user();
                $this->moveFile($file);
            }
        }

        $this->moveFile($file);
    }

    /**
     * @param string $file
     */
    private function importCourses($file)
    {
        $data = Import::csv_to_array($file);
        $this->logger->addInfo("Reading file: $file");

        if (!empty($data)) {
            foreach ($data as $row) {
                $courseInfo = api_get_course_info($row['course_code']);
                if (empty($courseInfo)) {
                    // Create
                    $params = array();
                    $params['title']                = $row['title'];
                    $params['exemplary_content']    = false;
                    $params['wanted_code']          = $row['course_code'];
                    $params['category_code']        = $row['category_code'];
                    $params['course_language']      = $row['language'];
                    //$params['gradebook_model_id']   = isset($course_values['gradebook_model_id']) ? $course_values['gradebook_model_id'] : null;
                    $courseInfo = CourseManager::create_course($params);

                    if (!empty($courseInfo)) {
                        $this->logger->addInfo("Courses - Course created ".$courseInfo['code']);
                    } else {
                        $this->logger->addError("Courses - Can't create course:".$row['title']);
                    }

                } else {
                    // Update
                    $params = array(
                        'title' => $row['title'],
                    );

                    $result = CourseManager::update_attributes($courseInfo['id'], $params);

                    if ($result) {
                        $this->logger->addInfo("Courses - Course updated ".$courseInfo['code']);
                    } else {
                        $this->logger->addError("Courses - Course NOT updated ".$courseInfo['code']);
                    }

                    /*course_language='".Database::escape_string($course_language)."',
                    title='".Database::escape_string($title)."',
                    category_code='".Database::escape_string($category_code)."',
                    tutor_name='".Database::escape_string($tutor_name)."',
                    visual_code='".Database::escape_string($visual_code)."',
                    department_name='".Database::escape_string($department_name)."',
                    department_url='".Database::escape_string($department_url)."',
                    disk_quota='".Database::escape_string($disk_quota)."',
                    visibility = '".Database::escape_string($visibility)."',
                    subscribe = '".Database::escape_string($subscribe)."',
                    unsubscribe='*/
                }
            }
        }
        $this->moveFile($file);
    }

    /**
     * @param string $file
     */
    private function importSessions($file)
    {
        $result = SessionManager::importCSV($file, true, 1, $this->logger);

        if (!empty($result['error_message'])) {
            $this->logger->addError($result['error_message']);
        }
        $this->logger->addInfo("Sessions - Sessions parsed: ".$result['session_counter']);
        $this->moveFile($file);
    }

    /**
     * @param string $file
     */
    private function unsubscribeUsers($file)
    {
        $data = Import::csv_to_array($file);
        $this->logger->addInfo("-- Unsubscribe Users --");
        $this->logger->addInfo("Reading file: $file");

    }

}
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\NativeMailerHandler;
use Monolog\Handler\RotatingFileHandler;

//use Monolog\Handler\SwiftMailerHandler;
//require_once api_get_path(LIBRARY_PATH).'swiftmailer/lib/swift_required.php';

$logger = new Logger('cron');

$to = "";
$subject = "Cron main/cron/import_csv.php ".date('Y-m-d h:i:s');
$from = api_get_setting('emailAdministrator');
$logger->pushHandler(new NativeMailerHandler($to, $subject, $from, Logger::ERROR));

$logger->pushHandler(new StreamHandler(api_get_path(SYS_ARCHIVE_PATH).'import_csv.log'), Logger::ERROR);
$logger->pushHandler(new RotatingFileHandler('import_csv', 5, Logger::ERROR));

$import = new ImportCsv($logger);
$import->run();
