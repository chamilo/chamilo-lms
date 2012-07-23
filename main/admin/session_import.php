<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.admin
 */

$language_file = array('admin', 'registration');
$cidReset = true;

require '../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script(true);

require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH).'mail.lib.inc.php';

$form_sent = 0;
$error_message = ''; // Avoid conflict with the global variable $error_msg (array type) in add_course.conf.php.
if (isset($_GET['action']) && $_GET['action'] == 'show_message') {
    $error_message = Security::remove_XSS($_GET['message']);
}


$tbl_user                   = Database::get_main_table(TABLE_MAIN_USER);
$tbl_course                 = Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_course_user            = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_session                = Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_user           = Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_session_course         = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_course_user    = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

$tool_name = get_lang('ImportSessionListXMLCSV');

$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));

set_time_limit(0);

// Set this option to true to enforce strict purification for usenames.
$purification_option_for_usernames = false;

$inserted_in_course = array();

global $_configuration;                            

if ($_POST['formSent']) {
    if (isset($_FILES['import_file']['tmp_name']) && !empty($_FILES['import_file']['tmp_name'])) {
        $form_sent = $_POST['formSent'];
        $file_type = $_POST['file_type'];
        $send_mail = $_POST['sendMail'] ? 1 : 0;
        //$updatesession = $_POST['updatesession'] ? 1 : 0;
        $updatesession = 0;
        $sessions = array();

        $session_counter = 0;

        if ($file_type == 'xml') {

            // XML

            // SimpleXML for PHP5 deals with various encodings, but how many they are, what are version issues, do we need to waste time with configuration options?
            // For avoiding complications we go some sort of "PHP4 way" - we convert the input xml-file into UTF-8 before passing it to the parser.
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
                                case 'student' : 
                                    $status = 5; 
                                    break;
                                case 'teacher' : 
                                    $status = 1; 
                                    break;
                                default : 
                                    $status = 5; 
                                    $error_message .= get_lang('StudentStatusWasGivenTo').' : '.$username.'<br />';
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
                                case 'student' : $status = 5; break;
                                case 'teacher' : $status = 1; break;
                                default : $status = 5; $error_message .= get_lang('StudentStatusWasGivenTo').' : '.$username.'<br />';
                            }

                            $sql = "UPDATE $tbl_user SET
                                    lastname = '".Database::escape_string($lastname)."',
                                    firstname = '".Database::escape_string($firstname)."',
                                    ".(empty($password) ? "" : "password = '".(api_get_encrypted_password($password))."',")."
                                    email = '".Database::escape_string($email)."',
                                    official_code = '".Database::escape_string($official_code)."',
                                    phone = '".Database::escape_string($phone)."',
                                    status = '".Database::escape_string($status)."'
                                WHERE username = '".Database::escape_string($username)."'";

                            Database::query($sql);
                        }
                    }
                }

                // Creating  courses from <Sessions> <Courses> base node.
                
                if (count($root->Courses->Course) > 0) {
                    foreach ($root->Courses->Course as $courseNode) {
                        
                        $params = array();
                        if (empty($courseNode->CourseTitle)) {
                            $params['title']            = api_utf8_decode($courseNode->CourseCode);
                        } else {
                            $params['title']            = api_utf8_decode($courseNode->CourseTitle);
                        }
                        $params['wanted_code']      = api_utf8_decode($courseNode->CourseCode);         
                        $params['tutor_name']       = null;
                        $params['course_category']  = null;                    
                        $params['course_language']  = api_get_valid_language(api_utf8_decode($courseNode->CourseLanguage));
                        $params['user_id']          = api_get_user_id();
                        
                        // Looking up for the teacher.
                        $username       = trim(api_utf8_decode($courseNode->CourseTeacher));
                        $sql = "SELECT user_id, lastname, firstname FROM $tbl_user WHERE username='$username'";
                        $rs = Database::query($sql);
                        list($user_id, $lastname, $firstname) = Database::fetch_array($rs);
                                               
                        $params['teachers']  = $user_id;
                        CourseManager::create_course($params);
                    }
                }

                // Creating sessions from <Sessions> base node.
                if (count($root->Session) > 0) {
                    foreach ($root->Session as $node_session) {
                        $course_counter = 0;
                        $user_counter = 0;

                        $session_name = trim(api_utf8_decode($node_session->SessionName));
                        $coach = UserManager::purify_username(api_utf8_decode($node_session->Coach), $purification_option_for_usernames);

                        if (!empty($coach)) {
                            $coach_id = UserManager::get_user_id_from_username($coach);
                            if ($coach_id === false) {
                                $error_message .= get_lang('UserDoesNotExist').' : '.$coach.'<br />';
                                // Forcing the coach id if user does not exist.
                                $coach_id = api_get_user_id();
                            }
                        } else {
                            // Forcing the coach id.
                            $coach_id = api_get_user_id();
                        }

                        $date_start = trim(api_utf8_decode($node_session->DateStart)); // Just in case - encoding conversion.

                        if (!empty($date_start)) {
                            list($year_start, $month_start, $day_start) = explode('/', $date_start);
                            if(empty($year_start) || empty($month_start) || empty($day_start)) {
                                $error_message .= get_lang('WrongDate').' : '.$date_start.'<br />';
                                break;
                            } else {
                                $time_start = mktime(0, 0, 0, $month_start, $day_start, $year_start);
                            }

                            $date_end = trim(api_utf8_decode($node_session->DateEnd));
                            if (!empty($date_start)) {
                                list($year_end, $month_end, $day_end) = explode('/', $date_end);
                                if (empty($year_end) || empty($month_end) || empty($day_end)) {
                                    $error_message .= get_lang('Error').' : '.$date_end.'<br />';
                                    break;
                                } else {
                                    $time_end = mktime(0, 0, 0, $month_end, $day_end, $year_end);
                                }
                            }
                            if ($time_end - $time_start < 0) {
                                $error_message .= get_lang('StartDateShouldBeBeforeEndDate').' : '.$date_end.'<br />';
                            }
                        }

                        $visibility = trim(api_utf8_decode($node_session->Visibility));
                        $session_category_id = trim(api_utf8_decode($node_session->SessionCategory));

                        if (!$updatesession) {
                            // Always create a session.
                            $unique_name = false; // This MUST be initializead.
                            $i = 0;
                            // Change session name, verify that session doesn't exist.
                            while (!$unique_name) {
                                if ($i > 1) {
                                    $suffix = ' - '.$i;
                                }
                                $sql = 'SELECT 1 FROM '.$tbl_session.' WHERE name="'.Database::escape_string($session_name.$suffix).'"';
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
                                    id_coach = '$coach_id',
                                    date_start = '$date_start',
                                    date_end = '$date_end',
                                    visibility = '$visibility',
                                    session_category_id = '$session_category_id',
                                    session_admin_id=".intval($_user['user_id']);
                            $rs_session = Database::query($sql_session);
                            $session_id = Database::insert_id();
                            $session_counter++;

                        } else {
                            // Update the session if it is needed.
                            $my_session_result = SessionManager::get_session_by_name($session_name);
                            if ($my_session_result === false) {
                                // Creating the session.
                                $sql_session = "INSERT IGNORE INTO $tbl_session SET
                                        name = '".Database::escape_string($session_name)."',
                                        id_coach = '$coach_id',
                                        date_start = '$date_start',
                                        date_end = '$date_end',
                                        visibility = '$visibility',
                                        session_category_id = '$session_category_id',
                                        session_admin_id=".intval($_user['user_id']);
                                $rs_session = Database::query($sql_session);
                                $session_id = Database::insert_id();
                                $session_counter++;
                            } else {
                                // if the session already exists - update it.
                                $sql_session = "UPDATE $tbl_session SET
                                        id_coach = '$coach_id',
                                        date_start = '$date_start',
                                        date_end = '$date_end',
                                        visibility = '$visibility',
                                        session_category_id = '$session_category_id'
                                    WHERE name = '$session_name'";
                                $rs_session = Database::query($sql_session);
                                $session_id = Database::query("SELECT id FROM $tbl_session WHERE name='$session_name'");
                                list($session_id) = Database::fetch_array($session_id);
                                Database::query("DELETE FROM $tbl_session_user WHERE id_session='$session_id'");
                                Database::query("DELETE FROM $tbl_session_course WHERE id_session='$session_id'");
                                Database::query("DELETE FROM $tbl_session_course_user WHERE id_session='$session_id'");
                            }
                        }

                        // Associate the session with access_url.
                        global $_configuration;                        
                        if ($_configuration['multiple_access_urls']) {                            
                            $access_url_id = api_get_current_access_url_id();
                            UrlManager::add_session_to_url($session_id, $access_url_id);
                        } else {
                            // We fill by default the access_url_rel_session table.
                            UrlManager::add_session_to_url($session_id, 1);
                        }


                        // Adding users to the new session.
                        foreach ($node_session->User as $node_user) {
                            $username = UserManager::purify_username(api_utf8_decode($node_user), $purification_option_for_usernames);
                            $user_id = UserManager::get_user_id_from_username($username);
                            if ($user_id !== false) {
                                $sql = "INSERT IGNORE INTO $tbl_session_user SET
                                        id_user='$user_id',
                                        id_session = '$session_id'";
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
                                $course_info = CourseManager::get_course_information($course_code);

                                $session_course_relation = SessionManager::relation_session_course_exist($session_id, $course_code);
                                if (!$session_course_relation) {
                                    $sql_course = "INSERT INTO $tbl_session_course SET
                                            course_code = '$course_code',
                                            id_session='$session_id'";
                                    $rs_course = Database::query($sql_course);
                                }

                                $course_coaches = explode(',', $node_course->Coach);

                                // Adding coachs to session course user
                                foreach ($course_coaches as $course_coach) {
                                    $coach_id = UserManager::purify_username(api_utf8_decode($course_coach), $purification_option_for_usernames);
                                    $coach_id = UserManager::get_user_id_from_username($course_coach);
                                    if ($coach_id !== false) {
                                        $sql = "INSERT IGNORE INTO $tbl_session_course_user SET
                                                id_user='$coach_id',
                                                course_code='$course_code',
                                                id_session = '$session_id',
                                                status = 2 ";
                                        $rs_coachs = Database::query($sql);
                                    } else {
                                        $error_message .= get_lang('UserDoesNotExist').' : '.$user.'<br />';
                                    }
                                }

                                // Adding users.
                                $course_counter++;
                                $users_in_course_counter = 0;
                                foreach ($node_course->User as $node_user) {
                                    $username = UserManager::purify_username(api_utf8_decode($node_user), $purification_option_for_usernames);
                                    $user_id = UserManager::get_user_id_from_username($username);
                                    if ($user_id !== false) {
                                        // Adding to session_rel_user table.
                                        $sql = "INSERT IGNORE INTO $tbl_session_user SET
                                                id_user='$user_id',
                                                id_session = '$session_id'";
                                        $rs_user = Database::query($sql);
                                        $user_counter++;
                                        // Adding to session_rel_user_rel_course table.
                                        $sql = "INSERT IGNORE INTO $tbl_session_course_user SET
                                                id_user='$user_id',
                                                course_code='$course_code',
                                                id_session = '$session_id'";
                                        $rs_users = Database::query($sql);
                                        $users_in_course_counter++;
                                    } else {
                                        $error_message .= get_lang('UserDoesNotExist').' : '.$username.'<br />';
                                    }
                                }
                                $update_session_course = "UPDATE $tbl_session_course SET nbr_users='$users_in_course_counter' WHERE course_code='$course_code'";
                                Database::query($update_session_course);
                                $inserted_in_course[$course_code] = $course_info['title'];

                            }

                            if (CourseManager::course_exists($course_code, true)) {
                                // If the course exists we continue.
                                // Also subscribe to virtual courses through check on visual code.
                                $list = CourseManager :: get_courses_info_from_visual_code($course_code);
                                foreach ($list as $vcourse) {
                                    if ($vcourse['code'] == $course_code) {
                                        // Ignore, this has already been inserted.
                                    } else {

                                        $sql_course = "INSERT INTO $tbl_session_course SET
                                                course_code = '".$vcourse['code']."',
                                                id_session='$session_id'";
                                        $rs_course = Database::query($sql_course);

                                        $course_coaches = explode(",",$node_course->Coach);

                                        // adding coachs to session course user
                                        foreach ($course_coaches as $course_coach) {
                                            $coach_id = UserManager::purify_username(api_utf8_decode($course_coach), $purification_option_for_usernames);
                                            $coach_id = UserManager::get_user_id_from_username($course_coach);
                                            if ($coach_id !== false) {
                                                $sql = "INSERT IGNORE INTO $tbl_session_course_user SET
                                                        id_user='$coach_id',
                                                        course_code='{$vcourse['code']}',
                                                        id_session = '$session_id',
                                                        status = 2 ";
                                                $rs_coachs = Database::query($sql);
                                            } else {
                                                $error_message .= get_lang('UserDoesNotExist').' : '.$user.'<br />';
                                            }
                                        }

                                        // adding users
                                        $course_counter++;
                                        $users_in_course_counter = 0;
                                        foreach ($node_course->User as $node_user) {
                                            $username = UserManager::purify_username(api_utf8_decode($node_user), $purification_option_for_usernames);
                                            $user_id = UserManager::get_user_id_from_username($username);
                                            if ($user_id !== false) {
                                                // Adding to session_rel_user table.
                                                $sql = "INSERT IGNORE INTO $tbl_session_user SET
                                                        id_user='$user_id',
                                                        id_session = '$session_id'";
                                                $rs_user = Database::query($sql);
                                                $user_counter++;
                                                // Adding to session_rel_user_rel_course table.
                                                $sql = "INSERT IGNORE INTO $tbl_session_course_user SET
                                                        id_user='$user_id',
                                                        course_code='{$vcourse['code']}',
                                                        id_session = '$session_id'";
                                                $rs_users = Database::query($sql);
                                                $users_in_course_counter++;
                                            } else {
                                                $error_message .= get_lang('UserDoesNotExist').' : '.$username.'<br />';
                                            }
                                        }
                                        $update_session_course = "UPDATE $tbl_session_course SET nbr_users='$users_in_course_counter' WHERE course_code='$course_code'";
                                        Database::query($update_session_course);
                                        $inserted_in_course[$course_code] = $course_info['title'];

                                    }
                                    $inserted_in_course[$vcourse['code']] = $vcourse['title'];
                                }

                            } else {
                                // Tthe course does not exist.
                                $error_message .= get_lang('CourseDoesNotExist').' : '.$course_code.'<br />';
                            }
                        }
                        Database::query("UPDATE $tbl_session SET nbr_users='$user_counter', nbr_courses='$course_counter' WHERE id='$session_id'");
                    }

                }
                if (empty($root->Users->User) && empty($root->Courses->Course) && empty($root->Session)) {
                    $error_message = get_lang('NoNeededData');
                }
            } else {
                $error_message .= get_lang('XMLNotValid');
            }
        } else {

            // CSV

            $content = file($_FILES['import_file']['tmp_name']);

            if (!api_strstr($content[0], ';')) {
                $error_message = get_lang('NotCSV');
            } else {
                $tag_names = array();

                foreach ($content as $key => $enreg) {
                    $enreg = explode(';', trim($enreg));
                    if ($key) {
                        foreach ($tag_names as $tag_key => $tag_name) {
                            $sessions[$key - 1][$tag_name] = $enreg[$tag_key];
                        }
                    } else {
                        foreach ($enreg as $tag_name) {
                            $tag_names[] = api_preg_replace('/[^a-zA-Z0-9_\-]/', '', $tag_name);
                        }
                        if (!in_array('SessionName', $tag_names) || !in_array('DateStart', $tag_names) || !in_array('DateEnd', $tag_names)) {
                            $error_message = get_lang('NoNeededData');
                            break;
                        }
                    }
                }

                // Looping the sessions.
                foreach ($sessions as $enreg) {
                    $user_counter = 0;
                    $course_counter = 0;

                    $session_name           = $enreg['SessionName'];
                    $date_start             = $enreg['DateStart'];
                    $date_end               = $enreg['DateEnd'];
                    $visibility             = $enreg['Visibility'];
                    $session_category_id    = $enreg['SessionCategory'];

                    // Searching a coach.
                    if (!empty($enreg['Coach'])) {
                        $coach_id = UserManager::get_user_id_from_username($enreg['Coach']);
                        if ($coach_id === false) {
                            // If the coach-user does not exist - I'm the coach.
                            $coach_id = api_get_user_id();
                        }
                    } else {
                        $coach_id = api_get_user_id();
                    }

                    if (!$updatesession) {
                        // Always create a session.
                        $unique_name = false; // This MUST be initializead.
                        $i = 0;
                        // Change session name, verify that session doesn't exist.
                        while (!$unique_name) {
                            if ($i > 1) {
                                $suffix = ' - '.$i;
                            }
                            $sql = 'SELECT 1 FROM '.$tbl_session.' WHERE name="'.Database::escape_string($session_name.$suffix).'"';
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
                                id_coach = '$coach_id',
                                date_start = '$date_start',
                                date_end = '$date_end',
                                visibility = '$visibility',
                                session_category_id = '$session_category_id',
                                session_admin_id=".intval($_user['user_id']);
                        $rs_session = Database::query($sql_session);
                        $session_id = Database::insert_id();
                        $session_counter++;
                    } else {
                        $my_session_result = SessionManager::get_session_by_name($session_name);
                        if ($my_session_result === false) {

                            // Creating a session.
                            $sql_session = "INSERT IGNORE INTO $tbl_session SET
                                    name = '$session_name',
                                    id_coach = '$coach_id',
                                    date_start = '$date_start',
                                    date_end = '$date_end',
                                    visibility = '$visibility',
                                    session_category_id = '$session_category_id'";

                            $rs_session = Database::query($sql_session);
                            // We get the last insert id.
                            $my_session_result = SessionManager::get_session_by_name($session_name);
                            $session_id = $my_session_result['id'];
                            //echo '<br>';
                        } else {
                            // The session already exists, update it then.
                            $sql_session = "UPDATE $tbl_session SET
                                    id_coach = '$coach_id',
                                    date_start = '$date_start',
                                    date_end = '$date_end',
                                    visibility = '$visibility',
                                    session_category_id = '$session_category_id'
                                WHERE name = '$session_name'";
                            $rs_session = Database::query($sql_session);
                            $session_id = Database::query("SELECT id FROM $tbl_session WHERE name='$session_name'");
                            list($session_id) = Database::fetch_array($session_id);
                            Database::query("DELETE FROM $tbl_session_user WHERE id_session='$session_id'");
                            Database::query("DELETE FROM $tbl_session_course WHERE id_session='$session_id'");
                            Database::query("DELETE FROM $tbl_session_course_user WHERE id_session='$session_id'");
                        }
                        $session_counter++;
                    }

                    $users = explode('|', $enreg['Users']);

                    // Adding the relationship "Session - User".
                    if (is_array($users)) {
                        foreach ($users as $user) {
                            $user_id = UserManager::get_user_id_from_username($user);
                            if ($user_id !== false) {
                                // Insert new users.
                                $sql = "INSERT IGNORE INTO $tbl_session_user SET
                                        id_user='$user_id',
                                        id_session = '$session_id'";
                                $rs_user = Database::query($sql);
                                $user_counter++;
                            }
                        }
                    }

                    $courses = explode('|', $enreg['Courses']);

                    foreach ($courses as $course) {
                        $course_code = api_strtoupper(api_substr($course, 0, api_strpos($course, '[')));

                        if (CourseManager::course_exists($course_code)) {

                            // If the course exists we continue.
                            $course_info = CourseManager::get_course_information($course_code);

                            $coach = api_strstr($course, '[');
                            $coach = api_substr($coach, 1, api_strpos($coach,']') - 1);

                            if (!empty($coach)) {
                                $coach_id = UserManager::get_user_id_from_username($coach);
                                if ($coach_id === false) {
                                    $coach_id = '';
                                }
                            } else {
                                $coach = '';
                            }
                            // Adding the course to a session.
                            $sql_course = "INSERT IGNORE INTO $tbl_session_course SET
                                    course_code = '$course_code',
                                    id_session='$session_id'";
                            $rs_course = Database::query($sql_course);
                            $course_counter++;
                            
                            $course_split = array();
                            $pattern = "/\[(.*?)\]/";
                            preg_match_all($pattern, $course, $matches);
                            if (isset($matches[1])) {
                                $course_coaches = $matches[1][0]; 
                                $course_users   = $matches[1][1];
                            }
                            
                            $course_users   = explode(',', $course_users);
                            $course_coaches = explode(',', $course_coaches);
                                                        
                            // Adding coaches to session course user
                            if (!empty($course_coaches)) {
                                foreach ($course_coaches as $course_coach) {
                                    $coach_id = UserManager::get_user_id_from_username($course_coach);
                                    if ($coach_id !== false) {
                                        $sql = "INSERT IGNORE INTO $tbl_session_course_user SET
                                                id_user='$coach_id',
                                                course_code='$course_code',
                                                id_session = '$session_id',
                                                status = 2 ";
                                        $rs_coachs = Database::query($sql);
                                    } else {
                                        $error_message .= get_lang('UserDoesNotExist').' : '.$user.'<br />';
                                    }
                                }
                            }

                            $users_in_course_counter = 0;
                            
                            // Adding the relationship "Session - Course - User".
                            foreach ($course_users as $user) {
                                $user_id = UserManager::get_user_id_from_username($user);
                                if ($user_id !== false) {
                                    $sql = "INSERT IGNORE INTO $tbl_session_course_user SET
                                            id_user='$user_id',
                                            course_code='$course_code',
                                            id_session = '$session_id'";
                                    $rs_users = Database::query($sql);
                                    $users_in_course_counter++;
                                } else {
                                    $error_message .= get_lang('UserDoesNotExist').' : '.$user.'<br />';
                                }
                            }
                            $sql = "UPDATE $tbl_session_course SET nbr_users='$users_in_course_counter' WHERE course_code='$course_code'";
                            Database::query($sql);

                            $course_info = CourseManager::get_course_information($course_code);
                            $inserted_in_course[$course_code] = $course_info['title'];
                        } else {
                            // TODO: We should create the course as in the XML import.
                        }

                        if (CourseManager::course_exists($course_code, true)) {

                            $list = CourseManager :: get_courses_info_from_visual_code($course_code);

                            foreach ($list as $vcourse) {
                                if ($vcourse['code'] == $course_code) {
                                    // Ignore, this has already been inserted.
                                } else {

                                    $coach = api_strstr($course, '[');
                                    $coach = api_substr($coach, 1, api_strpos($coach,']') - 1);

                                    // Adding the relationship "Session - Course".
                                    $sql_course = "INSERT IGNORE INTO $tbl_session_course SET
                                                    course_code = '".$vcourse['code']."',
                                                    id_session='$session_id'";

                                    $rs_course = Database::query($sql_course);

                                    // adding coachs to session course user
                                    foreach ($course_coaches as $course_coach) {
                                        $coach_id = UserManager::get_user_id_from_username($course_coach);
                                        if ($coach_id !== false) {
                                            $sql = "INSERT IGNORE INTO $tbl_session_course_user SET
                                                    id_user='$coach_id',
                                                    course_code='{$vcourse['code']}',
                                                    id_session = '$session_id',
                                                    status = 2 ";
                                            $rs_coachs = Database::query($sql);
                                        } else {
                                            $error_message .= get_lang('UserDoesNotExist').' : '.$user.'<br />';
                                        }
                                    }

                                    $users_in_course_counter = 0;
                                    // Adding the relationship "Session - Course - User".
                                    foreach ($course_users as $user) {
                                        $user_id = UserManager::get_user_id_from_username($user);
                                        if ($user_id !== false) {
                                            $sql = "INSERT IGNORE INTO $tbl_session_course_user SET
                                                    id_user='$user_id',
                                                    course_code='{$vcourse['code']}',
                                                    id_session = '$session_id'";
                                            $rs_users = Database::query($sql);
                                            $users_in_course_counter++;
                                        } else {
                                            $error_message .= get_lang('UserDoesNotExist').' : '.$user.'<br />';
                                        }
                                    }
                                    Database::query("UPDATE $tbl_session_course SET nbr_users='$users_in_course_counter' WHERE course_code='".$vcourse['code']."'");
                                }
                            }
                            $inserted_in_course[$vcourse['code']] = $vcourse['title'];
                        }
                    }
                    $access_url_id = api_get_current_access_url_id();
                    UrlManager::add_session_to_url($session_id, $access_url_id);
                    $sql_update_users = "UPDATE $tbl_session SET nbr_users='$user_counter', nbr_courses='$course_counter' WHERE id='$session_id'";
                    Database::query($sql_update_users);
                }
            }
        }

        if (!empty($error_message)) {
            $error_message = get_lang('ButProblemsOccured').' :<br />'.$error_message;
        }

        if (count($inserted_in_course) > 1) {
            $warn = get_lang('SeveralCoursesSubscribedToSessionBecauseOfSameVisualCode').': ';
            foreach ($inserted_in_course as $code => $title) {
                $warn .= ' '.$title.' ('.$code.'),';
            }
            $warn = substr($warn, 0, -1);
        }
        if ($session_counter == 1) {
            header('Location: resume_session.php?id_session='.$session_id.'&warn='.urlencode($warn));
            exit;
        } else {
            header('Location: session_list.php?action=show_message&message='.urlencode(get_lang('FileImported').' '.$error_message).'&warn='.urlencode($warn));
            exit;
        }
    } else {
        $error_message = get_lang('NoInputFile');
    }
}

