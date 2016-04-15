<?php
/**
 * Licence: GPL
 * Please contact CBlue regarding any licences issues.
 * Author: noel@cblue.be
 *  Copyright: CBlue SPRL, 20XX
 *
 * External login module : FACEBOOK
 *
 * This files provides the facebookConnect()  and facebook_get_url functions
 * Please edit the facebook.conf.php file to adapt it to your fb application parameter
 */

require_once dirname(__FILE__) . '/../../inc/global.inc.php';
require_once dirname(__FILE__) . '/facebook.init.php';
require_once dirname(__FILE__) . '/facebook-php-sdk/autoload.php';

use Facebook\FacebookSession;
use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookRequest;
use Facebook\FacebookResponse;
use Facebook\FacebookSDKException;
use Facebook\FacebookRequestException;
use Facebook\FacebookAuthorizationException;
use Facebook\GraphObject;
use Facebook\Entities\AccessToken;
use Facebook\HttpClients\FacebookCurlHttpClient;
use Facebook\HttpClients\FacebookHttpable;

require_once dirname(__FILE__) . '/functions.inc.php';

// dont rename $facebook_config to $facebookConfig otherwise get a "Facebook\\FacebookSDKException"
FacebookSession::setDefaultApplication($facebook_config['appId'], $facebook_config['secret']);

/**
 * This function connect to facebook and retrieves the user info
 * If user does not exist in chamilo, it creates it and logs in
 * If user already exists, it updates his info
 */
function facebookConnect()
{
    global $facebook_config;
    global $helper;

    try {
        $helper = new FacebookRedirectLoginHelper($facebook_config['return_url']);
        $session = $helper->getSessionFromRedirect();
        // see if we have a session
        if (isset($session)) {
            // graph api request for user data
            $request = new FacebookRequest($session, 'GET', '/me?fields=id,first_name,last_name,email,locale');
            $response = $request->execute();
            // get response
            $graphObject = $response->getGraphObject(Facebook\GraphUser::className());
            $username = changeToValidChamiloLogin($graphObject->getProperty('email'));
            $email = $graphObject->getProperty('email');
            $locale = $graphObject->getProperty('locale');
            $language = facebookPluginGetLanguage($locale);
            if (!$language) {
                $language='en_US';
            }

            //Checks if user already exists in chamilo
            $u = array(
                'firstname' => $graphObject->getProperty('first_name'),
                'lastname' => $graphObject->getProperty('last_name'),
                'status' => STUDENT,
                'email' => $graphObject->getProperty('email'),
                'username' => $username,
                'language' => $language,
                'password' => 'facebook',
                'auth_source' => 'facebook',
                // 'courses' => $user_info['courses'],
                // 'profile_link' => $user_info['profile_link'],
                // 'worldwide_bu' => $user_info['worlwide_bu'],
                // 'manager' => $user_info['manager'],
                'extra' => array()
            );

            $chamiloUinfo = api_get_user_info_from_email($email);
            if ($chamiloUinfo === false) {
                // we have to create the user
                $chamilo_uid = external_add_user($u);
                if ($chamilo_uid !== false) {
                    $_user['user_id'] = $chamilo_uid;
                    $_user['uidReset'] = true;
                    $_SESSION['_user'] = $_user;
                    header('Location:' . api_get_path(WEB_PATH));
                    exit();
                } else {
                    return false;
                }
            } else {
                // User already exists, update info and login
                $chamilo_uid = $chamiloUinfo['user_id'];
                $u['user_id'] = $chamilo_uid;
                external_update_user($u);
                $_user['user_id'] = $chamilo_uid;
                $_user['uidReset'] = true;
                $_SESSION['_user'] = $_user;
                header('Location:' . api_get_path(WEB_PATH));
                exit();
            }
        }
    } catch (FacebookRequestException $ex) {
        echo $ex;
    } catch (Exception $ex) {
        // When validation fails or other local issues
    }
}

/**
 * Get facebook login url for the platform
 * @return string
 */
function facebookGetLoginUrl()
{
    global $facebook_config;
    $helper = new FacebookRedirectLoginHelper($facebook_config['return_url']);
    $loginUrl =   $helper->getLoginUrl(
        array('scope' => 'email')
    );

    return $loginUrl;
}

/**
 * Return a valid Chamilo login
 * Chamilo login only use characters lettres, des chiffres et les signes _ . -
 * @param $in_txt
 * @return mixed
 */
function changeToValidChamiloLogin($in_txt)
{
    return preg_replace("/[^a-zA-Z1-9_\-.]/", "_", $in_txt);
}

/**
 * Get user language
 * @param string $language
 * @return bool
 */
function facebookPluginGetLanguage($language = 'en_US')
{
    $language = substr($language, 0, 2);
    $sqlResult = Database::query(
        "SELECT english_name FROM ".
        Database::get_main_table(TABLE_MAIN_LANGUAGE).
        " WHERE available = 1 AND isocode = '$language'"
    );
    if (Database::num_rows($sqlResult)) {
        $result = Database::fetch_array($sqlResult);
        return $result['english_name'];
    }

    return false;
}

