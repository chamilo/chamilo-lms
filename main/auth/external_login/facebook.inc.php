<?php
/**
 * Licence: GPL
 * Please contact CBlue regarding any licences issues.
 * Author: noel@cblue.be
 *  Copyright: CBlue SPRL, 20XX
 *
 * External login module : FACEBOOK
 *
 * This files provides the facebook_connect()  and facebook_get_url functions
 * Please edit the facebook.conf.php file to adapt it to your fb application parameter
 **/
require_once dirname(__FILE__).'/../../inc/global.inc.php';
require_once dirname(__FILE__).'/facebook.conf.php';
require_once dirname(__FILE__).'/facebook-php-sdk/src/facebook.php';
require_once dirname(__FILE__).'/functions.inc.php';

/** 
  * This function connect to facebook and retrieves the user info
  * If user does not exist in chamilo, it creates it and logs in
  * If user already exists, it updates his info
**/
function facebook_connect() { 
	global $facebook;
	// See if there is a user from a cookie
	$user = $facebook->getUser();
	if ($user) {
		try {
			//Gets facebook user info
			$fu = $facebook->api('/me');
			$username = api_get_setting('login_is_email') == 'true' ? $fu['email'] : $fu['username'];
			//Checks if user already exists in chamilo
			$u = array(
					'firstname' => $fu['first_name'],
					'lastname' => $fu['last_name'],
					'status' => STUDENT,
					'email' => $fu['email'],
					'username' => $username,
					'language' => 'french',
					'password' => DEFAULT_PASSWORD,
					'auth_source' => 'facebook',
					//'courses' => $user_info['courses'],
					//'profile_link' => $user_info['profile_link'],
					//'worldwide_bu' => $user_info['worlwide_bu'],
					//'manager' => $user_info['manager'],
					'extra' => array()
					);
			$cu = api_get_user_info_from_username($username);
			$chamilo_uinfo = api_get_user_info_from_username($username);
			if ( $chamilo_uinfo  === false) {
				//we have to create the user
				$chamilo_uid = external_add_user($u);
				if ($chamilo_uid !==false) {
					$_user['user_id'] = $chamilo_uid;
					$_user['uidReset'] = true;  
					$_SESSION['_user'] = $_user;
					header('Location:'.api_get_path(WEB_PATH));
					exit();
				} else {
					return false;
				}
			} else {//User already exists, update info and login
				$chamilo_uid = $chamilo_uinfo['user_id'];
				$u['user_id'] = $chamilo_uid;
				external_update_user($u);
				$_user['user_id'] = $chamilo_uid;
				$_user['uidReset'] = true;  
				$_SESSION['_user'] = $_user;
				header('Location:'.api_get_path(WEB_PATH));
				exit();
			}
		} catch (FacebookApiException $e) {
			echo '<pre>'.htmlspecialchars(print_r($e, true)).'</pre>';
			$user = null;
		}
	}
}

/**
* Get facebook login url for the platform
**/
function facebook_get_login_url(){
	global $facebook, $facebook_config;

	$login_url = $facebook->getLoginUrl(
			array(
				'scope'	=> 'email,publish_stream',
				'redirect_uri' => $facebook_config['return_url']
				)
			); 
	return $login_url;
}

