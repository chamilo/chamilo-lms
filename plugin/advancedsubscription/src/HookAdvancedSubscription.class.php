<?php
/**
 * Created by PhpStorm.
 * User: dbarreto
 * Date: 19/12/14
 * Time: 09:56 AM
 */

require_once __DIR__ . '/../config.php';

class HookAdvancedSubscription extends HookObserver implements
    HookAdminBlockObserverInterface,
    HookWSRegistrationObserverInterface
{

    protected function __construct()
    {
        parent::__construct(
            'plugin/advancedsubscription/src/HookAdvancedSubscription.class.php',
            'advancedsubscription'
        );
    }

    /**
     * @param HookAdminBlockEventInterface $hook
     * @return int
     */
    public function hookAdminBlock(HookAdminBlockEventInterface $hook)
    {
        $data = $hook->getEventData();
        if ($data['type'] === HOOK_TYPE_PRE) {
            // Nothing to do
        } elseif ($data['type'] === HOOK_TYPE_POST) {
            if (isset($data['blocks'])) {
                $data['blocks']['sessions']['items'][] = array(
                    'url' => 'configure_plugin.php?name=advancedsubscription',
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
     * @param HookWSRegistrationEventInterface $hook
     * @return int
     */
    public function hookWSRegistration(HookWSRegistrationEventInterface $hook)
    {
        $data = $hook->getEventData();
        if ($data['type'] === HOOK_TYPE_PRE) {

        } elseif ($data['type'] === HOOK_TYPE_POST) {
           /** @var \nusoap_server $server */
            $server = &$data['server'];

            /** WSSessionListInCategory */

            // Output params for WSSessionListInCategory
            $server->wsdl->addComplexType(
                'sessionBrief',
                'complexType',
                'struct',
                'all',
                '',
                array(
                    'name' => array('name' => 'name', 'type' => 'xsd:string'), //Course string code
                    'as_description' => array('name' => 'description', 'type' => 'xsd:string'), //Chamilo user_id
                    'modalidad' => array('name' => 'start_date', 'type' => 'xsd:string'),
                    'date_start' => array('name' => 'start_date', 'type' => 'xsd:string'),
                    'date_end' => array('name' => 'end_date', 'type' => 'xsd:string'),
                    'duracion' => array('name' => 'date_end', 'type' => 'xsd:string'),
                    'vacantes' => array('name' => 'quota', 'type' => 'xsd:string'),
                    'horario' => array('name' => 'schedule', 'type' => 'xsd:string'),
                )
            );

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

            // Input params for editing users
            $server->wsdl->addComplexType(
                'sessionCategoryInput',
                'complexType',
                'struct',
                'all',
                '',
                array(
                    'id' => array('name' => 'id', 'type' => 'xsd:string'), // Course string code
                    'name' => array('name' => 'name', 'type' => 'xsd:string'), // Chamilo user_id
                    'target' => array('name' => 'target', 'type' => 'xsd:string'), // Publico objetivo
                    'secret_key'   => array('name' => 'secret_key', 'type' => 'xsd:string')
                )
            );

            // Input params for get session Details
            $server->wsdl->addComplexType(
                'advsubSessionDetailInput',
                'complexType',
                'struct',
                'all',
                '',
                array(
                    'user_id' => array('name' => 'id', 'type' => 'xsd:int'), // Chamilo user_id
                    'session_id' => array('name' => 'name', 'type' => 'xsd:int'), // Chamilo session_id
                    'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string'),
                    'profile_completed' => array('name' => 'profile_completed', 'type' => 'xsd:float'),
                    'is_connected' => array('name' => 'secret_key', 'type' => 'xsd:boolean'),
                )
            );

            // Output params for get session Details
            $server->wsdl->addComplexType(
                'advsubSessionDetail',
                'complexType',
                'struct',
                'all',
                '',
                array(
                    'id' => array('name' => 'id', 'type' => 'xsd:string'),
                    'cost' => array('name' => 'cost', 'type' => 'xsd:float'),
                    'place' => array('name' => 'place', 'type' => 'xsd:string'),
                    'visitors' => array('name' => 'visitors', 'type' => 'xsd:string'),
                    'duration' => array('name' => 'duration', 'type' => 'xsd:int'),
                    'brochure' => array('name' => 'brochure', 'type' => 'xsd:string'),
                    'banner' => array('name' => 'banner', 'type' => 'xsd:string'),
                    'description_full' => array('name' => 'description_full', 'type' => 'xsd:string'),
                    'status' => array('name' => 'status', 'type' => 'xsd:string'),
                    'action_url' => array('name' => 'action_url', 'type' => 'xsd:string'),
                    'message' => array('name' => 'error_message', 'type' => 'xsd:string'),
                )
            );

            /** WSListSessionsDetailsByCategory **/

            $server->wsdl->addComplexType(
                'listSessionsDetailsByCategory',
                'complexType',
                'struct',
                'all',
                '',
                array(
                    'id' => array('name' => 'id', 'type' => 'xsd:string'), // session_category.id
                    'category_name' => array('name' => 'category_name', 'type' => 'xsd:string'), // session_category.name
                    'access_url_id' => array('name' => 'access_url_id', 'type' => 'xsd:int'), // session_category.access_url_id
                    'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string') // secret key
                ),
                array(),
                'tns:listSessionsDetailsByCategory'
            );

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

            // Register the method to expose
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

            $server->register(
                'HookAdvancedSubscription..WSAdvsubEncrypt', // method name
                array('data' => 'xsd:string'), // input parameters
                array('return' => 'xsd:string'), // output parameters
                'urn:WSRegistration', // namespace
                'urn:WSRegistration#WSAdvsubEncrypt', // soapaction
                'rpc', // style
                'encoded', // use
                'This service encrypt data to be used later in urls' // documentation
            );

            $server->register(
                'HookAdvancedSubscription..WSSessionGetDetailsByUser', // method name
                array('advsubSessionDetailInput' => 'tns:advsubSessionDetailInput'), // input parameters
                array('return' => 'tns:advsubSessionDetail'), // output parameters
                'urn:WSRegistration', // namespace
                'urn:WSRegistration#WSSessionGetDetailsByUser', // soapaction
                'rpc', // style
                'encoded', // use
                'This service encrypt data to be used later in urls' // documentation
            );

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
    }

    /**
     * @param $params
     * @return null|soap_fault
     */
    public static function WSSessionListInCategory($params)
    {
        global $debug;

        if ($debug) error_log('WSUserSubscribedInCourse');
        if ($debug) error_log('Params '. print_r($params, 1));
        if (!WSHelperVerifyKey($params)) {

           //return return_error(WS_ERROR_SECRET_KEY);
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

        // Get the session brief List by category
        $sessionList = SessionManager::getSessionBriefListByCategory($sessionCategoryId, $params['target']);

        return $sessionList;
    }

    /**
     * @param array $data
     * @return null|soap_fault|string
     */
    public static function WSAdvsubEncrypt($data)
    {
        global $debug;

        if ($debug) error_log('WSUserSubscribedInCourse');
        if ($debug) error_log('Params '. print_r($data, 1));

        // Check if data is an array
        if (is_array($data)) {
            if (!WSHelperVerifyKey($data)) {

                //return return_error(WS_ERROR_SECRET_KEY);
            }
            $result = AdvancedSubscriptionPlugin::create()->encrypt($data);
        } elseif (is_string($data)) {
            $data = unserialize($data);
            if (!WSHelperVerifyKey($data)) {

                //return return_error(WS_ERROR_SECRET_KEY);
            }
            if (is_array($data)) {
                $result = AdvancedSubscriptionPlugin::create()->encrypt($data);
            }
        } else {
            // Return soap fault Not valid input params

            $result = return_error(WS_ERROR_INVALID_INPUT);
        }

        return $result;
    }

    /**
     * @param $params
     * @return null|soap_fault
     */
    public function WSSessionGetDetailsByUser($params)
    {
        global $debug;

        if ($debug) error_log('WSUserSubscribedInCourse');
        if ($debug) error_log('Params '. print_r($params, 1));
        if (!WSHelperVerifyKey($params)) {

            //return return_error(WS_ERROR_SECRET_KEY);
        }
        $result = return_error(WS_ERROR_NOT_FOUND_RESULT);
        // Check params
        if (is_array($params) && !empty($params['session_id']) && !empty($params['user_id'])) {
            $userId = (int) $params['user_id'];
            $sessionId = (int) $params['session_id'];
            // Check if student is already subscribed

            $advsubPlugin = AdvancedSubscriptionPlugin::create();
            $isOpen = $advsubPlugin->isSessionOpen($sessionId);
            $status = $advsubPlugin->getQueueStatus($userId, $sessionId);
            $vacancy = $advsubPlugin->getVacancy($sessionId);
            $data = $advsubPlugin->getSessionDetails($sessionId);
            if (!empty($data) && is_array($data)) {
                $data['status'] = $status;
                // 5 Cases:
                if ($isOpen) {
                    // Go to Course session
                    $data['action_url'] = $advsubPlugin->getSessionUrl($sessionId);
                } else {
                    try {
                        $isAble = $advsubPlugin->isAbleToRequest($userId, $params);
                        $data['message'] = $advsubPlugin->getStatusMessage($status, $isAble);
                    } catch (\Exception $e) {
                        $data['message'] = $e->getMessage();
                    }
                    if ($vacancy > 0) {
                        // Check conditions
                        if ($status === ADV_SUB_QUEUE_STATUS_NO_QUEUE) {
                            // No in Queue, require queue subscription url action
                            $data['action_url'] = $advsubPlugin->getQueueUrl($params);
                        } elseif ($status === ADV_SUB_QUEUE_STATUS_ADMIN_APPROVED) {
                            // send url action
                            $data['action_url'] = $advsubPlugin->getSessionUrl($sessionId);
                        } else {
                            // In queue, output status message, no more info.
                        }
                    } else {
                        if ($status === ADV_SUB_QUEUE_STATUS_ADMIN_APPROVED) {
                            $data['action_url'] = $advsubPlugin->getSessionUrl($sessionId);
                        } else {
                            // in Queue or not, cannot be subscribed to session
                            $data['action_url'] = $advsubPlugin->getQueueUrl($params);
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
     * @param array List of parameters (id, category_name, access_url_id, secret_key)
     * @return array|soap_fault Sessions list (id=>[title=>'title',url='http://...',date_start=>'...',date_end=>''])
     */
    function WSListSessionsDetailsByCategory($params)
    {
        global $debug;

        if ($debug) error_log('WSListSessionsDetailsByCategory');
        if ($debug) error_log('Params '. print_r($params, 1));
        $secretKey = $params['secret_key'];

        // Check if secret key is valid
        if(!WSHelperVerifyKey($secretKey)) {

            //return return_error(WS_ERROR_SECRET_KEY);
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
        //@TODO: Not implemented yet, see BT#9092
        // Check if advanced inscription plugin is enabled
        $isAdvancedInscriptionEnabled = false;
        if ($isAdvancedInscriptionEnabled) {
            // Get validated and waiting queue users count for each session
            foreach ($sessionList as &$session) {
                // Add validated and queue users count
                $session['validated_user_num'] = 0;
                $session['waiting_user_num'] = 0;
            }
        } else {
            // Set -1 to validated and waiting queue users count
        }

        return $sessionList;
    }

}