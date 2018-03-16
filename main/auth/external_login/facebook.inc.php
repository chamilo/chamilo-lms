<?php
/**
 * Licence: GPL
 * Please contact CBlue regarding any licences issues.
 * Author: noel@cblue.be
 *  Copyright: CBlue SPRL, 20XX.
 *
 * External login module : FACEBOOK
 *
 * This files provides the facebookConnect()  and facebook_get_url functions
 * Please edit the facebook.conf.php file to adapt it to your fb application parameter
 */
require_once __DIR__.'/../../inc/global.inc.php';
require_once __DIR__.'/facebook.init.php';
require_once __DIR__.'/functions.inc.php';

/**
 * This function connect to facebook and retrieves the user info
 * If user does not exist in chamilo, it creates it and logs in
 * If user already exists, it updates his info.
 */
function facebookConnect()
{
    $fb = new \Facebook\Facebook([
        'app_id' => $GLOBALS['facebook_config']['appId'],
        'app_secret' => $GLOBALS['facebook_config']['secret'],
        'default_graph_version' => 'v2.2',
    ]);

    $helper = $fb->getRedirectLoginHelper();

    try {
        $accessToken = $helper->getAccessToken();
    } catch (Facebook\Exceptions\FacebookResponseException $e) {
        Display::addFlash(
            Display::return_message('Facebook Graph returned an error: '.$e->getMessage(), 'error')
        );

        header('Location: '.api_get_path(WEB_PATH));
        exit;
    } catch (Facebook\Exceptions\FacebookSDKException $e) {
        Display::addFlash(
            Display::return_message('Facebook SDK returned an error: '.$e->getMessage(), 'error')
        );

        header('Location: '.api_get_path(WEB_PATH));
        exit;
    }

    if (!isset($accessToken)) {
        if (!$helper->getError()) {
            return;
        }

        if (isset($_GET['loginFailed'])) {
            return;
        }

        $error = implode('<br>', [
            'Error: '.$helper->getError(),
            'Error Code: '.$helper->getErrorCode(),
            'Error Reason: '.$helper->getErrorReason(),
            'Error Description: '.$helper->getErrorDescription(),
        ]);

        Display::addFlash(
            Display::return_message($error, 'error', false)
        );

        header('Location: '.api_get_path(WEB_PATH));
        exit;
    }

    $oAuth2Client = $fb->getOAuth2Client();
    $tokenMetadata = $oAuth2Client->debugToken($accessToken);
    $tokenMetadata->validateAppId($GLOBALS['facebook_config']['appId']);
    $tokenMetadata->validateExpiration();

    if (!$accessToken->isLongLived()) {
        try {
            $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            Display::addFlash(
                Display::return_message('Error getting long-lived access token: '.$e->getMessage(), 'error')
            );

            header('Location: '.api_get_path(WEB_PATH));
            exit;
        }
    }

    try {
        $response = $fb->get('/me?fields=id,first_name,last_name,locale,email', $accessToken->getValue());
    } catch (Facebook\Exceptions\FacebookResponseException $e) {
        Display::addFlash(
            Display::return_message('Graph returned an error: '.$e->getMessage(), 'error')
        );

        header('Location: '.api_get_path(WEB_PATH));
        exit;
    } catch (Facebook\Exceptions\FacebookSDKException $e) {
        Display::addFlash(
            Display::return_message('Facebook SDK returned an error: '.$e->getMessage(), 'error')
        );

        header('Location: '.api_get_path(WEB_PATH));
        exit;
    }

    $user = $response->getGraphUser();
    $language = facebookPluginGetLanguage($user['locale']);

    if (!$language) {
        $language = 'en_US';
    }

    $u = [
        'firstname' => $user->getFirstName(),
        'lastname' => $user->getLastName(),
        'status' => STUDENT,
        'email' => $user->getEmail(),
        'username' => changeToValidChamiloLogin($user->getEmail()),
        'language' => $language,
        'password' => 'facebook',
        'auth_source' => 'facebook',
        'extra' => [],
    ];
    $chamiloUinfo = api_get_user_info_from_email($user->getEmail());

    $_user['uidReset'] = true;
    $_user['language'] = $language;

    if ($chamiloUinfo === false) {
        // We have to create the user
        $chamilo_uid = external_add_user($u);

        if ($chamilo_uid === false) {
            Display::addFlash(
                Display::return_message(get_lang('UserNotRegistered'), 'error')
            );

            header('Location: '.api_get_path(WEB_PATH));
            exit;
        }

        $_user['user_id'] = $chamilo_uid;
        $_SESSION['_user'] = $_user;

        header('Location: '.api_get_path(WEB_PATH));
        exit();
    }

    // User already exists, update info and login
    $chamilo_uid = $chamiloUinfo['user_id'];
    $u['user_id'] = $chamilo_uid;
    external_update_user($u);
    $_user['user_id'] = $chamilo_uid;
    $_SESSION['_user'] = $_user;

    header('Location: '.api_get_path(WEB_PATH));
    exit();
}

/**
 * Get facebook login url for the platform.
 *
 * @return string
 */
function facebookGetLoginUrl()
{
    $fb = new \Facebook\Facebook([
        'app_id' => $GLOBALS['facebook_config']['appId'],
        'app_secret' => $GLOBALS['facebook_config']['secret'],
        'default_graph_version' => 'v2.2',
    ]);

    $helper = $fb->getRedirectLoginHelper();
    $loginUrl = $helper->getLoginUrl(api_get_path(WEB_PATH).'?action=fbconnect', [
        'email',
    ]);

    return $loginUrl;
}

/**
 * Return a valid Chamilo login
 * Chamilo login only use characters lettres, des chiffres et les signes _ . -.
 *
 * @param $in_txt
 *
 * @return mixed
 */
function changeToValidChamiloLogin($in_txt)
{
    return preg_replace("/[^a-zA-Z1-9_\-.]/", "_", $in_txt);
}

/**
 * Get user language.
 *
 * @param string $language
 *
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
