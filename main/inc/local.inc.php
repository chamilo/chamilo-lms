<?php
/** @deprecated */
exit;



/* For licensing terms, see /license.txt */
/**
 *
 *  SCRIPT PURPOSE
 *
 *
 * You can request a course id. It will check if the course Id requested is the
 * same as the current one. If it isn't it will update session information from
 * the database. You can also force the course reset if you want ($cidReset).
 *
 * All the course information is stored in the $_course array.
 *
 * You can request a group id. The script will check if the group id requested is the
 * same as the current one. If it isn't it will update session information from
 * the database. You can also force the course reset if you want ($gidReset).
 *
 * The course id is stored in $_cid session variable.
 * The group  id is stored in $_gid session variable.
 *
 * @package chamilo.include
*/

// verified if exists the username and password in session current

use \ChamiloSession as Session;

// Conditional login
/*
 * Disabling for now
if (isset($_SESSION['conditional_login']['uid']) && $_SESSION['conditional_login']['can_login']=== true) {
    $uData = UserManager::get_user_info_by_id($_SESSION['conditional_login']['uid']);
    ConditionalLogin::check_conditions($uData);
    $_user['user_id'] = $_SESSION['conditional_login']['uid'];
    $_user['status']  = $uData['status'];
    Session::write('_user', $_user);
    Session::erase('conditional_login');
    $uidReset=true;
}*/

// parameters passed via GET
$gidReq = isset($_GET["gidReq"]) ? Database::escape_string($_GET["gidReq"]) : null;

//this fixes some problems with generic functionalities like
//My Agenda & What's New icons linking to courses
// $cidReq can be set in the session
$cidReq = isset($_SESSION['_cid']) ? Database::escape_string($_SESSION['_cid']) : null;
// $cidReq can be set in URL-parameter
$cidReq = isset($_GET["cidReq"]) ? Database::escape_string($_GET["cidReq"]) : $cidReq;
$cidReset = isset($cidReset) ? Database::escape_string($cidReset) : '';

// $cidReset can be set in URL-parameter
$cidReset = (isset($_GET['cidReq']) && ((isset($_SESSION['_cid']) && $_GET['cidReq'] != $_SESSION['_cid']) || (!isset($_SESSION['_cid'])))) ? Database::escape_string($_GET["cidReq"]) : $cidReset;

// $gidReset can be set in URL-parameter
$gidReset = isset($gidReset) ? $gidReset : '';

// parameters passed via POST
$login = isset($_POST["login"]) ? $_POST["login"] : '';
// register if the user is just logging in, in order to redirect him
$logging_in = false;

/*  MAIN CODE  */

$errorMessage = null;
$loginFailed = true;

