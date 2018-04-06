<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This file contains the necessary elements to implement a Single Sign On.
 *
   INSERT INTO `settings_current` (`variable`, `type`, `category`, `selected_value`, `title`, `comment`, `access_url`, access_url_changeable)
   VALUES ('sso_authentication_subclass', 'textfield', 'Security', 'TCC', 'SSOSubclass', 'SSOSubclassComment', 1, 0);
 *
 * @package chamilo.auth.sso
 */

/**
 * The SSO class allows for management of remote Single Sign On resources.
 */
class ssoTCC
{
    public $protocol;   // 'http://',
    public $domain;     // 'localhost/project/drupal',
    public $auth_uri;   // '/?q=user',
    public $deauth_uri; // '/?q=logout',
    public $referer;    // http://my.chamilo.com/main/auth/profile.php

    /**
     * Instanciates the object, initializing all relevant URL strings.
     */
    public function __construct()
    {
        $this->protocol = api_get_setting('sso_authentication_protocol');
        // There can be multiple domains, so make sure to take only the first
        // This might be later extended with a decision process
        $domains = explode('/,/', api_get_setting('sso_authentication_domain'));
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
        $forceSsoRedirect = api_get_setting('sso_force_redirect');
        if ($forceSsoRedirect === 'true') {
            // no_redirect means Drupal sent the signal to logout. When redirecting to Drupal, the $_GET['stop'] param is
            // set to 1, to allow Drupal to know that this is it, the logout is already done in Chamilo and there's no
            // need to do it again
            if (empty($_GET['no_redirect'])) {
                header('Location: '.$this->deauth_url.'&stop=1');
            } else {
                header('Location: '.$this->protocol.$this->domain);
            }
            exit;
        }
    }

    /**
     * Sends the user to the master URL for a check of active connection.
     */
    public function ask_master()
    {
        // Generate a single usage token that must be encoded by the master
        $_SESSION['sso_challenge'] = api_generate_password(48);
        // Redirect browser to the master URL
        $params = '';
        if (empty($_GET['no_redirect'])) {
            $params = 'sso_referer='.urlencode($this->referer).
                '&sso_target='.urlencode($this->target).
                '&sso_challenge='.urlencode($_SESSION['sso_challenge']).
                '&sso_ruri='.urlencode($this->referrer_uri);
            if (strpos($this->master_url, "?") === false) {
                $params = "?{$params}";
            } else {
                $params = "&{$params}";
            }
        }
        header('Location: '.$this->master_url.$params);
        exit;
    }

    /**
     * Validates the received active connection data with the database.
     *
     * @return null|false Return the loginFailed variable value to local.inc.php
     */
    public function check_user()
    {
        global $_user;
        $loginFailed = false;

        //change the way we recover the cookie depending on how it is formed
        $sso = $this->decode_cookie($_REQUEST['sso_cookie']);

        $value = explode(';;', $sso);
        $ssoSecret = substr($value[1], 0, 5);
        $value = $value[0];

        $userExtraFieldValue = new ExtraFieldValue('user');
        $userData = $userExtraFieldValue->get_item_id_from_field_variable_and_field_value(
            'tcc_user_id',
            $value
        );

        if ($userData) {
            $userId = $userData['item_id'];
        } else {
            return false;
        }

        //get token that should have been used and delete it
        //from session since it can only be used once
        $sso_challenge = '';
        if (isset($_SESSION['sso_challenge'])) {
            $sso_challenge = $_SESSION['sso_challenge'];
            unset($_SESSION['sso_challenge']);
        }

        // lookup the user in the main database
        $user_table = Database::get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT id, username, password, auth_source, active, expiration_date, status
                FROM $user_table
                WHERE id = '".$userId."'";
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            $uData = Database::fetch_array($result);
            //Check the user's password
            if ($uData['auth_source'] == PLATFORM_AUTH_SOURCE) {
                $secret = substr(api_get_security_key(), 0, 5);
                // Check if secret sent in sso is correct
                if ((string) $ssoSecret == (string) $secret) {
                    //Check if the account is active (not locked)
                    if ($uData['active'] == '1') {
                        // check if the expiration date has not been reached
                        if (empty($uData['expiration_date']) ||
                            $uData['expiration_date'] > date('Y-m-d H:i:s') ||
                            $uData['expiration_date'] == '0000-00-00 00:00:00'
                        ) {
                            //If Multiple URL is enabled
                            if (api_get_multiple_access_url()) {
                                //Check the access_url configuration setting if the user is registered in the access_url_rel_user table
                                //Getting the current access_url_id of the platform
                                $current_access_url_id = api_get_current_access_url_id();
                                // my user is subscribed in these
                                //sites: $my_url_list
                                $my_url_list = api_get_access_url_from_user($uData['id']);
                            } else {
                                $current_access_url_id = 1;
                                $my_url_list = [1];
                            }

                            $my_user_is_admin = UserManager::is_admin($uData['id']);

                            if ($my_user_is_admin === false) {
                                if (is_array($my_url_list) && count($my_url_list) > 0) {
                                    if (in_array($current_access_url_id, $my_url_list)) {
                                        // the user has permission to enter at this site
                                        $_user['user_id'] = $uData['id'];
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
                                    $_user['user_id'] = $uData['id'];
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
                                        header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=access_url_inactive');
                                        exit;
                                    }
                                }
                            }
                        } else {
                            // user account expired
                            $loginFailed = true;
                            Session::erase('_uid');
                            header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=account_expired');
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
                    //Secret sent through SSO is incorrect
                    $loginFailed = true;
                    Session::erase('_uid');
                    header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=wrong_password');
                    exit;
                }
            } else {
                //Auth_source is wrong
                $loginFailed = true;
                Session::erase('_uid');
                header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=wrong_authentication_source');
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
     * @return string If the URL is obtained return the drupal_user_id. Otherwise return false
     */
    public function generateProfileEditingURL($userId = 0, $asAdmin = false)
    {
        $userId = intval($userId);

        if (empty($userId)) {
            $userId = api_get_user_id();
        }

        $userExtraFieldValue = new ExtraFieldValue('user');
        $drupalUserIdData = $userExtraFieldValue->get_values_by_handler_and_field_variable(
            $userId,
            'tcc_user_id'
        );

        // If this is an administrator, allow him to make some changes in
        // the Chamilo profile
        if ($asAdmin && api_is_platform_admin(true)) {
            return api_get_path(WEB_CODE_PATH)."admin/user_edit.php?user_id=$userId";
        }
        // If the user doesn't match a Drupal user, give the normal profile
        // link
        /*        if ($drupalUserIdData === false) {
                    return api_get_path(WEB_CODE_PATH) . 'auth/profile.php';
                }
                // In all other cases, generate a link to the Drupal profile edition
                $drupalUserId = $drupalUserIdData['value'];
                $url = "{$this->protocol}{$this->domain}/user/{$drupalUserId}/edit";
        
                return $url;
        */
        return api_get_path(WEB_CODE_PATH).'auth/profile.php';
    }

    /**
     * Decode the cookie (this function may vary depending on the
     * Single Sign On implementation.
     *
     * @param string $value
     *
     * @return array Parsed and unencoded cookie
     */
    private function decode_cookie($value)
    {
        $key = substr(api_get_security_key(), 0, 16);
        $ivsize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($ivsize, MCRYPT_RAND);
        $valuedecode = base64_decode($value);

        return mcrypt_decrypt(
            MCRYPT_RIJNDAEL_128,
            $key,
            $valuedecode,
            MCRYPT_MODE_ECB,
            $iv
        );
    }
}
