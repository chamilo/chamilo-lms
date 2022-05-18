<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Session;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script(true);
api_protect_limit_for_session_admin();

$formSent = 0;
$tblUser = Database::get_main_table(TABLE_MAIN_USER);
$tblSession = Database::get_main_table(TABLE_MAIN_SESSION);
$tblSessionUser = Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tblSessionCourse = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tblSessionCourseUser = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

$toolName = get_lang('Import sessions list');

//$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('Administration'));
$interbreadcrumb[] = ['url' => 'session_list.php', 'name' => get_lang('Session list')];

set_time_limit(0);

// Set this option to true to enforce strict purification for usenames.
$purificationOptionForUsernames = false;
$insertedInCourse = [];
$errorMessage = '';
$warn = null;
$updatesession = null;
$userInfo = api_get_user_info();

if (isset($_POST['formSent']) && $_POST['formSent']) {
    if (isset($_FILES['import_file']['tmp_name']) &&
        !empty($_FILES['import_file']['tmp_name'])
    ) {
        $formSent = $_POST['formSent'];
        $fileType = isset($_POST['file_type']) ? $_POST['file_type'] : null;
        $sendMail = isset($_POST['sendMail']) && $_POST['sendMail'] ? 1 : 0;
        $isOverwrite = isset($_POST['overwrite']) && $_POST['overwrite'] ? true : false;
        $deleteUsersNotInList = isset($_POST['delete_users_not_in_list']) ? true : false;
        $sessions = [];
        $sessionCounter = 0;

        if ('xml' === $fileType) {
            // XML
            // SimpleXML for PHP5 deals with various encodings, but how many they are, what are version issues,
            // do we need to waste time with configuration options?
            // For avoiding complications we go some sort of "PHP4 way" - we convert the input xml-file into UTF-8
            // before passing it to the parser.
            // Instead of:
            // $root = @simplexml_load_file($_FILES['import_file']['tmp_name']);
            // we may use the following construct:
            // $root = @simplexml_load_string(api_utf8_encode_xml(file_get_contents($_FILES['import_file']['tmp_name'])));
            // To ease debugging let us use:
            $content = file_get_contents($_FILES['import_file']['tmp_name']);

            $content = api_utf8_encode_xml($content);
            $root = @simplexml_load_string($content);
            unset($content);

            if (is_object($root)) {
                if (count($root->Users->User) > 0) {
                    // Creating/updating users from <Sessions> <Users> base node.
                    foreach ($root->Users->User as $nodeUser) {
                        $username = $usernameOld = trim(api_utf8_decode($nodeUser->Username));
                        if (UserManager::is_username_available($username)) {
                            $password = api_utf8_decode($nodeUser->Password);
                            if (empty($password)) {
                                $password = api_generate_password();
                            }
                            switch ($nodeUser->Status) {
                                case 'student':
                                    $status = 5;
                                    break;
                                case 'teacher':
                                    $status = 1;
                                    break;
                                default:
                                    $status = 5;
                                    $errorMessage .= get_lang('Learner status has been given to').' : '.$username.'<br />';
                            }

                            $result = UserManager::create_user(
                                api_utf8_decode($nodeUser->Firstname),
                                api_utf8_decode($nodeUser->Lastname),
                                $status,
                                api_utf8_decode($nodeUser->Email),
                                $username,
                                $password,
                                api_utf8_decode($nodeUser->OfficialCode),
                                null,
                                api_utf8_decode($nodeUser->Phone),
                                null,
                                PLATFORM_AUTH_SOURCE,
                                null,
                                1,
                                0,
                                null,
                                null,
                                $sendMail
                            );
                        } else {
                            $lastname = trim(api_utf8_decode($nodeUser->Lastname));
                            $firstname = trim(api_utf8_decode($nodeUser->Firstname));
                            $password = api_utf8_decode($nodeUser->Password);
                            $email = trim(api_utf8_decode($nodeUser->Email));
                            $officialCode = trim(api_utf8_decode($nodeUser->OfficialCode));
                            $phone = trim(api_utf8_decode($nodeUser->Phone));
                            $status = trim(api_utf8_decode($nodeUser->Status));
                            switch ($status) {
                                case 'student':
                                    $status = 5;
                                    break;
                                case 'teacher':
                                    $status = 1;
                                    break;
                                default:
                                    $status = 5;
                                    $errorMessage .= get_lang('Learner status has been given to').' : '.$username.'<br />';
                            }

                            $userId = UserManager::get_user_id_from_username($username);

                            if (!empty($userId)) {
                                UserManager::update_user(
                                    $userId,
                                    $firstname,
                                    $lastname,
                                    $username,
                                    $password,
                                    null,
                                    $email,
                                    $status,
                                    $officialCode,
                                    $phone,
                                    null, //$picture_uri,
                                    null, //$expiration_date,
                                    null, //$active,
                                    null, //$creator_id = null,
                                    0,
                                    null, //$extra = null,
                                    null, //$language = 'english',
                                    null, //$encrypt_method = '',
                                    false,
                                    0 //$reset_password = 0
                                );
                            }
                        }
                    }
                }

                // Creating  courses from <Sessions> <Courses> base node.
                if (count($root->Courses->Course) > 0) {
                    foreach ($root->Courses->Course as $courseNode) {
                        $params = [];
                        if (empty($courseNode->CourseTitle)) {
                            $params['title'] = api_utf8_decode($courseNode->CourseCode);
                        } else {
                            $params['title'] = api_utf8_decode($courseNode->CourseTitle);
                        }
                        $params['wanted_code'] = api_utf8_decode($courseNode->CourseCode);
                        $params['tutor_name'] = null;
                        $params['course_category'] = null;
                        $params['course_language'] = api_utf8_decode($courseNode->CourseLanguage);
                        $params['user_id'] = api_get_user_id();

                        // Looking up for the teacher.
                        $username = trim(api_utf8_decode($courseNode->CourseTeacher));
                        $sql = "SELECT id, lastname, firstname FROM $tblUser WHERE username='$username'";
                        $rs = Database::query($sql);
                        if (Database::num_rows($rs) > 0) {
                            [$userId, $lastname, $firstname] = Database::fetch_array($rs);
                            $params['teachers'] = $userId;
                        } else {
                            $params['teachers'] = api_get_user_id();
                        }
                        CourseManager::create_course($params);
                    }
                }

                // Creating sessions from <Sessions> base node.
                if (count($root->Session) > 0) {
                    foreach ($root->Session as $nodeSession) {
                        $courseCounter = 0;
                        $userCounter = 0;

                        $sessionName = trim(api_utf8_decode($nodeSession->SessionName));
                        $coach = UserManager::purify_username(
                            api_utf8_decode($nodeSession->Coach),
                            $purificationOptionForUsernames
                        );

                        if (!empty($coach)) {
                            $coachId = UserManager::get_user_id_from_username($coach);
                            if (false === $coachId) {
                                $errorMessage .= get_lang('This user doesn\'t exist').' : '.$coach.'<br />';
                                // Forcing the coach id if user does not exist.
                                $coachId = api_get_user_id();
                            }
                        } else {
                            // Forcing the coach id.
                            $coachId = api_get_user_id();
                        }

                        // Just in case - encoding conversion.
                        $dStart = trim(api_utf8_decode($nodeSession->DateStart));

                        if (!empty($dStart)) {
                            [$yearStart, $monthStart, $dayStart] = explode('/', $dStart);
                            if (empty($yearStart) || empty($monthStart) || empty($dayStart)) {
                                $errorMessage .= get_lang('Wrong date format (yyyy-mm-dd)').' : '.$dStart.'<br />';
                                break;
                            } else {
                                $timeStart = mktime(0, 0, 0, (int) $monthStart, (int) $dayStart, (int) $yearStart);
                            }

                            $dateEnd = trim(api_utf8_decode($nodeSession->DateEnd));
                            if (!empty($dStart)) {
                                [$yearEnd, $monthEnd, $dayEnd] = explode('/', $dateEnd);
                                if (empty($yearEnd) || empty($monthEnd) || empty($dayEnd)) {
                                    $errorMessage .= get_lang('Error').' : '.$dateEnd.'<br />';
                                    break;
                                } else {
                                    $timeEnd = mktime(0, 0, 0, $monthEnd, $dayEnd, $yearEnd);
                                }
                            }
                            if ($timeEnd - $timeStart < 0) {
                                $errorMessage .= get_lang('The first date should be before the end date').' : '.$dateEnd.'<br />';
                            }
                        }

                        // Default visibility
                        $visibilityAfterExpirationPerSession = 1;
                        if (isset($nodeSession->VisibilityAfterExpiration)) {
                            $visibility = trim(api_utf8_decode($nodeSession->VisibilityAfterExpiration));
                            switch ($visibility) {
                                case 'read_only':
                                    $visibilityAfterExpirationPerSession = SESSION_VISIBLE_READ_ONLY;
                                    break;
                                case 'accessible':
                                    $visibilityAfterExpirationPerSession = SESSION_VISIBLE;
                                    break;
                                case 'not_accessible':
                                    $visibilityAfterExpirationPerSession = SESSION_INVISIBLE;
                                    break;
                            }
                        }
                        $sessionCategoryId = trim(api_utf8_decode($nodeSession->SessionCategory));

                        if (!$isOverwrite) {
                            // Always create a session.
                            $uniqueName = false; // This MUST be initializead.
                            $i = 0;
                            $suffix = '';
                            // Change session name, verify that session doesn't exist.
                            while (!$uniqueName) {
                                if ($i > 1) {
                                    $suffix = ' - '.$i;
                                }
                                $sql = 'SELECT id FROM '.$tblSession.'
                                        WHERE name="'.Database::escape_string($sessionName.$suffix).'"';
                                $rs = Database::query($sql);
                                if (Database::result($rs, 0, 0)) {
                                    $i++;
                                } else {
                                    $uniqueName = true;
                                    $sessionName .= $suffix;
                                }
                            }

                            // Creating the session.
                            $sqlSession = "INSERT IGNORE INTO $tblSession SET
                                    name = '".Database::escape_string($sessionName)."',
                                    access_start_date = '$dStart',
                                    access_end_date = '$dateEnd',
                                    visibility = '$visibilityAfterExpirationPerSession',
                                    nbr_users = 0,
                                    nbr_courses = 0,
                                    nbr_classes = 0,
                                    status = 0,
                                    duration = 0,
                                    session_category_id = '$sessionCategoryId'";
                            $rsSession = Database::query($sqlSession);
                            $sessionId = Database::insert_id();
                            Database::insert(
                                $tblSessionUser,
                                [
                                    'relation_type' => Session::GENERAL_COACH,
                                    'duration' => 0,
                                    'registered_at' => api_get_utc_datetime(),
                                    'user_id' => $coachId,
                                    'session_id' => $sessionId,
                                ]
                            );
                            Database::insert(
                                $tblSessionUser,
                                [
                                    'relation_type' => Session::SESSION_ADMIN,
                                    'duration' => 0,
                                    'registered_at' => api_get_utc_datetime(),
                                    'user_id' => (int) $userInfo['user_id'],
                                    'session_id' => $sessionId,
                                ]
                            );
                            $sessionCounter++;
                        } else {
                            // Update the session if it is needed.
                            $mySessionResult = SessionManager::get_session_by_name($sessionName);
                            if (false === $mySessionResult) {
                                // Creating the session.
                                $sqlSession = "INSERT IGNORE INTO $tblSession SET
                                        name = '".Database::escape_string($sessionName)."',
                                        access_start_date = '$dStart',
                                        access_end_date = '$dateEnd',
                                        visibility = '$visibilityAfterExpirationPerSession',
                                        nbr_users = 0,
                                        nbr_courses = 0,
                                        nbr_classes = 0,
                                        status = 0,
                                        duration = 0,
                                        session_category_id = '$sessionCategoryId'";
                                $rsSession = Database::query($sqlSession);
                                $sessionId = Database::insert_id();
                                Database::insert(
                                    $tblSessionUser,
                                    [
                                        'relation_type' => Session::GENERAL_COACH,
                                        'duration' => 0,
                                        'registered_at' => api_get_utc_datetime(),
                                        'user_id' => $coachId,
                                        'session_id' => $sessionId,
                                    ]
                                );
                                Database::insert(
                                    $tblSessionUser,
                                    [
                                        'relation_type' => Session::SESSION_ADMIN,
                                        'duration' => 0,
                                        'registered_at' => api_get_utc_datetime(),
                                        'user_id' => (int) $userInfo['user_id'],
                                        'session_id' => $sessionId,
                                    ]
                                );
                                $sessionCounter++;
                            } else {
                                // if the session already exists - update it.
                                $sqlSession = "UPDATE $tblSession SET
                                        access_start_date = '$dStart',
                                        access_end_date = '$dateEnd',
                                        visibility = '$visibilityAfterExpirationPerSession',
                                        session_category_id = '$sessionCategoryId'
                                    WHERE name = '$sessionName'";
                                $rsSession = Database::query($sqlSession);
                                $sessionId = Database::query("SELECT id FROM $tblSession WHERE name='$sessionName'");
                                [$sessionId] = Database::fetch_array($sessionId);
                                Database::query("DELETE FROM $tblSessionUser WHERE session_id ='$sessionId'");
                                Database::query("DELETE FROM $tblSessionCourse WHERE session_id='$sessionId'");
                                Database::query("DELETE FROM $tblSessionCourseUser WHERE session_id='$sessionId'");
                                Database::insert(
                                    $tblSessionUser,
                                    [
                                        'relation_type' => Session::GENERAL_COACH,
                                        'duration' => 0,
                                        'registered_at' => api_get_utc_datetime(),
                                        'user_id' => $coachId,
                                        'session_id' => $sessionId,
                                    ]
                                );
                            }
                        }

                        // Associate the session with access_url.
                        if (api_is_multiple_url_enabled()) {
                            $accessUrlId = api_get_current_access_url_id();
                            UrlManager::add_session_to_url($sessionId, $accessUrlId);
                        } else {
                            // We fill by default the access_url_rel_session table.
                            UrlManager::add_session_to_url($sessionId, 1);
                        }

                        // Adding users to the new session.
                        foreach ($nodeSession->User as $nodeUser) {
                            $username = UserManager::purify_username(api_utf8_decode($nodeUser), $purificationOptionForUsernames);
                            $userId = UserManager::get_user_id_from_username($username);
                            if (false !== $userId) {
                                $sql = "INSERT IGNORE INTO $tblSessionUser SET
                                        user_id ='$userId',
                                        session_id = '$sessionId',
                                        registered_at = '".api_get_utc_datetime()."'";
                                $rsUser = Database::query($sql);
                                $userCounter++;
                            }
                        }

                        // Adding courses to a session.
                        foreach ($nodeSession->Course as $nodeCourse) {
                            $course_code = Database::escape_string(trim(api_utf8_decode($nodeCourse->CourseCode)));
                            // Verify that the course pointed by the course code node exists.
                            if (CourseManager::course_exists($course_code)) {
                                // If the course exists we continue.
                                $course_info = api_get_course_info($course_code);
                                $courseId = $course_info['real_id'];

                                $sessionCourseRelation = SessionManager::relation_session_course_exist(
                                    $sessionId,
                                    $courseId
                                );
                                if (!$sessionCourseRelation) {
                                    $sql_course = "INSERT INTO $tblSessionCourse SET
                                            c_id = $courseId,
                                            session_id = $sessionId";
                                    $rs_course = Database::query($sql_course);
                                    SessionManager::installCourse($sessionId, $courseId);
                                }

                                $courseCoaches = explode(',', $nodeCourse->Coach);

                                // Adding coachs to session course user
                                foreach ($courseCoaches as $course_coach) {
                                    //$coachId = UserManager::purify_username(api_utf8_decode($course_coach), $purification_option_for_usernames);
                                    $coachId = UserManager::get_user_id_from_username($course_coach);
                                    if (false !== $coachId) {
                                        $sql = "INSERT IGNORE INTO $tblSessionCourseUser SET
                                                user_id='$coachId',
                                                c_id = '$courseId',
                                                session_id = '$sessionId',
                                                status = ".Session::COURSE_COACH;
                                        $rs_coachs = Database::query($sql);
                                    } else {
                                        $errorMessage .= get_lang('This user doesn\'t exist').' : '.$coachId.'<br />';
                                    }
                                }

                                // Adding users.
                                $courseCounter++;
                                $usersInCourseCounter = 0;
                                foreach ($nodeCourse->User as $nodeUser) {
                                    $username = UserManager::purify_username(api_utf8_decode($nodeUser), $purificationOptionForUsernames);
                                    $userId = UserManager::get_user_id_from_username($username);
                                    if (false !== $userId) {
                                        // Adding to session_rel_user table.
                                        $sql = "INSERT IGNORE INTO $tblSessionUser SET
                                                user_id ='$userId',
                                                session_id = '$sessionId',
                                                registered_at = '".api_get_utc_datetime()."'";
                                        $rsUser = Database::query($sql);
                                        $userCounter++;
                                        // Adding to session_rel_user_rel_course table.
                                        $sql = "INSERT IGNORE INTO $tblSessionCourseUser SET
                                                user_id = '$userId',
                                                c_id = '$courseId',
                                                session_id = '$sessionId'";
                                        $rsUsers = Database::query($sql);
                                        $usersInCourseCounter++;
                                    } else {
                                        $errorMessage .= get_lang('This user doesn\'t exist').' : '.$username.'<br />';
                                    }
                                }
                                $sql = "UPDATE $tblSessionCourse SET nbr_users='$usersInCourseCounter' WHERE c_id='$courseId'";
                                Database::query($sql);
                                $insertedInCourse[$course_code] = $course_info['title'];
                            }
                        }
                        Database::query("UPDATE $tblSession SET nbr_users='$userCounter', nbr_courses='$courseCounter' WHERE id='$sessionId'");
                    }
                }
                if (empty($root->Users->User) && empty($root->Courses->Course) && empty($root->Session)) {
                    $errorMessage = get_lang('The specified file doesn\'t contain all needed data !');
                }
            } else {
                $errorMessage .= get_lang('XML document is not valid');
            }
        } else {
            // CSV
            $updateCourseCoaches = isset($_POST['update_course_coaches']) ? true : false;
            $addOriginalCourseTeachersAsCourseSessionCoaches = isset($_POST['add_me_as_coach']) ? true : false;

            $result = SessionManager::importCSV(
                $_FILES['import_file']['tmp_name'],
                $isOverwrite,
                api_get_user_id(),
                null,
                [],
                null,
                null,
                null,
                1,
                [],
                $deleteUsersNotInList,
                $updateCourseCoaches,
                false,
                $addOriginalCourseTeachersAsCourseSessionCoaches,
                false
            );
            $sessionList = $result['session_list'];
            $errorMessage = $result['error_message'];
            $sessionCounter = $result['session_counter'];
        }

        if (!empty($errorMessage)) {
            $errorMessage = get_lang('but problems occured').' :<br />'.$errorMessage;
        }

        if (!empty($insertedInCourse) && count($insertedInCourse) > 1) {
            $warn = get_lang('Several courses were subscribed to the session because of a duplicate course code').': ';
            foreach ($insertedInCourse as $code => $title) {
                $warn .= ' '.$title.' ('.$code.'),';
            }
            $warn = substr($warn, 0, -1);
        }
        if (1 == $sessionCounter) {
            if ('csv' === $fileType) {
                $sessionId = current($sessionList);
            }
            Display::addFlash(Display::return_message($warn));
            header('Location: resume_session.php?id_session='.$sessionId);
            exit;
        } else {
            Display::addFlash(Display::return_message(get_lang('File imported').' '.$errorMessage));
            header('Location: session_list.php');
            exit;
        }
    } else {
        $errorMessage = get_lang('No file was sent');
    }
}

