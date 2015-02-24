<?php
/* For license terms, see /license.txt */
/**
 * A script to test session details by user web service
 * @package chamilo.plugin.advanced_subscription
 */

require_once __DIR__ . '/../config.php';
// Protect test
api_protect_admin_script();

// exit;

/**
 * Code copied from registration soap
 */

define('WS_ERROR_SECRET_KEY', 1);
define('WS_ERROR_NOT_FOUND_RESULT', 2);
define('WS_ERROR_INVALID_INPUT', 3);
define('WS_ERROR_SETTING', 4);

/**
 * Copied function from registration.soap.php
 * @param $code
 * @return null|soap_fault
 */
function return_error($code) {
    $fault = null;
    switch ($code) {
        case WS_ERROR_SECRET_KEY:
            $fault = new soap_fault('Server', '', 'Secret key is not correct or params are not correctly set');
            break;
        case WS_ERROR_NOT_FOUND_RESULT:
            $fault = new soap_fault('Server', '', 'No result was found for this query');
            break;
        case WS_ERROR_INVALID_INPUT:
            $fault = new soap_fault('Server', '', 'The input variables are invalid o are not correctly set');
            break;
        case WS_ERROR_SETTING:
            $fault = new soap_fault('Server', '', 'Please check the configuration for this webservice');
            break;
    }
    return $fault;
}

/**
 * Copied function from registration.soap.php
 * For this test, always return true
 * @param $params
 * @return bool
 */
function WSHelperVerifyKey($params)
{

    return true;
    /*
    global $_configuration, $debug;
    if (is_array($params)) {
        $secret_key = $params['secret_key'];
    } else {
        $secret_key = $params;
    }
    //error_log(print_r($params,1));
    $check_ip = false;
    $ip_matches = false;
    $ip = trim($_SERVER['REMOTE_ADDR']);
    // if we are behind a reverse proxy, assume it will send the
    // HTTP_X_FORWARDED_FOR header and use this IP instead
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        list($ip1, $ip2) = split(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ip1);
    }
    if ($debug)
        error_log("ip: $ip");
    // Check if a file that limits access from webservices exists and contains
    // the restraining check
    if (is_file('webservice-auth-ip.conf.php')) {
        include 'webservice-auth-ip.conf.php';
        if ($debug)
            error_log("webservice-auth-ip.conf.php file included");
        if (!empty($ws_auth_ip)) {
            $check_ip = true;
            $ip_matches = api_check_ip_in_range($ip, $ws_auth_ip);
            if ($debug)
                error_log("ip_matches: $ip_matches");
        }
    }

    if ($debug) {
        error_log("checkip " . intval($check_ip));
    }

    if ($check_ip) {
        $security_key = $_configuration['security_key'];
    } else {
        $security_key = $ip.$_configuration['security_key'];
        //error_log($secret_key.'-'.$security_key);
    }

    $result = api_is_valid_secret_key($secret_key, $security_key);
    //error_log($secret_key.'-'.$security_key);
    if ($debug)
        error_log('WSHelperVerifyKey result: '.intval($result));
    return $result;
    */
}

/**
 * End copied code
 */

$plugin = AdvancedSubscriptionPlugin::create();
$hookPlugin = HookAdvancedSubscription::create();
// Get validation hash
$hash = Security::remove_XSS($_REQUEST['v']);
// Get data from request (GET or POST)
$data = array();
$params = array();
$data['action'] = 'subscribe';
$data['currentUserId'] = api_get_user_id();
$data['queueId'] = 0;
$data['is_connected'] = true;
$data['profile_completed'] = 90.0;
// Init result array

$data['sessionId'] = intval($_REQUEST['s']);
$data['studentUserId'] = intval($_REQUEST['u']);

$params['user_id'] = $data['studentUserId'];
$params['session_id'] = $data['sessionId'];
$params['profile_completed'] = $data['profile_completed'];
$params['is_connected'] = $data['is_connected'];
$params['secret_key'] = 'secret_key';
$result = $hookPlugin->WSSessionGetDetailsByUser($params);
echo '<pre>', print_r($result, 1) , '</pre>';
if (is_array($result) && isset($result['action_url'])) {
    echo '<br />';
    echo Display::url($result['message'], $result['action_url']);
}