if (!empty($_SESSION['_user']['user_id']) && !$login) {
    // uid is in session => login already done, continue with this value
    $_user['user_id'] = $_SESSION['_user']['user_id'];
    //Check if we have to reset user data
    //This param can be used to reload user data if user has been logged by external script
    if (isset($_SESSION['_user']['uidReset']) && $_SESSION['_user']['uidReset']) {
        $uidReset = true;
    }
} else {

    if (isset($_user['user_id'])) {
        unset($_user['user_id']);
    }

    //Platform legal terms and conditions
    if (api_get_setting('allow_terms_conditions') == 'true') {
        if (isset($_POST['login']) && isset($_POST['password']) && isset($_SESSION['term_and_condition']['user_id'])) {
            $user_id = $_SESSION['term_and_condition']['user_id'];    // user id
            // Update the terms & conditions
            $legal_type = null;
            //verify type of terms and conditions
            if (isset($_POST['legal_info'])) {
                $info_legal = explode(':', $_POST['legal_info']);
                $legal_type = LegalManager::get_type_of_terms_and_conditions($info_legal[0], $info_legal[1]);
            }

            //is necessary verify check
            if ($legal_type == 1) {
                if ((isset($_POST['legal_accept']) && $_POST['legal_accept']=='1')) {
                    $legal_option = true;
                } else {
                    $legal_option = false;
                }
            }

            //no is check option
            if ($legal_type == 0) {
                $legal_option=true;
            }

            if (isset($_POST['legal_accept_type']) && $legal_option===true) {
                $cond_array = explode(':', $_POST['legal_accept_type']);
                if (!empty($cond_array[0]) && !empty($cond_array[1])){
                    $time = time();
                    $condition_to_save = intval($cond_array[0]).':'.intval($cond_array[1]).':'.$time;
                    UserManager::update_extra_field_value($user_id,'legal_accept',$condition_to_save);
                }
            }
        }
    }

    //IF cas is activated and user isn't logged in
    if (api_get_setting('cas_activate') == 'true') {
        $cas_activated = true;
    } else {
        $cas_activated = false;
    }

    $cas_login = false;
    if ($cas_activated AND !isset($_user['user_id']) AND !isset($_POST['login'])) {
        require_once api_get_path(SYS_PATH).'main/auth/cas/authcas.php';
        $cas_login = cas_is_authenticated();
    }

    if ((isset($_POST['login']) AND isset($_POST['password']) ) OR ($cas_login)) {

        // $login && $password are given to log in
        if ( $cas_login  && empty($_POST['login']) ) {
            $login = $cas_login;
        } else {
            $login      = $_POST['login'];
            $password   = $_POST['password'];
        }

        //Lookup the user in the main database
        $user_table = Database::get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT user_id, username, auth_source, password FROM $user_table
                WHERE username = '".Database::escape_string($login)."'";
        $result = Database::query($sql);

        // @todo use a UserProvider

        if (Database::num_rows($result) > 0) {
            $uData = Database::fetch_array($result);

            if ($uData['auth_source'] == PLATFORM_AUTH_SOURCE || $uData['auth_source'] == CAS_AUTH_SOURCE) {
                //The authentification of this user is managed by Chamilo itself
                $password = api_get_encrypted_password(trim(stripslashes($password)));

                // Check the user's password
                if (($password == $uData['password'] or $cas_login) and (trim($login) == $uData['username'])) {

                    $uData = api_get_user_info($uData['user_id'], false, false, true);
                    $extraFields = $uData['extra_fields'];
                    // $update_type = UserManager::get_extra_user_data_by_field($uData['user_id'], 'update_type');
                    $update_type = isset($extraFields['extra_update_type']) ? $extraFields['extra_update_type'] : null;

                    if (!empty($extAuthSource[$update_type]['updateUser']) && file_exists($extAuthSource[$update_type]['updateUser'])) {
                        include_once $extAuthSource[$update_type]['updateUser'];
                    }

                    // Check if the account is active (not locked)
                    if ($uData['active'] == '1') {

                        // Check if the expiration date has not been reached
                        if ($uData['expiration_date'] > date('Y-m-d H:i:s') OR $uData['expiration_date'] == '0000-00-00 00:00:00') {
                            global $_configuration;

                            if (isset($_configuration['multiple_access_urls']) && $_configuration['multiple_access_urls']) {
                                //Check if user is an admin
                                $my_user_is_admin = UserManager::is_admin($uData['user_id']);

                                // This user is subscribed in these sites => $my_url_list
                                $my_url_list = api_get_access_url_from_user($uData['user_id']);

                                //Check the access_url configuration setting if the user is registered in the access_url_rel_user table
                                //Getting the current access_url_id of the platform
                                $current_access_url_id = api_get_current_access_url_id();

                                if ($my_user_is_admin === false) {

                                    if (is_array($my_url_list) && count($my_url_list) > 0) {
                                        // the user have the permissions to enter at this site
                                        if (in_array($current_access_url_id, $my_url_list)) {
                                            ConditionalLogin::check_conditions($uData);
                                            Session::write('_user', $uData);
                                            $logging_in = true;
                                        } else {
                                            $loginFailed = true;
                                            Session::erase('_uid');
                                            $errorMessage = 'access_url_inactive';
                                        }
                                    } else {
                                        $loginFailed = true;
                                        Session::erase('_uid');
                                        $errorMessage = 'access_url_inactive';
                                    }
                                } else {
                                    //Only admins of the "main" (first) Chamilo portal can login wherever they want
                                    //Check if this admin have the access_url_id = 1 which means the principal
                                    if (in_array(1, $my_url_list)) {
                                        ConditionalLogin::check_conditions($uData);
                                        Session::write('_user', $uData);
                                    } else {
                                        //This means a secondary admin wants to login so we check as he's a normal user
                                        if (in_array($current_access_url_id, $my_url_list)) {
                                            Session::write('_user', $uData);
                                        } else {
                                            $loginFailed = true;
                                            Session::erase('_uid');
                                            $errorMessage = 'access_url_inactive';
                                        }
                                    }
                                }
                            } else {
                                ConditionalLogin::check_conditions($uData);
                                Session::write('_user', $uData);
                                $logging_in = true;
                            }
                        } else {
                            $loginFailed = true;
                            Session::erase('_uid');
                            $errorMessage = 'account_expired';
                        }
                    } else {
                        $loginFailed = true;
                        Session::erase('_uid');
                        $errorMessage = 'account_inactive';
                    }
                } else {
                    // login failed: username or password incorrect
                    $loginFailed = true;
                    Session::erase('_uid');
                    $errorMessage = 'user_password_incorrect';
                }

                if (isset($uData['creator_id']) && isset($_user) && $_user['user_id'] != $uData['creator_id']) {
                    //first login for a not self registred
                    //e.g. registered by a teacher
                    //do nothing (code may be added later)
                }
            } elseif (!empty($extAuthSource[$uData['auth_source']]['login']) && file_exists($extAuthSource[$uData['auth_source']]['login'])) {
                /*
                 * Process external authentication
                 * on the basis of the given login name
                 */
                $loginFailed = true;  // Default initialisation. It could
                // change after the external authentication
                $key = $uData['auth_source']; //'ldap','shibboleth'...
                /* >>>>>>>> External authentication modules <<<<<<<<< */
                // see configuration.php to define these
                include_once($extAuthSource[$key]['login']);
                /* >>>>>>>> External authentication modules <<<<<<<<< */
            } else { // no standard Chamilo login - try external authentification
                //huh... nothing to do... we shouldn't get here
                error_log('Chamilo Authentication file '. $extAuthSource[$uData['auth_source']]['login']. ' could not be found - this might prevent your system from doing the corresponding authentication process',0);
            }
        } else {
            // login failed, Database::num_rows($result) <= 0
            $loginFailed = true;  // Default initialisation. It could
            // change after the external authentication

            /*
             * In this section:
             * there is no entry for the $login user in the Chamilo
             * database. This also means there is no auth_source for the user.
             * We let all external procedures attempt to add him/her
             * to the system.
             *
             * Process external login on the basis
             * of the authentication source list
             * provided by the configuration settings.
             * If the login succeeds, for going further,
             * Chamilo needs the $_user['user_id'] variable to be
             * set and registered in the session. It's the
             * responsability of the external login script
             * to provide this $_user['user_id'].
             */

            if (isset($extAuthSource) && is_array($extAuthSource)) {
                foreach($extAuthSource as $thisAuthSource) {
                    if (!empty($thisAuthSource['newUser']) && file_exists($thisAuthSource['newUser'])) {
                        include_once($thisAuthSource['newUser']);
                    } else {
                        error_log('Chamilo Authentication file '. $thisAuthSource['newUser']. ' could not be found - this might prevent your system from using the authentication process in the user creation process',0);
                    }
                }
            } //end if is_array($extAuthSource)
            if ($loginFailed) { //If we are here username given is wrong
                //header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=user_password_incorrect');
                $errorMessage = 'user_password_incorrect';
            }
        } //end else login failed
    } elseif (api_get_setting('sso_authentication') === 'true' &&  !in_array('webservices', explode('/', $_SERVER['REQUEST_URI']))) {
        /**
         * TODO:
         * - Work on a better validation for webservices paths. Current is very poor and exit
         */
        $subsso = api_get_setting('sso_authentication_subclass');
        if (!empty($subsso)) {
            require_once api_get_path(SYS_CODE_PATH).'auth/sso/sso.'.$subsso.'.class.php';
            $subsso = 'sso'.$subsso;
            $osso = new $subsso(); //load the subclass
        } else {
            $osso = new sso();
        }
        if (isset($_SESSION['_user']['user_id'])) {

        } else {
            // Handle cookie comming from Master Server
            if (!isset($_GET['sso_referer']) && !isset($_GET['loginFailed']) && isset($_GET['sso_cookie'])) {
                // Redirect to master server
                $osso->ask_master();
            } elseif (isset($_GET['sso_cookie'])) {
                // Here we are going to check the origin of
                // what the call says should be used for
                // authentication, and ensure  we know it
                $matches_domain = false;
                if (isset($_GET['sso_referer'])) {
                    $protocol = api_get_setting('sso_authentication_protocol');
                    // sso_authentication_domain can list
                    // several, comma-separated, domains
                    $master_urls = split(',',api_get_setting('sso_authentication_domain'));
                    if (!empty($master_urls)) {
                        $master_auth_uri = api_get_setting('sso_authentication_auth_uri');
                        foreach ($master_urls as $mu) {
                            if (empty($mu)) { continue; }
                            // for each URL, check until we find *one* that matches the $_GET['sso_referer'], then skip the rest
                            if ($protocol.trim($mu).$master_auth_uri === $_GET['sso_referer']) {
                                $matches_domain = true;
                                break;
                            }
                        }
                    } else {
                        error_log('Your sso_authentication_master param is empty. Check the platform configuration, security section. It can be a list of comma-separated domains');
                    }
                }
                if ($matches_domain) {
                    //make all the process of checking
                    //if the user exists (delegated to the sso class)
                    $osso->check_user();
                } else {
                    error_log('Check the sso_referer URL in your script, it doesn\'t match any of the possibilities');
                    //Request comes from unknown source
                    $loginFailed = true;
                    Session::erase('_uid');
                    $errorMessage = 'unrecognize_sso_origin';
                }
            }
        }//end logout ... else ... login
    } elseif (api_get_setting('openid_authentication') == 'true') {
        if (!empty($_POST['openid_url'])) {
            include api_get_path(SYS_CODE_PATH).'auth/openid/login.php';
            openid_begin(trim($_POST['openid_url']), api_get_path(WEB_PATH).'index.php');
            //this last function should trigger a redirect, so we can die here safely
            die('Openid login redirection should be in progress');
        } elseif (!empty($_GET['openid_identity'])) {
            //it's usual for PHP to replace '.' (dot) by '_' (underscore) in URL parameters
            include(api_get_path(SYS_CODE_PATH).'auth/openid/login.php');
            $res = openid_complete($_GET);
            if ($res['status'] == 'success') {
                $id1 = Database::escape_string($res['openid.identity']);
                //have another id with or without the final '/'
                $id2 = (substr($id1,-1,1)=='/'?substr($id1,0,-1):$id1.'/');
                //lookup the user in the main database
                $user_table = Database::get_main_table(TABLE_MAIN_USER);
                $sql = "SELECT user_id, username, password, auth_source, active, expiration_date
                    FROM $user_table
                    WHERE openid = '$id1'
                    OR openid = '$id2' ";
                $result = Database::query($sql);
                if ($result !== false) {
                    if (Database::num_rows($result)>0) {
                        //$row = Database::fetch_array($res);
                        $uData = Database::fetch_array($result);

                        if ($uData['auth_source'] == PLATFORM_AUTH_SOURCE) {
                            // the authentification of this user is managed by Chamilo itself

                            // check if the account is active (not locked)
                            if ($uData['active']=='1') {
                                // check if the expiration date has not been reached
                                if ($uData['expiration_date']>date('Y-m-d H:i:s') OR $uData['expiration_date']=='0000-00-00 00:00:00') {
                                    $_user['user_id'] = $uData['user_id'];
                                    $_user['status']  = $uData['status'];
                                    Session::write('_user', $_user);
                                } else {
                                    $loginFailed = true;
                                    Session::erase('_uid');
                                    $errorMessage = 'account_expired';
                                }
                            } else {
                                $loginFailed = true;
                                Session::erase('_uid');
                                $errorMessage = 'account_inactive';
                            }
                            if (isset($uData['creator_id']) && $_user['user_id'] != $uData['creator_id']) {
                                //first login for a not self registred
                                //e.g. registered by a teacher
                                //do nothing (code may be added later)
                            }
                        }
                    } else {
                        //Redirect to the subscription form
                        header('Location: '.api_get_path(WEB_CODE_PATH).'auth/inscription.php?username='.$res['openid.sreg.nickname'].'&email='.$res['openid.sreg.email'].'&openid='.$res['openid.identity'].'&openid_msg=idnotfound');
                        //$loginFailed = true;
                    }
                } else {
                    $loginFailed = true;
                }
            } else {
                $loginFailed = true;
            }
        }
    } elseif (KeyAuth::is_enabled()) {
        $success = KeyAuth::instance()->login();
        if ($success) {
            $use_anonymous = false;
        }
    }
    $uidReset = true;
} // end