Display::display_header($toolName);
$actions = '<a href="../session/session_list.php">'.
    Display::return_icon('back.png', get_lang('Back to').' '.get_lang('Administration'), '', ICON_SIZE_MEDIUM).
    '</a>';
echo Display::toolbarAction('session_import', [$actions]);

if (!empty($errorMessage)) {
    echo Display::return_message($errorMessage, 'normal', false);
}

$form = new FormValidator('import_sessions', 'post', api_get_self(), null, ['enctype' => 'multipart/form-data']);
$form->addElement('hidden', 'formSent', 1);
$form->addElement('file', 'import_file', get_lang('Import marks in an assessment'));
$form->addElement(
    'radio',
    'file_type',
    [
        get_lang('File type'),
        Display::url(
            get_lang('Example CSV file'),
            api_get_path(WEB_CODE_PATH).'admin/example_session.csv',
            ['target' => '_blank', 'download' => null]
        ),
    ],
    'CSV',
    'csv'
);
$form->addElement(
    'radio',
    'file_type',
    [
        null,
        Display::url(
            get_lang('Example XML file'),
            api_get_path(WEB_CODE_PATH).'admin/example_session.xml',
            ['target' => '_blank', 'download' => null]
        ),
    ],
    'XML',
    'xml'
);

$form->addElement('checkbox', 'overwrite', null, get_lang('If a session exists, update it'));
$form->addElement(
    'checkbox',
    'delete_users_not_in_list',
    null,
    get_lang('Unsubscribe students which are not in the imported list')
);
$form->addElement('checkbox', 'update_course_coaches', null, get_lang('Clean and update course coaches'));
$form->addElement('checkbox', 'add_me_as_coach', null, get_lang('Add me as coach'));
$form->addElement('checkbox', 'sendMail', null, get_lang('Send a mail to users'));
$form->addButtonImport(get_lang('Import session(s)'));

