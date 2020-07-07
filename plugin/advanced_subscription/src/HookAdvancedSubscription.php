<?php
/* For licensing terms, see /license.txt */
/**
 * Hook Observer for Advanced subscription plugin.
 *
 * @author Daniel Alejandro Barreto Alva <daniel.barreto@beeznest.com>
 *
 * @package chamilo.plugin.advanced_subscription
 */
require_once __DIR__.'/../config.php';

/**
 * Class HookAdvancedSubscription extends the HookObserver to implements
 * specific behaviour when the AdvancedSubscription plugin is enabled.
 */
class HookAdvancedSubscription extends HookObserver implements HookAdminBlockObserverInterface, HookWSRegistrationObserverInterface, HookNotificationContentObserverInterface
{
    public static $plugin;

    /**
     * Constructor. Calls parent, mainly.
     */
    protected function __construct()
    {
        self::$plugin = AdvancedSubscriptionPlugin::create();
        parent::__construct(
            'plugin/advanced_subscription/src/HookAdvancedSubscription.class.php',
            'advanced_subscription'
        );
    }

    /**
     * @return array
     */
    public function hookAdminBlock(HookAdminBlockEventInterface $hook)
    {
        $data = $hook->getEventData();
        // if ($data['type'] === HOOK_EVENT_TYPE_PRE) // Nothing to do
        if ($data['type'] === HOOK_EVENT_TYPE_POST) {
            if (isset($data['blocks'])) {
                $data['blocks']['sessions']['items'][] = [
                    'url' => '../../plugin/advanced_subscription/src/admin_view.php',
                    'label' => get_plugin_lang('plugin_title', 'AdvancedSubscriptionPlugin'),
                ];
            }
        } // Else: Hook type is not valid, nothing to do

        return $data;
    }

