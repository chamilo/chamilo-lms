<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Session;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script(true);
api_protect_limit_for_session_admin();

$form_sent = 0;
$tbl_user = Database::get_main_table(TABLE_MAIN_USER);
$tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
$tblSessionUser = Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

$tool_name = get_lang('Import sessions list');

//$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('Administration'));
$interbreadcrumb[] = ['url' => 'session_list.php', 'name' => get_lang('Session list')];

set_time_limit(0);

// Set this option to true to enforce strict purification for usenames.
$purification_option_for_usernames = false;
$insertedInCourse = [];
$error_message = '';
$warn = null;
$updatesession = null;
$userInfo = api_get_user_info();

if (isset($_POST['formSent']) && $_POST['formSent']) {
    if (isset($_FILES['import_file']['tmp_name']) &&
        !empty($_FILES['import_file']['tmp_name'])
    ) {
        $form_sent = $_POST['formSent'];
        $file_type = isset($_POST['file_type']) ? $_POST['file_type'] : null;
        $send_mail = isset($_POST['sendMail']) && $_POST['sendMail'] ? 1 : 0;
        $isOverwrite = isset($_POST['overwrite']) && $_POST['overwrite'] ? true : false;
        $deleteUsersNotInList = isset($_POST['delete_users_not_in_list']) ? true : false;
        $sessions = [];
        $session_counter = 0;

        if ('xml' === $file_type) {
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
                    foreach ($root->Users->User as $node_user) {
                        $username = $username_old = trim(api_utf8_decode($node_user->Username));
                        if (UserManager::is_username_available($username)) {
                            $password = api_utf8_decode($node_user->Password);
                            if (empty($password)) {
                                $password = api_generate_password();
                            }
                            switch ($node_user->Status) {
                                case 'student':
                                    $status = 5;
                                    break;
                                case 'teacher':
                                    $status = 1;
                                    break;
                                default:
                                    $status = 5;
                                    $error_message .= get_lang('Learner status has been given to').' : '.$username.'<br />';
                            }

                            $result = UserManager::create_user(
                                api_utf8_decode($node_user->Firstname),
                                api_utf8_decode($node_user->Lastname),
                                $status,
                                api_utf8_decode($node_user->Email),
                                $username,
                                $password,
                                api_utf8_decode($node_user->OfficialCode),
                                null,
                                api_utf8_decode($node_user->Phone),
                                null,
                                PLATFORM_AUTH_SOURCE,
                                null,
                                1,
                                0,
                                null,
                                null,
                                $send_mail
                            );
                        } else {
                            $lastname = trim(api_utf8_decode($node_user->Lastname));
                            $firstname = trim(api_utf8_decode($node_user->Firstname));
                            $password = api_utf8_decode($node_user->Password);
                            $email = trim(api_utf8_decode($node_user->Email));
                            $official_code = trim(api_utf8_decode($node_user->OfficialCode));
                            $phone = trim(api_utf8_decode($node_user->Phone));
                            $status = trim(api_utf8_decode($node_user->Status));
                            switch ($status) {
                                case 'student':
                                    $status = 5;
                                    break;
                                case 'teacher':
                                    $status = 1;
                                    break;
                                default:
                                    $status = 5;
                                    $error_message .= get_lang('Learner status has been given to').' : '.$username.'<br />';
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
                                    $official_code,
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
                        $sql = "SELECT user_id, lastname, firstname FROM $tbl_user WHERE username='$username'";
                        $rs = Database::query($sql);
                        [$userId, $lastname, $firstname] = Database::fetch_array($rs);

                        $params['teachers'] = $userId;
                        CourseManager::create_course($params);
                    }
                }

                // Creating sessions from <Sessions> base node.
                if (count($root->Session) > 0) {
                    foreach ($root->Session as $node_session) {
                        $course_counter = 0;
                        $user_counter = 0;

                        $session_name = trim(api_utf8_decode($node_session->SessionName));
                        $coach = UserManager::purify_username(
                            api_utf8_decode($node_session->Coach),
                            $purification_option_for_usernames
                        );

                        if (!empty($coach)) {
                            $coachId = UserManager::get_user_id_from_username($coach);
                            if (false === $coachId) {
                                $error_message .= get_lang('This user doesn\'t exist').' : '.$coach.'<br />';
                                // Forcing the coach id if user does not exist.
                                $coachId = api_get_user_id();
                            }
                        } else {
                            // Forcing the coach id.
                            $coachId = api_get_user_id();
                        }

                        // Just in case - encoding conversion.
                        $date_start = trim(api_utf8_decode($node_session->DateStart));

                        if (!empty($date_start)) {
                            [$yearStart, $monthStart, $dayStart] = explode('/', $date_start);
                            if (empty($yearStart) || empty($monthStart) || empty($dayStart)) {
                                $error_message .= get_lang('Wrong date format (yyyy-mm-dd)').' : '.$date_start.'<br />';
                                break;
                            } else {
                                $time_start = mktime(0, 0, 0, (int) $monthStart, (int) $dayStart, (int) $yearStart);
                            }

                            $dateEnd = trim(api_utf8_decode($node_session->DateEnd));
                            if (!empty($date_start)) {
                                [$yearEnd, $monthEnd, $dayEnd] = explode('/', $dateEnd);
                                if (empty($yearEnd) || empty($monthEnd) || empty($dayEnd)) {
                                    $error_message .= get_lang('Error').' : '.$dateEnd.'<br />';
                                    break;
                                } else {
                                    $time_end = mktime(0, 0, 0, $monthEnd, $dayEnd, $yearEnd);
                                }
                            }
                            if ($time_end - $time_start < 0) {
                                $error_message .= get_lang('The first date should be before the end date').' : '.$dateEnd.'<br />';
                            }
                        }

                        $visibility = trim(api_utf8_decode($node_session->Visibility));
                        $sessionCategoryId = trim(api_utf8_decode($node_session->SessionCategory));

                        if (!$updatesession) {
                            // Always create a session.
                            $unique_name = false; // This MUST be initializead.
                            $i = 0;
                            // Change session name, verify that session doesn't exist.
                            while (!$unique_name) {
                                if ($i > 1) {
                                    $suffix = ' - '.$i;
                                }
                                $sql = 'SELECT 1 FROM '.$tbl_session.'
                                        WHERE name="'.Database::escape_string($session_name.$suffix).'"';
                                $rs = Database::query($sql);
                                if (Database::result($rs, 0, 0)) {
                                    $i++;
                                } else {
                                    $unique_name = true;
                                    $session_name .= $suffix;
                                }
                            }

                            // Creating the session.
                            $sql_session = "INSERT IGNORE INTO $tbl_session SET
                                    name = '".Database::escape_string($session_name)."',
                                    access_start_date = '$date_start',
                                    access_end_date = '$dateEnd',
                                    visibility = '$visibility',
                                    session_category_id = '$sessionCategoryId'";
                            $rs_session = Database::query($sql_session);
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
                            $session_counter++;
                        } else {
                            // Update the session if it is needed.
                            $my_session_result = SessionManager::get_session_by_name($session_name);
                            if (false === $my_session_result) {
                                // Creating the session.
                                $sql_session = "INSERT IGNORE INTO $tbl_session SET
                                        name = '".Database::escape_string($session_name)."',
                                        access_start_date = '$date_start',
                                        access_end_date = '$dateEnd',
                                        visibility = '$visibility',
                                        session_category_id = '$sessionCategoryId'";
                                $rs_session = Database::query($sql_session);
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
                                $session_counter++;
                            } else {
                                // if the session already exists - update it.
                                $sql_session = "UPDATE $tbl_session SET
                                        access_start_date = '$date_start',
                                        access_end_date = '$dateEnd',
                                        visibility = '$visibility',
                                        session_category_id = '$sessionCategoryId'
                                    WHERE name = '$session_name'";
                                $rs_session = Database::query($sql_session);
                                $sessionId = Database::query("SELECT id FROM $tbl_session WHERE name='$session_name'");
                                [$sessionId] = Database::fetch_array($sessionId);
                                Database::query("DELETE FROM $tblSessionUser WHERE session_id ='$sessionId'");
                                Database::query("DELETE FROM $tbl_session_course WHERE session_id='$sessionId'");
                                Database::query("DELETE FROM $tbl_session_course_user WHERE session_id='$sessionId'");
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
                            $access_url_id = api_get_current_access_url_id();
                            UrlManager::add_session_to_url($sessionId, $access_url_id);
                        } else {
                            // We fill by default the access_url_rel_session table.
                            UrlManager::add_session_to_url($sessionId, 1);
                        }

                        // Adding users to the new session.
                        foreach ($node_session->User as $node_user) {
                            $username = UserManager::purify_username(api_utf8_decode($node_user), $purification_option_for_usernames);
                            $user_id = UserManager::get_user_id_from_username($username);
                            if (false !== $user_id) {
                                $sql = "INSERT IGNORE INTO $tblSessionUser SET
                                        user_id ='$user_id',
                                        session_id = '$sessionId',
                                        registered_at = '".api_get_utc_datetime()."'";
                                $rs_user = Database::query($sql);
                                $user_counter++;
                            }
                        }

                        // Adding courses to a session.
                        foreach ($node_session->Course as $node_course) {
                            $course_code = Database::escape_string(trim(api_utf8_decode($node_course->CourseCode)));
                            // Verify that the course pointed by the course code node exists.
                            if (CourseManager::course_exists($course_code)) {
                                // If the course exists we continue.
                                $course_info = api_get_course_info($course_code);
                                $courseId = $course_info['real_id'];

                                $session_course_relation = SessionManager::relation_session_course_exist(
                                    $sessionId,
                                    $courseId
                                );
                                if (!$session_course_relation) {
                                    $sql_course = "INSERT INTO $tbl_session_course SET
                                            c_id = $courseId,
                                            session_id = $sessionId";
                                    $rs_course = Database::query($sql_course);
                                    SessionManager::installCourse($sessionId, $courseId);
                                }

                                $course_coaches = explode(',', $node_course->Coach);

                                // Adding coachs to session course user
                                foreach ($course_coaches as $course_coach) {
                                    //$coachId = UserManager::purify_username(api_utf8_decode($course_coach), $purification_option_for_usernames);
                                    $coachId = UserManager::get_user_id_from_username($course_coach);
                                    if (false !== $coachId) {
                                        $sql = "INSERT IGNORE INTO $tbl_session_course_user SET
                                                user_id='$coachId',
                                                c_id = '$courseId',
                                                session_id = '$sessionId',
                                                status = ".Session::COURSE_COACH;
                                        $rs_coachs = Database::query($sql);
                                    } else {
                                        $error_message .= get_lang('This user doesn\'t exist').' : '.$coachId.'<br />';
                                    }
                                }

                                // Adding users.
                                $course_counter++;
                                $users_in_course_counter = 0;
                                foreach ($node_course->User as $node_user) {
                                    $username = UserManager::purify_username(api_utf8_decode($node_user), $purification_option_for_usernames);
                                    $user_id = UserManager::get_user_id_from_username($username);
                                    if (false !== $user_id) {
                                        // Adding to session_rel_user table.
                                        $sql = "INSERT IGNORE INTO $tblSessionUser SET
                                                user_id ='$user_id',
                                                session_id = '$sessionId',
                                                registered_at = '".api_get_utc_datetime()."'";
                                        $rs_user = Database::query($sql);
                                        $user_counter++;
                                        // Adding to session_rel_user_rel_course table.
                                        $sql = "INSERT IGNORE INTO $tbl_session_course_user SET
                                                user_id = '$user_id',
                                                c_id = '$courseId',
                                                session_id = '$sessionId'";
                                        $rs_users = Database::query($sql);
                                        $users_in_course_counter++;
                                    } else {
                                        $error_message .= get_lang('This user doesn\'t exist').' : '.$username.'<br />';
                                    }
                                }
                                $sql = "UPDATE $tbl_session_course SET nbr_users='$users_in_course_counter' WHERE c_id='$courseId'";
                                Database::query($sql);
                                $insertedInCourse[$course_code] = $course_info['title'];
                            }
                        }
                        Database::query("UPDATE $tbl_session SET nbr_users='$user_counter', nbr_courses='$course_counter' WHERE id='$sessionId'");
                    }
                }
                if (empty($root->Users->User) && empty($root->Courses->Course) && empty($root->Session)) {
                    $error_message = get_lang('The specified file doesn\'t contain all needed data !');
                }
            } else {
                $error_message .= get_lang('XML document is not valid');
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
            $error_message = $result['error_message'];
            $session_counter = $result['session_counter'];
        }

        if (!empty($error_message)) {
            $error_message = get_lang('but problems occured').' :<br />'.$error_message;
        }

        if (!empty($insertedInCourse) && count($insertedInCourse) > 1) {
            $warn = get_lang('Several courses were subscribed to the session because of a duplicate course code').': ';
            foreach ($insertedInCourse as $code => $title) {
                $warn .= ' '.$title.' ('.$code.'),';
            }
            $warn = substr($warn, 0, -1);
        }
        if (1 == $session_counter) {
            if ('csv' === $file_type) {
                $sessionId = current($sessionList);
            }
            Display::addFlash(Display::return_message($warn));
            header('Location: resume_session.php?id_session='.$sessionId);
            exit;
        } else {
            Display::addFlash(Display::return_message(get_lang('File imported').' '.$error_message));
            header('Location: session_list.php');
            exit;
        }
    } else {
        $error_message = get_lang('No file was sent');
    }
}

Display::display_header($tool_name);
$actions = '<a href="../session/session_list.php">'.
    Display::return_icon('back.png', get_lang('Back to').' '.get_lang('Administration'), '', ICON_SIZE_MEDIUM).
    '</a>';
echo Display::toolbarAction('session_import', [$actions]);

if (!empty($error_message)) {
    echo Display::return_message($error_message, 'normal', false);
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
