<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.webservices
 */
require_once __DIR__.'/../inc/global.inc.php';

api_protect_webservices();

$debug = true;

define('WS_ERROR_SECRET_KEY', 1);
define('WS_ERROR_NOT_FOUND_RESULT', 2);
define('WS_ERROR_INVALID_INPUT', 3);
define('WS_ERROR_SETTING', 4);

/**
 * @param int $code
 */
function return_error($code)
{
    $fault = null;
    switch ($code) {
        case WS_ERROR_SECRET_KEY:
            $fault = new soap_fault(
                'Server',
                '',
                'Secret key is not correct or params are not correctly set'
            );
            break;
        case WS_ERROR_NOT_FOUND_RESULT:
            $fault = new soap_fault(
                'Server',
                '',
                'No result was found for this query'
            );
            break;
        case WS_ERROR_INVALID_INPUT:
            $fault = new soap_fault(
                'Server',
                '',
                'The input variables are invalid o are not correctly set'
            );
            break;
        case WS_ERROR_SETTING:
            $fault = new soap_fault(
                'Server',
                '',
                'Please check the configuration for this webservice'
            );
            break;
    }

    return $fault;
}

/**
 * @param array $params
 *
 * @return bool
 */
function WSHelperVerifyKey($params)
{
    global $_configuration, $debug;
    if (is_array($params)) {
        $secret_key = $params['secret_key'];
    } else {
        $secret_key = $params;
    }
    //error_log(print_r($params,1));
    $check_ip = false;
    $ip_matches = false;
    $ip = trim($_SERVER['REMOTE_ADDR']);
    // if we are behind a reverse proxy, assume it will send the
    // HTTP_X_FORWARDED_FOR header and use this IP instead
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        list($ip1, $ip2) = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ip1);
    }
    if ($debug) {
        error_log("ip: $ip");
    }
    // Check if a file that limits access from webservices exists and contains
    // the restraining check
    if (is_file('webservice-auth-ip.conf.php')) {
        include 'webservice-auth-ip.conf.php';
        if ($debug) {
            error_log("webservice-auth-ip.conf.php file included");
        }
        if (!empty($ws_auth_ip)) {
            $check_ip = true;
            $ip_matches = api_check_ip_in_range($ip, $ws_auth_ip);
            if ($debug) {
                error_log("ip_matches: $ip_matches");
            }
        }
    }

    if ($debug) {
        error_log("checkip ".intval($check_ip));
    }

    if ($check_ip) {
        $security_key = $_configuration['security_key'];
    } else {
        $security_key = $ip.$_configuration['security_key'];
        //error_log($secret_key.'-'.$security_key);
    }

    $result = api_is_valid_secret_key($secret_key, $security_key);
    //error_log($secret_key.'-'.$security_key);
    if ($debug) {
        error_log('WSHelperVerifyKey result: '.intval($result));
    }

    return $result;
}

// Create the server instance
$server = new soap_server();

/** @var HookWSRegistration $hook */
$hook = HookWSRegistration::create();
if (!empty($hook)) {
    $hook->setEventData(['server' => $server]);
    $res = $hook->notifyWSRegistration(HOOK_EVENT_TYPE_PRE);
    if (!empty($res['server'])) {
        $server = $res['server'];
    }
}

$server->soap_defencoding = 'UTF-8';

// Initialize WSDL support
$server->configureWSDL('WSAccessUrl', 'urn:WSAccessUrl');

$server->wsdl->addComplexType(
    'portalItem',
    'complexType',
    'struct',
    'all',
    '',
    [
        'id' => ['name' => 'id', 'type' => 'xsd:string'],
        'url' => ['name' => 'url', 'type' => 'xsd:string'],
    ]
);

$server->wsdl->addComplexType(
    'portalList',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    [],
    [
        [
            'ref' => 'SOAP-ENC:arrayType',
            'wsdl:arrayType' => 'tns:portalItem[]',
        ],
    ],
    'tns:portalItem'
);

$server->wsdl->addComplexType(
    'getPortals',
    'complexType',
    'struct',
    'all',
    '',
    [
        'secret_key' => ['name' => 'secret_key', 'type' => 'xsd:string'],
    ]
);

// Register the method to expose
$server->register(
    'WSGetPortals', // method name
    ['getPortals' => 'tns:getPortals'], // input parameters
    ['return' => 'tns:portalList'], // output parameters
    'urn:WSAccessUrl', // namespace
    'urn:WSAccessUrl#WSGetPortals', // soapaction
    'rpc', // style
    'encoded', // use
    'This service adds a user to portal'               // documentation
);

