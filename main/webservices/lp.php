<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.webservices
 */
require_once '../inc/global.inc.php';
$libpath = api_get_path(LIBRARY_PATH);
require_once $libpath.'nusoap/nusoap.php';
require_once $libpath.'fileManage.lib.php';
require_once $libpath.'fileUpload.lib.php';
require_once api_get_path(INCLUDE_PATH).'lib/mail.lib.inc.php';
require_once $libpath.'add_course.lib.inc.php';

$debug = false;

define('WS_ERROR_SECRET_KEY', 1);

function return_error($code) {
    $fault = null;
    switch ($code) {
        case WS_ERROR_SECRET_KEY:
            $fault = new soap_fault('Server', '', 'Secret key is not correct or params are not correctly set');
            break;
    }
    return $fault;
}

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
        list($ip1, $ip2) = split(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ip1);
    }
    if ($debug)
        error_log("ip: $ip");
    // Check if a file that limits access from webservices exists and contains
    // the restraining check
    if (is_file('webservice-auth-ip.conf.php')) {
        include 'webservice-auth-ip.conf.php';
        if ($debug)
            error_log("webservice-auth-ip.conf.php file included");
        if (!empty($ws_auth_ip)) {
            $check_ip = true;
            $ip_matches = api_check_ip_in_range($ip, $ws_auth_ip);
            if ($debug)
                error_log("ip_matches: $ip_matches");
        }
    }

    if ($debug) {
        error_log("checkip " . intval($check_ip));
    }

    if ($check_ip) {
        $security_key = $_configuration['security_key'];
    } else {
        $security_key = $ip.$_configuration['security_key'];
        //error_log($secret_key.'-'.$security_key);
    }

    $result = api_is_valid_secret_key($secret_key, $security_key);
    //error_log($secret_key.'-'.$security_key);
    if ($debug)
        error_log('WSHelperVerifyKey result: '.intval($result));
    return $result;
}

// Create the server instance
$server = new soap_server();

//$server->soap_defencoding = 'UTF-8';

// Initialize WSDL support
$server->configureWSDL('WSLP', 'urn:WSLP');




// Input params for editing users
$server->wsdl->addComplexType(
    'params',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'course_id_name' => array(
            'name' => 'course_id_name',
            'type' => 'xsd:string',
        ),
        'course_id_value' => array(
            'name' => 'course_id_name',
            'type' => 'xsd:string',
        ),
        'session_id_name' => array(
            'name' => 'session_id_name',
            'type' => 'xsd:string',
        ),
        'session_id_value' => array(
            'name' => 'session_id_value',
            'type' => 'xsd:string',
        ),
        'file' => array('name' => 'file', 'type' => 'xsd:string'),
        'filename' => array('name' => 'filename', 'type' => 'xsd:string'),
        'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string'),
    )
);

// Register the method to expose
$server->register('WSImportLP',                            // method name
    array('params' => 'tns:params'),  // input parameters
    array('return' => 'xsd:string'),                                        // output parameters
    'urn:WSLP',                                                   // namespace
    'urn:WSLP#WSImportLP',                       // soapaction
    'rpc',                                                                  // style
    'encoded',                                                              // use
    'This service adds users'                                               // documentation
);

function WSImportLP($params)
{
    if (!WSHelperVerifyKey($params)) {
        return return_error(WS_ERROR_SECRET_KEY);
    }

    $courseIdName = $params['course_id_name'];
    $courseIdValue = $params['course_id_value'];
    $sessionIdName = isset($params['session_id_name']) ? $params['session_id_name'] : null;
    $sessionIdValue = isset($params['session_id_value']) ? $params['session_id_value'] : null;

    $courseCode = CourseManager::get_course_id_from_original_id(
        $courseIdValue,
        $courseIdName
    );


    $sessionId = 0;
    if (!empty($sessionIdName) && !empty($sessionIdValue)) {
        $sessionId = SessionManager::get_session_id_from_original_id(
            $sessionIdValue,
            $sessionIdName
        );
    }

    $courseInfo = api_get_course_info($courseCode);

    if (empty($courseInfo)) {
        return 'no course found';
    }

    $proximity = 'local';
    $maker = 'Scorm';
    $maxScore = ''; //$_REQUEST['use_max_score']

    $oScorm = new scorm();
    $file = $params['file'];
    error_log(print_r($params, 1));
    exit;
    $uniqueFile = uniqid();
    file_put_contents($uniqueFile, api_get_path(SYS_ARCHIVE_PATH).$uniqueFile);
    $fileName = $params['filename'];

    $fileInfo = array(
        'tmp_name' => $file,
        'name' => $fileName
    );

    $manifest = $oScorm->import_package($fileInfo, '', $courseInfo);
    error_log($manifest);

    if (!$manifest) {
        //if api_set_failure
        return 0;
    }

    if (!empty($manifest)) {
        $oScorm->parse_manifest($manifest);
        $oScorm->import_manifest(
            $courseInfo['code'],
            $maxScore,
            $sessionId
        );
    }

    $oScorm->set_proximity($proximity, $courseId);
    $oScorm->set_maker($maker, $courseId);
    //$oScorm->set_jslib('scorm_api.php');
}


// Use the request to (try to) invoke the service
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
// If you send your data in utf8 then this value must be false.
if (isset($_configuration['registration.soap.php.decode_utf8'])) {
    if ($_configuration['registration.soap.php.decode_utf8']) {
        $server->decode_utf8 = true;
    } else {
        $server->decode_utf8 = false;
    }
}
$server->service($HTTP_RAW_POST_DATA);