    /**
     * Add Webservices to registration.soap.php.
     *
     * @return mixed (int or false)
     */
    public function hookWSRegistration(HookWSRegistrationEventInterface $hook)
    {
        $data = $hook->getEventData();
        //if ($data['type'] === HOOK_EVENT_TYPE_PRE) // nothing to do
        if ($data['type'] === HOOK_EVENT_TYPE_POST) {
            /** @var \nusoap_server $server */
            $server = &$data['server'];

            /** WSSessionListInCategory */

            // Output params for sessionBriefList WSSessionListInCategory
            $server->wsdl->addComplexType(
                'sessionBrief',
                'complexType',
                'struct',
                'all',
                '',
                [
                    // session.id
                    'id' => ['name' => 'id', 'type' => 'xsd:int'],
                    // session.name
                    'name' => ['name' => 'name', 'type' => 'xsd:string'],
                    // session.short_description
                    'short_description' => ['name' => 'short_description', 'type' => 'xsd:string'],
                    // session.mode
                    'mode' => ['name' => 'mode', 'type' => 'xsd:string'],
                    // session.date_start
                    'date_start' => ['name' => 'date_start', 'type' => 'xsd:string'],
                    // session.date_end
                    'date_end' => ['name' => 'date_end', 'type' => 'xsd:string'],
                    // session.human_text_duration
                    'human_text_duration' => ['name' => 'human_text_duration', 'type' => 'xsd:string'],
                    // session.vacancies
                    'vacancies' => ['name' => 'vacancies', 'type' => 'xsd:string'],
                    // session.schedule
                    'schedule' => ['name' => 'schedule', 'type' => 'xsd:string'],
                ]
            );

            //Output params for WSSessionListInCategory
            $server->wsdl->addComplexType(
                'sessionBriefList',
                'complexType',
                'array',
                '',
                'SOAP-ENC:Array',
                [],
                [
                    ['ref' => 'SOAP-ENC:arrayType',
                        'wsdl:arrayType' => 'tns:sessionBrief[]', ],
                ],
                'tns:sessionBrief'
            );

            // Input params for WSSessionListInCategory
            $server->wsdl->addComplexType(
                'sessionCategoryInput',
                'complexType',
                'struct',
                'all',
                '',
                [
                    'id' => ['name' => 'id', 'type' => 'xsd:string'], // session_category.id
                    'name' => ['name' => 'name', 'type' => 'xsd:string'], // session_category.name
                    'target' => ['name' => 'target', 'type' => 'xsd:string'], // session.target
                    'secret_key' => ['name' => 'secret_key', 'type' => 'xsd:string'],
                ]
            );

            // Input params for WSSessionGetDetailsByUser
            $server->wsdl->addComplexType(
                'advsubSessionDetailInput',
                'complexType',
                'struct',
                'all',
                '',
                [
                    // user_field_values.value
                    'user_id' => ['name' => 'user_id', 'type' => 'xsd:int'],
                    // user_field.user_id
                    'user_field' => ['name' => 'user_field', 'type' => 'xsd:string'],
                    // session.id
                    'session_id' => ['name' => 'session_id', 'type' => 'xsd:int'],
                    // user.profile_completes
                    'profile_completed' => ['name' => 'profile_completed', 'type' => 'xsd:float'],
                    // user.is_connected
                    'is_connected' => ['name' => 'is_connected', 'type' => 'xsd:boolean'],
                    'secret_key' => ['name' => 'secret_key', 'type' => 'xsd:string'],
                ]
            );

            // Output params for WSSessionGetDetailsByUser
            $server->wsdl->addComplexType(
                'advsubSessionDetail',
                'complexType',
                'struct',
                'all',
                '',
                [
                    // session.id
                    'id' => ['name' => 'id', 'type' => 'xsd:string'],
                    // session.code
                    'code' => ['name' => 'code', 'type' => 'xsd:string'],
                    // session.place
                    'cost' => ['name' => 'cost', 'type' => 'xsd:float'],
                    // session.place
                    'place' => ['name' => 'place', 'type' => 'xsd:string'],
                    // session.allow_visitors
                    'allow_visitors' => ['name' => 'allow_visitors', 'type' => 'xsd:string'],
                    // session.teaching_hours
                    'teaching_hours' => ['name' => 'teaching_hours', 'type' => 'xsd:int'],
                    // session.brochure
                    'brochure' => ['name' => 'brochure', 'type' => 'xsd:string'],
                    // session.banner
                    'banner' => ['name' => 'banner', 'type' => 'xsd:string'],
                    // session.description
                    'description' => ['name' => 'description', 'type' => 'xsd:string'],
                    // status
                    'status' => ['name' => 'status', 'type' => 'xsd:string'],
                    // action_url
                    'action_url' => ['name' => 'action_url', 'type' => 'xsd:string'],
                    // message
                    'message' => ['name' => 'error_message', 'type' => 'xsd:string'],
                ]
            );

            /** WSListSessionsDetailsByCategory */

            // Input params for WSListSessionsDetailsByCategory
            $server->wsdl->addComplexType(
                'listSessionsDetailsByCategory',
                'complexType',
                'struct',
                'all',
                '',
                [
                    // session_category.id
                    'id' => ['name' => 'id', 'type' => 'xsd:string'],
                    // session_category.access_url_id
                    'access_url_id' => ['name' => 'access_url_id', 'type' => 'xsd:int'],
                    // session_category.name
                    'category_name' => ['name' => 'category_name', 'type' => 'xsd:string'],
                    // secret key
                    'secret_key' => ['name' => 'secret_key', 'type' => 'xsd:string'],
                ],
                [],
                'tns:listSessionsDetailsByCategory'
            );

            // Output params for sessionDetailsCourseList WSListSessionsDetailsByCategory
            $server->wsdl->addComplexType(
                'sessionDetailsCourse',
                'complexType',
                'struct',
                'all',
                '',
                [
                    'course_id' => ['name' => 'course_id', 'type' => 'xsd:int'], // course.id
                    'course_code' => ['name' => 'course_code', 'type' => 'xsd:string'], // course.code
                    'course_title' => ['name' => 'course_title', 'type' => 'xsd:string'], // course.title
                    'coach_username' => ['name' => 'coach_username', 'type' => 'xsd:string'], // user.username
                    'coach_firstname' => ['name' => 'coach_firstname', 'type' => 'xsd:string'], // user.firstname
                    'coach_lastname' => ['name' => 'coach_lastname', 'type' => 'xsd:string'], // user.lastname
                ]
            );

            // Output array for sessionDetails WSListSessionsDetailsByCategory
            $server->wsdl->addComplexType(
                'sessionDetailsCourseList',
                'complexType',
                'array',
                '',
                'SOAP-ENC:Array',
                [],
                [
                    [
                        'ref' => 'SOAP-ENC:arrayType',
                        'wsdl:arrayType' => 'tns:sessionDetailsCourse[]',
                    ],
                ],
                'tns:sessionDetailsCourse'
            );

            // Output params for sessionDetailsList WSListSessionsDetailsByCategory
            $server->wsdl->addComplexType(
                'sessionDetails',
                'complexType',
                'struct',
                'all',
                '',
                [
                    // session.id
                    'id' => [
                        'name' => 'id',
                        'type' => 'xsd:int',
                    ],
                    // session.id_coach
                    'coach_id' => [
                        'name' => 'coach_id',
                        'type' => 'xsd:int',
                    ],
                    // session.name
                    'name' => [
                        'name' => 'name',
                        'type' => 'xsd:string',
                    ],
                    // session.nbr_courses
                    'courses_num' => [
                        'name' => 'courses_num',
                        'type' => 'xsd:int',
                    ],
                    // session.nbr_users
                    'users_num' => [
                        'name' => 'users_num',
                        'type' => 'xsd:int',
                    ],
                    // session.nbr_classes
                    'classes_num' => [
                        'name' => 'classes_num',
                        'type' => 'xsd:int',
                    ],
                    // session.date_start
                    'date_start' => [
                        'name' => 'date_start',
                        'type' => 'xsd:string',
                    ],
                    // session.date_end
                    'date_end' => [
                        'name' => 'date_end',
                        'type' => 'xsd:string',
                    ],
                    // session.nb_days_access_before_beginning
                    'access_days_before_num' => [
                        'name' => 'access_days_before_num',
                        'type' => 'xsd:int',
                    ],
                    // session.nb_days_access_after_end
                    'access_days_after_num' => [
                        'name' => 'access_days_after_num',
                        'type' => 'xsd:int',
                    ],
                    // session.session_admin_id
                    'session_admin_id' => [
                        'name' => 'session_admin_id',
                        'type' => 'xsd:int',
                    ],
                    // session.visibility
                    'visibility' => [
                        'name' => 'visibility',
                        'type' => 'xsd:int',
                    ],
                    // session.session_category_id
                    'session_category_id' => [
                        'name' => 'session_category_id',
                        'type' => 'xsd:int',
                    ],
                    // session.promotion_id
                    'promotion_id' => [
                        'name' => 'promotion_id',
                        'type' => 'xsd:int',
                    ],
                    // session.number of registered users validated
                    'validated_user_num' => [
                        'name' => 'validated_user_num',
                        'type' => 'xsd:int',
                    ],
                    // session.number of registered users from waiting queue
                    'waiting_user_num' => [
                        'name' => 'waiting_user_num',
                        'type' => 'xsd:int',
                    ],
                    // extra fields
                    // Array(field_name, field_value)
                    'extra' => [
                        'name' => 'extra',
                        'type' => 'tns:extrasList',
                    ],
                    // course and coaches data
                    // Array(course_id, course_code, course_title, coach_username, coach_firstname, coach_lastname)
                    'course' => [
                        'name' => 'courses',
                        'type' => 'tns:sessionDetailsCourseList',
                    ],
                ]
            );

            // Output params for WSListSessionsDetailsByCategory
            $server->wsdl->addComplexType(
                'sessionDetailsList',
                'complexType',
                'array',
                '',
                'SOAP-ENC:Array',
                [],
                [
                    [
                        'ref' => 'SOAP-ENC:arrayType',
                        'wsdl:arrayType' => 'tns:sessionDetails[]',
                    ],
                ],
                'tns:sessionDetails'
            );

            // Register the method for WSSessionListInCategory
            $server->register(
                'HookAdvancedSubscription..WSSessionListInCategory', // method name
                ['sessionCategoryInput' => 'tns:sessionCategoryInput'], // input parameters
                ['return' => 'tns:sessionBriefList'], // output parameters
                'urn:WSRegistration', // namespace
                'urn:WSRegistration#WSSessionListInCategory', // soapaction
                'rpc', // style
                'encoded', // use
                'This service checks if user assigned to course' // documentation
            );

            // Register the method for WSSessionGetDetailsByUser
            $server->register(
                'HookAdvancedSubscription..WSSessionGetDetailsByUser', // method name
                ['advsubSessionDetailInput' => 'tns:advsubSessionDetailInput'], // input parameters
                ['return' => 'tns:advsubSessionDetail'], // output parameters
                'urn:WSRegistration', // namespace
                'urn:WSRegistration#WSSessionGetDetailsByUser', // soapaction
                'rpc', // style
                'encoded', // use
                'This service return session details to specific user' // documentation
            );

            // Register the method for WSListSessionsDetailsByCategory
            $server->register(
                'HookAdvancedSubscription..WSListSessionsDetailsByCategory', // method name
                ['name' => 'tns:listSessionsDetailsByCategory'], // input parameters
                ['return' => 'tns:sessionDetailsList'], // output parameters
                'urn:WSRegistration', // namespace
                'urn:WSRegistration#WSListSessionsDetailsByCategory', // soapaction
                'rpc', // style
                'encoded', // use
                'This service returns a list of detailed sessions by a category' // documentation
            );

            return $data;
        } // Else: Nothing to do

        return false;
    }

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
        $sessionList = SessionManager::getShortSessionListAndExtraByCategory(
            $sessionCategoryId,
            $params['target'],
            $fields,
            $datePub
        );