// Define the method WSAddUserToPortal
function WSGetPortals($params)
{
    global $debug;
    if (!WSHelperVerifyKey($params['secret_key'])) {
        return return_error(WS_ERROR_SECRET_KEY);
    }
    $urlData = UrlManager::get_url_data();

    $return = [];
    foreach ($urlData as $data) {
        $return[] = [
            'id' => $data['id'],
            'url' => $data['url'],
        ];
    }
    if ($debug) {
        error_log(print_r($return, 1));
    }

    return $return;
}

$server->wsdl->addComplexType(
    'AddUserToPortal',
    'complexType',
    'struct',
    'all',
    '',
    [
        'secret_key' => ['name' => 'secret_key', 'type' => 'xsd:string'],
        'user_id' => ['name' => 'user_id', 'type' => 'xsd:string'],
        'portal_id' => ['name' => 'portal_id', 'type' => 'xsd:string'],
    ]
);

// Register the method to expose
$server->register(
    'WSAddUserToPortal', // method name
    ['addUserToPortal' => 'tns:AddUserToPortal'], // input parameters
    ['return' => 'xsd:string'], // output parameters
    'urn:WSAccessUrl', // namespace
    'urn:WSAccessUrl#WSAddUserToPortal', // soapaction
    'rpc', // style
    'encoded', // use
    'This service adds a user to portal'               // documentation
);

// Define the method WSAddUserToPortal
function WSAddUserToPortal($params)
{
    if (!WSHelperVerifyKey($params['secret_key'])) {
        return return_error(WS_ERROR_SECRET_KEY);
    }

    $userId = $params['user_id'];
    $portalId = $params['portal_id'];

    UrlManager::add_user_to_url($userId, $portalId);

    $result = UrlManager::relation_url_user_exist($userId, $portalId);
    if (!empty($result)) {
        return 1;
    }

    return 0;
}

// Register the method to expose
$server->register(
    'WSRemoveUserFromPortal', // method name
    ['removeUserFromPortal' => 'tns:AddUserToPortal'], // input parameters
    ['return' => 'xsd:string'], // output parameters
    'urn:WSAccessUrl', // namespace
    'urn:WSAccessUrl#WSRemoveUserFromPortal', // soapaction
    'rpc', // style
    'encoded', // use
    'This service remove a user from a portal'                  // documentation
);

// Define the method WSDeleteUserFromGroup
function WSRemoveUserFromPortal($params)
{
    if (!WSHelperVerifyKey($params['secret_key'])) {
        return return_error(WS_ERROR_SECRET_KEY);
    }

    $userId = $params['user_id'];
    $portalId = $params['portal_id'];

    UrlManager::delete_url_rel_user($userId, $portalId);

    $result = UrlManager::relation_url_user_exist($userId, $portalId);
    if (empty($result)) {
        return 1;
    }

    return 0;
}

$server->wsdl->addComplexType(
    'getPortalListFromUser',
    'complexType',
    'struct',
    'all',
    '',
    [
        'secret_key' => ['name' => 'secret_key', 'type' => 'xsd:string'],
        'user_id' => ['name' => 'user_id', 'type' => 'xsd:string'],
    ]
);

// Register the method to expose
$server->register(
    'WSGetPortalListFromUser', // method name
    ['getPortalListFromUser' => 'tns:getPortalListFromUser'], // input parameters
    ['return' => 'tns:portalList'], // output parameters
    'urn:WSAccessUrl', // namespace
    'urn:WSAccessUrl#WSGetPortalListFromUser', // soapaction
    'rpc', // style
    'encoded', // use
    'This service remove a user from a portal'                  // documentation
);

// Define the method WSDeleteUserFromGroup
function WSGetPortalListFromUser($params)
{
    if (!WSHelperVerifyKey($params['secret_key'])) {
        return return_error(WS_ERROR_SECRET_KEY);
    }

    $userId = $params['user_id'];

    $result = UrlManager::get_access_url_from_user($userId);
    if (!empty($result)) {
        foreach ($result as &$data) {
            $data['id'] = $data['access_url_id'];
        }
    }

    return $result;
}

// Course ws
$server->wsdl->addComplexType(
    'getPortalListFromCourse',
    'complexType',
    'struct',
    'all',
    '',
    [
        'secret_key' => ['name' => 'secret_key', 'type' => 'xsd:string'],
        'original_course_id_name' => ['name' => 'original_course_id_name', 'type' => 'xsd:string'],
        'original_course_id_value' => ['name' => 'original_course_id_value', 'type' => 'xsd:string'],
    ]
);