if ($loginFailed == true && !empty($errorMessage)) {
    header('Location: '.api_get_path(WEB_PUBLIC_PATH).'index?error='.$errorMessage);
    exit;
}

//Now check for anonymous user mode
if (isset($use_anonymous) && $use_anonymous) {
    //if anonymous mode is set, then try to set the current user as anonymous
    //if he doesn't have a login yet
    api_set_anonymous();
} else {
    //if anonymous mode is not set, then check if this user is anonymous. If it
    //is, clean it from being anonymous (make him a nobody :-))
    api_clear_anonymous();
}

// if the requested course is different from the course in session

if (!empty($cidReq) && (!isset($_SESSION['_cid']) or (isset($_SESSION['_cid']) && $cidReq != $_SESSION['_cid']))) {
    $cidReset = true;
    $gidReset = true;    // As groups depend from courses, group id is reset
}

// Setting app user variable
$_user = Session::read('_user');

if ($_user && !isset($_user['complete_name'])) {
    $_user = api_get_user_info(api_get_user_id(), false, false, true);
    Session::write('_user', $_user);
}

$app['current_user'] = $_user;

/* USER INIT */

if (isset($uidReset) && $uidReset) {   // session data refresh requested
    unset($_SESSION['_user']['uidReset']);
    $is_platformAdmin = false;
    $is_allowedCreateCourse = false;

    if (isset($_user['user_id']) && $_user['user_id'] && !api_is_anonymous()) {
        // a uid is given (log in succeeded)
        $user_table     = Database::get_main_table(TABLE_MAIN_USER);
        $admin_table    = Database::get_main_table(TABLE_MAIN_ADMIN);
        $track_e_login  = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);

        $sql = "SELECT user.*, a.user_id is_admin, login.login_date
            FROM $user_table
            LEFT JOIN $admin_table a
            ON user.user_id = a.user_id
            LEFT JOIN $track_e_login login
            ON user.user_id  = login.login_user_id
            WHERE user.user_id = '".$_user['user_id']."'
            ORDER BY login.login_date DESC LIMIT 1";

        $result = Database::query($sql);

        if (Database::num_rows($result) > 0) {
            // Extracting the user data

            $uData = Database::fetch_array($result);
            $_user = api_format_user($uData, false);
            $_user['lastLogin']        = api_strtotime($uData['login_date'], 'UTC');
            $is_platformAdmin           = (bool) (! is_null( $uData['is_admin']));
            $is_allowedCreateCourse     = (bool) (($uData ['status'] == COURSEMANAGER) or (api_get_setting('drhCourseManagerRights') and $uData['status'] == DRH));
            ConditionalLogin::check_conditions($uData);

            Session::write('_user', $_user);
            UserManager::update_extra_field_value($_user['user_id'], 'already_logged_in', 'true');

            Session::write('is_platformAdmin', $is_platformAdmin);
            Session::write('is_allowedCreateCourse', $is_allowedCreateCourse);

        } else {
            header('location:'.api_get_path(WEB_PATH));
            exit;
        }
    } else { // no uid => logout or Anonymous
        Session::erase('_user');
        Session::erase('_uid');
    }
    Session::write('is_platformAdmin', $is_platformAdmin);
    Session::write('is_allowedCreateCourse', $is_allowedCreateCourse);
} else { // continue with the previous values
    $_user                    = isset($_SESSION['_user']) ? $_SESSION['_user'] : null;
    $is_platformAdmin         = isset($_SESSION['is_platformAdmin']) ? $_SESSION['is_platformAdmin'] : false;
    $is_allowedCreateCourse   = isset($_SESSION['is_allowedCreateCourse']) ? $_SESSION['is_allowedCreateCourse'] : false;
}

