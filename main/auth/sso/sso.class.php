<?php
/* For licensing terms, see /license.txt */
/**
 * This file contains the necessary elements to implement a Single Sign On 
 * mechanism with an arbitrary external web application (given some light 
 * development there) and is based on the Drupal-Chamilo module implementation.
 * To develop a new authentication mechanism, please extend this class and
 * overwrite its method, then modify the corresponding calling code in 
 * main/inc/local.inc.php
 * @package chamilo.auth.sso
 */
/**
 * The SSO class allows for management or remote Single Sign On resources
 */
class sso {
    public $protocol; //    'http://',
    public $domain; //    'localhost/project/drupal5',
    public $auth_uri; //    '/?q=user',
    public $deauth_uri; //    '/?q=logout',
    public $referer; // http://my.chamilo.com/main/auth/profile.php

    /**
     * Instanciates the object, initializing all relevant URL strings
     */
    public function __construct() {
        $this->protocol   = api_get_setting('sso_authentication_protocol');
        $this->domain     = api_get_setting('sso_authentication_domain');
        $this->auth_uri   = api_get_setting('sso_authentication_auth_uri');
        $this->deauth_uri = api_get_setting('sso_authentication_unauth_uri');
        //cut the string to avoid recursive URL construction in case of failure
        $this->referer    = $this->protocol.$_SERVER['HTTP_HOST'].substr($_SERVER['REQUEST_URI'],0,strpos($_SERVER['REQUEST_URI'],'sso'));
        $this->deauth_url = $this->protocol.$this->domain.$this->deauth_uri;
        $this->master_url = $this->protocol.$this->domain.$this->auth_uri;
        $this->target     = api_get_path(WEB_PATH);
    }
    /**
     * Unlogs the user from the remote server 
     */
    public function logout() {
        header('Location: '.$this->deauth_url);
        exit;
    }
    /**
     * Sends the user to the master URL for a check of active connection
     */
    public function ask_master() {
        header('Location: '.$this->master_url.'&sso_referer='.urlencode($this->referer).'&sso_target='.urlencode($this->target));
        exit;
    }
    /**
     * Validates the received active connection data with the database
     * @return	bool	Return the loginFailed variable value to local.inc.php
     */
    public function check_user() {
        global $_user, $_configuration;
        $loginFailed = false;
        //change the way we recover the cookie depending on how it is formed
        $sso = $this->decode_cookie($_GET['sso_cookie']);
        
        //error_log('check_user');
        //error_log('sso decode cookie: '.print_r($sso,1));
        
        //lookup the user in the main database
        $user_table = Database::get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT user_id, username, password, auth_source, active, expiration_date
                FROM $user_table
                WHERE username = '".trim(Database::escape_string($sso['username']))."'";
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            //error_log('user exists');
            $uData = Database::fetch_array($result);
            //Check the user's password
            if ($uData['auth_source'] == PLATFORM_AUTH_SOURCE) {
             
                //the authentification of this user is managed by Chamilo itself
                // check the user's password
                // password hash comes already parsed in sha1, md5 or none
                
                /*
                error_log($sso['secret']);
                error_log($uData['password']);
                error_log($sso['username']);
                error_log($uData['username']);
                */
                
                if ($sso['secret'] === sha1($uData['password']) 
                    && ($sso['username'] == $uData['username'])) {
                    error_log('user n password are ok');                    
                    //Check if the account is active (not locked)
                    if ($uData['active']=='1') {
                        // check if the expiration date has not been reached
                        if ($uData['expiration_date'] > date('Y-m-d H:i:s') 
                         OR $uData['expiration_date']=='0000-00-00 00:00:00') {
                            //If Multiple URL is enabled
                            if (api_get_multiple_access_url()) {
                                $admin_table = Database::get_main_table(TABLE_MAIN_ADMIN);
                                //Check if user is an admin
                                $sql = "SELECT user_id FROM $admin_table
                                        WHERE user_id = '".intval($uData['user_id'])."' LIMIT 1";
                                $result = Database::query($sql);
                                $my_user_is_admin = false;
                                if (Database::num_rows($result) > 0) {
                                    $my_user_is_admin = true;
                                }
                                //Check the access_url configuration setting if
                                // the user is registered in the 
                                // access_url_rel_user table
                                //Getting the current access_url_id 
                                // of the platform
                                $current_access_url_id = api_get_current_access_url_id();
                                // my user is subscribed in these 
                                //sites: $my_url_list
                                $my_url_list = api_get_access_url_from_user($uData['user_id']);
                                if ($my_user_is_admin === false) {
                                    if (is_array($my_url_list) && count($my_url_list)>0 ) {
                                        if (in_array($current_access_url_id, $my_url_list)) {
                                            // the user has permission to enter at this site
                                            $_user['user_id'] = $uData['user_id'];
                                            Session::write('_user',$_user);
                                            event_login();
                                            // Redirect to homepage
                                            $sso_target = isset($sso['target']) ? $sso['target'] : api_get_path(WEB_PATH) .'.index.php';
                                            header('Location: '. $sso_target);
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
                                        Session::write('_user',$_user);
                                        event_login();
                                    } else {
                                        //Secondary URL admin wants to login 
                                        // so we check as a normal user
                                        if (in_array($current_access_url_id, $my_url_list)) {
                                            $_user['user_id'] = $uData['user_id'];
                                            Session::write('_user',$_user);
                                            event_login();
                                        } else {
                                            $loginFailed = true;
                                            Session::erase('_uid');
                                            header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=access_url_inactive');
                                            exit;
                                        }
                                    }
                                }
                            } else {
                                //Single URL access (Only 1 portal)
                                $_user['user_id'] = $uData['user_id'];
                                Session::write('_user',$_user);
                                event_login();
                                // Redirect to homepage
                                /* Login was successfull, stay on Chamilo 
                                $protocol = api_get_setting('sso_authentication_protocol');
                                $master_url = api_get_setting('sso_authentication_domain');
                                $target = $protocol.$master_url;
                                $sso_target = isset($target) ? $target : api_get_path(WEB_PATH) .'.index.php';
                                header('Location: '. $sso_target);
                                exit;
                                */
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
     * Decode the cookie (this function may vary depending on the
     * Single Sign On implementation
     * @param	string	Encoded cookie
     * @return  array   Parsed and unencoded cookie
     */
    private function decode_cookie($cookie) {
        return unserialize(base64_decode($cookie));
    }
}
