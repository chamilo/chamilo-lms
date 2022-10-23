<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\Utils\ChamiloApi;
use Chamilo\CoreBundle\Entity\Repository\SequenceResourceRepository;
use Chamilo\CoreBundle\Entity\SequenceResource;
use ChamiloSession as Session;

/**
 *                             SCRIPT PURPOSE.
 *
 * This script initializes and manages Chamilo session information. It
 * keeps available session information up to date.
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
 *
 *                    VARIABLES AFFECTING THE SCRIPT BEHAVIOR
 *
 * string  $login
 * string  $password
 * boolean $logout
 *
 * string  $cidReq   : course id requested
 * boolean $cidReset : ask for a course Reset, if no $cidReq is provided in the
 *                     same time, all course informations is removed from the
 *                     current session
 *
 * int     $gidReq   : group Id requested
 * boolean $gidReset : ask for a group Reset, if no $gidReq is provided in the
 *                     same time, all group informations is removed from the
 *                     current session
 *
 *
 *                   VARIABLES SET AND RETURNED BY THE SCRIPT
 *
 * All the variables below are set and returned by this script.
 *
 * USER VARIABLES
 *
 * string    $_user ['firstName'   ]
 * string    $_user ['lastName'    ]
 * string    $_user ['mail'        ]
 * string    $_user ['lastLogin'   ]
 * string    $_user ['official_code']
 * string    $_user ['picture_uri'  ]
 * string    $_user['user_id']
 *
 * boolean $is_platformAdmin
 * boolean $is_allowedCreateCourse
 *
 * COURSE VARIABLES
 * see the function get_course_info_with_category
 * boolean $is_courseMember
 * boolean $is_courseTutor
 * boolean $is_courseAdmin
 *
 *
 * GROUP VARIABLES
 *
 * int     $_gid (the group id)
 *
 *
 *                       IMPORTANT ADVICE FOR DEVELOPERS
 *
 * We strongly encourage developers to use a connection layer at the top of
 * their scripts rather than use these variables, as they are, inside the core
 * of their scripts. It will make code maintenance much easier.
 *
 *    Many if the functions you need you can already find in the
 *    api.lib.php
 *
 * We encourage you to use functions to access these global "kernel" variables.
 * You can add them to e.g. the main API library.
 *
 *
 *                               SCRIPT STRUCTURE
 *
 * 1. The script determines if there is an authentication attempt. This part
 * only chek if the login name and password are valid. Afterwards, it set the
 * $_user['user_id'] (user id) and the $uidReset flag. Other user informations are retrieved
 * later. It's also in this section that optional external authentication
 * devices step in.
 *
 * 2. The script determines what other session informations have to be set or
 * reset, setting correctly $cidReset (for course) and $gidReset (for group).
 *
 * 3. If needed, the script retrieves the other user informations (first name,
 *   last name, ...) and stores them in session.
 *
 * 4. If needed, the script retrieves the course information and stores them
 * in session
 *
 * 5. The script initializes the user permission status and permission for the
 * course level
 *
 * 6. If needed, the script retrieves group informations an store them in
 * session.
 *
 * 7. The script initializes the user status and permission for the group level.
 *
 *    @package chamilo.include
 */

// Verified if exists the username and password in session current

// Facebook connexion, if activated
if (api_is_facebook_auth_activated() && !api_get_user_id()) {
    require_once api_get_path(SYS_PATH).'main/auth/external_login/facebook.inc.php';
    if (isset($facebook_config['appId']) && isset($facebook_config['secret'])) {
        facebookConnect();
    }
}

// Conditional login
if (isset($_SESSION['conditional_login']['uid']) && $_SESSION['conditional_login']['can_login'] === true) {
    $uData = api_get_user_info($_SESSION['conditional_login']['uid']);
    ConditionalLogin::check_conditions($uData);

    $_user['user_id'] = $_SESSION['conditional_login']['uid'];
    $_user['status'] = $uData['status'];
    Session::write('_user', $_user);
    Session::erase('conditional_login');
    $uidReset = true;
    Event::eventLogin($_user['user_id']);
}

// parameters passed via GET
$logout = isset($_GET['logout']) ? $_GET['logout'] : '';
$gidReq = isset($_GET['gidReq']) ? (int) $_GET['gidReq'] : '';

// Keep a trace of the course and session from which we are getting out, to
// enable proper course logout tracking in courseLogout()
$logoutInfo = [];
if (!empty($logout) || !empty($cidReset)) {
    $uid = 0;
    if (!empty($_SESSION['_user']) && !empty($_SESSION['_user']['user_id'])) {
        $uid = $_SESSION['_user']['user_id'];
    }
    $cid = 0;
    if (!empty($_SESSION['_cid'])) {
        $cid = api_get_course_int_id($_SESSION['_cid']);
    }
    $logoutInfo = [
        'uid' => $uid,
        'cid' => $cid,
        'sid' => api_get_session_id(),
    ];
}

$courseCodeFromSession = api_get_course_id();

// $cidReq can be set in URL-parameter
$cidReq = isset($_GET['cidReq']) ? Database::escape_string($_GET['cidReq']) : '';
$cidReset = isset($cidReset) ? (bool) $cidReset : false;

// $cDir is a special url param sent from a redirection from /courses/[DIR]/index.php...
// It replaces cidReq in some opportunities
$cDir = isset($_GET['cDir']) && !empty($_GET['cDir']) ? $_GET['cDir'] : '';

// if there is a cDir parameter in the URL and $cidReq could not be determined
if (!empty($cDir) && empty($cidReq)) {
    $courseCode = CourseManager::getCourseCodeFromDirectory($cDir);
    if (!empty($courseCode)) {
        $cidReq = $courseCode;
    }
}

if (empty($cidReq) && !empty($courseCodeFromSession)) {
    $cidReq = $courseCodeFromSession;
}

if (empty($cidReset)) {
    if ($courseCodeFromSession != $cidReq) {
        $cidReset = true;
    }
} else {
    $cidReq = null;
}

$gidReset = isset($gidReset) ? $gidReset : '';
// $gidReset can be set in URL-parameter

// parameters passed via POST
$login = isset($_POST["login"]) ? $_POST["login"] : '';
// register if the user is just logging in, in order to redirect him
$logging_in = false;

/*  MAIN CODE  */
if (array_key_exists('forceCASAuthentication', $_POST)) {
    unset($_SESSION['_user']);
    unset($_user);
    if (api_is_anonymous()) {
        Session::destroy();
    }
}

// Not allowed for users with auth_source ims_lti outsite a tool provider
if ('true' === api_get_plugin_setting('lti_provider', 'enabled')) {
    global $cidReset;
    require_once api_get_path(SYS_PLUGIN_PATH).'lti_provider/src/LtiProvider.php';
    $isLtiRequest = LtiProvider::create()->isLtiRequest($_REQUEST, $_SESSION);
    $user = api_get_user_info();
    if ($cidReset) {
        $isLtiRequest = false;
    }
    if (!empty($user) && IMS_LTI_SOURCE === $user['auth_source'] && !$isLtiRequest) {
        if (isset($_SESSION['_ltiProvider']) && !empty($_SESSION['_ltiProvider']['launch_url'])) {
            $redirectLti = $_SESSION['_ltiProvider']['launch_url'].'&from=lti_provider';
            header('Location: '.$redirectLti);
            exit;
        }
    }
}

