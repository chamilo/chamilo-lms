<?php

/* For licensing terms, see /license.txt */
/**
 * Script to receipt request to subscribe and confirmation action to queue.
 *
 * @author Daniel Alejandro Barreto Alva <daniel.barreto@beeznest.com>
 */
/**
 * Init.
 */
require_once __DIR__.'/../config.php';

$plugin = AdvancedSubscriptionPlugin::create();
// Get validation hash
$hash = Security::remove_XSS($_REQUEST['v']);
// Get data from request (GET or POST)
$data['action'] = Security::remove_XSS($_REQUEST['a']);
$data['sessionId'] = (int) ($_REQUEST['s']);
$data['currentUserId'] = (int) ($_REQUEST['current_user_id']);
$data['studentUserId'] = (int) ($_REQUEST['u']);
$data['queueId'] = (int) ($_REQUEST['q']);
$data['newStatus'] = (int) ($_REQUEST['e']);
// Student always is connected
// $data['is_connected'] = isset($_REQUEST['is_connected']) ? boolval($_REQUEST['is_connected']) : false;
$data['is_connected'] = true;
$data['profile_completed'] = isset($_REQUEST['profile_completed']) ? (float) ($_REQUEST['profile_completed']) : 0;
$data['accept_terms'] = isset($_REQUEST['accept_terms']) ? (int) ($_REQUEST['accept_terms']) : 0;
$data['courseId'] = isset($_REQUEST['c']) ? (int) ($_REQUEST['c']) : 0;
// Init result array
$result = ['error' => true, 'errorMessage' => get_lang('There was an error.')];
$showJSON = true;
// Check if data is valid or is for start subscription
$verified = $plugin->checkHash($data, $hash) || 'subscribe' == $data['action'];
if ($verified) {
    switch ($data['action']) {
        case 'check': // Check minimum requirements
            try {
                $res = AdvancedSubscriptionPlugin::create()->isAllowedToDoRequest($data['studentUserId'], $data);
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
            // Start subscription to queue
            $res = AdvancedSubscriptionPlugin::create()->startSubscription(
                $data['studentUserId'],
                $data['sessionId'],
                $data
            );
            // Check if queue subscription was successful
            if (true === $res) {
                $legalEnabled = api_get_plugin_setting('courselegal', 'tool_enable');
                if ($legalEnabled) {
                    // Save terms confirmation
                    CourseLegalPlugin::create()->saveUserLegal(
                        $data['studentUserId'],
                        $data['courseId'],
                        $data['sessionId'],
                        false
                    );
                }
                // Prepare data
                // Get session data
                // Assign variables
                $fieldsArray = [
                    'description',
                    'target',
                    'mode',
                    'publication_end_date',
                    'recommended_number_of_participants',
                ];
                $sessionArray = api_get_session_info($data['sessionId']);
                $extraSession = new ExtraFieldValue('session');
                $extraField = new ExtraField('session');
                // Get session fields
                $fieldList = $extraField->get_all([
                    'variable IN ( ?, ?, ?, ?, ?)' => $fieldsArray,
                ]);
                // Index session fields
                foreach ($fieldList as $field) {
                    $fields[$field['id']] = $field['variable'];
                }

                $mergedArray = array_merge([$data['sessionId']], array_keys($fields));
                $sessionFieldValueList = $extraSession->get_all(
                    [
                        'item_id = ? field_id IN ( ?, ?, ?, ?, ?, ?, ? )' => $mergedArray,
                    ]
                );
                foreach ($sessionFieldValueList as $sessionFieldValue) {
                    // Check if session field value is set in session field list
                    if (isset($fields[$sessionFieldValue['field_id']])) {
                        $var = $fields[$sessionFieldValue['field_id']];
                        $val = $sessionFieldValue['value'];
                        // Assign session field value to session
                        $sessionArray[$var] = $val;
                    }
                }
                // Get student data
                $studentArray = api_get_user_info($data['studentUserId']);
                $studentArray['picture'] = $studentArray['avatar'];

                // Get superior data if exist
                $superiorId = UserManager::getFirstStudentBoss($data['studentUserId']);
                if (!empty($superiorId)) {
                    $superiorArray = api_get_user_info($superiorId);
                } else {
                    $superiorArray = null;
                }
                // Get admin data
                $adminsArray = UserManager::get_all_administrators();
                $isWesternNameOrder = api_is_western_name_order();
                foreach ($adminsArray as &$admin) {
                    $admin['complete_name'] = $isWesternNameOrder ?
                        $admin['firstname'].', '.$admin['lastname'] : $admin['lastname'].', '.$admin['firstname']
                    ;
                }
                unset($admin);
                // Set data
                $data['action'] = 'confirm';
                $data['student'] = $studentArray;
                $data['superior'] = $superiorArray;
                $data['admins'] = $adminsArray;
                $data['session'] = $sessionArray;
                $data['signature'] = api_get_setting('Institution');

                // Check if student boss exists
                if (empty($superiorId)) {
                    // Student boss does not exist
                    // Update status to accepted by boss
                    $res = $plugin->updateQueueStatus($data, ADVANCED_SUBSCRIPTION_QUEUE_STATUS_BOSS_APPROVED);
                    if (!empty($res)) {
                        // Prepare admin url
                        $data['admin_view_url'] = api_get_path(WEB_PLUGIN_PATH).
                            'advanced_subscription/src/admin_view.php?s='.$data['sessionId'];
                        // Send mails
                        $result['mailIds'] = $plugin->sendMail(
                            $data,
                            ADVANCED_SUBSCRIPTION_ACTION_STUDENT_REQUEST_NO_BOSS
                        );
                        // Check if mails were sent
                        if (!empty($result['mailIds'])) {
                            $result['error'] = false;
                            $result['errorMessage'] = 'No error';
                            $result['pass'] = true;
                            // Check if exist an email to render
                            if (isset($result['mailIds']['render'])) {
                                // Render mail
                                $url = $plugin->getRenderMailUrl(['queueId' => $result['mailIds']['render']]);
                                header('Location: '.$url);
                                exit;
                            }
                        }
                    }
                } else {
                    // Student boss does exist
                    // Get url to be accepted by boss
                    $data['newStatus'] = ADVANCED_SUBSCRIPTION_QUEUE_STATUS_BOSS_APPROVED;
                    $data['student']['acceptUrl'] = $plugin->getQueueUrl($data);
                    // Get url to be rejected by boss
                    $data['newStatus'] = ADVANCED_SUBSCRIPTION_QUEUE_STATUS_BOSS_DISAPPROVED;
                    $data['student']['rejectUrl'] = $plugin->getQueueUrl($data);
                    // Send mails
                    $result['mailIds'] = $plugin->sendMail($data, ADVANCED_SUBSCRIPTION_ACTION_STUDENT_REQUEST);
                    // Check if mails were sent
                    if (!empty($result['mailIds'])) {
                        $result['error'] = false;
                        $result['errorMessage'] = 'No error';
                        $result['pass'] = true;
                        // Check if exist an email to render
                        if (isset($result['mailIds']['render'])) {
                            // Render mail
                            $url = $plugin->getRenderMailUrl(['queueId' => $result['mailIds']['render']]);
                            header('Location: '.$url);
                            exit;
                        }
                    }
                }
            } else {
                $lastMessageId = $plugin->getLastMessageId($data['studentUserId'], $data['sessionId']);
                if (false !== $lastMessageId) {
                    // Render mail
                    $url = $plugin->getRenderMailUrl(['queueId' => $lastMessageId]);
                    header('Location: '.$url);
                    exit;
                } else {
                    if (is_string($res)) {
                        $result['errorMessage'] = $res;
                    } else {
                        $result['errorMessage'] = 'User can not be subscribed';
                    }
                    $result['pass'] = false;
                    $url = $plugin->getTermsUrl($data, ADVANCED_SUBSCRIPTION_TERMS_MODE_FINAL);
                    header('Location: '.$url);
                    exit;
                }
            }

            break;
        case 'confirm':
            // Check if new status is set
            if (isset($data['newStatus'])) {
                if (ADVANCED_SUBSCRIPTION_QUEUE_STATUS_ADMIN_APPROVED === $data['newStatus']) {
                    try {
                        $isAllowToDoRequest = $plugin->isAllowedToDoRequest($data['studentUserId'], $data);
                    } catch (Exception $ex) {
                        $messageTemplate = new Template(null, false, false);
                        $messageTemplate->assign(
                            'content',
                            Display::return_message($ex->getMessage(), 'error', false)
                        );
                        $messageTemplate->display_no_layout_template();
                        $showJSON = false;

                        break;
                    }
                }

                // Update queue status
                $res = $plugin->updateQueueStatus($data, $data['newStatus']);
                if (true === $res) {
                    // Prepare data
                    // Prepare session data
                    $fieldsArray = [
                        'description',
                        'target',
                        'mode',
                        'publication_end_date',
                        'recommended_number_of_participants',
                    ];
                    $sessionArray = api_get_session_info($data['sessionId']);
                    $extraSession = new ExtraFieldValue('session');
                    $extraField = new ExtraField('session');
                    // Get session fields
                    $fieldList = $extraField->get_all([
                        'variable IN ( ?, ?, ?, ?, ?)' => $fieldsArray,
                    ]);
                    // Index session fields
                    foreach ($fieldList as $field) {
                        $fields[$field['id']] = $field['variable'];
                    }

                    $mergedArray = array_merge([$data['sessionId']], array_keys($fields));
                    $sessionFieldValueList = $extraSession->get_all(
                        ['session_id = ? field_id IN ( ?, ?, ?, ?, ?, ?, ? )' => $mergedArray]
                    );
                    foreach ($sessionFieldValueList as $sessionFieldValue) {
                        // Check if session field value is set in session field list
                        if (isset($fields[$sessionFieldValue['field_id']])) {
                            $var = $fields[$sessionFieldValue['field_id']];
                            $val = $sessionFieldValue['value'];
                            // Assign session field value to session
                            $sessionArray[$var] = $val;
                        }
                    }
                    // Prepare student data
                    $studentArray = api_get_user_info($data['studentUserId']);
                    $studentArray['picture'] = $studentArray['avatar'];
                    // Prepare superior data
                    $superiorId = UserManager::getFirstStudentBoss($data['studentUserId']);
                    if (!empty($superiorId)) {
                        $superiorArray = api_get_user_info($superiorId);
                    } else {
                        $superiorArray = null;
                    }
                    // Prepare admin data
                    $adminsArray = UserManager::get_all_administrators();
                    $isWesternNameOrder = api_is_western_name_order();
                    foreach ($adminsArray as &$admin) {
                        $admin['complete_name'] = $isWesternNameOrder ?
                            $admin['firstname'].', '.$admin['lastname'] : $admin['lastname'].', '.$admin['firstname']
                        ;
                    }
                    unset($admin);
                    // Set data
                    $data['student'] = $studentArray;
                    $data['superior'] = $superiorArray;
                    $data['admins'] = $adminsArray;
                    $data['session'] = $sessionArray;
                    $data['signature'] = api_get_setting('Institution');
                    $data['admin_view_url'] = api_get_path(WEB_PLUGIN_PATH)
                        .'advanced_subscription/src/admin_view.php?s='.$data['sessionId'];
                    // Check if exist and action in data
                    if (empty($data['mailAction'])) {
                        // set action in data by new status
                        switch ($data['newStatus']) {
                            case ADVANCED_SUBSCRIPTION_QUEUE_STATUS_BOSS_APPROVED:
                                $data['mailAction'] = ADVANCED_SUBSCRIPTION_ACTION_SUPERIOR_APPROVE;

                                break;
                            case ADVANCED_SUBSCRIPTION_QUEUE_STATUS_BOSS_DISAPPROVED:
                                $data['mailAction'] = ADVANCED_SUBSCRIPTION_ACTION_SUPERIOR_DISAPPROVE;

                                break;
                            case ADVANCED_SUBSCRIPTION_QUEUE_STATUS_ADMIN_APPROVED:
                                $data['mailAction'] = ADVANCED_SUBSCRIPTION_ACTION_ADMIN_APPROVE;

                                break;
                            case ADVANCED_SUBSCRIPTION_QUEUE_STATUS_ADMIN_DISAPPROVED:
                                $data['mailAction'] = ADVANCED_SUBSCRIPTION_ACTION_ADMIN_DISAPPROVE;

                                break;
                            default:
                                break;
                        }
                    }

                    // Student Session inscription
                    if (ADVANCED_SUBSCRIPTION_QUEUE_STATUS_ADMIN_APPROVED == $data['newStatus']) {
                        SessionManager::subscribeUsersToSession(
                            $data['sessionId'],
                            [$data['studentUserId']],
                            null,
                            false
                        );
                    }

                    // Send mails
                    $result['mailIds'] = $plugin->sendMail($data, $data['mailAction']);
                    // Check if mails were sent
                    if (!empty($result['mailIds'])) {
                        $result['error'] = false;
                        $result['errorMessage'] = 'User has been processed';
                        // Check if exist mail to render
                        if (isset($result['mailIds']['render'])) {
                            // Render mail
                            $url = $plugin->getRenderMailUrl(['queueId' => $result['mailIds']['render']]);
                            header('Location: '.$url);
                            exit;
                        }
                    }
                } else {
                    $result['errorMessage'] = 'User queue can not be updated';
                }
            }

            break;
        default:
            $result['errorMessage'] = 'This action does not exist!';
    }
}

if ($showJSON) {
    // Echo result as json
    echo json_encode($result);
}