$defaults = ['sendMail' => 'true', 'file_type' => 'csv'];

$options = api_get_configuration_value('session_import_settings');
if (!empty($options) && isset($options['options'])) {
    if (isset($options['options']['session_exists_default_option'])) {
        $defaults['overwrite'] = $options['options']['session_exists_default_option'];
    }
    if (isset($options['options']['send_mail_default_option'])) {
        $defaults['sendMail'] = $options['options']['send_mail_default_option'];
    }
}

$form->setDefaults($defaults);
Display::return_message(
    get_lang(
        'The XML import lets you add more info and create resources (courses, users). The CSV import will only create sessions and let you assign existing resources to them.'
    )
);
$form->display();

?>
<p><?php echo get_lang('The CSV file must look like this').' ('.get_lang('Fields in <strong>bold</strong> are mandatory.').')'; ?> :</p>
<pre>
<strong>SessionName</strong>;Coach;<strong>DateStart</strong>;<strong>DateEnd</strong>;Users;Courses;VisibilityAfterExpiration;DisplayStartDate;DisplayEndDate;CoachStartDate;CoachEndDate;Classes
<strong>Example 1</strong>;username;<strong>yyyy/mm/dd;yyyy/mm/dd</strong>;username1|username2;course1[coach1][username1,...]|course2[coach1][username1,...];read_only;yyyy/mm/dd;yyyy/mm/dd;yyyy/mm/dd;yyyy/mm/dd;class1|class2
<strong>Example 2</strong>;username;<strong>yyyy/mm/dd;yyyy/mm/dd</strong>;username1|username2;course1[coach1][username1,...]|course2[coach1][username1,...];accessible;yyyy/mm/dd;yyyy/mm/dd;yyyy/mm/dd;yyyy/mm/dd;class3|class4
<strong>Example 3</strong>;username;<strong>yyyy/mm/dd;yyyy/mm/dd</strong>;username1|username2;course1[coach1][username1,...]|course2[coach1][username1,...];not_accessible;yyyy/mm/dd;yyyy/mm/dd;yyyy/mm/dd;yyyy/mm/dd;class5|class6
</pre>
<p><?php echo get_lang('The XML file must look like this').' ('.get_lang('Fields in <strong>bold</strong> are mandatory.').')'; ?> :</p>
<pre>
&lt;?xml version=&quot;1.0&quot; encoding=&quot;UTF-8&quot;?&gt;
&lt;Sessions&gt;
    &lt;Users&gt;
        &lt;User&gt;
            &lt;Username&gt;<strong>username1</strong>&lt;/Username&gt;
            &lt;Lastname&gt;xxx&lt;/Lastname&gt;
            &lt;Firstname&gt;xxx&lt;/Firstname&gt;
            &lt;Password&gt;xxx&lt;/Password&gt;
            &lt;Email&gt;xxx@xx.xx&lt;/Email&gt;
            &lt;OfficialCode&gt;xxx&lt;/OfficialCode&gt;
            &lt;Phone&gt;xxx&lt;/Phone&gt;
            &lt;Status&gt;student|teacher&lt;/Status&gt;
        &lt;/User&gt;
    &lt;/Users&gt;
    &lt;Courses&gt;
        &lt;Course&gt;
            &lt;CourseCode&gt;<strong>xxx</strong>&lt;/CourseCode&gt;
            &lt;CourseTeacher&gt;<strong>teacher_username</strong>&lt;/CourseTeacher&gt;
            &lt;CourseLanguage&gt;xxx&lt;/CourseLanguage&gt;
            &lt;CourseTitle&gt;xxx&lt;/CourseTitle&gt;
            &lt;CourseDescription&gt;xxx&lt;/CourseDescription&gt;
        &lt;/Course&gt;
    &lt;/Courses&gt;
    &lt;Session&gt;
        <strong>&lt;SessionName&gt;xxx&lt;/SessionName&gt;</strong>
        &lt;Coach&gt;xxx&lt;/Coach&gt;
        <strong>&lt;DateStart&gt;yyyy/mm/dd&lt;/DateStart&gt;</strong>
        <strong>&lt;DateEnd&gt;yyyy/mm/dd&lt;/DateEnd&gt;</strong>
        &lt;User&gt;xxx&lt;/User&gt;
        &lt;User&gt;xxx&lt;/User&gt;
        &lt;Course&gt;
            &lt;CourseCode&gt;coursecode1&lt;/CourseCode&gt;
            &lt;Coach&gt;coach1&lt;/Coach&gt;
        &lt;User&gt;username1&lt;/User&gt;
        &lt;User&gt;username2&lt;/User&gt;
        &lt;/Course&gt;
    &lt;/Session&gt;

    &lt;Session&gt;
        <strong>&lt;SessionName&gt;xxx&lt;/SessionName&gt;</strong>
        &lt;Coach&gt;xxx&lt;/Coach&gt;
        <strong>&lt;DateStart&gt;xxx&lt;/DateStart&gt;</strong>
        <strong>&lt;DateEnd&gt;xxx&lt;/DateEnd&gt;</strong>
        &lt;User&gt;xxx&lt;/User&gt;
        &lt;User&gt;xxx&lt;/User&gt;
        &lt;Course&gt;
            &lt;CourseCode&gt;coursecode1&lt;/CourseCode&gt;
            &lt;Coach&gt;coach1&lt;/Coach&gt;
        &lt;User&gt;username1&lt;/User&gt;
        &lt;User&gt;username2&lt;/User&gt;
        &lt;/Course&gt;
    &lt;/Session&gt;
&lt;/Sessions&gt;
</pre>
<?php

Display::display_footer();