if (!isset($_SESSION['login_as'])) {
    $save_course_access = true;

    //The value  $_dont_save_user_course_access should be added before the call of global.inc.php see the main/inc/chat.ajax.php file
    //Disables the updates in the TRACK_E_COURSE_ACCESS table
    if (isset($_dont_save_user_course_access) && $_dont_save_user_course_access == true) {
        $save_course_access = false;
    }

    if ($save_course_access) {
        $course_tracking_table = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);

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
        }*/

        $session_lifetime    = 3600; // 1 hour

        $course_code = api_get_course_id();
        $courseId = api_get_course_int_id();
        $time = api_get_datetime();

        if (isset($_user['user_id']) && !empty($_user['user_id']) && !empty($courseId)) {

            //We select the last record for the current course in the course tracking table
            //But only if the login date is < than now + max_life_time
            $sql = "SELECT course_access_id FROM $course_tracking_table
                    WHERE   user_id     = ".intval($_user ['user_id'])." AND
                            c_id = '$courseId' AND
                            session_id  = ".api_get_session_id()." AND
                            login_course_date > now() - INTERVAL $session_lifetime SECOND
                    ORDER BY login_course_date DESC LIMIT 0,1";
            $result = Database::query($sql);

            if (Database::num_rows($result) > 0) {
                $i_course_access_id = Database::result($result,0,0);
                //We update the course tracking table
                $sql = "UPDATE $course_tracking_table  SET logout_course_date = '$time', counter = counter+1
                        WHERE course_access_id = ".intval($i_course_access_id)." AND session_id = ".api_get_session_id();
                Database::query($sql);
            } else {
                $sql="INSERT INTO $course_tracking_table (c_id, user_id, login_course_date, logout_course_date, counter, session_id)" .
                    "VALUES('".$courseId."', '".$_user['user_id']."', '$time', '$time', '1','".api_get_session_id()."')";
                Database::query($sql);
            }
        }
    }
}


/*  COURSE / USER REL. INIT */

$user_id    = isset($_user['user_id']) ? $_user['user_id'] : null;

//Course permissions
$is_courseAdmin     = false; //course teacher
$is_courseTutor     = false; //course teacher - some rights
$is_courseMember    = false; //course student
$is_courseCoach     = false; //course coach

//Course - User permissions
$is_sessionAdmin    = false;
$course_user_table = Database::get_main_table(TABLE_MAIN_COURSE_USER);

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
//Redirect::session_request_uri($logging_in, $user_id);
