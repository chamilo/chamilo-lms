<?php
/* For license terms, see /license.txt */
/**
 * A script to test session details by user web service
 * @package chamilo.plugin.advanced_subscription
 */

/**
 * Init
 */
require_once __DIR__ . '/../config.php';
// Protect test
api_protect_admin_script();

// exit;

$plugin = AdvancedSubscriptionPlugin::create();
$hookPlugin = HookAdvancedSubscription::create();
// Get params from request (GET or POST)
$params = array();
// Init result array
$params['user_id'] = intval($_REQUEST['u']);
$params['session_id'] = intval($_REQUEST['s']);
$params['profile_completed'] = 100;
$params['secret_key'] = 'ed639d402804ffa347b489be3e42f28058e402bf';

// Registration soap wsdl
$wsUrl = 'http://chamilo19.net/main/webservices/registration.soap.php?wsdl';
$options = array(
    'location' => $wsUrl,
    'uri' => $wsUrl,
);

/**
 * WS test
 */
try {
    // Init soap client
    $client = new SoapClient(null, $options);
    // Soap call to WS
    $result = $client->__soapCall('HookAdvancedSubscription..WSSessionGetDetailsByUser', array($params));
    echo '<pre>', print_r($result, 1) , '</pre>';
    if (is_object($result) && isset($result->action_url)) {
        echo '<br />';
        echo Display::url($result->message, $result->action_url);
    }
} catch (\Exception $e) {
    echo '<pre>', print_r($e, 1) , '</pre>';
}
