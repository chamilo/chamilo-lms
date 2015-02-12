<?php
/* For licensing terms, see /license.txt */
/**
 * Script to receipt request to subscribe and confirmation action to queue
 * @author Daniel Alejandro Barreto Alva <daniel.barreto@beeznest.com>
 * @package chamilo.plugin.advancedsubscription
 */

/**
 * Init
 */
require_once __DIR__ . '/../config.php';

$plugin = AdvancedSubscriptionPlugin::create();
$hash = Security::remove_XSS($_REQUEST['v']);
$data['a'] = Security::remove_XSS($_REQUEST['a']);
$data['s'] = intval($_REQUEST['s']);
$data['current_user_id'] = intval($_REQUEST['current_user_id']);
$data['u'] = intval($_REQUEST['u']);
$data['q'] = intval($_REQUEST['q']);
$data['e'] = intval($_REQUEST['e']);
$data['is_connected'] = isset($_REQUEST['is_connected']) ? boolval($_REQUEST['is_connected']) : false;
$data['profile_completed'] = isset($_REQUEST['profile_completed']) ? floatval($_REQUEST['profile_completed']) : 0;
$verified = $plugin->checkHash($data, $hash) || $data['a'] == 'subscribe';
// Init result array
$result = array('error' => true, 'errorMessage' => 'There was an error');
if ($verified) {
    switch($data['a']) {
        case 'check': // Check minimum requirements
            try {
                $res = AdvancedSubscriptionPlugin::create()->isAbleToRequest($data['u'], $data);
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
            $bossId = UserManager::getStudentBoss($data['u']);
            $res = AdvancedSubscriptionPlugin::create()->startSubscription($data['u'], $data['s'], $data);
            if ($res === true) {
                // send mail to superior
                $sessionArray = api_get_session_info($data['s']);
                $extraSession = new ExtraFieldValue('session');
                $var = $extraSession->get_values_by_handler_and_field_variable($data['s'], 'description');
                $sessionArray['description'] = $var['field_valiue'];
                $var = $extraSession->get_values_by_handler_and_field_variable($data['s'], 'target');
                $sessionArray['target'] = $var['field_valiue'];
                $var = $extraSession->get_values_by_handler_and_field_variable($data['s'], 'mode');
                $sessionArray['mode'] = $var['field_valiue'];
                $var = $extraSession->get_values_by_handler_and_field_variable($data['s'], 'publication_end_date');
                $sessionArray['publication_end_date'] = $var['field_value'];
                $var = $extraSession->get_values_by_handler_and_field_variable($data['s'], 'recommended_number_of_participants');
                $sessionArray['recommended_number_of_participants'] = $var['field_valiue'];
                $studentArray = api_get_user_info($data['u']);
                $studentArray['picture'] = UserManager::get_user_picture_path_by_id($studentArray['user_id'], 'web', false, true);
                $studentArray['picture'] = UserManager::get_picture_user($studentArray['user_id'], $studentArray['picture']['file'], 22, USER_IMAGE_SIZE_MEDIUM);
                $superiorId = UserManager::getStudentBoss($data['u']);
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
                $data['a'] = 'confirm';
                $data['student'] = $studentArray;
                $data['superior'] = $superiorArray;
                $data['admins'] = $adminsArray;
                $data['session'] = $sessionArray;
                $data['signature'] = api_get_setting('Institution');

                if (empty($superiorId)) { // Does not have boss
                    $res = $plugin->updateQueueStatus($data, ADV_SUB_QUEUE_STATUS_BOSS_APPROVED);
                    if (!empty($res)) {
                        $data['admin_view_url'] = api_get_path(WEB_PLUGIN_PATH) . 'advancedsubscription/src/admin_view.php?s=' . $data['s'];
                        $result['mailIds'] = $plugin->sendMail($data, ADV_SUB_ACTION_STUDENT_REQUEST_NO_BOSS);
                        if (!empty($result['mailIds'])) {
                            $result['error'] = false;
                            $result['errorMessage'] = 'No error';
                            $result['pass'] = true;
                            if (isset($result['mailIds']['render'])) {
                                // Render mail
                                $message = MessageManager::get_message_by_id($result['mailIds']['render']);
                                $message = str_replace(array('<br /><hr>', '<br />', '<br/>'), '', $message['content']);
                                echo $message;
                                exit;
                            }
                        }
                    }
                } else {
                    $data['e'] = ADV_SUB_QUEUE_STATUS_BOSS_APPROVED;
                    $data['student']['acceptUrl'] = $plugin->getQueueUrl($data);
                    $data['e'] = ADV_SUB_QUEUE_STATUS_BOSS_DISAPPROVED;
                    $data['student']['rejectUrl'] = $plugin->getQueueUrl($data);
                    $result['mailIds'] = $plugin->sendMail($data, ADV_SUB_ACTION_STUDENT_REQUEST);
                    if (!empty($result['mailIds'])) {
                        $result['error'] = false;
                        $result['errorMessage'] = 'No error';
                        $result['pass'] = true;
                        if (isset($result['mailIds']['render'])) {
                            // Render mail
                            $message = MessageManager::get_message_by_id($result['mailIds']['render']);
                            $message = str_replace(array('<br /><hr>', '<br />', '<br/>'), '', $message['content']);
                            echo $message;
                            exit;
                        }
                    }
                }
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
            if (isset($data['e'])) {
                $res = $plugin->updateQueueStatus($data, $data['e']);
                if ($res === true) {
                    $sessionArray = api_get_session_info($data['s']);
                    $extraSession = new ExtraFieldValue('session');
                    $var = $extraSession->get_values_by_handler_and_field_variable($data['s'], 'description');
                    $sessionArray['description'] = $var['field_valiue'];
                    $var = $extraSession->get_values_by_handler_and_field_variable($data['s'], 'target');
                    $sessionArray['target'] = $var['field_valiue'];
                    $var = $extraSession->get_values_by_handler_and_field_variable($data['s'], 'mode');
                    $sessionArray['mode'] = $var['field_valiue'];
                    $var = $extraSession->get_values_by_handler_and_field_variable($data['s'], 'publication_end_date');
                    $sessionArray['publication_end_date'] = $var['field_value'];
                    $var = $extraSession->get_values_by_handler_and_field_variable($data['s'], 'recommended_number_of_participants');
                    $sessionArray['recommended_number_of_participants'] = $var['field_valiue'];
                    $studentArray = api_get_user_info($data['u']);
                    $studentArray['picture'] = UserManager::get_user_picture_path_by_id($studentArray['user_id'], 'web', false, true);
                    $studentArray['picture'] = UserManager::get_picture_user($studentArray['user_id'], $studentArray['picture']['file'], 22, USER_IMAGE_SIZE_MEDIUM);
                    $superiorId = UserManager::getStudentBoss($data['u']);
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
                    $data['student'] = $studentArray;
                    $data['superior'] = $superiorArray;
                    $data['admins'] = $adminsArray;
                    $data['session'] = $sessionArray;
                    $data['signature'] = api_get_setting('Institution');
                    $data['admin_view_url'] = api_get_path(WEB_PLUGIN_PATH) . 'advancedsubscription/src/admin_view.php?s=' . $data['s'];
                    if (empty($data['action'])) {
                        switch ($data['e']) {
                            case ADV_SUB_QUEUE_STATUS_BOSS_APPROVED:
                                $data['action'] = ADV_SUB_ACTION_SUPERIOR_APPROVE;
                                break;
                            case ADV_SUB_QUEUE_STATUS_BOSS_DISAPPROVED:
                                $data['action'] = ADV_SUB_ACTION_SUPERIOR_DISAPPROVE;
                                break;
                            case ADV_SUB_QUEUE_STATUS_ADMIN_APPROVED:
                                $data['action'] = ADV_SUB_ACTION_ADMIN_APPROVE;
                                break;
                            case ADV_SUB_QUEUE_STATUS_ADMIN_DISAPPROVED:
                                $data['action'] = ADV_SUB_ACTION_ADMIN_DISAPPROVE;
                                break;
                            default:
                                break;
                        }
                    }

                    // Student Session inscription
                    if ($data['e'] == ADV_SUB_QUEUE_STATUS_ADMIN_APPROVED) {
                        SessionManager::suscribe_users_to_session($data['s'], array($data['u']), null, false);
                    }

                    $result['mailIds'] = $plugin->sendMail($data, $data['action']);
                    if (!empty($result['mailIds'])) {
                        $result['error'] = false;
                        $result['errorMessage'] = 'User has been processed';
                        if (isset($result['mailIds']['render'])) {
                            // Render mail
                            $message = MessageManager::get_message_by_id($result['mailIds']['render']);
                            $message = str_replace(array('<br /><hr>', '<br />', '<br/>'), '', $message['content']);
                            echo $message;
                            exit;
                        }
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
