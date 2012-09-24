<?php

use \ChamiloSession as Session;

/* For licensing terms, see /license.txt */
/**
 * 	Code library for login process
 *
 * @author Olivier Cauberghe <olivier.cauberghe@UGent.be>, Ghent University
 * @author Julio Montoya		<gugli100@gmail.com>
 * @package chamilo.login
 */

/**
 * Class
 * @package chamilo.login
 */
class Login
{

    /**
     * Get user account list
     *
     * @param array $user array with keys: email, password, uid, loginName
     * @param boolean $reset
     * @param boolean $by_username
     * @return unknown
     */
    public static function get_user_account_list($user, $reset = false, $by_username = false)
    {
        global $_configuration;
        //$portal_url = $_configuration['root_web'];
        $portal_url = api_get_path(WEB_PATH);

        if ($_configuration['multiple_access_urls']) {
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $url = api_get_access_url($access_url_id);
                $portal_url = $url['url'];
            }
        }

        if ($reset) {
            if ($by_username) {
                $secret_word = self::get_secret_word($user['email']);
                if ($reset) {
                    $reset_link = $portal_url . "main/auth/lostPassword.php?reset=" . $secret_word . "&id=" . $user['uid'];
                } else {
                    $reset_link = get_lang('Pass') . " : $user[password]";
                }
                $user_account_list = get_lang('YourRegistrationData') . " : \n" . get_lang('UserName') . ' : ' . $user['loginName'] . "\n" . get_lang('ResetLink') . ' : ' . $reset_link . '';

                if ($user_account_list) {
                    $user_account_list = "\n-----------------------------------------------\n" . $user_account_list;
                }
            } else {
                foreach ($user as $this_user) {
                    $secret_word = self::get_secret_word($this_user['email']);
                    if ($reset) {
                        $reset_link = $portal_url . "main/auth/lostPassword.php?reset=" . $secret_word . "&id=" . $this_user['uid'];
                    } else {
                        $reset_link = get_lang('Pass') . " : $this_user[password]";
                    }
                    $user_account_list[] = get_lang('YourRegistrationData') . " : \n" . get_lang('UserName') . ' : ' . $this_user['loginName'] . "\n" . get_lang('ResetLink') . ' : ' . $reset_link . '';
                }
                if ($user_account_list) {
                    $user_account_list = implode("\n-----------------------------------------------\n", $user_account_list);
                }
            }
        } else {
            if (!$by_username) {
                $user = $user[0];
            }
            $reset_link = get_lang('Pass') . " : $user[password]";
            $user_account_list = get_lang('YourRegistrationData') . " : \n" . get_lang('UserName') . ' : ' . $user['loginName'] . "\n" . $reset_link . '';
        }
        return $user_account_list;
    }

    /**
     * This function sends the actual password to the user
     *
     * @param int $user
     * @author Olivier Cauberghe <olivier.cauberghe@UGent.be>, Ghent University
     */
    public static function send_password_to_user($user, $by_username = false) {
        global $_configuration;
        $email_subject = "[" . api_get_setting('siteName') . "] " . get_lang('LoginRequest'); // SUBJECT

        if ($by_username) { // Show only for lost password
            $user_account_list = self::get_user_account_list($user, false, $by_username); // BODY
            $email_to = $user['email'];
        } else {
            $user_account_list = self::get_user_account_list($user); // BODY
            $email_to = $user[0]['email'];
        }

        $portal_url = api_get_path(WEB_PATH);
        if ($_configuration['multiple_access_urls']) {
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $url = api_get_access_url($access_url_id);
                $portal_url = $url['url'];
            }
        }

        $email_body = get_lang('YourAccountParam') . " " . $portal_url . "\n\n$user_account_list";
        // SEND MESSAGE
        $sender_name = api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'), null, PERSON_NAME_EMAIL_ADDRESS);
        $email_admin = api_get_setting('emailAdministrator');

        if (api_mail_html('', $email_to, $email_subject, $email_body, $sender_name, $email_admin) == 1) {
            return get_lang('your_password_has_been_reset');
        } else {
            $admin_email = Display :: encrypted_mailto_link(api_get_setting('emailAdministrator'), api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname')));            
            return sprintf(get_lang('ThisPlatformWasUnableToSendTheEmailPleaseContactXForMoreInformation'), $admin_email);            
        }
    }

    /**
     * Handle encrypted password, send an email to a user with his password 
     *
     * @param int	user id
     * @param bool	$by_username
     *
     * @author Olivier Cauberghe <olivier.cauberghe@UGent.be>, Ghent University
     */
    public static function handle_encrypted_password($user, $by_username = false) {
        global $_configuration;
        $email_subject = "[" . api_get_setting('siteName') . "] " . get_lang('LoginRequest'); // SUBJECT

        if ($by_username) { // Show only for lost password
            $user_account_list = self::get_user_account_list($user, true, $by_username); // BODY
            $email_to = $user['email'];
        } else {
            $user_account_list = self::get_user_account_list($user, true); // BODY
            $email_to = $user[0]['email'];
        }

        $secret_word = self::get_secret_word($email_to);
        $email_body = get_lang('DearUser') . " :\n" . get_lang('password_request') . "\n";
        $email_body .= $user_account_list . "\n-----------------------------------------------\n\n";
        $email_body .= get_lang('PasswordEncryptedForSecurity');

        $email_body .= "\n\n" . get_lang('Formula') . ",\n" . api_get_setting('administratorName') . " " . api_get_setting('administratorSurname') . "\n" . get_lang('PlataformAdmin') . " - " . api_get_setting('siteName');

        $sender_name = api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'), null, PERSON_NAME_EMAIL_ADDRESS);
        $email_admin = api_get_setting('emailAdministrator');

        if (@api_mail_html('', $email_to, $email_subject, $email_body, $sender_name, $email_admin) == 1) {

            if (CustomPages::enabled()) {
                return get_lang('YourPasswordHasBeenEmailed');
            } else {
                Display::display_confirmation_message(get_lang('YourPasswordHasBeenEmailed'));
            }
        } else {
            $admin_email = Display :: encrypted_mailto_link(api_get_setting('emailAdministrator'), api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname')));            
            $message = sprintf(get_lang('ThisPlatformWasUnableToSendTheEmailPleaseContactXForMoreInformation'), $admin_email);
            
            if (CustomPages::enabled()) {
                return $message;
            } else {
                Display::display_error_message($message, false);
            }
        }
    }

    /**
     * Gets the secret word
     * @author Olivier Cauberghe <olivier.cauberghe@UGent.be>, Ghent University
     */
    public static function get_secret_word($add)
    {
        global $_configuration;
        return $secret_word = md5($_configuration['security_key'] . $add);
    }

    /**
     * Resets a password
     * @author Olivier Cauberghe <olivier.cauberghe@UGent.be>, Ghent University
     */
    public static function reset_password($secret, $id, $by_username = false)
    {
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $id = intval($id);
        $sql = "SELECT user_id AS uid, lastname AS lastName, firstname AS firstName, username AS loginName, password, email FROM " . $tbl_user . " WHERE user_id=$id";
        $result = Database::query($sql);
        $num_rows = Database::num_rows($result);

        if ($result && $num_rows > 0) {
            $user = Database::fetch_array($result);
        } else {
            return get_lang('CouldNotResetPassword');
        }

        if (self::get_secret_word($user['email']) == $secret) { // OK, secret word is good. Now change password and mail it.
            $user['password'] = api_generate_password();
            $crypted = $user['password'];
            $crypted = api_get_encrypted_password($crypted);
            $sql = "UPDATE " . $tbl_user . " SET password='$crypted' WHERE user_id = $id";
            $result = Database::query($sql);
            return self::send_password_to_user($user, $by_username);
        } else {
            return get_lang('NotAllowed');
        }
    }

    /**
     *
     * @global bool   $is_platformAdmin
     * @global bool   $is_allowedCreateCourse
     * @global object $_user 
     */
    public static function init_user($user_id, $reset)
    {
        global $is_platformAdmin;
        global $is_allowedCreateCourse;
        global $_user;

        if (isset($reset) && $reset) {    // session data refresh requested
            unset($_SESSION['_user']['uidReset']);
            $is_platformAdmin = false;
            $is_allowedCreateCourse = false;
            $_user['user_id'] = $user_id;

            if (isset($_user['user_id']) && $_user['user_id'] && !api_is_anonymous()) {
                // a uid is given (log in succeeded)
                $user_table = Database::get_main_table(TABLE_MAIN_USER);
                $admin_table = Database::get_main_table(TABLE_MAIN_ADMIN);
                $track_e_login = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);

                $sql = "SELECT user.*, a.user_id is_admin, UNIX_TIMESTAMP(login.login_date) login_date
                        FROM $user_table
                        LEFT JOIN $admin_table a
                        ON user.user_id = a.user_id
                        LEFT JOIN $track_e_login login
                        ON user.user_id  = login.login_user_id
                        WHERE user.user_id = '" . $_user['user_id'] . "'
                        ORDER BY login.login_date DESC LIMIT 1";

                $result = Database::query($sql);

                if (Database::num_rows($result) > 0) {
                    // Extracting the user data

                    $uData = Database::fetch_array($result);

                    $_user['firstName'] = $uData['firstname'];
                    $_user['lastName'] = $uData['lastname'];
                    $_user['mail'] = $uData['email'];
                    $_user['lastLogin'] = $uData['login_date'];
                    $_user['official_code'] = $uData['official_code'];
                    $_user['picture_uri'] = $uData['picture_uri'];
                    $_user['user_id'] = $uData['user_id'];
                    $_user['language'] = $uData['language'];
                    $_user['auth_source'] = $uData['auth_source'];
                    $_user['theme'] = $uData['theme'];
                    $_user['status'] = $uData['status'];

                    $is_platformAdmin = (bool) (!is_null($uData['is_admin']));
                    $is_allowedCreateCourse = (bool) (($uData['status'] == COURSEMANAGER) or (api_get_setting('drhCourseManagerRights') and $uData['status'] == DRH));
                    ConditionalLogin::check_conditions($uData);

                    Session::write('_user', $_user);
                    UserManager::update_extra_field_value($_user['user_id'], 'already_logged_in', 'true');
                    Session::write('is_platformAdmin', $is_platformAdmin);
                    Session::write('is_allowedCreateCourse', $is_allowedCreateCourse);

//                  
//                    
//                    // If request_uri is setted we have to go further to have course permissions
//                    if (empty($_SESSION['request_uri']) || !isset($_SESSION['request_uri'])) {
//                        if (isset($_SESSION['noredirection'])) {
//                            //If we just want to reset info without redirecting user
//                            unset($_SESSION['noredirection']);
//                        } else {
//                            LoginRedirection::redirect();
//                        }
//                    }
                } else {
                    header('location:' . api_get_path(WEB_PATH));
                    //exit("WARNING UNDEFINED UID !! ");
                }
            } else { // no uid => logout or Anonymous
                Session::erase('_user');
                Session::erase('_uid');
            }

            Session::write('is_platformAdmin', $is_platformAdmin);
            Session::write('is_allowedCreateCourse', $is_allowedCreateCourse);
        } else { // continue with the previous values
            $_user = $_SESSION['_user'];
            $is_platformAdmin = $_SESSION['is_platformAdmin'];
            $is_allowedCreateCourse = $_SESSION['is_allowedCreateCourse'];
        }
    }

    /**
     *
     * @global bool $is_platformAdmin
     * @global bool $is_allowedCreateCourse
     * @global object $_user
     * @global int $_cid
     * @global array $_course
     * @global type $_real_cid
     * @global type $_courseUser
     * @global type $is_courseAdmin
     * @global type $is_courseTutor
     * @global type $is_courseCoach
     * @global type $is_courseMember
     * @global type $is_sessionAdmin
     * @global type $is_allowed_in_course
     * 
     * @param type $course_id
     * @param type $reset 
     */
    static function init_course($course_id, $reset)
    {
        global $_configuration;
        global $is_platformAdmin;
        global $is_allowedCreateCourse;
        global $_user;

        global $_cid;
        global $_course;
        global $_real_cid;
        global $_courseUser;

        global $is_courseAdmin;  //course teacher
        global $is_courseTutor;  //course teacher - some rights
        global $is_courseCoach;  //course coach
        global $is_courseMember; //course student
        global $is_sessionAdmin;
        global $is_allowed_in_course;

        if ($reset) {
            // Course session data refresh requested or empty data        
            if ($course_id) {
                $course_table = Database::get_main_table(TABLE_MAIN_COURSE);
                $course_cat_table = Database::get_main_table(TABLE_MAIN_CATEGORY);
                $sql = "SELECT course.*, course_category.code faCode, course_category.name faName
                        FROM $course_table
                        LEFT JOIN $course_cat_table
                        ON course.category_code = course_category.code
                        WHERE course.code = '$course_id'";
                $result = Database::query($sql);

                if (Database::num_rows($result) > 0) {
                    $course_data = Database::fetch_array($result);
                    //@TODO real_cid should be cid, for working with numeric course id
                    $_real_cid = $course_data['id'];

                    $_cid = $course_data['code'];
                    $_course = array();
                    $_course['real_id'] = $course_data['id'];
                    $_course['id'] = $course_data['code']; //auto-assigned integer
                    $_course['code'] = $course_data['code'];
                    $_course['name'] = $course_data['title'];
                    $_course['title'] = $course_data['title'];
                    $_course['official_code'] = $course_data['visual_code']; // use in echo
                    $_course['sysCode'] = $course_data['code']; // use as key in db
                    $_course['path'] = $course_data['directory']; // use as key in path
                    $_course['dbName'] = $course_data['db_name']; // use as key in db list
                    $_course['db_name'] = $course_data['db_name']; // not needed in Chamilo 1.9
                    $_course['dbNameGlu'] = $_configuration['table_prefix'] . $course_data['db_name'] . $_configuration['db_glue']; // use in all queries //not needed in Chamilo 1.9
                    $_course['titular'] = $course_data['tutor_name']; // this should be deprecated and use the table course_rel_user
                    $_course['language'] = $course_data['course_language'];
                    $_course['extLink']['url'] = $course_data['department_url'];
                    $_course['extLink']['name'] = $course_data['department_name'];
                    $_course['categoryCode'] = $course_data['faCode'];
                    $_course['categoryName'] = $course_data['faName'];
                    $_course['visibility'] = $course_data['visibility'];
                    $_course['subscribe_allowed'] = $course_data['subscribe'];
                    $_course['unsubscribe'] = $course_data['unsubscribe'];
                    $_course['activate_legal'] = $course_data['activate_legal'];
                    $_course['show_score'] = $course_data['show_score']; //used in the work tool

                    Session::write('_cid', $_cid);
                    Session::write('_course', $_course);

                    //@TODO real_cid should be cid, for working with numeric course id
                    Session::write('_real_cid', $_real_cid);

                    // if a session id has been given in url, we store the session
                    
                    // Database Table Definitions
                    $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
                    $tbl_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
                    $tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

                    if (!empty($_GET['id_session'])) {
                        $_SESSION['id_session'] = intval($_GET['id_session']);
                        $sql = 'SELECT name FROM ' . $tbl_session . ' WHERE id="' . intval($_SESSION['id_session']) . '"';
                        $rs = Database::query($sql);
                        list($_SESSION['session_name']) = Database::fetch_array($rs);
                    } else {
                        Session::erase('session_name');
                        Session::erase('id_session');
                    }

                    if (!isset($_SESSION['login_as'])) {
                        //Course login
                        if (isset($_user['user_id'])) {
                            event_course_login($_course['sysCode'], $_user['user_id'], api_get_session_id());
                        }
                    }
                } else {
                    //exit("WARNING UNDEFINED CID !! ");
                    header('location:' . api_get_path(WEB_PATH));
                }
            } else {
                Session::erase('_cid');
                Session::erase('_real_cid');
                Session::erase('_course');

                if (!empty($_SESSION)) {
                    foreach ($_SESSION as $key => $session_item) {
                        if (strpos($key, 'lp_autolunch_') === false) {
                            continue;
                        } else {
                            if (isset($_SESSION[$key])) {
                                Session::erase($key);
                            }
                        }
                    }
                }
                //Deleting session info 
                if (api_get_session_id()) {
                    Session::erase('id_session');
                    Session::erase('session_name');
                }
            }
        } else {
            // Continue with the previous values       
            if (empty($_SESSION['_course']) OR empty($_SESSION['_cid'])) { //no previous values...
                $_cid = -1;        //set default values that will be caracteristic of being unset
                $_course = -1;
            } else {
                $_cid = $_SESSION['_cid'];
                $_course = $_SESSION['_course'];

                // these lines are usefull for tracking. Indeed we can have lost the id_session and not the cid.
                // Moreover, if we want to track a course with another session it can be usefull        
                if (!empty($_GET['id_session'])) {
                    $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
                    $sql = 'SELECT name FROM ' . $tbl_session . ' WHERE id="' . intval($_SESSION['id_session']) . '"';
                    $rs = Database::query($sql);
                    list($_SESSION['session_name']) = Database::fetch_array($rs);
                    $_SESSION['id_session'] = intval($_GET['id_session']);
                }

                if (!isset($_SESSION['login_as'])) {
                    $save_course_access = true;

                    //The value  $_dont_save_user_course_access should be added before the call of global.inc.php see the main/inc/chat.ajax.php file
                    //Disables the updates in the TRACK_E_COURSE_ACCESS table
                    if (isset($_dont_save_user_course_access) && $_dont_save_user_course_access == true) {
                        $save_course_access = false;
                    }

                    if ($save_course_access) {
                        $course_tracking_table = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);

                        /*
                         * When $_configuration['session_lifetime'] is too big 100 hours (in order to let users take exercises with no problems)
                         * the function Tracking::get_time_spent_on_the_course() returns big values (200h) due the condition: 
                         * login_course_date > now() - INTERVAL $session_lifetime SECOND
                         * 
                         */
                        /*
                          if (isset($_configuration['session_lifetime'])) {
                          $session_lifetime    = $_configuration['session_lifetime'];
                          } else {
                          $session_lifetime    = 3600; // 1 hour
                          } */

                        $session_lifetime = 3600; // 1 hour

                        $course_code = $_course['sysCode'];
                        $time = api_get_datetime();

                        if (isset($_user['user_id']) && !empty($_user['user_id'])) {

                            //We select the last record for the current course in the course tracking table
                            //But only if the login date is < than now + max_life_time            
                            $sql = "SELECT course_access_id FROM $course_tracking_table
                            WHERE   user_id     = " . intval($_user ['user_id']) . " AND
                                    course_code = '$course_code' AND 
                                    session_id  = " . api_get_session_id() . " AND
                                    login_course_date > now() - INTERVAL $session_lifetime SECOND
                        ORDER BY login_course_date DESC LIMIT 0,1";
                            $result = Database::query($sql);

                            if (Database::num_rows($result) > 0) {
                                $i_course_access_id = Database::result($result, 0, 0);
                                //We update the course tracking table
                                $sql = "UPDATE $course_tracking_table  SET logout_course_date = '$time', counter = counter+1  
                                WHERE course_access_id = " . intval($i_course_access_id) . " AND session_id = " . api_get_session_id();
                                //error_log($sql);
                                Database::query($sql);
                            } else {
                                $sql = "INSERT INTO $course_tracking_table (course_code, user_id, login_course_date, logout_course_date, counter, session_id)" .
                                    "VALUES('" . $course_code . "', '" . $_user['user_id'] . "', '$time', '$time', '1','" . api_get_session_id() . "')";
                                //error_log($sql);
                                Database::query($sql);
                            }
                        }
                    }
                }
            }
        }
        /*  COURSE / USER REL. INIT */

        $session_id = api_get_session_id();
        $user_id = isset($_user['user_id']) ? $_user['user_id'] : null;

        //Course permissions
        $is_courseAdmin = false; //course teacher
        $is_courseTutor = false; //course teacher - some rights
        $is_courseMember = false; //course student
        //Course - User permissions
        $is_sessionAdmin = false;

        if ($reset) {

            if (isset($user_id) && $user_id && isset($_cid) && $_cid) {

                //Check if user is subscribed in a course        
                $course_user_table = Database::get_main_table(TABLE_MAIN_COURSE_USER);
                $sql = "SELECT * FROM $course_user_table
                   WHERE user_id  = '" . $user_id . "' AND relation_type <> " . COURSE_RELATION_TYPE_RRHH . "
                   AND course_code = '$course_id'";
                $result = Database::query($sql);

                $cuData = null;
                if (Database::num_rows($result) > 0) { // this  user have a recorded state for this course
                    $cuData = Database::fetch_array($result, 'ASSOC');
                    $is_courseAdmin = (bool) ($cuData['status'] == 1 );
                    $is_courseTutor = (bool) ($cuData['tutor_id'] == 1 );
                    $is_courseMember = true;

                    //Checking if the user filled the course legal agreement
                    if ($_course['activate_legal'] == 1 && !api_is_platform_admin()) {
                        $user_is_subscribed = CourseManager::is_user_accepted_legal($user_id, $_course['id'], $session_id);
                        if (!$user_is_subscribed) {
                            $url = api_get_path(WEB_CODE_PATH) . 'course_info/legal.php?course_code=' . $_course['code'] . '&session_id=' . $session_id;
                            header('Location: ' . $url);
                            exit;
                        }
                    }
                    $_courseUser['role'] = $cuData['role'];
                    Session::write('_courseUser', $_courseUser);
                }

                //We are in a session course? Check session permissions
                if (!empty($session_id)) {

                    //I'm not the teacher of the course 
                    if ($is_courseAdmin == false) {
                        // this user has no status related to this course
                        // The user is subscribed in a session? The user is a Session coach a Session admin ?

                        $tbl_session = Database :: get_main_table(TABLE_MAIN_SESSION);
                        $tbl_session_course = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
                        $tbl_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

                        //Session coach, session admin, course coach admin 
                        $sql = "SELECT session.id_coach, session_admin_id, session_rcru.id_user
                		FROM $tbl_session session, $tbl_session_course_user session_rcru
					    WHERE  session_rcru.id_session  = session.id AND 
					           session_rcru.course_code = '$_cid' AND 
					           session_rcru.id_user     = '$user_id' AND 
                               session_rcru.id_session  = $session_id AND
					           session_rcru.status      = 2";

                        $result = Database::query($sql);
                        $row = Database::store_result($result);

                        //I'm a session admin?                
                        if (isset($row) && isset($row[0]) && $row[0]['session_admin_id'] == $user_id) {
                            $_courseUser['role'] = 'Professor';
                            $is_courseMember = false;
                            $is_courseTutor = false;
                            $is_courseAdmin = false;
                            $is_courseCoach = false;
                            $is_sessionAdmin = true;
                        } else {
                            //Im a coach or a student?
                            $sql = "SELECT id_user, status FROM " . $tbl_session_course_user . "
                            WHERE   course_code = '$_cid' AND 
                                    id_user     = '" . $user_id . "' AND
                                    id_session  = '" . $session_id . "'                                    
                            LIMIT 1";
                            $result = Database::query($sql);

                            if (Database::num_rows($result)) {
                                $row = Database::fetch_array($result, 'ASSOC');

                                $session_course_status = $row['status'];

                                switch ($session_course_status) {
                                    case '2': // coach - teacher
                                        $_courseUser['role'] = 'Professor';
                                        $is_courseMember = true;
                                        $is_courseTutor = true;
                                        $is_courseCoach = true;
                                        $is_sessionAdmin = false;

                                        if (api_get_setting('extend_rights_for_coach') == 'true') {
                                            $is_courseAdmin = true;
                                        } else {
                                            $is_courseAdmin = false;
                                        }
                                        Session::write('_courseUser', $_courseUser);
                                        break;
                                    case '0': //student
                                        $is_courseMember = true;
                                        $is_courseTutor = false;
                                        $is_courseAdmin = false;
                                        $is_sessionAdmin = false;
                                        Session::write('_courseUser', $_courseUser);
                                        break;
                                    default:
                                        //unregister user
                                        $is_courseMember = false;
                                        $is_courseTutor = false;
                                        $is_courseAdmin = false;
                                        $is_sessionAdmin = false;
                                        Session::erase('_courseUser');
                                        break;
                                }
                            } else {
                                //unregister user
                                $is_courseMember = false;
                                $is_courseTutor = false;
                                $is_courseAdmin = false;
                                $is_sessionAdmin = false;
                                Session::erase('_courseUser');
                            }
                        }
                    }

                    //If I'm the admin platform i'm a teacher of the course
                    if ($is_platformAdmin) {
                        $is_courseAdmin = true;
                    }
                }
            } else { // keys missing => not anymore in the course - user relation
                // course
                $is_courseMember = false;
                $is_courseAdmin = false;
                $is_courseTutor = false;
                $is_courseCoach = false;
                $is_sessionAdmin = false;
                Session::erase('_courseUser');
            }

            //Checking the course access
            $is_allowed_in_course = false;

            if (isset($_course)) {
                switch ($_course['visibility']) {
                    case COURSE_VISIBILITY_OPEN_WORLD: //3
                        $is_allowed_in_course = true;
                        break;
                    case COURSE_VISIBILITY_OPEN_PLATFORM : //2
                        if (isset($user_id) && !api_is_anonymous($user_id)) {
                            $is_allowed_in_course = true;
                        }
                        break;
                    case COURSE_VISIBILITY_REGISTERED: //1
                        if ($is_platformAdmin || $is_courseMember) {
                            $is_allowed_in_course = true;
                        }
                        break;
                    case COURSE_VISIBILITY_CLOSED: //0
                        if ($is_platformAdmin || $is_courseAdmin) {
                            $is_allowed_in_course = true;
                        }
                        break;
                }
            }

            // check the session visibility
            if ($is_allowed_in_course == true) {
                //if I'm in a session

                if ($session_id != 0) {
                    if (!$is_platformAdmin) {
                        // admin and session coach are *not* affected to the invisible session mode
                        // the coach is not affected because he can log in some days after the end date of a session
                        $session_visibility = api_get_session_visibility($session_id);

                        switch ($session_visibility) {
                            case SESSION_INVISIBLE:
                                $is_allowed_in_course = false;
                                break;
                        }
                        //checking date
                    }
                }
            }

            // save the states
            Session::write('is_courseAdmin', $is_courseAdmin);
            Session::write('is_courseMember', $is_courseMember);
            Session::write('is_courseTutor', $is_courseTutor);
            Session::write('is_courseCoach', $is_courseCoach);
            Session::write('is_allowed_in_course', $is_allowed_in_course);

            Session::write('is_sessionAdmin', $is_sessionAdmin);
        } else { // continue with the previous values
            if (isset($_SESSION ['_courseUser'])) {
                $_courseUser = $_SESSION ['_courseUser'];
            }

            $is_courseAdmin = $_SESSION ['is_courseAdmin'];
            $is_courseTutor = $_SESSION ['is_courseTutor'];
            $is_courseCoach = $_SESSION ['is_courseCoach'];
            $is_courseMember = $_SESSION ['is_courseMember'];
            $is_allowed_in_course = $_SESSION ['is_allowed_in_course'];
        }
    }

    /**
     *
     * @global int $_cid
     * @global array $_course
     * @global int $_gid
     * 
     * @param int $group_id
     * @param bool $reset 
     */
    static function init_group($group_id, $reset)
    {
        global $_cid;
        global $_course;
        global $_gid;

        if ($reset) { // session data refresh requested
            if ($group_id && $_cid && !empty($_course['real_id'])) { // have keys to search data
                $group_table = Database::get_course_table(TABLE_GROUP);
                $sql = "SELECT * FROM $group_table WHERE c_id = " . $_course['real_id'] . " AND id = '$group_id'";
                $result = Database::query($sql);
                if (Database::num_rows($result) > 0) { // This group has recorded status related to this course
                    $gpData = Database::fetch_array($result);
                    $_gid = $gpData ['id'];
                    Session::write('_gid', $_gid);
                } else {
                    Session::erase('_gid');
                }
            } elseif (isset($_SESSION['_gid']) or isset($_gid)) { // Keys missing => not anymore in the group - course relation
                Session::erase('_gid');
            }
        } elseif (isset($_SESSION['_gid'])) { // continue with the previous values
            $_gid = $_SESSION ['_gid'];
        } else { //if no previous value, assign caracteristic undefined value
            $_gid = -1;
        }

        //set variable according to student_view_enabled choices
        if (api_get_setting('student_view_enabled') == "true") {
            if (isset($_GET['isStudentView'])) {
                if ($_GET['isStudentView'] == 'true') {
                    if (isset($_SESSION['studentview'])) {
                        if (!empty($_SESSION['studentview'])) {
                            // switching to studentview
                            $_SESSION['studentview'] = 'studentview';
                        }
                    }
                } elseif ($_GET['isStudentView'] == 'false') {
                    if (isset($_SESSION['studentview'])) {
                        if (!empty($_SESSION['studentview'])) {
                            // switching to teacherview
                            $_SESSION['studentview'] = 'teacherview';
                        }
                    }
                }
            } elseif (!empty($_SESSION['studentview'])) {
                //all is fine, no change to that, obviously
            } elseif (empty($_SESSION['studentview'])) {
                // We are in teacherview here
                $_SESSION['studentview'] = 'teacherview';
            }
        }
    }
    
    /**
     * Returns true if user exists in the platform when asking the password
     * 
     * @param string $username (email or username)
     * @return boolean
     */
    function get_user_accounts_by_username($username) {        
        if (strpos($username,'@')){
            $username = api_strtolower($username);
            $email = true;
        } else {
            $username = api_strtolower($username);
            $email = false;
        }

		$condition = '';
		if ($email) {
			$condition = "LOWER(email) = '".Database::escape_string($username)."' ";
		} else {
            $condition = "LOWER(username) = '".Database::escape_string($username)."'";
        }

		$tbl_user = Database :: get_main_table(TABLE_MAIN_USER);
		$query = "SELECT user_id AS uid, lastname AS lastName, firstname AS firstName, username AS loginName, password, email, 
                         status AS status, official_code, phone, picture_uri, creator_id 
				 FROM  $tbl_user
				 WHERE ( $condition AND active = 1) ";
		$result 	= Database::query($query);
		$num_rows 	= Database::num_rows($result);
        if ($result && $num_rows > 0) {
            return Database::store_result($result);
        }
        return false;
    }
}
