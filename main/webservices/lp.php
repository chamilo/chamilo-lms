<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.webservices
 */
require_once __DIR__.'/../inc/global.inc.php';

api_protect_webservices();

ini_set('memory_limit', -1);
/*
ini_set('upload_max_filesize', '4000M');
ini_set('post_max_size', '4000M');
ini_set('max_execution_time', '80000');
ini_set('max_input_time', '80000');
*/

$debug = true;

define('WS_ERROR_SECRET_KEY', 1);

function return_error($code)
{
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
        list($ip1) = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
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

//$server->soap_defencoding = 'UTF-8';

// Initialize WSDL support
$server->configureWSDL('WSLP', 'urn:WSLP');

$server->wsdl->addComplexType(
    'params',
    'complexType',
    'struct',
    'all',
    '',
    [
        'course_id_name' => [
            'name' => 'course_id_name',
            'type' => 'xsd:string',
        ],
        'course_id_value' => [
            'name' => 'course_id_name',
            'type' => 'xsd:string',
        ],
        'session_id_name' => [
            'name' => 'session_id_name',
            'type' => 'xsd:string',
        ],
        'session_id_value' => [
            'name' => 'session_id_value',
            'type' => 'xsd:string',
        ],
        'file_data' => ['name' => 'file', 'type' => 'xsd:string'],
        'filename' => ['name' => 'filename', 'type' => 'xsd:string'],
        'lp_name' => ['name' => 'lp_name', 'type' => 'xsd:string'],
        'secret_key' => ['name' => 'secret_key', 'type' => 'xsd:string'],
    ]
);

// Register the method to expose
$server->register(
    'WSImportLP', // method name
    ['params' => 'tns:params'], // input parameters
    ['return' => 'xsd:string'], // output parameters
    'urn:WSLP', // namespace
    'urn:WSLP#WSImportLP', // soapaction
    'rpc', // style
    'encoded', // use
    'This service adds users'                                               // documentation
);

/**
 * @param array $params
 *
 * @return int|string
 */
function WSImportLP($params)
{
    global $debug;
    if (!WSHelperVerifyKey($params)) {
        return return_error(WS_ERROR_SECRET_KEY);
    }
    if ($debug) {
        error_log('WSImportLP');
    }

    $courseIdName = $params['course_id_name'];
    $courseIdValue = $params['course_id_value'];
    $sessionIdName = isset($params['session_id_name']) ? $params['session_id_name'] : null;
    $sessionIdValue = isset($params['session_id_value']) ? $params['session_id_value'] : null;

    $lpName = $params['lp_name'];

    $courseInfo = CourseManager::getCourseInfoFromOriginalId(
        $courseIdValue,
        $courseIdName
    );
    $courseId = $courseInfo['real_id'];

    if (empty($courseInfo)) {
        if ($debug) {
            error_log('Course not found');
        }

        return 'Course not found';
    }

    $sessionId = 0;
    if (!empty($sessionIdName) && !empty($sessionIdValue)) {
        $sessionId = SessionManager::getSessionIdFromOriginalId(
            $sessionIdValue,
            $sessionIdName
        );

        if (empty($sessionId)) {
            if ($debug) {
                error_log('Session not found');
            }

            return 'Session not found';
        }
    }

    $proximity = 'local';
    $maker = 'Scorm';
    $maxScore = ''; //$_REQUEST['use_max_score']

    $oScorm = new scorm($courseInfo['code']);
    $fileData = base64_decode($params['file_data']);

    $uniqueFile = uniqid();
    $userId = 1; // admin
    $filePath = api_get_path(SYS_ARCHIVE_PATH).$uniqueFile;
    file_put_contents($filePath, $fileData);

    $fileName = $params['filename'];

    $fileInfo = [
        'tmp_name' => $filePath,
        'name' => $fileName,
    ];

    $manifest = $oScorm->import_package($fileInfo, '', $courseInfo);

    if (!$manifest) {
        if ($debug) {
            error_log('manifest.xml file not found');
        }

        return 'manifest.xml file not found';
    }

    $manifestData = $oScorm->parse_manifest($manifest);

    if (!empty($manifestData)) {
        $oScorm->import_manifest(
            $courseInfo['code'],
            $maxScore,
            $sessionId,
            $userId
        );
        $oScorm->set_name($lpName);
        $oScorm->set_proximity($proximity, $courseId);
        $oScorm->set_maker($maker, $courseId);
        //$oScorm->set_jslib('scorm_api.php');

        if ($debug) {
            error_log('scorm was added');
        }

        return 1;
    } else {
        if ($debug) {
            error_log('manifest data empty');
        }

        return 'manifest data empty';
    }
}

$server->wsdl->addComplexType(
    'paramsGetLpList',
    'complexType',
    'struct',
    'all',
    '',
    [
        'course_id_name' => [
            'name' => 'course_id_name',
            'type' => 'xsd:string',
        ],
        'course_id_value' => [
            'name' => 'course_id_name',
            'type' => 'xsd:string',
        ],
        'session_id_name' => [
            'name' => 'session_id_name',
            'type' => 'xsd:string',
        ],
        'session_id_value' => [
            'name' => 'session_id_value',
            'type' => 'xsd:string',
        ],
        'secret_key' => ['name' => 'secret_key', 'type' => 'xsd:string'],
    ]
);

$server->wsdl->addComplexType(
    'lpListItem',
    'complexType',
    'struct',
    'all',
    '',
    [
        'id' => ['name' => 'id', 'type' => 'xsd:string'],
        'name' => ['name' => 'name', 'type' => 'xsd:string'],
    ]
);

$server->wsdl->addComplexType(
    'lpList',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    [],
    [['ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:lpListItem[]']],
    'tns:lpListItem'
);

// Register the method to expose
$server->register(
    'WSGetLpList', // method name
    ['params' => 'tns:paramsGetLpList'], // input parameters
    ['return' => 'tns:lpList'], // output parameters
    'urn:WSLP', // namespace
    'urn:WSLP#WSGetLpList', // soapaction
    'rpc', // style
    'encoded', // use
    'This service adds users'                                               // documentation
);

/**
 * @param array $params
 *
 * @return int|string
 */
function WSGetLpList($params)
{
    global $debug;
    if (!WSHelperVerifyKey($params)) {
        return return_error(WS_ERROR_SECRET_KEY);
    }

    $courseIdName = $params['course_id_name'];
    $courseIdValue = $params['course_id_value'];

    $sessionIdName = isset($params['session_id_name']) ? $params['session_id_name'] : null;
    $sessionIdValue = isset($params['session_id_value']) ? $params['session_id_value'] : null;

    $courseInfo = CourseManager::getCourseInfoFromOriginalId(
        $courseIdValue,
        $courseIdName
    );

    if (empty($courseInfo)) {
        if ($debug) {
            error_log("Course not found: $courseIdName : $courseIdValue");
        }

        return 'Course not found';
    }

    $courseId = $courseInfo['real_id'];

    $sessionId = 0;
    if (!empty($sessionIdName) && !empty($sessionIdValue)) {
        $sessionId = SessionManager::get_session_id_from_original_id(
            $sessionIdValue,
            $sessionIdName
        );

        if (empty($sessionId)) {
            if ($debug) {
                error_log('Session not found');
            }

            return 'Session not found';
        }
    }

    $list = new LearnpathList(null, $courseInfo, $sessionId);
    $flatList = $list->get_flat_list();
    $result = [];
    foreach ($flatList as $id => $lp) {
        $result[] = [
            'id' => $id,
            'name' => $lp['lp_name'],
        ];
    }

    return $result;
}

$server->wsdl->addComplexType(
    'paramsDeleteLp',
    'complexType',
    'struct',
    'all',
    '',
    [
        'course_id_name' => [
            'name' => 'course_id_name',
            'type' => 'xsd:string',
        ],
        'course_id_value' => [
            'name' => 'course_id_name',
            'type' => 'xsd:string',
        ],
        'lp_id' => [
            'name' => 'lp_id',
            'type' => 'xsd:string',
        ],
        'secret_key' => ['name' => 'secret_key', 'type' => 'xsd:string'],
    ]
);

// Register the method to expose
$server->register(
    'WSDeleteLp', // method name
    ['params' => 'tns:paramsDeleteLp'], // input parameters
    ['return' => 'xsd:string'], // output parameters
    'urn:WSLP', // namespace
    'urn:WSLP#WSDeleteLp', // soapaction
    'rpc', // style
    'encoded', // use
    'This service deletes a LP'                                               // documentation
);

/**
 * @param array $params
 *
 * @return int|string
 */
function WSDeleteLp($params)
{
    global $debug;
    if (!WSHelperVerifyKey($params)) {
        return return_error(WS_ERROR_SECRET_KEY);
    }

    $courseIdName = $params['course_id_name'];
    $courseIdValue = $params['course_id_value'];
    $lpId = $params['lp_id'];

    $sessionIdName = isset($params['session_id_name']) ? $params['session_id_name'] : null;
    $sessionIdValue = isset($params['session_id_value']) ? $params['session_id_value'] : null;

    $courseInfo = CourseManager::getCourseInfoFromOriginalId(
        $courseIdValue,
        $courseIdName
    );

    if (empty($courseInfo)) {
        if ($debug) {
            error_log("Course not found: $courseIdName : $courseIdValue");
        }

        return 'Course not found';
    }
    $courseId = $courseInfo['real_id'];
    $courseCode = $courseInfo['code'];

    $sessionId = 0;

    /*
    if (!empty($sessionIdName) && !empty($sessionIdValue)) {
        $sessionId = SessionManager::get_session_id_from_original_id(
            $sessionIdValue,
            $sessionIdName
        );

        if (empty($sessionId)) {

            if ($debug) error_log('Session not found');
            return 'Session not found';
        }
    }
    */

    $lp = new learnpath($courseCode, $lpId, null);
    if ($lp) {
        if ($debug) {
            error_log("LP deleted $lpId");
        }

        $course_dir = $courseInfo['directory'].'/document';
        $sys_course_path = api_get_path(SYS_COURSE_PATH);
        $base_work_dir = $sys_course_path.$course_dir;

        $items = $lp->get_flat_ordered_items_list($lpId, 0, $courseId);

        if (!empty($items)) {
            /** @var $item learnpathItem */
            foreach ($items as $itemId) {
                $item = new learnpathItem($itemId, null, $courseId);

                if ($item) {
                    $documentId = $item->get_path();

                    if ($debug) {
                        error_log("lp item id found #$itemId");
                    }

                    $documentInfo = DocumentManager::get_document_data_by_id(
                        $documentId,
                        $courseInfo['code'],
                        false,
                        $sessionId
                    );

                    if (!empty($documentInfo)) {
                        if ($debug) {
                            error_log("Document id deleted #$documentId");
                        }
                        DocumentManager::delete_document(
                            $courseInfo,
                            null,
                            $base_work_dir,
                            $sessionId,
                            $documentId
                        );
                    } else {
                        if ($debug) {
                            error_log("No document found for id #$documentId");
                        }
                    }
                } else {
                    if ($debug) {
                        error_log("Document not found #$itemId");
                    }
                }
            }
        }

        $lp->delete($courseInfo, $lpId, 'remove');

        return 1;
    }

    return 0;
}

$server->wsdl->addComplexType(
    'lpItem',
    'complexType',
    'struct',
    'all',
    '',
    [
        'data' => ['name' => 'data', 'type' => 'xsd:string'],
        'title' => ['name' => 'title', 'type' => 'xsd:string'],
        'filename' => ['name' => 'filename', 'type' => 'xsd:string'],
    ]
);

$server->wsdl->addComplexType(
    'lpItemList',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    [],
    [['ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:lpItem[]']],
    'tns:lpItem'
);

$server->wsdl->addComplexType(
    'paramsCreateLp',
    'complexType',
    'struct',
    'all',
    '',
    [
        'course_id_name' => [
            'name' => 'course_id_name',
            'type' => 'xsd:string',
        ],
        'course_id_value' => [
            'name' => 'course_id_name',
            'type' => 'xsd:string',
        ],
        /*'session_id_name' => array(
            'name' => 'session_id_name',
            'type' => 'xsd:string',
        ),
        'session_id_value' => array(
            'name' => 'session_id_value',
            'type' => 'xsd:string',
        ),*/
        'lp_name' => [
            'name' => 'lp_name',
            'type' => 'xsd:string',
        ],
        'lp_item_list' => [
            'name' => 'lp_item_list',
            'type' => 'tns:lpItemList',
        ],
        'secret_key' => ['name' => 'secret_key', 'type' => 'xsd:string'],
    ]
);

// Register the method to expose
$server->register(
    'WSCreateLp', // method name
    ['params' => 'tns:paramsCreateLp'], // input parameters
    ['return' => 'xsd:string'], // output parameters
    'urn:WSLP', // namespace
    'urn:WSLP#WSCreateLp', // soapaction
    'rpc', // style
    'encoded', // use
    'This service creates a LP'                                               // documentation
);

/**
 * @param array $params
 *
 * @return soap_fault|null
 */
function WSCreateLp($params)
{
    global $debug;
    if (!WSHelperVerifyKey($params)) {
        return return_error(WS_ERROR_SECRET_KEY);
    }

    if ($debug) {
        error_log('WSCreateLp');
    }

    $courseIdName = $params['course_id_name'];
    $courseIdValue = $params['course_id_value'];
    $lpName = $params['lp_name'];
    $lpItemList = $params['lp_item_list'];

    /*$sessionIdName = isset($params['session_id_name']) ? $params['session_id_name'] : null;
    $sessionIdValue = isset($params['session_id_value']) ? $params['session_id_value'] : null;*/

    $courseInfo = CourseManager::getCourseInfoFromOriginalId(
        $courseIdValue,
        $courseIdName
    );

    if (empty($courseInfo)) {
        if ($debug) {
            error_log('Course not found');
        }
    }

    $userId = 1;
    $courseId = $courseInfo['real_id'];
    $courseCode = $courseInfo['code'];

    /*$sessionId = 0;
    if (!empty($sessionIdName) && !empty($sessionIdValue)) {
        $sessionId = SessionManager::get_session_id_from_original_id(
            $sessionIdValue,
            $sessionIdName
        );

        if (empty($sessionId)) {

            if ($debug) {
                error_log('Session not found');
            }

            return 'Session not found';
        }
    }*/
    if ($debug) {
        error_log('add_lp');
    }
    $lpId = learnpath::add_lp(
        $courseCode,
        $lpName,
        '',
        'chamilo',
        'manual',
        '',
        '',
        '',
        0,
        $userId
    );

    if ($lpId) {
        if ($debug) {
            error_log('LP created');
        }

        $lp = new learnpath($courseCode, $lpId, null);

        $previousId = 0;
        foreach ($lpItemList as $lpItem) {
            $info = pathinfo($lpItem['filename']);
            $extension = $info['extension'];
            $data = base64_decode($lpItem['data']);

            if ($debug) {
                error_log('create_document: '.$info['filename']);
            }

            $documentId = $lp->create_document(
                $courseInfo,
                $data,
                $info['filename'],
                $extension,
                0,
                $userId
            );

            if ($documentId) {
                if ($debug) {
                    error_log("Document created $documentId");

                    $itemId = $lp->add_item(
                        null,
                        $previousId,
                        'document',
                        $documentId,
                        $lpItem['title'],
                        '',
                        '',
                        0,
                        $userId
                    );

                    $previousId = $itemId;

                    if ($itemId) {
                        if ($debug) {
                            error_log("Item added");
                        }
                    } else {
                        if ($debug) {
                            error_log("Item not added");
                        }
                    }
                }
            } else {
                if ($debug) {
                    error_log("Document NOT created");
                }
            }
        }

        return 1;
    } else {
        if ($debug) {
            error_log('LP not created');
        }
    }

    return 0;
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
