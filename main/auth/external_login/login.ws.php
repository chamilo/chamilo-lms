<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

// External login module : WS (for Web Services)
/**
 * This file is included in main/inc/local.inc.php at user login if the user
 * have 'ws' in his auth_source field instead of 'platform'.
 */

// Configure the web service URL here. e.g. http://174.1.1.19:8020/login.asmx?WSDL
$wsUrl = '';

// include common authentication functions
require_once __DIR__.'/functions.inc.php';
// call the login checker (defined below)
$isValid = loginWSAuthenticate($login, $password, $wsUrl);

// if the authentication was successful, proceed
if ($isValid === 1) {
    //error_log('WS authentication worked');
    $chamiloUser = api_get_user_info_from_username($login);
    $loginFailed = false;
    $_user['user_id'] = $chamiloUser['user_id'];
    $_user['status'] = (isset($chamiloUser['status']) ? $chamiloUser['status'] : 5);
    $_user['uidReset'] = true;
    Session::write('_user', $_user);
    $uidReset = true;
    $logging_in = true;
    Event::eventLogin($_user['user_id']);
} else {
    //error_log('WS authentication error - user not approved by external WS');
    $loginFailed = true;
    $uidReset = false;
    if (isset($_user) && isset($_user['user_id'])) {
        unset($_user['user_id']);
    }
}

/**
 * Checks whether a user has the right to enter on the platform or not.
 *
 * @param string The username, as provided in form
 * @param string The cleartext password, as provided in form
 * @param string The WS URL, as provided at the beginning of this script
 */
function loginWSAuthenticate($username, $password, $wsUrl)
{
    // check params
    if (empty($username) || empty($password) || empty($wsUrl)) {
        return false;
    }
    // Create new SOAP client instance
    $client = new SoapClient($wsUrl);
    if (!$client) {
        return false;
    }
    // Include phpseclib methods, because of a bug with AES/CFB in mcrypt
    include_once api_get_path(LIBRARY_PATH).'phpseclib/Crypt/AES.php';
    // Define all elements necessary to the encryption
    $key = '-+*%$({[]})$%*+-';
    // Complete password con PKCS7-specific padding
    $blockSize = 16;
    $padding = $blockSize - (strlen($password) % $blockSize);
    $password .= str_repeat(chr($padding), $padding);
    $cipher = new Crypt_AES(CRYPT_AES_MODE_CFB);
    $cipher->setKeyLength(128);
    $cipher->setKey($key);
    $cipher->setIV($key);

    $cipheredPass = $cipher->encrypt($password);
    // Mcrypt call left for documentation purposes - broken, see https://bugs.php.net/bug.php?id=51146
    //$cipheredPass = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $password,  MCRYPT_MODE_CFB, $key);

    // Following lines present for debug purposes only
    /*
    $arr = preg_split('//', $cipheredPass, -1, PREG_SPLIT_NO_EMPTY);
    foreach ($arr as $char) {
        error_log(ord($char));
    }
    */
    // Change to base64 to avoid communication alteration
    $passCrypted = base64_encode($cipheredPass);
    // The call to the webservice will change depending on your definition
    try {
        $response = $client->validateUser(
            [
                'user' => $username,
                'pass' => $passCrypted,
                'system' => 'chamilo',
            ]
        );
    } catch (SoapFault $fault) {
        error_log('Caught something');
        if ($fault->faultstring != 'Could not connect to host') {
            error_log('Not a connection problem');
            throw $fault;
        } else {
            error_log('Could not connect to WS host');
        }

        return 0;
    }

    return $response->validateUserResult;
}