if (!empty($_SESSION['_user']['user_id']) && !($login || $logout)) {
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

    $termsAndCondition = Session::read('term_and_condition');

    // Platform legal terms and conditions
    if (api_get_setting('allow_terms_conditions') === 'true' &&
        api_get_setting('load_term_conditions_section') === 'login'
    ) {
        if (isset($_POST['login']) && isset($_POST['password']) &&
            isset($termsAndCondition['user_id'])
        ) {
            // user id
            $user_id = $termsAndCondition['user_id'];
            // Update the terms & conditions
            $legal_type = null;
            //verify type of terms and conditions
            if (isset($_POST['legal_info'])) {
                $info_legal = explode(':', $_POST['legal_info']);
                $legal_type = LegalManager::get_type_of_terms_and_conditions(
                    $info_legal[0],
                    $info_legal[1]
                );
            }

            // is necessary verify check
            if ($legal_type == 1) {
                if ((isset($_POST['legal_accept']) && $_POST['legal_accept'] == '1')) {
                    $legal_option = true;
                } else {
                    $legal_option = false;
                }
            }

            // no is check option
            if ($legal_type == 0) {
                $legal_option = true;
            }

            if (isset($_POST['legal_accept_type']) && $legal_option === true) {
                $cond_array = explode(':', $_POST['legal_accept_type']);
                if (!empty($cond_array[0]) && !empty($cond_array[1])) {
                    $time = time();
                    $condition_to_save = intval($cond_array[0]).':'.intval($cond_array[1]).':'.$time;
                    UserManager::update_extra_field_value(
                        $user_id,
                        'legal_accept',
                        $condition_to_save
                    );
                }
            }
        }
    }

    // IF cas is activated and user isn't logged in
    if ('true' === api_get_setting('cas_activate')
        && !isset($_user['user_id'])
        && !isset($_POST['login'])
        && !$logout
    ) {
        // load the CAS system to authenticate the user
        require_once __DIR__.'/../auth/cas/cas_var.inc.php';

        $load = true;
        if (isset($cas['skip_force_redirect_in'])) {
            $skipCas = $cas['skip_force_redirect_in'];
            foreach ($skipCas as $folder) {
                if (false !== strpos($_SERVER['REQUEST_URI'], $folder)) {
                    $load = false;
                    break;
                }
            }
        }

        if ($load) {
            // redirect to CAS server if not authenticated yet and so configured
            if (is_array($cas) && array_key_exists('force_redirect', $cas) && $cas['force_redirect']
                || array_key_exists('forceCASAuthentication', $_POST)
                || array_key_exists('checkLoginCas', $_GET)
                || array_key_exists('ticket', $_GET)
            ) {
                phpCAS::forceAuthentication();
            }
            // check whether we are authenticated
            if (phpCAS::isAuthenticated()) {
                // the user was successfully authenticated by the CAS server, read its CAS user identification
                $casUser = phpCAS::getUser();

                // make sure the user exists in the database
                $login = UserManager::casUserLoginName($casUser);
                $_user = null;
                if (false === $login) {
                    // the CAS-authenticated user does not yet exist in internal database
                    // see whether we are supposed to create it
                    switch (api_get_setting('cas_add_user_activate')) {
                        case PLATFORM_AUTH_SOURCE:
                            // create the new user from its CAS user identifier
                            $login = UserManager::createCASAuthenticatedUserFromScratch($casUser);
                            $_user = api_get_user_info_from_username($login);
                            UserManager::updateCasUser($_user);

                            break;

                        case LDAP_AUTH_SOURCE:
                            // find the new user's LDAP record from its CAS user identifier and copy information
                            $login = UserManager::createCASAuthenticatedUserFromLDAP($casUser);
                            $_user = api_get_user_info_from_username($login);
                            break;

                        default:
                            // no automatic user creation is configured, just complain about it
                            throw new Exception(get_lang('NoUserMatched'));
                    }
                } else {
                    $_user = api_get_user_info_from_username($login);
                    switch (api_get_setting('cas_add_user_activate')) {
                        case PLATFORM_AUTH_SOURCE:
                            UserManager::updateCasUser($_user);

                            break;
                    }
                }
                // $login is set and the user exists in the database

                // Check if the account is active (not locked)
                if ($_user['active'] == '1') {
                    // Check if the expiration date has not been reached
                    if ($_user['expiration_date'] > date('Y-m-d H:i:s')
                        || empty($_user['expiration_date'])
                    ) {
                        global $_configuration;

                        if (api_is_multiple_url_enabled()) {
                            // Check if user is an admin
                            $my_user_is_admin = UserManager::is_admin($_user['user_id']);

                            // This user is subscribed in these sites => $my_url_list
                            $my_url_list = api_get_access_url_from_user($_user['user_id']);

                            //Check the access_url configuration setting if
                            // the user is registered in the access_url_rel_user table
                            //Getting the current access_url_id of the platform
                            $current_access_url_id = api_get_current_access_url_id();

                            // the user have the permissions to enter at this site
                            if (is_array($my_url_list) &&
                                in_array($current_access_url_id, $my_url_list)
                            ) {
                            } else {
                                phpCAS::logout();
                                $location = api_get_path(WEB_PATH)
                                    .'index.php?loginFailed=1&error=access_url_inactive';
                                header('Location: '.$location);
                                exit;
                            }
                        }
                        Session::write('_user', $_user);
                        Event::eventLogin($_user['user_id']);
                    } else {
                        phpCAS::logout();
                        header(
                            'Location: '.api_get_path(WEB_PATH)
                            .'index.php?loginFailed=1&error=account_expired'
                        );
                        exit;
                    }
                } else {
                    phpCAS::logout();
                    header(
                        'Location: '.api_get_path(WEB_PATH)
                        .'index.php?loginFailed=1&error=account_inactive'
                    );
                    exit;
                }
                // update the user record from LDAP if so required by settings
                if ('true' === api_get_setting("update_user_info_cas_with_ldap")) {
                    UserManager::updateUserFromLDAP($login);
                }

                $doNotRedirectToCourse = true; // we should already be on the right page, no need to redirect
            }
        }
        //If plugin oauth2 is activated with force_redirect and user isn't logged in
    } elseif ('true' === api_get_plugin_setting('oauth2', 'enable')
        && 'true' === api_get_plugin_setting('oauth2', 'force_redirect')
        && !isset($_user['user_id'])
        && !isset($_POST['login'])
        && !$logout
    ) {
        $skipFolderOauth = [];
        $skipFolderOauth = explode(',', api_get_plugin_setting('oauth2', 'skip_force_redirect_in'));
        $load = true;
        foreach ($skipFolderOauth as $folder) {
            if (false !== strpos($_SERVER['REQUEST_URI'], $folder)) {
                $load = false;
                break;
            }
        }
        if ($load) {
            $plugin = OAuth2::create();
            $provider = $plugin->getProvider();
            // If we don't have an authorization code then get one
            if (!array_key_exists('code', $_GET)) {
                // Fetch the authorization URL from the provider; this returns the
                // urlAuthorize option and generates and applies any necessary parameters
                // (e.g. state).
                $authorizationUrl = $provider->getAuthorizationUrl();

                // Get the state generated for you and store it to the session.
                ChamiloSession::write('oauth2state', $provider->getState());

                // Redirect the user to the authorization URL.
                header('Location: '.$authorizationUrl);
                exit;
            }
            // Check given state against previously stored one to mitigate CSRF attack
            if (!array_key_exists('state', $_GET) || ($_GET['state'] !== ChamiloSession::read('oauth2state'))) {
                ChamiloSession::erase('oauth2state');
                exit('Invalid state');
            }
        }
    } elseif (isset($_POST['login']) && isset($_POST['password'])) {
        // $login && $password are given to log in
        if (empty($login) || !empty($_POST['login'])) {
            $login = $_POST['login'];
            $password = $_POST['password'];
        }

        $userManager = UserManager::getManager();
        $userRepository = UserManager::getRepository();

        // Lookup the user in the main database
        $user_table = Database::get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT user_id, username, password, auth_source, active, expiration_date, status, salt, last_login
                FROM $user_table
                WHERE username = '".Database::escape_string($login)."'";
        $result = Database::query($sql);

        $captchaValidated = true;
        $captcha = api_get_setting('allow_captcha');
        $allowCaptcha = $captcha == 'true';

        if (Database::num_rows($result) > 0) {
            $uData = Database::fetch_array($result, 'ASSOC');
            if ($allowCaptcha) {
                // Checking captcha
                if (isset($_POST['captcha'])) {
                    // Check captcha
                    $captchaText = $_POST['captcha'];
                    /** @var Text_CAPTCHA $obj */
                    $obj = isset($_SESSION['template.lib']) ? $_SESSION['template.lib'] : null;
                    if ($obj) {
                        $obj->getPhrase();
                        if ($obj->getPhrase() != $captchaText) {
                            $captchaValidated = false;
                        } else {
                            $captchaValidated = true;
                        }
                    }
                    if (isset($_SESSION['captcha_question'])) {
                        $captcha_question = $_SESSION['captcha_question'];
                        $captcha_question->destroy();
                    }
                }

                // Redirect to login page
                if ($captchaValidated == false) {
                    $loginFailed = true;
                    Session::erase('_uid');
                    Session::write('loginFailed', '1');

                    header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=wrong_captcha');
                    exit;
                }

                // Check if account is blocked by captcha user extra field see function api_block_account_captcha()
                $blockedUntilDate = api_get_user_blocked_by_captcha($login);

                if (isset($blockedUntilDate) && !empty($blockedUntilDate)) {
                    if (time() > api_strtotime($blockedUntilDate, 'UTC')) {
                        api_clean_account_captcha($login);
                    } else {
                        $loginFailed = true;
                        Session::erase('_uid');
                        Session::write('loginFailed', '1');

                        header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=blocked_by_captcha');
                        exit;
                    }
                }
            }

            if ($uData['auth_source'] == PLATFORM_AUTH_SOURCE ||
                $uData['auth_source'] == CAS_AUTH_SOURCE
            ) {
                $validPassword = isset($password) && UserManager::checkPassword(
                        $uData['password'],
                        $password,
                        $uData['salt'],
                        $uData['user_id']
                    );

                $checkUserFromExternalWebservice = false;
                // If user can't connect directly to chamilo then check the webservice setting
                if ($validPassword === false) {
                    // Use external webservice to
                    $options = api_get_configuration_value('webservice_validation');
                    if (!empty($options) && isset($options['options']) && !empty($options['options'])) {
                        $options = $options['options'];
                        $soapclient = new nusoap_client($options['wsdl']);
                        $function = $options['check_login_function'];
                        $params = [
                            'login' => $uData['username'],
                            'password' => $password,
                        ];
                        $result = $soapclient->call($function, [serialize($params)]);
                        if ($error = $soapclient->getError()) {
                            error_log('error');
                            error_log(print_r($error, 1));
                        } elseif ((int) $result === 1) {
                            $checkUserFromExternalWebservice = true;
                        }
                    }

                    $checkLoginCredentialHook = CheckLoginCredentialsHook::create();

                    if (!empty($checkLoginCredentialHook)) {
                        $checkLoginCredentialHook->setEventData([
                            'user' => $uData,
                            'credentials' => [
                                'username' => $login,
                                'password' => $password,
                            ],
                        ]);
                        $validPassword = $checkLoginCredentialHook->notifyLoginCredentials();
                    }
                }

                // Check the user's password
                if (($validPassword || $checkUserFromExternalWebservice) &&
                    (trim($login) == $uData['username'])
                ) {
                    // Means that the login was loaded in a different page than index.php
                    // we force the reload of the course access using cidReset just in case
                    if (isset($_REQUEST['redirect_after_not_allow_page']) &&
                        $_REQUEST['redirect_after_not_allow_page'] == 1
                    ) {
                        $cidReset = true;
                    }
                    $update_type = UserManager::get_extra_user_data_by_field(
                        $uData['user_id'],
                        'update_type'
                    );

                    $update_type = isset($update_type['update_type']) ? $update_type['update_type'] : '';
                    if (!empty($extAuthSource[$update_type]['updateUser'])
                        && file_exists($extAuthSource[$update_type]['updateUser'])
                    ) {
                        include_once $extAuthSource[$update_type]['updateUser'];
                    }

                    // Check if the account is active (not locked)
                    if ($uData['active'] == '1') {
                        // Check if the expiration date has not been reached
                        if ($uData['expiration_date'] > date('Y-m-d H:i:s')
                            || empty($uData['expiration_date'])
                        ) {
                            global $_configuration;

                            if (api_is_multiple_url_enabled()) {
                                // Check if user is an admin
                                $my_user_is_admin = UserManager::is_admin($uData['user_id']);

                                // This user is subscribed in these sites => $my_url_list
                                $my_url_list = api_get_access_url_from_user($uData['user_id']);

                                //Check the access_url configuration setting if
                                // the user is registered in the access_url_rel_user table
                                //Getting the current access_url_id of the platform
                                $current_access_url_id = api_get_current_access_url_id();

                                if ($my_user_is_admin === false) {
                                    // the user have the permissions to enter at this site
                                    if (is_array($my_url_list) &&
                                        in_array($current_access_url_id, $my_url_list)
                                    ) {
                                        UserManager::redirectToResetPassword($uData['user_id']);
                                        ConditionalLogin::check_conditions($uData);

                                        $_user['user_id'] = $uData['user_id'];
                                        $_user['status'] = $uData['status'];
                                        Session::write('_user', $_user);
                                        Event::eventLoginAttempt($uData['username'], true);
                                        Event::eventLogin($_user['user_id']);
                                        $logging_in = true;
                                    } else {
                                        $loginFailed = true;
                                        Session::erase('_uid');
                                        Session::write('loginFailed', '1');
                                        Event::eventLoginAttempt($uData['username']);
                                        UserManager::blockIfMaxLoginAttempts($uData);
                                        // Fix cas redirection loop
                                        // https://support.chamilo.org/issues/6124
                                        $location = api_get_path(WEB_PATH)
                                            .'index.php?loginFailed=1&error=access_url_inactive';
                                        header('Location: '.$location);
                                        exit;
                                    }
                                } else {
                                    // Only admins of the "main" (first) Chamilo portal can login wherever they want
                                    if (in_array(1, $my_url_list)) {
                                        // Check if this admin have the access_url_id = 1 which means the principal
                                        ConditionalLogin::check_conditions($uData);
                                        $_user['user_id'] = $uData['user_id'];
                                        $_user['status'] = $uData['status'];
                                        Session::write('_user', $_user);
                                        Event::eventLogin($_user['user_id']);
                                        Event::eventLoginAttempt($uData['username'], true);
                                        $logging_in = true;
                                    } else {
                                        //This means a secondary admin wants to login so we check as he's a normal user
                                        if (in_array($current_access_url_id, $my_url_list)) {
                                            UserManager::redirectToResetPassword($uData['user_id']);
                                            $_user['user_id'] = $uData['user_id'];
                                            $_user['status'] = $uData['status'];
                                            Session::write('_user', $_user);
                                            Event::eventLogin($_user['user_id']);
                                            Event::eventLoginAttempt($uData['username'], true);
                                            $logging_in = true;
                                        } else {
                                            $loginFailed = true;
                                            Session::erase('_uid');
                                            Session::write('loginFailed', '1');
                                            Event::eventLoginAttempt($uData['username']);
                                            UserManager::blockIfMaxLoginAttempts($uData);
                                            header(
                                                'Location: '.api_get_path(WEB_PATH)
                                                .'index.php?loginFailed=1&error=access_url_inactive'
                                            );
                                            exit;
                                        }
                                    }
                                }
                            } else {
                                UserManager::redirectToResetPassword($uData['user_id']);
                                ConditionalLogin::check_conditions($uData);
                                $_user['user_id'] = $uData['user_id'];
                                $_user['status'] = $uData['status'];

                                Session::write('_user', $_user);
                                Event::eventLogin($uData['user_id']);
                                Event::eventLoginAttempt($uData['username'], true);
                                $logging_in = true;
                            }
                        } else {
                            $loginFailed = true;
                            Session::erase('_uid');
                            Session::write('loginFailed', '1');
                            header(
                                'Location: '.api_get_path(WEB_PATH)
                                .'index.php?loginFailed=1&error=account_expired'
                            );
                            exit;
                        }
                    } else {
                        $loginFailed = true;
                        Session::erase('_uid');
                        Session::write('loginFailed', '1');
                        header(
                            'Location: '.api_get_path(WEB_PATH)
                            .'index.php?loginFailed=1&error=account_inactive'
                        );
                        exit;
                    }
                } else {
                    // login failed: username or password incorrect
                    $loginFailed = true;
                    Session::erase('_uid');
                    Session::write('loginFailed', '1');
                    Event::eventLoginAttempt($uData['username']);
                    UserManager::blockIfMaxLoginAttempts($uData);

                    if ($allowCaptcha) {
                        if (isset($_SESSION['loginFailedCount'])) {
                            $_SESSION['loginFailedCount']++;
                        } else {
                            $_SESSION['loginFailedCount'] = 1;
                        }

                        $numberMistakesToBlockAccount = api_get_setting('captcha_number_mistakes_to_block_account');

                        if (isset($_SESSION['loginFailedCount'])) {
                            if ($_SESSION['loginFailedCount'] >= $numberMistakesToBlockAccount) {
                                api_block_account_captcha($login);
                            }
                        }
                    }

                    header(
                        'Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=user_password_incorrect'
                    );
                    exit;
                }

                // update user expiration date when the login is the first time
                if (isset($_user['status']) && STUDENT == $_user['status']) {
                    $userExpirationXDate = api_get_configuration_value('update_student_expiration_x_date');
                    $userSpentTime = Tracking::get_time_spent_on_the_platform($_user['user_id']);
                    if (false !== $userExpirationXDate && empty($userSpentTime)) {
                        $expDays = (int) $userExpirationXDate['days'];
                        $expMonths = (int) $userExpirationXDate['months'];
                        $date = new DateTime();
                        $duration = "P{$expMonths}M{$expDays}D";
                        $date->add(new DateInterval($duration));
                        $newExpirationDate = $date->format('Y-m-d H:i:s');
                        UserManager::updateExpirationDate($_user['user_id'], $newExpirationDate);
                    }
                }

                if (isset($uData['creator_id']) && $_user['user_id'] != $uData['creator_id']) {
                    //first login for a not self registered
                    //e.g. registered by a teacher
                    //do nothing (code may be added later)
                }
            } elseif (!empty($extAuthSource[$uData['auth_source']]['login'])
                && file_exists($extAuthSource[$uData['auth_source']]['login'])
                ) {
                /*
                 * Process external authentication
                 * on the basis of the given login name
                 */
                $loginFailed = true; // Default initialisation. It could
                // change after the external authentication
                $key = $uData['auth_source']; //'ldap','shibboleth'...

                // Check if organisationemail email exist for this user and replace the current login with
                $extraFieldValue = new ExtraFieldValue('user');
                $newLogin = $extraFieldValue->get_values_by_handler_and_field_variable(
                    $uData['user_id'],
                    'organisationemail'
                );

                if (!empty($newLogin) && isset($newLogin['value'])) {
                    $login = $newLogin['value'];
                }

                /* >>>>>>>> External authentication modules <<<<<<<<< */
                // see configuration.php to define these
                include_once $extAuthSource[$key]['login'];
            /* >>>>>>>> External authentication modules <<<<<<<<< */
            } else { // no standard Chamilo login - try external authentication
                //huh... nothing to do... we shouldn't get here
                error_log(
                    'Chamilo Authentication file defined in'.
                    ' $extAuthSource could not be found - this might prevent'.
                    ' your system from doing the corresponding authentication'.
                    ' process',
                    0
                );
            }
        } else {
            Event::eventLoginAttempt($login);
            UserManager::blockIfMaxLoginAttempts(['username' => $login, 'last_login' => null]);

            $extraFieldValue = new ExtraFieldValue('user');
            $uData = $extraFieldValue->get_item_id_from_field_variable_and_field_value(
                'organisationemail',
                $login
            );
            if (!empty($uData)) {
                $uData = api_get_user_info($uData['item_id']);

                if (!empty($extAuthSource[$uData['auth_source']]['login'])
                    && file_exists($extAuthSource[$uData['auth_source']]['login'])
                ) {
                    /*
                     * Process external authentication
                     * on the basis of the given login name
                     */
                    $loginFailed = true; // Default initialisation. It could
                    // change after the external authentication
                    $key = $uData['auth_source']; //'ldap','shibboleth'...

                    /* >>>>>>>> External authentication modules <<<<<<<<< */
                    // see configuration.php to define these
                    include_once $extAuthSource[$key]['login'];
                }
            } else {
                // change after the external authentication
                // login failed, Database::num_rows($result) <= 0
                $loginFailed = true; // Default initialisation. It could
            }

            // login failed, Database::num_rows($result) <= 0
            $loginFailed = true; // Default initialisation. It could
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
             * responsibility of the external login script
             * to provide this $_user['user_id'].
             */
            if (isset($extAuthSource) && is_array($extAuthSource)) {
                foreach ($extAuthSource as $thisAuthSource) {
                    if (!empty($thisAuthSource['login']) && file_exists($thisAuthSource['login'])) {
                        include_once $thisAuthSource['login'];
                    }
                    if (isset($thisAuthSource['newUser']) && file_exists($thisAuthSource['newUser'])) {
                        include_once $thisAuthSource['newUser'];
                    } else {
                        error_log(
                            'Chamilo Authentication external file'.
                            ' could not be found - this might prevent your system from using'.
                            ' the authentication process in the user creation process',
                            0
                        );
                    }
                }
            } //end if is_array($extAuthSource)

            $checkUserInfo = Session::read('_user');
            if ($loginFailed && empty($checkUserInfo)) {
                //If we are here username given is wrong
                Session::write('loginFailed', '1');
                header(
                    'Location: '.api_get_path(WEB_PATH)
                    .'index.php?loginFailed=1&error=user_password_incorrect'
                );
                exit;
            }
        } //end else login failed
    } elseif (api_get_setting('sso_authentication') === 'true'
        && !in_array('webservices', explode('/', $_SERVER['REQUEST_URI']))
        ) {
        /**
         * TODO:
         * - Work on a better validation for webservices paths. Current is very poor and exit.
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
            if ($logout) {
                // Make custom redirect after logout
                online_logout($_SESSION['_user']['user_id'], false);
                Event::courseLogout($logoutInfo);
                $osso->logout(); //redirects and exits
            }
        } elseif (!$logout) {
            // Handle cookie from Master Server
            $forceSsoRedirect = api_get_setting('sso_force_redirect');
            if ($forceSsoRedirect === 'true') {
                // all users to be redirected unless they are connected (removed req on sso_cookie)
                $redirectToMasterConditions = !isset($_REQUEST['sso_referer']) && !isset($_GET['loginFailed']);
            } else {
                //  Users to still see the homepage without connecting
                $redirectToMasterConditions = !isset($_REQUEST['sso_referer']) && !isset($_GET['loginFailed']) && isset($_GET['sso_cookie']);
            }

            if ($redirectToMasterConditions) {
                // Redirect to master server
                $osso->ask_master();
            } elseif (isset($_REQUEST['sso_cookie'])) {
                // Here we are going to check the origin of
                // what the call says should be used for
                // authentication, and ensure  we know it
                $matches_domain = false;
                if (isset($_REQUEST['sso_referer'])) {
                    $protocol = api_get_setting('sso_authentication_protocol');
                    // sso_authentication_domain can list
                    // several, comma-separated, domains
                    $master_urls = preg_split('/,/', api_get_setting('sso_authentication_domain'));
                    if (!empty($master_urls)) {
                        $master_auth_uri = api_get_setting('sso_authentication_auth_uri');
                        foreach ($master_urls as $mu) {
                            if (empty($mu)) {
                                continue;
                            }
                            // For each URL, check until we find *one* that matches the $_GET['sso_referer'],
                            //  then skip other possibilities
                            // Do NOT compare the whole referer, as this might cause confusing errors with friendly urls,
                            // like in Drupal /?q=user& vs /user?
                            $referrer = substr($_REQUEST['sso_referer'], 0, strrpos($_REQUEST['sso_referer'], '/'));
                            if ($protocol.trim($mu) === $referrer) {
                                $matches_domain = true;
                                break;
                            }
                        }
                    } else {
                        error_log(
                            'Your sso_authentication_master param is empty. '.
                            'Check the platform configuration, security section. '.
                            'It can be a list of comma-separated domains'
                        );
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
                    Session::write('loginFailed', '1');
                    header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=unrecognize_sso_origin');
                    exit;
                }
            }
            //end logout ... else ... login
        } elseif ($logout) {
            //if there was an attempted logout without a previous login, log
            // this anonymous user out as well but avoid redirect
            online_logout(null, false);
            Event::courseLogout($logoutInfo);
            $osso->logout(); //redirects and exits
        }
    } elseif (api_get_setting('openid_authentication') == 'true') {
        if (!empty($_POST['openid_url'])) {
            include api_get_path(SYS_CODE_PATH).'auth/openid/login.php';
            openid_begin(trim($_POST['openid_url']), api_get_path(WEB_PATH).'index.php');
            //this last function should trigger a redirect, so we can die here safely
            exit('Openid login redirection should be in progress');
        } elseif (!empty($_GET['openid_identity'])) { //it's usual for PHP to replace '.' (dot) by '_' (underscore) in URL parameters
            include api_get_path(SYS_CODE_PATH).'auth/openid/login.php';
            $res = openid_complete($_GET);
            if ($res['status'] == 'success') {
                $id1 = Database::escape_string($res['openid.identity']);
                //have another id with or without the final '/'
                $id2 = (substr($id1, -1, 1) == '/' ? substr($id1, 0, -1) : $id1.'/');
                //lookup the user in the main database
                $user_table = Database::get_main_table(TABLE_MAIN_USER);
                $sql = "SELECT user_id, username, password, auth_source, active, expiration_date
                        FROM $user_table
                        WHERE openid = '$id1'
                        OR openid = '$id2' ";
                $result = Database::query($sql);
                if ($result !== false) {
                    if (Database::num_rows($result) > 0) {
                        $uData = Database::fetch_array($result);

                        if ($uData['auth_source'] == PLATFORM_AUTH_SOURCE) {
                            //the authentification of this user is managed by Chamilo itself

                            // check if the account is active (not locked)
                            if ($uData['active'] == '1') {
                                // check if the expiration date has not been reached
                                if ($uData['expiration_date'] > date('Y-m-d H:i:s')
                                    || empty($uData['expiration_date'])
                                ) {
                                    $_user['user_id'] = $uData['user_id'];
                                    $_user['status'] = $uData['status'];

                                    Session::write('_user', $_user);
                                    Event::eventLogin($_user['user_id']);
                                } else {
                                    $loginFailed = true;
                                    Session::erase('_uid');
                                    Session::write('loginFailed', '1');
                                    header('Location: index.php?loginFailed=1&error=account_expired');
                                    exit;
                                }
                            } else {
                                $loginFailed = true;
                                Session::erase('_uid');
                                Session::write('loginFailed', '1');
                                header('Location: index.php?loginFailed=1&error=account_inactive');
                                exit;
                            }
                            if (isset($uData['creator_id']) && $_user['user_id'] != $uData['creator_id']) {
                                //first login for a not self registred
                                //e.g. registered by a teacher
                                //do nothing (code may be added later)
                            }
                        }
                    } else {
                        // Redirect to the subscription form
                        Session::write('loginFailed', '1');
                        header(
                            'Location: '.api_get_path(WEB_CODE_PATH)
                            .'auth/inscription.php?username='.$res['openid.sreg.nickname']
                            .'&email='.$res['openid.sreg.email']
                            .'&openid='.$res['openid.identity']
                            .'&openid_msg=idnotfound'
                        );
                        exit;
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
    $cidReset = true;
    $gidReset = true;
} // end else

$maxAnons = api_get_configuration_value('max_anonymous_users');

 // Now check for anonymous user mode
if (isset($use_anonymous) && $use_anonymous) {
    //if anonymous mode is set, then try to set the current user as anonymous
    //if he doesn't have a login yet
    $anonResult = api_set_anonymous();
    if ($maxAnons >= 2 && $anonResult) {
        $uidReset = true;
        Event::eventLogin($_user['user_id']);
    }
} else {
    //if anonymous mode is not set, then check if this user is anonymous. If it
    //is, clean it from being anonymous (make him a nobody :-))
    api_clear_anonymous();
}

// if the requested course is different from the course in session
if (!empty($cidReq) && (!isset($_SESSION['_cid']) ||
    (isset($_SESSION['_cid']) && $cidReq != $_SESSION['_cid']))
) {
    $cidReset = true;
    $gidReset = true; // As groups depend from courses, group id is reset
}

/* USER INIT */
if (isset($uidReset) && $uidReset) {
    // session data refresh requested
    unset($_SESSION['_user']['uidReset']);
    $is_platformAdmin = false;
    $is_allowedCreateCourse = false;
    $isAnonymous = api_is_anonymous();
    if ($maxAnons >= 2) {
        $isAnonymous = false;
    }

    if (isset($_user['user_id']) && $_user['user_id'] && !$isAnonymous) {
        // a uid is given (log in succeeded)
        $_SESSION['loginFailed'] = false;
        unset($_SESSION['loginFailedCount']);
        unset($_SESSION['loginToBlock']);

        $user_table = Database::get_main_table(TABLE_MAIN_USER);
        $admin_table = Database::get_main_table(TABLE_MAIN_ADMIN);
        $track_e_login = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);

        $sql = "SELECT * FROM $user_table WHERE id = ".$_user['user_id'];
        $result = Database::query($sql);

        if (Database::num_rows($result) > 0) {
            // Extracting the user data
            $uData = Database::fetch_array($result);
            $_user = _api_format_user($uData, false);
            $is_platformAdmin = UserManager::is_admin($_user['user_id']);
            $is_allowedCreateCourse = $uData['status'] == COURSEMANAGER || (api_get_setting('drhCourseManagerRights') && $uData['status'] == DRH);
            ConditionalLogin::check_conditions($uData);

            Session::write('_user', $_user);
            UserManager::update_extra_field_value($_user['id'], 'already_logged_in', 'true');
            Session::write('is_platformAdmin', $is_platformAdmin);
            Session::write('is_allowedCreateCourse', $is_allowedCreateCourse);
        } else {
            header('Location:'.api_get_path(WEB_PATH));
            exit;
        }
    } else {
        if (!api_is_anonymous()) {
            // no uid => logout or Anonymous
            Session::erase('_user');
            Session::erase('_uid');
        }
    }
    Session::write('is_platformAdmin', $is_platformAdmin);
    Session::write('is_allowedCreateCourse', $is_allowedCreateCourse);
} else { // continue with the previous values
    $_user = $_SESSION['_user'];
    $is_platformAdmin = isset($_SESSION['is_platformAdmin']) ? $_SESSION['is_platformAdmin'] : false;
    $is_allowedCreateCourse = isset($_SESSION['is_allowedCreateCourse']) ? $_SESSION['is_allowedCreateCourse'] : false;
}

$logoutCourseCalled = false;
if (!isset($_SESSION['login_as'])) {
    $save_course_access = true;
    $_course = Session::read('_course');
    if ($_course && isset($_course['real_id'])) {
        // The value  $_dont_save_user_course_access should be added before
        // the call of global.inc.php see the main/inc/chat.ajax.php file
        // Disables the updates in the TRACK_E_COURSE_ACCESS table
        if (isset($_dont_save_user_course_access) && $_dont_save_user_course_access == true) {
            $save_course_access = false;
        } else {
            $logoutCourseCalled = true;
            Event::courseLogout($logoutInfo);
        }
    }
}

$sessionIdFromGet = isset($_GET['id_session']) ? (int) $_GET['id_session'] : false;
// if a session id has been given in url, we store the session if course was set:
$sessionIdFromSession = api_get_session_id();
$checkFromDatabase = false;
// User change from session id
if (($sessionIdFromGet !== false && $sessionIdFromGet !== $sessionIdFromSession) || $cidReset) {
    $cidReset = true;
    $checkFromDatabase = true;
    Session::erase('session_name');
    Session::erase('id_session');

    // Deleting session from $_SESSION means also deleting $_SESSION['_course'] and group info
    Session::erase('_real_cid');
    Session::erase('_cid');
    Session::erase('oLP');
    Session::erase('_course');
    Session::erase('_gid');
}

if ($checkFromDatabase && !empty($sessionIdFromGet)) {
    $sessionInfo = api_get_session_info($sessionIdFromGet);
    if (!empty($sessionInfo)) {
        Session::write('session_name', $sessionInfo['name']);
        Session::write('id_session', $sessionInfo['id']);
    } else {
        $cidReset = true;
        $gidReset = true;
        Session::erase('session_name');
        Session::erase('id_session');

        // Deleting session from $_SESSION means also deleting $_SESSION['_course'] and group info
        Session::erase('_real_cid');
        Session::erase('_cid');
        Session::erase('oLP');
        Session::erase('_course');
        Session::erase('_gid');
        api_not_allowed(true);
    }
}

/*  COURSE INIT */
if ($cidReset) {
    // Course session data refresh requested or empty data
    if ($cidReq) {
        $_course = api_get_course_info($cidReq);

        if (!empty($_course)) {
            //@TODO real_cid should be cid, for working with numeric course id
            $_real_cid = $_course['real_id'];
            $_cid = $_course['code'];

            Session::write('_real_cid', $_real_cid);
            Session::write('_cid', $_cid);
            Session::write('_course', $_course);

            if (!empty($_GET['gidReq'])) {
                $_SESSION['_gid'] = (int) $_GET['gidReq'];
            } else {
                Session::erase('_gid');
            }

            // Course login
            if (isset($_user['user_id'])) {
                Event::eventCourseLogin(
                    api_get_course_int_id(),
                    api_get_user_id(),
                    api_get_session_id()
                );
            }
        } else {
            //exit("WARNING UNDEFINED CID !! ");
            header('Location:'.api_get_path(WEB_PATH));
            exit;
        }
    } else {
        // Leave a logout time in the track_e_course_access table if we were in a course
        if ($logoutCourseCalled == false) {
            Event::courseLogout($logoutInfo);
        }
        Session::erase('_cid');
        Session::erase('oLP');
        Session::erase('_real_cid');
        Session::erase('_course');
        Session::erase('session_name');
        Session::erase('id_session');

        if (!empty($_SESSION)) {
            foreach ($_SESSION as $key => $session_item) {
                if (strpos($key, 'lp_autolaunch_') === false) {
                    continue;
                } else {
                    if (isset($_SESSION[$key])) {
                        Session::erase($key);
                    }
                }
            }
        }

        if (api_get_group_id()) {
            Session::erase('_gid');
        }

        if (api_is_in_gradebook()) {
            api_remove_in_gradebook();
        }
    }
} else {
    // Continue with the previous values
    if (empty($_SESSION['_course']) && !empty($_SESSION['_cid'])) {
        //Just in case $_course is empty we try to load if the c_id still exists
        $_course = api_get_course_info($_SESSION['_cid']);
        if (!empty($_course)) {
            $_real_cid = $_course['real_id'];
            $_cid = $_course['code'];

            Session::write('_real_cid', $_real_cid);
            Session::write('_cid', $_cid);
            Session::write('_course', $_course);
        }
    }

    if (empty($_SESSION['_course']) || empty($_SESSION['_cid'])) { //no previous values...
        $_cid = -1; // Set default values
        $_course = -1;
    } else {
        $_cid = $_SESSION['_cid'];
        $_course = $_SESSION['_course'];

        if (!empty($_REQUEST['gidReq'])) {
            $_SESSION['_gid'] = (int) $_REQUEST['gidReq'];

            $group_table = Database::get_course_table(TABLE_GROUP);
            $sql = "SELECT * FROM $group_table
                    WHERE c_id = ".$_course['real_id']." AND id = '$gidReq'";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) { // This group has recorded status related to this course
                $gpData = Database::fetch_array($result);
                $_gid = $gpData['id'];
                Session::write('_gid', $_gid);
            }
        } else {
            Session::write('_gid', 0);
        }
    }
}

