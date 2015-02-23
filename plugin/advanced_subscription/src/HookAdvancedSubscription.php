<?php
/* For licensing terms, see /license.txt */
/**
 * Hook Observer for Advanced subscription plugin
 * @author Daniel Alejandro Barreto Alva <daniel.barreto@beeznest.com>
 * @package chamilo.plugin.advanced_subscription
 */

require_once __DIR__ . '/../config.php';

/**
 * Class HookAdvancedSubscription extends the HookObserver to implements
 * specific behaviour when the AdvancedSubscription plugin is enabled
 */
class HookAdvancedSubscription extends HookObserver implements
    HookAdminBlockObserverInterface,
    HookWSRegistrationObserverInterface,
    HookNotificationContentObserverInterface
{
    public $plugin;

    /**
     * Constructor. Calls parent, mainly.
     */
    protected function __construct()
    {
        $this->plugin = AdvancedSubscriptionPlugin::create();
        parent::__construct(
            'plugin/advanced_subscription/src/HookAdvancedSubscription.class.php',
            'advanced_subscription'
        );
    }

    /**
     * @param HookAdminBlockEventInterface $hook
     * @return int
     */
    public function hookAdminBlock(HookAdminBlockEventInterface $hook)
    {
        $data = $hook->getEventData();
        if ($data['type'] === HOOK_EVENT_TYPE_PRE) {
            // Nothing to do
        } elseif ($data['type'] === HOOK_EVENT_TYPE_POST) {

            if (isset($data['blocks'])) {
                $data['blocks']['sessions']['items'][] = array(
                    'url' => '../../plugin/advanced_subscription/src/admin_view.php',
                    'label' => get_plugin_lang('plugin_title', 'AdvancedSubscriptionPlugin'),
                );
            }
        } else {
            // Hook type is not valid
            // Nothing to do
        }

        return $data;
    }

    /**
     * Add Webservices to registration.soap.php
     * @param HookWSRegistrationEventInterface $hook
     * @return mixed (int or false)
     */
    public function hookWSRegistration(HookWSRegistrationEventInterface $hook)
    {
        $data = $hook->getEventData();
        if ($data['type'] === HOOK_EVENT_TYPE_PRE) {

        } elseif ($data['type'] === HOOK_EVENT_TYPE_POST) {
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
                array(
                    // session.id
                    'id' => array('name' => 'id', 'type' => 'xsd:int'),
                    // session.name
                    'name' => array('name' => 'name', 'type' => 'xsd:string'),
                    // session.short_description
                    'short_description' => array('name' => 'short_description', 'type' => 'xsd:string'),
                    // session.mode
                    'mode' => array('name' => 'mode', 'type' => 'xsd:string'),
                    // session.date_start
                    'date_start' => array('name' => 'date_start', 'type' => 'xsd:string'),
                    // session.date_end
                    'date_end' => array('name' => 'date_end', 'type' => 'xsd:string'),
                    // session.human_text_duration
                    'duration' => array('name' => 'duration', 'type' => 'xsd:string'),
                    // session.vacancies
                    'vacancies' => array('name' => 'vacancies', 'type' => 'xsd:string'),
                    // session.schedule
                    'schedule' => array('name' => 'schedule', 'type' => 'xsd:string'),
                )
            );

            //Output params for WSSessionListInCategory
            $server->wsdl->addComplexType(
                'sessionBriefList',
                'complexType',
                'array',
                '',
                'SOAP-ENC:Array',
                array(),
                array(
                    array('ref'=>'SOAP-ENC:arrayType',
                        'wsdl:arrayType'=>'tns:sessionBrief[]')
                ),
                'tns:sessionBrief'
            );

            // Input params for WSSessionListInCategory
            $server->wsdl->addComplexType(
                'sessionCategoryInput',
                'complexType',
                'struct',
                'all',
                '',
                array(
                    'id' => array('name' => 'id', 'type' => 'xsd:string'), // session_category.id
                    'name' => array('name' => 'name', 'type' => 'xsd:string'), // session_category.name
                    'target' => array('name' => 'target', 'type' => 'xsd:string'), // session.target
                    'secret_key'   => array('name' => 'secret_key', 'type' => 'xsd:string')
                )
            );

            // Input params for WSSessionGetDetailsByUser
            $server->wsdl->addComplexType(
                'advsubSessionDetailInput',
                'complexType',
                'struct',
                'all',
                '',
                array(
                    // user.user_id
                    'user_id' => array('name' => 'user_id', 'type' => 'xsd:int'),
                    // session.id
                    'session_id' => array('name' => 'session_id', 'type' => 'xsd:int'),
                    // user.profile_completes
                    'profile_completed' => array('name' => 'profile_completed', 'type' => 'xsd:float'),
                    // user.is_connected
                    'is_connected' => array('name' => 'is_connected', 'type' => 'xsd:boolean'),
                    'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string'),
                )
            );

            // Output params for WSSessionGetDetailsByUser
            $server->wsdl->addComplexType(
                'advsubSessionDetail',
                'complexType',
                'struct',
                'all',
                '',
                array(
                    // session.id
                    'id' => array('name' => 'id', 'type' => 'xsd:string'),
                    // session.cost
                    'cost' => array('name' => 'cost', 'type' => 'xsd:float'),
                    // session.place
                    'place' => array('name' => 'place', 'type' => 'xsd:string'),
                    // session.allow_visitors
                    'allow_visitors' => array('name' => 'allow_visitors', 'type' => 'xsd:string'),
                    // session.duration
                    'duration' => array('name' => 'duration', 'type' => 'xsd:int'),
                    // session.brochure
                    'brochure' => array('name' => 'brochure', 'type' => 'xsd:string'),
                    // session.banner
                    'banner' => array('name' => 'banner', 'type' => 'xsd:string'),
                    // session.description
                    'description' => array('name' => 'description', 'type' => 'xsd:string'),
                    // status
                    'status' => array('name' => 'status', 'type' => 'xsd:string'),
                    // action_url
                    'action_url' => array('name' => 'action_url', 'type' => 'xsd:string'),
                    // message
                    'message' => array('name' => 'error_message', 'type' => 'xsd:string'),
                )
            );

            /** WSListSessionsDetailsByCategory **/

            // Input params for WSListSessionsDetailsByCategory
            $server->wsdl->addComplexType(
                'listSessionsDetailsByCategory',
                'complexType',
                'struct',
                'all',
                '',
                array(
                    // session_category.id
                    'id' => array('name' => 'id', 'type' => 'xsd:string'),
                    // session_category.access_url_id
                    'access_url_id' => array('name' => 'access_url_id', 'type' => 'xsd:int'),
                    // session_category.name
                    'category_name' => array('name' => 'category_name', 'type' => 'xsd:string'),
                    // secret key
                    'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string'),
                ),
                array(),
                'tns:listSessionsDetailsByCategory'
            );

            // Output params for sessionDetailsCourseList WSListSessionsDetailsByCategory
            $server->wsdl->addComplexType(
                'sessionDetailsCourse',
                'complexType',
                'struct',
                'all',
                '',
                array(
                    'course_id' => array('name' => 'course_id', 'type' => 'xsd:int'), // course.id
                    'course_code' => array('name' => 'course_code', 'type' => 'xsd:string'), // course.code
                    'course_title' => array('name' => 'course_title', 'type' => 'xsd:string'), // course.title
                    'coach_username' => array('name' => 'coach_username', 'type' => 'xsd:string'), // user.username
                    'coach_firstname' => array('name' => 'coach_firstname', 'type' => 'xsd:string'), // user.firstname
                    'coach_lastname' => array('name' => 'coach_lastname', 'type' => 'xsd:string'), // user.lastname
                )
            );


            // Output array for sessionDetails WSListSessionsDetailsByCategory
            $server->wsdl->addComplexType(
                'sessionDetailsCourseList',
                'complexType',
                'array',
                '',
                'SOAP-ENC:Array',
                array(),
                array(
                    array(
                        'ref' => 'SOAP-ENC:arrayType',
                        'wsdl:arrayType' => 'tns:sessionDetailsCourse[]',
                    )
                ),
                'tns:sessionDetailsCourse'
            );

            // Output params for sessionDetailsList WSListSessionsDetailsByCategory
            $server->wsdl->addComplexType(
                'sessionDetails',
                'complexType',
                'struct',
                'all',
                '',
                array(
                    // session.id
                    'id' => array(
                        'name' => 'id',
                        'type' => 'xsd:int'
                    ),
                    // session.id_coach
                    'coach_id' => array(
                        'name' => 'coach_id',
                        'type' => 'xsd:int'
                    ),
                    // session.name
                    'name' => array(
                        'name' => 'name',
                        'type' => 'xsd:string'
                    ),
                    // session.nbr_courses
                    'courses_num' => array(
                        'name' => 'courses_num',
                        'type' => 'xsd:int'
                    ),
                    // session.nbr_users
                    'users_num' => array(
                        'name' => 'users_num',
                        'type' => 'xsd:int'
                    ),
                    // session.nbr_classes
                    'classes_num' => array(
                        'name' => 'classes_num',
                        'type' => 'xsd:int'
                    ),
                    // session.date_start
                    'date_start' => array(
                        'name' => 'date_start',
                        'type' => 'xsd:string'
                    ),
                    // session.date_end
                    'date_end' => array(
                        'name' => 'date_end',
                        'type' => 'xsd:string'
                    ),
                    // session.nb_days_access_before_beginning
                    'access_days_before_num' => array(
                        'name' => 'access_days_before_num',
                        'type' => 'xsd:int'
                    ),
                    // session.nb_days_access_after_end
                    'access_days_after_num' => array(
                        'name' => 'access_days_after_num',
                        'type' => 'xsd:int'
                    ),
                    // session.session_admin_id
                    'session_admin_id' => array(
                        'name' => 'session_admin_id',
                        'type' => 'xsd:int'
                    ),
                    // session.visibility
                    'visibility' => array(
                        'name' => 'visibility',
                        'type' => 'xsd:int'
                    ),
                    // session.session_category_id
                    'session_category_id' => array(
                        'name' => 'session_category_id',
                        'type' => 'xsd:int'
                    ),
                    // session.promotion_id
                    'promotion_id' => array(
                        'name' => 'promotion_id',
                        'type' => 'xsd:int'
                    ),
                    // session.number of registered users validated
                    'validated_user_num' => array(
                        'name' => 'validated_user_num',
                        'type' => 'xsd:int'
                    ),
                    // session.number of registered users from waiting queue
                    'waiting_user_num' => array(
                        'name' => 'waiting_user_num',
                        'type' => 'xsd:int'
                    ),
                    // extra fields
                    // Array(field_name, field_value)
                    'extra' => array(
                        'name' => 'extra',
                        'type' => 'tns:extrasList'
                    ),
                    // course and coaches data
                    // Array(course_id, course_code, course_title, coach_username, coach_firstname, coach_lastname)
                    'course' => array(
                        'name' => 'courses',
                        'type' => 'tns:sessionDetailsCourseList'
                    ),
                )
            );

            // Output params for WSListSessionsDetailsByCategory
            $server->wsdl->addComplexType(
                'sessionDetailsList',
                'complexType',
                'array',
                '',
                'SOAP-ENC:Array',
                array(),
                array(
                    array(
                        'ref' => 'SOAP-ENC:arrayType',
                        'wsdl:arrayType' => 'tns:sessionDetails[]',
                    )
                ),
                'tns:sessionDetails'
            );

            // Register the method for WSSessionListInCategory
            $server->register(
                'HookAdvancedSubscription..WSSessionListInCategory', // method name
                array('sessionCategoryInput' => 'tns:sessionCategoryInput'), // input parameters
                array('return' => 'tns:sessionBriefList'), // output parameters
                'urn:WSRegistration', // namespace
                'urn:WSRegistration#WSSessionListInCategory', // soapaction
                'rpc', // style
                'encoded', // use
                'This service checks if user assigned to course' // documentation
            );

            // Register the method for WSSessionGetDetailsByUser
            $server->register(
                'HookAdvancedSubscription..WSSessionGetDetailsByUser', // method name
                array('advsubSessionDetailInput' => 'tns:advsubSessionDetailInput'), // input parameters
                array('return' => 'tns:advsubSessionDetail'), // output parameters
                'urn:WSRegistration', // namespace
                'urn:WSRegistration#WSSessionGetDetailsByUser', // soapaction
                'rpc', // style
                'encoded', // use
                'This service return session details to specific user' // documentation
            );

            // Register the method for WSListSessionsDetailsByCategory
            $server->register(
                'HookAdvancedSubscription..WSListSessionsDetailsByCategory', // method name
                array('name' => 'tns:listSessionsDetailsByCategory'), // input parameters
                array('return' => 'tns:sessionDetailsList'), // output parameters
                'urn:WSRegistration', // namespace
                'urn:WSRegistration#WSListSessionsDetailsByCategory', // soapaction
                'rpc', // style
                'encoded', // use
                'This service returns a list of detailed sessions by a category' // documentation
            );

            return $data;
        } else {
            // Nothing to do
        }
        return false;
    }

    /**
     * @param $params
     * @return null|soap_fault
     */
    public static function WSSessionListInCategory($params)
    {
        global $debug;

        if ($debug) {
            error_log('WSUserSubscribedInCourse');
            error_log('Params ' . print_r($params, 1));
        }
        if (!WSHelperVerifyKey($params)) {

           //return return_error(WS_ERROR_SECRET_KEY);
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
        $fields = array(
            'short_description',
            'mode',
            'human_text_duration',
            'vacancies',
            'brochure',
            'target',
            'schedule'
        );
        $sessionList = SessionManager::getShortSessionListAndExtraByCategory(
            $sessionCategoryId,
            $params['target'],
            $fields
        );

        return $sessionList;
    }

    /**
     * @param $params
     * @return null|soap_fault
     */
    public function WSSessionGetDetailsByUser($params)
    {
        global $debug;

        if ($debug) {
            error_log('WSUserSubscribedInCourse');
            error_log('Params ' . print_r($params, 1));
        }
        if (!WSHelperVerifyKey($params)) {

            return return_error(WS_ERROR_SECRET_KEY);
        }
        $result = return_error(WS_ERROR_NOT_FOUND_RESULT);
        // Check params
        if (is_array($params) && !empty($params['session_id']) && !empty($params['user_id'])) {
            $userId = (int) $params['user_id'];
            $sessionId = (int) $params['session_id'];
            // Check if student is already subscribed

            $isOpen = $this->plugin->isSessionOpen($sessionId);
            $status = $this->plugin->getQueueStatus($userId, $sessionId);
            $vacancy = $this->plugin->getVacancy($sessionId);
            $data = $this->plugin->getSessionDetails($sessionId);
            if (!empty($data) && is_array($data)) {
                $data['status'] = $status;
                // 5 Cases:
                if ($isOpen) {
                    // Go to Course session
                    $data['action_url'] = $this->plugin->getSessionUrl($sessionId);
                } else {
                    try {
                        $isAble = $this->plugin->isAllowedToDoRequest($userId, $params);
                        $data['message'] = $this->plugin->getStatusMessage($status, $isAble);
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
                        if ($status === ADVANCED_SUBSCRIPTION_QUEUE_STATUS_NO_QUEUE) {
                            // No in Queue, require queue subscription url action
                            $data['action_url'] = $this->plugin->getQueueUrl($params);
                        } elseif ($status === ADVANCED_SUBSCRIPTION_QUEUE_STATUS_ADMIN_APPROVED) {
                            // send url action
                            $data['action_url'] = $this->plugin->getSessionUrl($sessionId);
                        } else {
                            // In queue, output status message, no more info.
                        }
                    } else {
                        if ($status === ADVANCED_SUBSCRIPTION_QUEUE_STATUS_ADMIN_APPROVED) {
                            $data['action_url'] = $this->plugin->getSessionUrl($sessionId);
                        } else {
                            // in Queue or not, cannot be subscribed to session
                            $data['action_url'] = $this->plugin->getQueueUrl($params);
                        }
                    }
                }
                $result = $data;
            }
        } else {
            // Return soap fault Not valid input params
            $result = return_error(WS_ERROR_INVALID_INPUT);
        }

        return $result;
    }

    /**
     * Get a list of sessions (id, coach_id, name, courses_num, users_num, classes_num,
     * date_start, date_end, access_days_before_num, session_admin_id, visibility,
     * session_category_id, promotion_id,
     * validated_user_num, waiting_user_num,
     * extra, course) the validated_usernum and waiting_user_num are
     * used when have the plugin for advance incsription enables.
     * The extra data (field_name, field_value)
     * The course data (course_id, course_code, course_title,
     * coach_username, coach_firstname, coach_lastname)
     * @param array $params List of parameters (id, category_name, access_url_id, secret_key)
     * @return array|soap_fault Sessions list (id=>[title=>'title',url='http://...',date_start=>'...',date_end=>''])
     */
    public function WSListSessionsDetailsByCategory($params)
    {
        global $debug;

        if ($debug) {
            error_log('WSListSessionsDetailsByCategory');
            error_log('Params ' . print_r($params, 1));
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
        foreach ($sessionList as &$session) {
            // Add validated and queue users count
            $session['validated_user_num'] = $this->plugin->countQueueByParams(
                array(
                    'sessions' => array($session['id']),
                    'status' => array(ADVANCED_SUBSCRIPTION_QUEUE_STATUS_ADMIN_APPROVED)

                )
            );
            $session['waiting_user_num'] = $this->plugin->countQueueByParams(
                array(
                    'sessions' => array($session['id']),
                    'status' => array(
                        ADVANCED_SUBSCRIPTION_QUEUE_STATUS_START,
                        ADVANCED_SUBSCRIPTION_QUEUE_STATUS_BOSS_APPROVED,
                    ),
                )
            );
        }

        return $sessionList;
    }

    /**
     * Return notification content when the hook has been triggered
     * @param HookNotificationContentEventInterface $hook
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
                    array(
                        '<br /><hr>',
                        '<br />',
                        '<br/>',
                    ),
                    '',
                    $data['advanced_subscription_pre_content']
                );
            }

            return $data;
        } else {
            // Hook type is not valid
            // Nothing to do
        }
        return false;
    }

    /**
     * Return the notification data title if the hook was triggered
     * @param HookNotificationTitleEventInterface $hook
     * @return int
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
        } else {
            // Hook type is not valid
            // Nothing to do
        }
        return false;
    }
}