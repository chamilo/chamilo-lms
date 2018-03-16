<?php
/* For license terms, see /license.txt */
/**
 * A script to test session details by user web service.
 *
 * @package chamilo.plugin.advanced_subscription
 */

/**
 * Init.
 */
require_once __DIR__.'/../config.php';
// Protect test
api_protect_admin_script();

// exit;

$plugin = AdvancedSubscriptionPlugin::create();
$hookPlugin = HookAdvancedSubscription::create();
// Get params from request (GET or POST)
$params = [];
// Init result array
$params['user_id'] = intval($_REQUEST['u']);
$params['user_field'] = 'drupal_user_id';
$params['session_id'] = intval($_REQUEST['s']);
$params['profile_completed'] = 100;
$params['is_connected'] = true;

/**
 * Copied code from WSHelperVerifyKey function.
 */
/**
 * Start WSHelperVerifyKey.
 */
//error_log(print_r($params,1));
$check_ip = false;
$ip = trim($_SERVER['REMOTE_ADDR']);
// if we are behind a reverse proxy, assume it will send the
// HTTP_X_FORWARDED_FOR header and use this IP instead
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    list($ip1, $ip2) = split(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $ip = trim($ip1);
}
// Check if a file that limits access from webservices exists and contains
// the restraining check
if (is_file(api_get_path(WEB_CODE_PATH).'webservices/webservice-auth-ip.conf.php')) {
    include api_get_path(WEB_CODE_PATH).'webservices/webservice-auth-ip.conf.php';
    if (!empty($ws_auth_ip)) {
        $check_ip = true;
    }
}

global $_configuration;
if ($check_ip) {
    $security_key = $_configuration['security_key'];
} else {
    $security_key = $ip.$_configuration['security_key'];
    //error_log($secret_key.'-'.$security_key);
}
/**
 * End WSHelperVerifyKey.
 */
$params['secret_key'] = sha1($security_key);

// Registration soap wsdl
$wsUrl = api_get_path(WEB_CODE_PATH).'webservices/registration.soap.php?wsdl';
$options = [
    'location' => $wsUrl,
    'uri' => $wsUrl,
];

/**
 * WS test.
 */
try {
    // Init soap client
    $client = new SoapClient(null, $options);
    // Soap call to WS
    $result = $client->__soapCall('HookAdvancedSubscription..WSSessionGetDetailsByUser', [$params]);
    if (is_object($result) && isset($result->action_url)) {
        echo '<br />';
        echo Display::url("message".$result->message, $result->action_url);
    }
} catch (\Exception $e) {
    var_dump($e);
}
