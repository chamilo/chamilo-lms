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
                    'description' => array('name' => 'description', 'type' => 'xsd:string'), //Chamilo user_id
                    'modality' => array('name' => 'start_date', 'type' => 'xsd:string'),
                    'date_start' => array('name' => 'start_date', 'type' => 'xsd:string'),
                    'date_end' => array('name' => 'end_date', 'type' => 'xsd:string'),
                    'duration' => array('name' => 'date_end', 'type' => 'xsd:string'),
                    'quota' => array('name' => 'quota', 'type' => 'xsd:string'),
                    'schedule' => array('name' => 'schedule', 'type' => 'xsd:string'),
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
                    'cost' => array('name' => 'cost', 'type' => 'xsd:string'),
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

            // Register the method to expose
            $server->register('HookAdvancedSubscription..WSSessionListInCategory', // method name
                array('sessionCategoryInput' => 'tns:sessionCategoryInput'), // input parameters
                array('return' => 'tns:sessionBriefList'), // output parameters
                'urn:WSRegistration', // namespace
                'urn:WSRegistration#WSSessionListInCategory', // soapaction
                'rpc', // style
                'encoded', // use
                'This service checks if user assigned to course' // documentation
            );

            $server->register('HookAdvancedSubscription..WSAdvsubEncrypt', // method name
                array('sessionCategoryInput' => 'xsd:string'), // input parameters
                array('return' => 'xsd:string'), // output parameters
                'urn:WSRegistration', // namespace
                'urn:WSRegistration#WSAdvsubEncrypt', // soapaction
                'rpc', // style
                'encoded', // use
                'This service encrypt data to be used later in urls' // documentation
            );

            $server->register('HookAdvancedSubscription..WSSessionGetDetailsByUser', // method name
                array('input' => 'xsd:string'), // input parameters
                array('return' => 'tns:advsubSessionDetail'), // output parameters
                'urn:WSRegistration', // namespace
                'urn:WSRegistration#WSSessionGetDetailsByUser', // soapaction
                'rpc', // style
                'encoded', // use
                'This service encrypt data to be used later in urls' // documentation
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

        // Get the session brief List by category

        $sessionList = SessionManager::getSessionBriefListByCategory($sessionCategoryId, $params['target']);

        /*
        $extraFieldSession = new ExtraFieldValue('session');
        $hasExtraField = $extraFieldSession->get_values_by_handler_and_field_variable(key($sessionList), 'publico_objetivo');
        var_dump($hasExtraField);
        if ($hasExtraField != false) {
            // Has session extra fields, Nothing to do
        } else {
            // No session extra fields

            return return_error(WS_ERROR_NOT_FOUND_RESULT);
        }
        */

        return $sessionList;
    }

    /**
     * @param $data
     * @return null|soap_fault|string
     */
    public static function WSAdvsubEncrypt($data)
    {
        global $debug;

        if ($debug) error_log('WSUserSubscribedInCourse');
        if ($debug) error_log('Params '. print_r($data, 1));
        if (!WSHelperVerifyKey($data)) {

            //return return_error(WS_ERROR_SECRET_KEY);
        }
        // Check if data is a string
        if (is_string($data)) {
            $enc = AdvancedSubscriptionPlugin::create()->encrypt($data);
            if (is_string($enc) && strlen($enc) > 16) {
                $result = $enc;
            } else {
                $result = return_error(WS_ERROR_INVALID_INPUT);
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
                        } elseif ($status === ADV_SUB_QUEUE_STATUS_ADMIN_APPROVED) {
                            // send url action
                            $data['action_url'] = $advsubPlugin->getSessionUrl($sessionId);
                        } else {
                            // In queue, output status message, no more info.
                        }
                    } else {
                        if ($status === ADV_SUB_QUEUE_STATUS_ADMIN_APPROVED) {

                        } else {
                            // in Queue or not, cannot be subscribed to session
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

}