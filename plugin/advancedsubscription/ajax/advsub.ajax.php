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
    // Adv sub action
    $e = isset($data['e']) ? intval($data['e']) : null;
    // More data
    $params['is_connected'] = isset($data['is_connected']) ? $data['is_connected'] : false;
    $params['profile_completed'] = isset($data['profile_completed']) ? $data['profile_completed'] : 0;
    $params['accept'] = isset($data['accept']) ? $data['accept'] : false;
} else {
    // Action code
    $a = isset($_REQUEST['a']) ? Security::remove_XSS($_REQUEST['a']) : null;
    // User ID
    $u = isset($_REQUEST['u']) ? intval($_REQUEST['u']) : null;
    // Session ID
    $s = isset($_REQUEST['s']) ? intval($_REQUEST['s']) : null;
    // Adv sub action
    $e = isset($_REQUEST['e']) ? intval($_REQUEST['e']) : null;
    // More data
    $params['is_connected'] = isset($_REQUEST['is_connected']) ? $_REQUEST['is_connected'] : false;
    $params['profile_completed'] = isset($_REQUEST['profile_completed']) ? $_REQUEST['profile_completed'] : 0;
    $params['accept'] = isset($_REQUEST['accept']) ? $_REQUEST['accept'] : false;
}
// Init result array
$result = array('error' => true, 'errorMessage' => 'There was an error');
if (!empty($a) && !empty($u)) {
    switch($a) {
        case 'check': // Check minimum requirements
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
        case 'subscribe': // Subscription
            $bossId = UserManager::getStudentBoss($u);
            $res = AdvancedSubscriptionPlugin::create()->startSubscription($u, $s, $params);
            if ($res === true) {
                // send mail to superior
                $sessionArray = api_get_session_info($s);
                $extraSession = new ExtraFieldValue('session');
                $var = $extraSession->get_values_by_handler_and_field_variable($s, 'as_description');
                $sessionArray['as_description'] = $var['field_valiue'];
                $var = $extraSession->get_values_by_handler_and_field_variable($s, 'target');
                $sessionArray['target'] = $var['field_valiue'];
                $var = $extraSession->get_values_by_handler_and_field_variable($s, 'mode');
                $sessionArray['mode'] = $var['field_valiue'];
                $var = $extraSession->get_values_by_handler_and_field_variable($s, 'publication_end_date');
                $sessionArray['publication_end_date'] = $var['field_value'];
                $var = $extraSession->get_values_by_handler_and_field_variable($s, 'recommended_number_of_participants');
                $sessionArray['recommended_number_of_participants'] = $var['field_valiue'];
                $studentArray = api_get_user_info($u);
                $superiorId = UserManager::getStudentBoss($u);
                if (!empty($superiorId)) {
                    $superiorArray = api_get_user_info($superiorId);
                } else {
                    $superiorArray = null;
                }
                $adminsArray = UserManager::get_all_administrators();
                foreach ($adminsArray as &$admin) {
                    $admin['complete_name'] = $admin['lastname'] . ', ' . $admin['firstname'];
                }
                unset($admin);
                $data = array(
                    'student' => $studentArray,
                    'superior' => $superiorArray,
                    'admins' => $adminsArray,
                    'session' => $sessionArray,
                    'signature' => 'AQUI DEBE IR UNA FIRMA',
                    's' => $s,
                    'u' => $u,
                );

                if (empty($superiorId)) { // Does not have boss
                    $res = $plugin->updateQueueStatus($data, ADV_SUB_QUEUE_STATUS_BOSS_APPROVED);
                    if (!empty($res)) {
                        $data['admin_view_url'] = api_get_path(WEB_PLUGIN_PATH) . 'advancedsubscription/src/admin_view.php?s=' . $s;
                        $result['mails'] = $plugin->sendMail($data, ADV_SUB_ACTION_STUDENT_REQUEST_NO_BOSS);
                    }
                } else {
                    $dataUrl = array(
                        'a' => 'confirm',
                        's' => $s,
                        'u' => $u,
                    );

                    $dataUrl['e'] = ADV_SUB_QUEUE_STATUS_BOSS_APPROVED;
                    $data['acceptUrl'] = api_get_path(WEB_PLUGIN_PATH) . 'advancedsubscription/ajax/advsub.ajax.php' .
                        '?data=' . $plugin->encrypt($dataUrl);
                    $dataUrl['e'] = ADV_SUB_QUEUE_STATUS_BOSS_DISAPPROVED;
                    $data['rejectUrl'] = api_get_path(WEB_PLUGIN_PATH) . 'advancedsubscription/ajax/advsub.ajax.php' .
                        '?data=' . $plugin->encrypt($dataUrl);
                    $result['mails'] = $plugin->sendMail($data, ADV_SUB_ACTION_STUDENT_REQUEST);
                }
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
        case 'encrypt': // Encrypt
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
        case 'confirm':
            if (isset($e)) {
                $res = $plugin->updateQueueStatus($data, $e);
                if ($res === true) {
                    $sessionArray = api_get_session_info($s);
                    $extraSession = new ExtraFieldValue('session');
                    $var = $extraSession->get_values_by_handler_and_field_variable($s, 'as_description');
                    $sessionArray['as_description'] = $var['field_valiue'];
                    $var = $extraSession->get_values_by_handler_and_field_variable($s, 'target');
                    $sessionArray['target'] = $var['field_valiue'];
                    $var = $extraSession->get_values_by_handler_and_field_variable($s, 'mode');
                    $sessionArray['mode'] = $var['field_valiue'];
                    $var = $extraSession->get_values_by_handler_and_field_variable($s, 'publication_end_date');
                    $sessionArray['publication_end_date'] = $var['field_value'];
                    $var = $extraSession->get_values_by_handler_and_field_variable($s, 'recommended_number_of_participants');
                    $sessionArray['recommended_number_of_participants'] = $var['field_valiue'];
                    $studentArray = api_get_user_info($u);
                    $superiorArray = api_get_user_info(UserManager::getStudentBoss($u));
                    $adminsArray = UserManager::get_all_administrators();
                    foreach ($adminsArray as &$admin) {
                        $admin['complete_name'] = $admin['lastname'] . ', ' . $admin['firstname'];
                    }
                    unset($admin);
                    $data['student'] = $studentArray;
                    $data['superior'] = $superiorArray;
                    $data['admins'] = $adminsArray;
                    $data['session'] = $sessionArray;
                    $data['signature'] = 'AQUI DEBE IR UNA FIRMA';
                    $data['admin_view_url'] = api_get_path(WEB_PLUGIN_PATH) . 'advancedsubscription/src/admin_view.php';
                    if (empty($data['action'])) {
                        switch ($e) {
                            case ADV_SUB_QUEUE_STATUS_BOSS_APPROVED:
                                $data['action'] = ADV_SUB_ACTION_SUPERIOR_APPROVE;
                                break;
                            case ADV_SUB_QUEUE_STATUS_BOSS_DISAPPROVED:
                                $data['action'] = ADV_SUB_ACTION_SUPERIOR_APPROVE;
                                break;
                            case ADV_SUB_QUEUE_STATUS_ADMIN_APPROVED:
                                $data['action'] = ADV_SUB_ACTION_ADMIN_APPROVE;
                                break;
                            case ADV_SUB_QUEUE_STATUS_ADMIN_DISAPPROVED:
                                $data['action'] = ADV_SUB_QUEUE_STATUS_ADMIN_DISAPPROVED;
                                break;
                            default:
                                break;
                        }
                    }

                    $result['mailIds'] = $plugin->sendMail($data, $data['action']);
                    if (!empty($result['mailIds'])) {
                        $result['error'] = false;
                        $result['errorMessage'] = 'User has been processed';
                    }
                } else {
                    $result['errorMessage'] = 'User queue can not be updated';
                }
            }
            break;
        default:
            $result['errorMessage'] = 'Action do not exist!';
    }
}

echo json_encode($result);
