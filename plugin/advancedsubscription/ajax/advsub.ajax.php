<?php
/**
 * Created by PhpStorm.
 * User: dbarreto
 * Date: 22/12/14
 * Time: 01:51 PM
 */

require_once __DIR__ . '/../config.php';

$plugin = AdvancedSubscriptionPlugin::create();
$data = isset($_REQUEST['data']) ?
    strlen($_REQUEST['data']) > 16 ?
        $plugin->decrypt($_REQUEST['data']) :
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
                // send mail to superior
                $sessionArray = api_get_session_info($s);
                $extraSession = new ExtraFieldValue('session');
                $var = $extraSession->get_values_by_handler_and_field_variable($s, 'as_description');
                $sessionArray['description'] = $var['field_valiue'];
                $var = $extraSession->get_values_by_handler_and_field_variable($s, 'publico_objetivo');
                $sessionArray['publico_objetivo'] = $var['field_valiue'];
                $var = $extraSession->get_values_by_handler_and_field_variable($s, 'modalidad');
                $sessionArray['modalidad'] = $var['field_valiue'];
                $var = $extraSession->get_values_by_handler_and_field_variable($s, 'fin_publicacion');
                $sessionArray['publico_objetivo'] = $var['field_value'];
                $var = $extraSession->get_values_by_handler_and_field_variable($s, 'numero_recomendado_participantes');
                $sessionArray['recommended_subscription_limit'] = $var['field_valiue'];
                $studentArray = api_get_user_info($u);
                $superiorArray = api_get_user_info($u);
                $adminArray = api_get_user_info($u);
                $studentArray = api_get_user_info($u);

                $data = array(
                    'student' => $studentArray,
                    'superior' => $superiorArray,
                    'admin' => $adminArray,
                    'session' => $sessionArray,
                    'signature' => 'AQUI DEBE IR UNA FIRMA',
                );

                $plugin->sendMail($data, ADV_SUB_ACTION_STUDENT_REQUEST);
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
        case 'admin':
            $studentList = $plugin->listAllStudentsInQueueBySession($s);
            $sessionList = $plugin->listAllSessions();
            $tpl = new Template('TESTING');
            $tpl->assign('session', $studentList['session']);
            $tpl->assign('sessionItems', $sessionList);
            $tpl->assign('students', $studentList['students']);
            $content = $tpl->fetch('/advancedsubscription/views/index.tpl');
            $tpl->assign('content', $content);
            $tpl->display_one_col_template();
            exit;
        default:
            $result['errorMessage'] = 'Action do not exist!';
    }
}

echo json_encode($result);