// Register the method to expose
$server->register(
    'WSGetPortalListFromCourse', // method name
    ['getPortalListFromCourse' => 'tns:getPortalListFromCourse'], // input parameters
    ['return' => 'tns:portalList'], // output parameters
    'urn:WSAccessUrl', // namespace
    'urn:WSAccessUrl#getPortalListFromCourse', // soapaction
    'rpc', // style
    'encoded', // use
    'This service remove a user from a portal'                  // documentation
);

// Define the method WSDeleteUserFromGroup
function WSGetPortalListFromCourse($params)
{
    if (!WSHelperVerifyKey($params['secret_key'])) {
        return return_error(WS_ERROR_SECRET_KEY);
    }

    $courseInfo = CourseManager::getCourseInfoFromOriginalId(
        $params['original_course_id_value'],
        $params['original_course_id_name']
    );

    $courseId = $courseInfo['real_id'];

    $result = UrlManager::get_access_url_from_course($courseId);

    if (!empty($result)) {
        foreach ($result as &$data) {
            $data['id'] = $data['access_url_id'];
        }
    }

    return $result;
}

$server->wsdl->addComplexType(
    'addCourseToPortal',
    'complexType',
    'struct',
    'all',
    '',
    [
        'secret_key' => ['name' => 'secret_key', 'type' => 'xsd:string'],
        'portal_id' => ['name' => 'portal_id', 'type' => 'xsd:string'],
        'original_course_id_name' => ['name' => 'original_course_id_name', 'type' => 'xsd:string'],
        'original_course_id_value' => ['name' => 'original_course_id_value', 'type' => 'xsd:string'],
    ]
);

// Register the method to expose
$server->register(
    'WSAddCourseToPortal', // method name
    ['addCourseToPortal' => 'tns:addCourseToPortal'], // input parameters
    ['return' => 'xsd:string'], // output parameters
    'urn:WSAccessUrl', // namespace
    'urn:WSAccessUrl#WSAddCourseToPortal', // soapaction
    'rpc', // style
    'encoded', // use
    'This service adds a course to portal'               // documentation
);

// Define the method WSAddUserToPortal
function WSAddCourseToPortal($params)
{
    if (!WSHelperVerifyKey($params['secret_key'])) {
        return return_error(WS_ERROR_SECRET_KEY);
    }

    $courseInfo = CourseManager::getCourseInfoFromOriginalId(
        $params['original_course_id_value'],
        $params['original_course_id_name']
    );

    $courseId = $courseInfo['real_id'];
    $portalId = $params['portal_id'];

    UrlManager::add_course_to_url($courseId, $portalId);

    $result = UrlManager::relation_url_course_exist($courseId, $portalId);

    return intval($result);
}

// Register the method to expose
$server->register(
    'WSRemoveCourseFromPortal', // method name
    ['removeCourseFromPortal' => 'tns:addCourseToPortal'], // input parameters
    ['return' => 'xsd:string'], // output parameters
    'urn:WSAccessUrl', // namespace
    'urn:WSAccessUrl#WSRemoveCourseFromPortal', // soapaction
    'rpc', // style
    'encoded', // use
    'This service remove a course from a portal'                  // documentation
);

// Define the method WSDeleteUserFromGroup
function WSRemoveCourseFromPortal($params)
{
    if (!WSHelperVerifyKey($params['secret_key'])) {
        return return_error(WS_ERROR_SECRET_KEY);
    }

    $courseInfo = CourseManager::getCourseInfoFromOriginalId(
        $params['original_course_id_value'],
        $params['original_course_id_name']
    );

    $courseId = $courseInfo['real_id'];
    $portalId = $params['portal_id'];

    UrlManager::delete_url_rel_course($courseId, $portalId);
    $result = UrlManager::relation_url_course_exist($courseId, $portalId);

    if (empty($result)) {
        return true;
    }

    return false;
}

/* Delete user from group Web Service end */

// Add more webservices through hooks from plugins
if (!empty($hook)) {
    $hook->setEventData(['server' => $server]);
    $res = $hook->notifyWSRegistration(HOOK_EVENT_TYPE_POST);
    if (!empty($res['server'])) {
        $server = $res['server'];
    }
}

// Use the request to (try to) invoke the service
$GLOBALS['HTTP_RAW_POST_DATA'] = file_get_contents('php://input');
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';

// If you send your data in utf8 then this value must be false.
$decodeUTF8 = api_get_setting('registration.soap.php.decode_utf8');
if ($decodeUTF8 === 'true') {
    $server->decode_utf8 = true;
} else {
    $server->decode_utf8 = false;
}
$server->service($HTTP_RAW_POST_DATA);
