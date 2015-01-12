<?php
/**
 * Created by PhpStorm.
 * User: dbarreto
 * Date: 22/12/14
 * Time: 01:51 PM
 */

require_once '../config.php';

$plugin = AdvancedSubscriptionPlugin::create();
$data = isset($_REQUEST['data']) ?
    strlen($_REQUEST['data']) > 16 ?
        $plugin->decrypt(Security::remove_XSS($_REQUEST['data'])) :
        null :
    null;
// Get data
if (isset($data) && is_array($data)) {
    // Action code
    $a = isset($data['a']) ? $data['a'] : null;
    // User ID
    $u = isset($data['u']) ? $data['u'] : null;
    // Session ID
    $s = isset($data['s']) ? $data['s'] : null;
    // More data
    $params['is_connected'] = isset($data['is_connected']) ? $data['is_connected'] : false;
    $params['profile_completed'] = isset($data['profile_completed']) ? $data['profile_completed'] : 0;
    $params['accept'] = isset($data['accept']) ? $data['accept'] : false;
} else {
    // Action code
    $a = isset($_REQUEST['a']) ? Security::remove_XSS($_REQUEST['a']) : null;
    // User ID
    $u = isset($_REQUEST['u']) ? Security::remove_XSS($_REQUEST['u']) : null;
    // Session ID
    $s = isset($_REQUEST['s']) ? Security::remove_XSS($_REQUEST['s']) : null;
    // More data
    $params['is_connected'] = isset($_REQUEST['is_connected']) ? $_REQUEST['is_connected'] : false;
    $params['profile_completed'] = isset($_REQUEST['profile_completed']) ? $_REQUEST['profile_completed'] : 0;
    $params['accept'] = isset($_REQUEST['accept']) ? $_REQUEST['accept'] : false;
}
// Init result array
$result = array('error' => true, 'errorMessage' => 'There was an error');
if (!empty($a) && !empty($u)) {
    switch($a) {
        case 'first': // Check minimum requirements
            try {
                $res = AdvancedSubscriptionPlugin::create()->isAbleToRequest($u, $params);
                if ($res) {
                    $result['error'] = false;
                    $result['errorMessage'] = 'No error';
                    $result['pass'] = true;
                } else {
                    $result['errorMessage'] = 'User can not be subscribed';
                    $result['pass'] = false;
                }
            } catch (\Exception $e) {
                $result['errorMessage'] = $e->getMessage();
            }
            break;
        case 'second': // Subscription
            $res = AdvancedSubscriptionPlugin::create()->startSubscription($u, $s, $params);
            if ($res === true) {
                $result['error'] = false;
                $result['errorMessage'] = 'No error';
                $result['pass'] = true;
            } else {
                if (is_string($res)) {
                    $result['errorMessage'] = $res;
                } else {
                    $result['errorMessage'] = 'User can not be subscribed';
                }
                $result['pass'] = false;
            }
            break;
        case 'third': // Encrypt
            $plugin = AdvancedSubscriptionPlugin::create();
            $res = $plugin->encrypt($data);
            if (!empty($res) && strlen($res) > 16) {
                $result['error'] = false;
                $result['errorMessage'] = 'No error';
                $result['pass'] = true;
            } else {
                if (is_string($res)) {
                    $result['errorMessage'] = $res;
                } else {
                    $result['errorMessage'] = 'User can not be subscribed';
                }
                $result['pass'] = false;
            }
            break;
        default:
            $result['errorMessage'] = 'Action do not exist!';
            break;
    }
}

echo json_encode($result);