/*  COURSE / USER REL. INIT */
$session_id = api_get_session_id();
$user_id = isset($_user['user_id']) ? $_user['user_id'] : null;

//Course permissions
//if this code is uncommented in some platforms the is_courseAdmin is not correctly saved see BT#5789
/*$is_courseAdmin     = false; //course teacher
$is_courseTutor     = false; //course teacher - some rights
$is_courseMember    = false; //course student
$is_session_general_coach     = false; //course coach
*/
// Course - User permissions
$is_sessionAdmin = false;
$is_session_general_coach = false; //course coach
$is_courseAdmin = false;
$is_courseTutor = false;
$is_courseMember = false;

if ((isset($uidReset) && $uidReset) || $cidReset) {
    if (isset($user_id) && $user_id && isset($_real_cid) && $_real_cid) {
        // Check if user is subscribed in a course
        $course_user_table = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $sql = "SELECT * FROM $course_user_table
                WHERE
                    user_id  = $user_id AND
                    relation_type <> ".COURSE_RELATION_TYPE_RRHH." AND
                    c_id = $_real_cid";
        $result = Database::query($sql);

        $cuData = null;
        if (Database::num_rows($result) > 0) { // this  user have a recorded state for this course
            $cuData = Database::fetch_array($result, 'ASSOC');
            $is_courseAdmin = $cuData['status'] == 1;
            $is_courseTutor = $cuData['is_tutor'] == 1;
            $is_courseMember = true;
        }

        // We are in a session course? Check session permissions
        if (!empty($session_id)) {
            if (!empty($_course)) {
                if (!SessionManager::relation_session_course_exist($session_id, $_course['real_id'])) {
                    // Deleting all access.
                    Session::erase('session_name');
                    Session::erase('id_session');
                    Session::erase('_real_cid');
                    Session::erase('_cid');
                    Session::erase('oLP');
                    Session::erase('_course');
                    Session::erase('_gid');
                    Session::erase('is_courseAdmin');
                    Session::erase('is_courseMember');
                    Session::erase('is_courseTutor');
                    Session::erase('is_session_general_coach');
                    Session::erase('is_allowed_in_course');
                    Session::erase('is_sessionAdmin');

                    api_not_allowed(true);
                }
            }

            // I'm not the teacher of the course
            if ($is_courseAdmin == false) {
                // This user has no status related to this course
                // The user is subscribed in a session? The user is a Session coach a Session admin?
                $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
                $tbl_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
                $tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

                // Session coach, session admin or course coach admin
                $sql = "SELECT session.id_coach, session_admin_id, session_rcru.user_id
                        FROM $tbl_session session, $tbl_session_course_user session_rcru
                        WHERE
                            session_rcru.session_id  = session.id AND
                            session_rcru.c_id = $_real_cid AND
                            session_rcru.user_id = $user_id AND
                            session_rcru.session_id = $session_id AND
                            session_rcru.status = 2
                        ";

                $result = Database::query($sql);
                $row = Database::store_result($result);

                // Am I a session admin?
                if (isset($row) && isset($row[0]) && $row[0]['session_admin_id'] == $user_id) {
                    $is_courseMember = false;
                    $is_courseTutor = false;
                    $is_courseAdmin = false;
                    $is_session_general_coach = false;
                    $is_sessionAdmin = true;
                } else {
                    // Am I a session coach for this session?
                    $sql = "SELECT session.id, session.id_coach
                            FROM $tbl_session session
                            INNER JOIN $tbl_session_course sc
                            ON sc.session_id = session.id
                            WHERE session.id = $session_id
                            AND session.id_coach = $user_id
                            AND sc.c_id = $_real_cid";
                    $result = Database::query($sql);

                    if (Database::num_rows($result)) {
                        $is_courseMember = true;
                        $is_courseTutor = false;
                        $is_session_general_coach = true;
                        $is_sessionAdmin = false;
                    } else {
                        // Am I a course coach or a student?
                        $sql = "SELECT user_id, status
                               FROM $tbl_session_course_user
                               WHERE
                                    c_id = $_real_cid AND
                                    user_id = $user_id AND
                                    session_id = $session_id
                               LIMIT 1";
                        $result = Database::query($sql);

                        if (Database::num_rows($result)) {
                            $row = Database::fetch_array($result, 'ASSOC');
                            $session_course_status = $row['status'];

                            switch ($session_course_status) {
                                case '2': // coach - teacher
                                    $is_courseMember = true;
                                    $is_courseTutor = true;
                                    $is_session_general_coach = false;
                                    $is_sessionAdmin = false;

                                    if (api_get_setting('extend_rights_for_coach') == 'true') {
                                        $is_courseAdmin = true;
                                    } else {
                                        $is_courseAdmin = false;
                                    }
                                    break;
                                case '0': //Student
                                    $is_courseMember = true;
                                    $is_courseTutor = false;
                                    $is_courseAdmin = false;
                                    $is_session_general_coach = false;
                                    $is_sessionAdmin = false;

                                    // Check course dependency
                                    $entityManager = Database::getManager();
                                    /** @var SequenceResourceRepository $repo */
                                    $repo = $entityManager->getRepository('ChamiloCoreBundle:SequenceResource');
                                    $sequences = $repo->getRequirements($_real_cid, SequenceResource::COURSE_TYPE);

                                    if ($sequences) {
                                        $sequenceList = $repo->checkRequirementsForUser($sequences, SequenceResource::COURSE_TYPE, $user_id, $session_id);
                                        $completed = $repo->checkSequenceAreCompleted($sequenceList);

                                        if (false === $completed) {
                                            api_not_allowed(true);
                                        }
                                    }

                                    break;
                                default:
                                    //unregister user
                                    $is_courseMember = false;
                                    $is_courseTutor = false;
                                    $is_courseAdmin = false;
                                    $is_sessionAdmin = false;
                                    $is_session_general_coach = false;
                                    break;
                            }
                        } else {
                            // Unregister user
                            $is_courseMember = false;
                            $is_courseTutor = false;
                            $is_courseAdmin = false;
                            $is_sessionAdmin = false;
                            $is_session_general_coach = false;
                        }
                    }
                }

                // Drh can enter to a course as an student see BT#6770
                if (api_drh_can_access_all_session_content()) {
                    $sessionInfo = SessionManager::getSessionFollowedByDrh($user_id, $session_id);
                    if (!empty($sessionInfo) && !empty($sessionInfo['course_list'])) {
                        if (isset($sessionInfo['course_list'][$_course['real_id']])) {
                            $is_courseMember = true;
                            $is_courseTutor = false;
                            $is_session_general_coach = false;
                            $is_sessionAdmin = false;
                        }
                    }
                }
            }

            // If I'm the admin platform i'm a teacher of the course
            if ($is_platformAdmin) {
                $is_courseAdmin = true;
            }
        } else {
            // User has no access to the course
            // This will check if the course was added in one of his sessions
            // Then it will be redirected to that course-session
            if ($is_courseMember === false && $is_platformAdmin === false) {
                // Search session
                $courseSession = SessionManager::searchCourseInSessionsFromUser(
                    $user_id,
                    $_course['real_id']
                );

                $priorityList = [];
                if (!empty($courseSession)) {
                    foreach ($courseSession as $courseSessionItem) {
                        if (isset($courseSessionItem['session_id'])) {
                            $customSessionId = $courseSessionItem['session_id'];
                            $visibility = api_get_session_visibility($customSessionId, $_course['real_id']);

                            if ($visibility == SESSION_INVISIBLE) {
                                continue;
                            }

                            switch ($visibility) {
                                case SESSION_AVAILABLE:
                                    $priorityList[1][] = $customSessionId;
                                    break;
                                case SESSION_VISIBLE:
                                    $priorityList[2][] = $customSessionId;
                                    break;
                                case SESSION_VISIBLE_READ_ONLY:
                                    $priorityList[3][] = $customSessionId;
                                    break;
                            }
                        }
                    }
                }

                if (!empty($priorityList)) {
                    ksort($priorityList);
                    foreach ($priorityList as $sessionList) {
                        if (empty($sessionList)) {
                            continue;
                        }
                        foreach ($sessionList as $customSessionId) {
                            $currentUrl = htmlentities($_SERVER['REQUEST_URI']);
                            $currentUrl = str_replace('id_session=0', '', $currentUrl);
                            $currentUrl = str_replace('&amp;', '&', $currentUrl);

                            if (strpos($currentUrl, '?') !== false) {
                                $currentUrl = rtrim($currentUrl, '&');
                                $url = $currentUrl.'&id_session='.$customSessionId;
                            } else {
                                $url = $currentUrl.'?id_session='.$customSessionId;
                            }
                            $url = str_replace('&&', '&', $url);
                            //$url = $_course['course_public_url'].'?id_session='.$customSessionId;

                            Session::erase('_real_cid');
                            Session::erase('_cid');
                            Session::erase('oLP');
                            Session::erase('_course');

                            header('Location: '.$url);
                            exit;
                        }
                    }
                }
            }
        }
    } else { // keys missing => not anymore in the course - user relation
        // course
        $is_courseMember = false;
        $is_courseAdmin = false;
        $is_courseTutor = false;
        $is_session_general_coach = false;
        $is_sessionAdmin = false;
    }

    if (isset($_cid) && $_cid) {
        $my_user_id = isset($user_id) ? (int) $user_id : 0;
        $variable = 'accept_legal_'.$my_user_id.'_'.$_course['real_id'].'_'.$session_id;

        $user_pass_open_course = false;
        if (api_check_user_access_to_legal($_course) && Session::read($variable)) {
            $user_pass_open_course = true;
        }

        // Checking if the user filled the course legal agreement
        if ($_course['activate_legal'] == 1 && !api_is_platform_admin() && !api_is_anonymous()) {
            $user_is_subscribed = CourseManager::is_user_accepted_legal(
                $user_id,
                $_course['id'],
                $session_id
            ) || $user_pass_open_course;
            if (!$user_is_subscribed) {
                $url = api_get_path(WEB_CODE_PATH).'course_info/legal.php?course_code='.$_course['code'].'&session_id='.$session_id;
                header('Location: '.$url);
                exit;
            }
        }

        // Platform legal terms and conditions
        if (api_get_setting('allow_terms_conditions') === 'true' &&
            api_get_setting('load_term_conditions_section') === 'course'
        ) {
            $termAndConditionStatus = api_check_term_condition($user_id);
            // @todo not sure why we need the login password and update_term_status
            if ($termAndConditionStatus === false) {
                Session::write('term_and_condition', ['user_id' => $user_id]);
            } else {
                Session::erase('term_and_condition');
            }

            $termsAndCondition = Session::read('term_and_condition');

            if (isset($termsAndCondition['user_id'])) {
                // user id
                $user_id = $termsAndCondition['user_id'];
                // Update the terms & conditions
                $legal_type = null;
                // Verify type of terms and conditions
                if (isset($_POST['legal_info'])) {
                    $info_legal = explode(':', $_POST['legal_info']);
                    $legal_type = LegalManager::get_type_of_terms_and_conditions(
                        $info_legal[0],
                        $info_legal[1]
                    );
                }

                // is necessary verify check
                if ($legal_type === 1) {
                    if (isset($_POST['legal_accept']) && $_POST['legal_accept'] == '1') {
                        $legal_option = true;
                    } else {
                        $legal_option = false;
                    }
                }

                // no is check option
                if ($legal_type == 0) {
                    $legal_option = true;
                }

                if (isset($_POST['legal_accept_type']) && $legal_option === true) {
                    $cond_array = explode(':', $_POST['legal_accept_type']);
                    if (!empty($cond_array[0]) && !empty($cond_array[1])) {
                        $time = time();
                        $condition_to_save = intval($cond_array[0]).':'.intval($cond_array[1]).':'.$time;
                        UserManager::update_extra_field_value(
                            $user_id,
                            'legal_accept',
                            $condition_to_save
                        );
                    }
                }

                $redirect = true;
                $allow = api_get_configuration_value('allow_public_course_with_no_terms_conditions');
                if ($allow === true &&
                    isset($_course['visibility']) &&
                    $_course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD
                ) {
                    $redirect = false;
                }
                if ($redirect && !api_is_platform_admin()) {
                    $url = api_get_path(WEB_CODE_PATH).'auth/inscription.php';
                    header('Location:'.$url);
                    exit;
                }
            }
        }
    }

    // Checking the course access
    $is_allowed_in_course = false;

    if (isset($_course) && isset($_course['visibility'])) {
        switch ($_course['visibility']) {
            case COURSE_VISIBILITY_OPEN_WORLD: //3
                $is_allowed_in_course = true;
                break;
            case COURSE_VISIBILITY_OPEN_PLATFORM: //2
                $userAccess = api_get_configuration_value('block_registered_users_access_to_open_course_contents');
                // If this setting is not set or equals false, allow registered users to access content from any open
                // course
                if ($userAccess == false) {
                    if (isset($user_id) && !api_is_anonymous($user_id)) {
                        $is_allowed_in_course = true;
                    }
                } else {
                    // If the setting == true, then only allow users to access the content of an open course if they are
                    // directly subscribed to the course (so first check the registration to the course)
                    $courseCode = $_course['code'];
                    $isUserSubscribedInCourse = CourseManager::is_user_subscribed_in_course(
                        $user_id,
                        $courseCode,
                        $session_id
                    );
                    if (isset($user_id) && ($is_platformAdmin || $isUserSubscribedInCourse === true) && !api_is_anonymous($user_id)) {
                        $is_allowed_in_course = true;
                    }
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
            case COURSE_VISIBILITY_HIDDEN: //4
                if ($is_platformAdmin) {
                    $is_allowed_in_course = true;
                }
                break;
        }
    }

    if (!$is_platformAdmin) {
        if (!$is_courseMember &&
            isset($_course['registration_code']) &&
            !empty($_course['registration_code']) &&
            !Session::read('course_password_'.$_course['real_id'], false)
        ) {
            // if we are here we try to access to a course requiring password
            if ($is_allowed_in_course) {
                // the course visibility allows to access the course
                // with a password
                $url = api_get_path(WEB_CODE_PATH).'auth/set_temp_password.php?course_id='.$_course['real_id'].'&session_id='.$session_id;
                header('Location: '.$url);
                exit;
            } else {
                $is_courseMember = false;
                $is_courseAdmin = false;
                $is_courseTutor = false;
                $is_session_general_coach = false;
                $is_sessionAdmin = false;
                $is_allowed_in_course = false;
            }
        }
    } // check the session visibility

    if ($is_allowed_in_course == true) {
        //if I'm in a session
        if ($session_id != 0) {
            if (!$is_platformAdmin) {
                // admin is not affected to the invisible session mode
                $session_visibility = api_get_session_visibility($session_id);

                switch ($session_visibility) {
                    case SESSION_INVISIBLE:
                        $is_allowed_in_course = false;
                        break;
                }
            }
        }
    }

    // save the states
    if (isset($is_courseAdmin)) {
        Session::write('is_courseAdmin', $is_courseAdmin);
        if ($is_courseAdmin) {
            $is_allowed_in_course = true;
        }
    }
    if (isset($is_courseMember)) {
        Session::write('is_courseMember', $is_courseMember);
    }
    if (isset($is_courseTutor)) {
        Session::write('is_courseTutor', $is_courseTutor);
        if ($is_courseTutor) {
            $is_allowed_in_course = true;
        }
    }
    Session::write('is_session_general_coach', $is_session_general_coach);
    Session::write('is_allowed_in_course', $is_allowed_in_course);
    Session::write('is_sessionAdmin', $is_sessionAdmin);
} else {
    // Continue with the previous values
    $is_courseAdmin = isset($_SESSION['is_courseAdmin']) ? $_SESSION['is_courseAdmin'] : false;
    $is_courseTutor = isset($_SESSION['is_courseTutor']) ? $_SESSION['is_courseTutor'] : false;
    $is_session_general_coach = isset($_SESSION['is_session_general_coach']) ? $_SESSION['is_session_general_coach'] : false;
    $is_courseMember = isset($_SESSION['is_courseMember']) ? $_SESSION['is_courseMember'] : false;
    $is_allowed_in_course = isset($_SESSION['is_allowed_in_course']) ? $_SESSION['is_allowed_in_course'] : false;
}

//set variable according to student_view_enabled choices
if (api_get_setting('student_view_enabled') == "true") {
    $changed = false;
    if (isset($_GET['isStudentView'])) {
        if ($_GET['isStudentView'] == 'true') {
            if (isset($_SESSION['studentview'])) {
                if (!empty($_SESSION['studentview'])) {
                    // switching to studentview
                    $_SESSION['studentview'] = 'studentview';
                    $changed = true;
                }
            }
        } elseif ($_GET['isStudentView'] == 'false') {
            if (isset($_SESSION['studentview'])) {
                if (!empty($_SESSION['studentview'])) {
                    // switching to teacherview
                    $_SESSION['studentview'] = 'teacherview';
                    $changed = true;
                }
            }
        }
    } elseif (!empty($_SESSION['studentview'])) {
        //all is fine, no change to that, obviously
    } elseif (empty($_SESSION['studentview'])) {
        // We are in teacherview here
        $_SESSION['studentview'] = 'teacherview';
        $changed = true;
    }

    if ($changed) {
        Session::write('clean_sortable_table', true);
    }
}

if (isset($_cid)) {
    $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
    $time = api_get_utc_datetime();
    $sql = "UPDATE $tbl_course SET last_visit = '$time' WHERE code='$_cid'";
    Database::query($sql);
}

// direct login to course
if (isset($doNotRedirectToCourse)) {
} elseif (exist_firstpage_parameter()) {
    // The GotoCourse cookie is probably deprecated
    api_delete_firstpage_parameter(); // delete the cookie
}

Event::eventCourseLoginUpdate(
    api_get_course_int_id(),
    api_get_user_id(),
    api_get_session_id()
);
Redirect::session_request_uri($logging_in, $user_id);

if (!ChamiloApi::isAjaxRequest() && api_get_configuration_value('allow_mandatory_survey')) {
    SurveyManager::protectByMandatory();
}