// Display the header.
Display::display_header($tool_name);

if (count($inserted_in_course) > 1) {
    $msg = get_lang('SeveralCoursesSubscribedToSessionBecauseOfSameVisualCode').': ';
    foreach ($inserted_in_course as $code => $title) {
        $msg .= ' '.$title.' ('.$title.'),';
    }
    $msg = substr($msg, 0, -1);
    Display::display_warning_message($msg);
}

echo '<div class="actions">';
echo '<a href="../admin/index.php">'.Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('PlatformAdmin'),'',ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

if (!empty($error_message)) {
    Display::display_normal_message($error_message, false);
}

$form = new FormValidator('import_sessions', 'post', api_get_self(), null, array('enctype' => 'multipart/form-data'));
$form->addElement('hidden', 'formSent', 1);
$form->addElement('file', 'import_file', get_lang('ImportFileLocation'));

$form->addElement('radio', 'file_type', array(get_lang('FileType'), '<a href="example_session.xml" target="_blank">'.get_lang('ExampleXMLFile').'</a>'), 'CSV', 'csv');
$form->addElement('radio', 'file_type', array(null, '<a href="example_session.csv" target="_blank">'.get_lang('ExampleCSVFile').'</a>'), 'XML', 'xml');

$form->addElement('checkbox', 'sendMail', null, get_lang('SendMailToUsers'));
$form->addElement('button', 'submit', get_lang('ImportSession'));

$defaults = array('sendMail' => 'true','file_type' => 'csv');
$form->setDefaults($defaults);
$form->display();

?>
<font color="gray">
<p><?php echo get_lang('CSVMustLookLike').' ('.get_lang('MandatoryFields').')'; ?> :</p>

<blockquote>
<pre>
<strong>SessionName</strong>;Coach;<strong>DateStart</strong>;<strong>DateEnd</strong>;Users;Courses
<strong>xxx1</strong>;xxx;<strong>xxx;xxx</strong>;username1|username2;course1[coach1][username1,username2,...]|course2[coach1][username1,username2,...]
<strong>xxx2</strong>;xxx;<strong>xxx;xxx</strong>;username1|username2;course1[coach1][username1,username2,...]|course2[coach1][username1,username2,...]
</pre>
</blockquote>

<p><?php echo get_lang('XMLMustLookLike').' ('.get_lang('MandatoryFields').')'; ?> :</p>

<blockquote>
<pre>
&lt;?xml version=&quot;1.0&quot; encoding=&quot;<?php echo api_refine_encoding_id(api_get_system_encoding()); ?>&quot;?&gt;
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
</blockquote>
</font>
<?php

/* FOOTER */
Display::display_footer();