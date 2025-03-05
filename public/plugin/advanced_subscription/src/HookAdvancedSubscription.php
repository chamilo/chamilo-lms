<?php

/* For licensing terms, see /license.txt */

/**
 * Hook Observer for Advanced subscription plugin.
 *
 * @author Daniel Alejandro Barreto Alva <daniel.barreto@beeznest.com>
 */
require_once __DIR__.'/../config.php';

class HookAdvancedSubscription {
    /**
     * @param $params
     *
     * @return soap_fault|null
     */
    public static function WSSessionListInCategory($params)
    {
        global $debug;

        if ($debug) {
            error_log(__FUNCTION__);
            error_log('Params '.print_r($params, 1));
            if (!WSHelperVerifyKey($params)) {
                error_log(return_error(WS_ERROR_SECRET_KEY));
            }
        }
        // Check if category ID is set
        if (!empty($params['id']) && empty($params['name'])) {
            $sessionCategoryId = $params['id'];
        } elseif (!empty($params['name'])) {
            // Check if category name is set
            $sessionCategoryId = SessionManager::getSessionCategoryIdByName($params['name']);
            if (is_array($sessionCategoryId)) {
                $sessionCategoryId = current($sessionCategoryId);
            }
        } else {
            // Return soap fault Not valid input params

            return return_error(WS_ERROR_INVALID_INPUT);
        }

        // Get the session brief List by category
        $fields = [
            'id',
            'short_description',
            'mode',
            'human_text_duration',
            'vacancies',
            'schedule',
        ];
        $datePub = new DateTime();

        return SessionManager::getShortSessionListAndExtraByCategory(
            $sessionCategoryId,
            $params['target'],
            $fields,
            $datePub
        );
    }

    /**
     * @param $params
     *
     * @return soap_fault|null
     */
    public static function WSSessionGetDetailsByUser($params)
    {
        global $debug;

        if ($debug) {
            error_log('WSUserSubscribedInCourse');
            error_log('Params '.print_r($params, 1));
        }
        if (!WSHelperVerifyKey($params)) {
            return return_error(WS_ERROR_SECRET_KEY);
        }
        // Check params
        if (is_array($params) && !empty($params['session_id']) && !empty($params['user_id'])) {
            $userId = UserManager::get_user_id_from_original_id($params['user_id'], $params['user_field']);
            $sessionId = (int) $params['session_id'];
            // Check if user exists
            if (UserManager::is_user_id_valid($userId) &&
                SessionManager::isValidId($sessionId)
            ) {
                // Check if student is already subscribed
                $plugin = AdvancedSubscriptionPlugin::create();
                $isOpen = $plugin->isSessionOpen($sessionId);
                $status = $plugin->getQueueStatus($userId, $sessionId);
                $vacancy = $plugin->getVacancy($sessionId);
                $data = $plugin->getSessionDetails($sessionId);
                $isUserInTargetGroup = $plugin->isUserInTargetGroup($userId, $sessionId);
                if (!empty($data) && is_array($data)) {
                    $data['status'] = $status;
                    // Vacancy and queue status cases:
                    if ($isOpen) {
                        // Go to Course session
                        $data['action_url'] = $plugin->getOpenSessionUrl($userId, $params);
                        if (SessionManager::isUserSubscribedAsStudent($sessionId, $userId)) {
                            $data['status'] = 10;
                        }
                    } else {
                        if (!$isUserInTargetGroup) {
                            $data['status'] = -2;
                        } else {
                            try {
                                $isAllowed = $plugin->isAllowedToDoRequest($userId, $params);
                                $data['message'] = $plugin->getStatusMessage($status, $isAllowed);
                            } catch (\Exception $e) {
                                $data['message'] = $e->getMessage();
                            }
                            $params['action'] = 'subscribe';
                            $params['sessionId'] = (int) $sessionId;
                            $params['currentUserId'] = 0; // No needed
                            $params['studentUserId'] = (int) $userId;
                            $params['queueId'] = 0; // No needed
                            $params['newStatus'] = ADVANCED_SUBSCRIPTION_QUEUE_STATUS_START;
                            if ($vacancy > 0) {
                                // Check conditions
                                if (ADVANCED_SUBSCRIPTION_QUEUE_STATUS_NO_QUEUE == $status) {
                                    // No in Queue, require queue subscription url action
                                    $data['action_url'] = $plugin->getTermsUrl($params);
                                } elseif (ADVANCED_SUBSCRIPTION_QUEUE_STATUS_ADMIN_APPROVED == $status) {
                                    // send url action
                                    $data['action_url'] = $plugin->getSessionUrl($sessionId);
                                } // Else: In queue, output status message, no more info.
                            } else {
                                if (ADVANCED_SUBSCRIPTION_QUEUE_STATUS_ADMIN_APPROVED == $status) {
                                    $data['action_url'] = $plugin->getSessionUrl($sessionId);
                                } elseif (ADVANCED_SUBSCRIPTION_QUEUE_STATUS_NO_QUEUE == $status) {
                                    // in Queue or not, cannot be subscribed to session
                                    $data['action_url'] = $plugin->getTermsUrl($params);
                                } // Else: In queue, output status message, no more info.
                            }
                        }
                    }
                    $result = $data;
                } else {
                    // Return soap fault No result was found
                    $result = return_error(WS_ERROR_NOT_FOUND_RESULT);
                }
            } else {
                // Return soap fault No result was found
                $result = return_error(WS_ERROR_NOT_FOUND_RESULT);
            }
        } else {
            // Return soap fault Not valid input params
            $result = return_error(WS_ERROR_INVALID_INPUT);
        }

        return $result;
    }