        return $sessionList;
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
                        $data['action_url'] = self::$plugin->getOpenSessionUrl($userId, $params);
                        if (SessionManager::isUserSubscribedAsStudent($sessionId, $userId)) {
                            $data['status'] = 10;
                        }
                    } else {
                        if (!$isUserInTargetGroup) {
                            $data['status'] = -2;
                        } else {
                            try {
                                $isAllowed = self::$plugin->isAllowedToDoRequest($userId, $params);
                                $data['message'] = self::$plugin->getStatusMessage($status, $isAllowed);
                            } catch (\Exception $e) {
                                $data['message'] = $e->getMessage();
                            }
                            $params['action'] = 'subscribe';
                            $params['sessionId'] = intval($sessionId);
                            $params['currentUserId'] = 0; // No needed
                            $params['studentUserId'] = intval($userId);
                            $params['queueId'] = 0; // No needed
                            $params['newStatus'] = ADVANCED_SUBSCRIPTION_QUEUE_STATUS_START;
                            if ($vacancy > 0) {
                                // Check conditions
                                if ($status == ADVANCED_SUBSCRIPTION_QUEUE_STATUS_NO_QUEUE) {
                                    // No in Queue, require queue subscription url action
                                    $data['action_url'] = self::$plugin->getTermsUrl($params);
                                } elseif ($status == ADVANCED_SUBSCRIPTION_QUEUE_STATUS_ADMIN_APPROVED) {
                                    // send url action
                                    $data['action_url'] = self::$plugin->getSessionUrl($sessionId);
                                } // Else: In queue, output status message, no more info.
                            } else {
                                if ($status == ADVANCED_SUBSCRIPTION_QUEUE_STATUS_ADMIN_APPROVED) {
                                    $data['action_url'] = self::$plugin->getSessionUrl($sessionId);
                                } elseif ($status == ADVANCED_SUBSCRIPTION_QUEUE_STATUS_NO_QUEUE) {
                                    // in Queue or not, cannot be subscribed to session
                                    $data['action_url'] = self::$plugin->getTermsUrl($params);
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
        AdvancedSubscriptionPlugin::create();
        foreach ($sessionList as &$session) {
            // Add validated and queue users count
            $session['validated_user_num'] = self::$plugin->countQueueByParams(
                [
                    'sessions' => [$session['id']],
                    'status' => [ADVANCED_SUBSCRIPTION_QUEUE_STATUS_ADMIN_APPROVED],
                ]
            );
            $session['waiting_user_num'] = self::$plugin->countQueueByParams(
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

    /**
     * Return notification content when the hook has been triggered.
     *
     * @return mixed (int or false)
     */
    public function hookNotificationContent(HookNotificationContentEventInterface $hook)
    {
        $data = $hook->getEventData();
        if ($data['type'] === HOOK_EVENT_TYPE_PRE) {
            $data['advanced_subscription_pre_content'] = $data['content'];

            return $data;
        } elseif ($data['type'] === HOOK_EVENT_TYPE_POST) {
            if (isset($data['content']) &&
                !empty($data['content']) &&
                isset($data['advanced_subscription_pre_content']) &&
                !empty($data['advanced_subscription_pre_content'])
            ) {
                $data['content'] = str_replace(
                    [
                        '<br /><hr>',
                        '<br />',
                        '<br/>',
                    ],
                    '',
                    $data['advanced_subscription_pre_content']
                );
            }

            return $data;
        } //Else hook type is not valid, nothing to do

        return false;
    }

    /**
     * Return the notification data title if the hook was triggered.
     *
     * @return array|bool
     */
    public function hookNotificationTitle(HookNotificationTitleEventInterface $hook)
    {
        $data = $hook->getEventData();
        if ($data['type'] === HOOK_EVENT_TYPE_PRE) {
            $data['advanced_subscription_pre_title'] = $data['title'];

            return $data;
        } elseif ($data['type'] === HOOK_EVENT_TYPE_POST) {
            if (isset($data['advanced_subscription_pre_title']) &&
                !empty($data['advanced_subscription_pre_title'])
            ) {
                $data['title'] = $data['advanced_subscription_pre_title'];
            }

            return $data;
        } // Else: hook type is not valid, nothing to do

        return false;
    }
}
