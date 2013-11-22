<?php /* For licensing terms, see /license.txt */
// External login module : WS (for Web Services)
/**
 *
 * This file is included in main/inc/local.inc.php at user login if the user
 * have 'ws' in his auth_source field instead of 'platform'
 *
 * Variables that can be used :
 *    - $uData : associative array with those keys :
 *           -username
 *           -password
 *           -auth_source
 *           -active
 *           -expiration_date
 *
 * If login succeeds, we have 2 choices :
 *    1.  - set $loginFailed to false,
 *        - set $_SESSION['_user']['user_id'] with the Chamilo user_id
 *        - set $uidReset to true
 *        - upgrade user info in Chamilo database if needed
 *        - let the script local.inc.php continue
 *
 *    2.  - set $_SESSION['_user']['user_id'] with the Chamilo user_id
 *        - set $_SESSION['_user']['uidReset'] to true
 *        - upgrade user info in Chamilo database if needed
 *        - redirect to any page and let local.inc.php do the magic
 *
 * If login fails we have to redirect to index.php with the right message
 * Possible messages are :
 *  - index.php?loginFailed=1&error=access_url_inactive
 *  - index.php?loginFailed=1&error=account_expired
 *  - index.php?loginFailed=1&error=account_inactive
 *  - index.php?loginFailed=1&error=user_password_incorrect
 *  - index.php?loginFailed=1&error=unrecognize_sso_origin');
 *
 * */

use \ChamiloSession as Session;

// Configure the web service URL here. e.g. http://190.1.1.19:8051/login.asmx?WSDL
$wsUrl = '';

require_once dirname(__FILE__) . '/functions.inc.php';
//error_log('Entering login.ws.php');
$isValid = loginWSAuthenticate($login, $password, $wsUrl);
if ($isValid !== 0) {
    //error_log('ws_authenticate worked');
    $chamiloUser = UserManager::get_user_info($login);

    $loginFailed = false;
    $_user['user_id'] = $chamiloUser['user_id'];
    $_user['status'] = (isset($chamiloUser['status']) ? $chamiloUser['status'] : 5);
    $_user['uidReset'] = true;
    Session::write('_user', $_user);
    $uidReset = true;
    $logging_in = true;
    event_login();
    //error_log('Calling event_login');
} else {
    //error_log('ws_authenticate error');
    $loginFailed = true;
    $uidReset = false;
    if (isset($_user) && isset($_user['user_id'])) {
        unset($_user['user_id']);
    }
}

/**
 * Checks whether a user has the right to enter on the platform or not
 * @param $username
 * @param $password
 */
function loginWSAuthenticate($username, $password, $wsUrl) {
    if (empty($username) or empty($password) or empty($wsUrl)) {
        return false;
    }
    $client = new SoapClient($wsUrl);
    if (!$client) {
        return false;
    }
    $something =  $client->validaUsuarioAD(array($username, $password, 'chamilo'));
    error_log(print_r($something,1));
    return $something->validaUsuarioADResult;
}