    /**
     * Get a list of sessions (id, coach_id, name, courses_num, users_num, classes_num,
     * access_start_date, access_end_date, access_days_before_num, session_admin_id, visibility,
     * session_category_id, promotion_id,
     * validated_user_num, waiting_user_num,
     * extra, course) the validated_usernum and waiting_user_num are
     * used when have the plugin for advance incsription enables.
     * The extra data (field_name, field_value)
     * The course data (course_id, course_code, course_title,
     * coach_username, coach_firstname, coach_lastname).
     *
     * @param array $params List of parameters (id, category_name, access_url_id, secret_key)
     *
     * @return array|soap_fault Sessions list (id=>[title=>'title',url='http://...',date_start=>'...',date_end=>''])
     */
    public static function WSListSessionsDetailsByCategory($params)
    {
        global $debug;

        if ($debug) {
            error_log('WSListSessionsDetailsByCategory');
            error_log('Params '.print_r($params, 1));
        }
        $secretKey = $params['secret_key'];

        // Check if secret key is valid
        if (!WSHelperVerifyKey($secretKey)) {
            return return_error(WS_ERROR_SECRET_KEY);
        }

        // Check if category ID is set
        if (!empty($params['id']) && empty($params['category_name'])) {
            $sessionCategoryId = $params['id'];
        } elseif (!empty($params['category_name'])) {
            // Check if category name is set
            $sessionCategoryId = SessionManager::getSessionCategoryIdByName($params['category_name']);
            if (is_array($sessionCategoryId)) {
                $sessionCategoryId = current($sessionCategoryId);
            }
        } else {
            // Return soap fault Not valid input params

            return return_error(WS_ERROR_INVALID_INPUT);
        }

        // Get the session List by category
        $sessionList = SessionManager::getSessionListAndExtraByCategoryId($sessionCategoryId);

        if (empty($sessionList)) {
            // If not found any session, return error

            return return_error(WS_ERROR_NOT_FOUND_RESULT);
        }

        // Get validated and waiting queue users count for each session
        $plugin = AdvancedSubscriptionPlugin::create();
        foreach ($sessionList as &$session) {
            // Add validated and queue users count
            $session['validated_user_num'] = $plugin->countQueueByParams(
                [
                    'sessions' => [$session['id']],
                    'status' => [ADVANCED_SUBSCRIPTION_QUEUE_STATUS_ADMIN_APPROVED],
                ]
            );
            $session['waiting_user_num'] = $plugin->countQueueByParams(
                [
                    'sessions' => [$session['id']],
                    'status' => [
                        ADVANCED_SUBSCRIPTION_QUEUE_STATUS_START,
                        ADVANCED_SUBSCRIPTION_QUEUE_STATUS_BOSS_APPROVED,
                    ],
                ]
            );
        }

        return $sessionList;
    }
}
