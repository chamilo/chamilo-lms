<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This file contains the necessary elements to implement a Single Sign On
 * mechanism with an arbitrary external web application (given some light
 * development there) and is based on the Drupal-Chamilo module implementation.
 * To develop a new authentication mechanism, please extend this class and
 * overwrite its method, then modify the corresponding calling code in
 * main/inc/local.inc.php.
 *
 * @package chamilo.auth.sso
 */
/**
 * The SSO class allows for management or remote Single Sign On resources.
 */
class sso
{
    public $protocol; //    'http://',
    public $domain; //    'localhost/project/drupal5',
    public $auth_uri; //    '/?q=user',
    public $deauth_uri; //    '/?q=logout',
    public $referer; // http://my.chamilo.com/main/auth/profile.php

    /*
     * referrer_uri: [some/path/inside/Chamilo], might be used by module to
     * redirect the user to where he wanted to go initially in Chamilo
     */
    public $referrer_uri;

    /**
     * Instanciates the object, initializing all relevant URL strings.
     */
    public function __construct()
    {
        $this->protocol = api_get_setting('sso_authentication_protocol');
        // There can be multiple domains, so make sure to take only the first
        // This might be later extended with a decision process
        $domains = explode(',', api_get_setting('sso_authentication_domain'));
        $this->domain = trim($domains[0]);
        $this->auth_uri = api_get_setting('sso_authentication_auth_uri');
        $this->deauth_uri = api_get_setting('sso_authentication_unauth_uri');
        //cut the string to avoid recursive URL construction in case of failure
        $this->referer = $this->protocol.$_SERVER['HTTP_HOST'].substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], 'sso'));
        $this->deauth_url = $this->protocol.$this->domain.$this->deauth_uri;
        $this->master_url = $this->protocol.$this->domain.$this->auth_uri;
        $this->referrer_uri = base64_encode($_SERVER['REQUEST_URI']);
        $this->target = api_get_path(WEB_PATH);
    }

    /**
     * Unlogs the user from the remote server.
     */
    public function logout()
    {
        header('Location: '.$this->deauth_url);
        exit;
    }

    /**
     * Sends the user to the master URL for a check of active connection.
     */
    public function ask_master()
    {
        $tempKey = api_generate_password(32);
        $params = 'sso_referer='.urlencode($this->referer).
            '&sso_target='.urlencode($this->target).
            '&sso_challenge='.$tempKey.
            '&sso_ruri='.urlencode($this->referrer_uri);
        Session::write('tempkey', $tempKey);
        if (strpos($this->master_url, "?") === false) {
            $params = "?$params";
        } else {
            $params = "&$params";
        }
        header('Location: '.$this->master_url.$params);
        exit;
    }

    /**
     * Validates the received active connection data with the database.
     *
     * @return bool Return the loginFailed variable value to local.inc.php
     */
    public function check_user()
    {
        global $_user;
        $loginFailed = false;
        //change the way we recover the cookie depending on how it is formed
        $sso = $this->decode_cookie($_GET['sso_cookie']);

        //error_log('check_user');
        //error_log('sso decode cookie: '.print_r($sso,1));

        //lookup the user in the main database
        $user_table = Database::get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT user_id, username, password, auth_source, active, expiration_date, status
                FROM $user_table
                WHERE username = '".trim(Database::escape_string($sso['username']))."'";
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            //error_log('user exists');
            $uData = Database::fetch_array($result);
            //Check the user's password
            if ($uData['auth_source'] == PLATFORM_AUTH_SOURCE) {
                //This user's authentification is managed by Chamilo itself
                // check the user's password
                // password hash comes already parsed in sha1, md5 or none

                /*
                error_log($sso['secret']);
                error_log($uData['password']);
                error_log($sso['username']);
                error_log($uData['username']);
                */
                global $_configuration;
                // Two possible authentication methods here: legacy using password
                // and new using a temporary, session-fixed, tempkey
                if ((
                    $sso['username'] == $uData['username']
                        && $sso['secret'] === sha1(
                            $uData['username'].
                            Session::read('tempkey').
                            $_configuration['security_key']
                        )
                    )
                    or (
                        ($sso['secret'] === sha1($uData['password']))
                        && ($sso['username'] == $uData['username'])
                    )
                ) {
                    //error_log('user n password are ok');
                    //Check if the account is active (not locked)
                    if ($uData['active'] == '1') {
                        // check if the expiration date has not been reached
                        if (empty($uData['expiration_date'])
                            or $uData['expiration_date'] > date('Y-m-d H:i:s')
                            or $uData['expiration_date'] == '0000-00-00 00:00:00') {
                            //If Multiple URL is enabled
                            if (api_get_multiple_access_url()) {
                                //Check the access_url configuration setting if
                                // the user is registered in the access_url_rel_user table
                                //Getting the current access_url_id of the platform
                                $current_access_url_id = api_get_current_access_url_id();
                                // my user is subscribed in these
                                //sites: $my_url_list
                                $my_url_list = api_get_access_url_from_user($uData['user_id']);
                            } else {
                                $current_access_url_id = 1;
                                $my_url_list = [1];
                            }

                            $my_user_is_admin = UserManager::is_admin($uData['user_id']);

                            if ($my_user_is_admin === false) {
                                if (is_array($my_url_list) && count($my_url_list) > 0) {
                                    if (in_array($current_access_url_id, $my_url_list)) {
                                        // the user has permission to enter at this site
                                        $_user['user_id'] = $uData['user_id'];
                                        $_user = api_get_user_info($_user['user_id']);
                                        $_user['uidReset'] = true;
                                        Session::write('_user', $_user);
                                        Event::eventLogin($_user['user_id']);
                                        // Redirect to homepage
                                        $sso_target = '';
                                        if (!empty($sso['ruri'])) {
                                            //The referrer URI is *only* used if
                                            // the user credentials are OK, which
                                            // should be protection enough
                                            // against evil URL spoofing...
                                            $sso_target = api_get_path(WEB_PATH).base64_decode($sso['ruri']);
                                        } else {
                                            $sso_target = isset($sso['target']) ? $sso['target'] : api_get_path(WEB_PATH).'index.php';
                                        }
                                        header('Location: '.$sso_target);
                                        exit;
                                    } else {
                                        // user does not have permission for this site
                                        $loginFailed = true;
                                        Session::erase('_uid');
                                        header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=access_url_inactive');
                                        exit;
                                    }
                                } else {
                                    // there is no URL in the multiple
                                    // urls list for this user
                                    $loginFailed = true;
                                    Session::erase('_uid');
                                    header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=access_url_inactive');
                                    exit;
                                }
                            } else {
                                //Only admins of the "main" (first) Chamilo
                                // portal can login wherever they want
                                if (in_array(1, $my_url_list)) {
                                    //Check if this admin is admin on the
                                    // principal portal
                                    $_user['user_id'] = $uData['user_id'];
                                    $_user = api_get_user_info($_user['user_id']);
                                    $is_platformAdmin = $uData['status'] == COURSEMANAGER;
                                    Session::write('is_platformAdmin', $is_platformAdmin);
                                    Session::write('_user', $_user);
                                    Event::eventLogin($_user['user_id']);
                                } else {
                                    //Secondary URL admin wants to login
                                    // so we check as a normal user
                                    if (in_array($current_access_url_id, $my_url_list)) {
                                        $_user['user_id'] = $uData['user_id'];
                                        $_user = api_get_user_info($_user['user_id']);
                                        Session::write('_user', $_user);
                                        Event::eventLogin($_user['user_id']);
                                    } else {
                                        $loginFailed = true;
                                        Session::erase('_uid');
                                        header(
                                            'Location: '.api_get_path(WEB_PATH)
                                            .'index.php?loginFailed=1&error=access_url_inactive'
                                        );
                                        exit;
                                    }
                                }
                            }
                        } else {
                            // user account expired
                            $loginFailed = true;
                            Session::erase('_uid');
                            header(
                                'Location: '.api_get_path(WEB_PATH)
                                .'index.php?loginFailed=1&error=account_expired'
                            );
                            exit;
                        }
                    } else {
                        //User not active
                        $loginFailed = true;
                        Session::erase('_uid');
                        header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=account_inactive');
                        exit;
                    }
                } else {
                    //SHA1 of password is wrong
                    $loginFailed = true;
                    Session::erase('_uid');
                    header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=wrong_password');
                    exit;
                }
            } else {
                //Auth_source is wrong
                $loginFailed = true;
                Session::erase('_uid');
                header(
                    'Location: '.api_get_path(WEB_PATH)
                    .'index.php?loginFailed=1&error=wrong_authentication_source'
                );
                exit;
            }
        } else {
            //No user by that login
            $loginFailed = true;
            Session::erase('_uid');
            header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=user_not_found');
            exit;
        }

        return $loginFailed;
    }

    /**
     * Generate the URL for profile editing for a any user or the current user.
     *
     * @param int  $userId  Optional. The user id
     * @param bool $asAdmin Optional. Whether get the URL for the platform admin
     *
     * @return string The SSO URL
     */
    public function generateProfileEditingURL($userId = 0, $asAdmin = false)
    {
        $userId = intval($userId);

        if ($asAdmin && api_is_platform_admin(true)) {
            return api_get_path(WEB_CODE_PATH)."admin/user_edit.php?user_id=$userId";
        }

        return api_get_path(WEB_CODE_PATH).'auth/profile.php';
    }

    /**
     * Decode the cookie (this function may vary depending on the
     * Single Sign On implementation.
     *
     * @param	string	Encoded cookie
     *
     * @return array Parsed and unencoded cookie
     */
    private function decode_cookie($cookie)
    {
        return UnserializeApi::unserialize(
            'not_allowed_classes',
            base64_decode($cookie)
        );
    }
}
