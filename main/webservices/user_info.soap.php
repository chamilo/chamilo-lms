<?php

/* For licensing terms, see /license.txt */

/**
 * This script provides the caller service with user details.
 * It is set to work with the Chamilo module for Drupal:
 * http://drupal.org/project/chamilo.
 *
 * @author Yannick Warnier <yannick.warnier@dokeos.com>
 */
require_once __DIR__.'/../inc/global.inc.php';

api_protect_webservices();

// Create the server instance
$server = new soap_server();
// Initialize WSDL support
$server->configureWSDL('WSUserInfo', 'urn:WSUserInfo');

/* Register WSCourseList function */
// Register the data structures used by the service

$server->wsdl->addComplexType(
    'courseDetails',
    'complexType',
    'struct',
    'all',
    '',
    [
        'name' => 'code',
        'type' => 'xsd:string',
        'name' => 'title',
        'type' => 'xsd:string',
        'name' => 'url',
        'type' => 'xsd:string',
        'name' => 'teacher',
        'type' => 'xsd:string',
        'name' => 'language',
        'type' => 'xsd:string',
    ]
);

$server->wsdl->addComplexType(
    'courseList',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    [],
    [
        [
            'ref' => 'SOAP-ENC:arrayType',
            'wsdl:arrayType' => 'tns:courseDetails[]',
        ],
    ],
    'tns:courseDetails'
);

// Register the method to expose
$server->register(
    'WSCourseListOfUser', // method name
    [
        'username' => 'xsd:string',
        'signature' => 'xsd:string',
    ], // input parameters
    ['return' => 'xsd:Array'], // output parameters
    'urn:WSUserInfo', // namespace
    'urn:WSUserInfo#WSUserInfo', // soapaction
    'rpc', // style
    'encoded', // use
    'This service returns a list of courses'    // documentation
);

/**
 * Get a list of courses (code, url, title, teacher, language) for a specific
 * user and return to caller
 * Function registered as service. Returns strings in UTF-8.
 *
 * @param string User name in Chamilo
 * @param string Signature (composed of the sha1(username+apikey)
 *
 * @return array Courses list (code=>[title=>'title',url='http://...',teacher=>'...',language=>''],code=>[...],...)
 */
function WSCourseListOfUser($username, $signature)
{
    if (empty($username) or empty($signature)) {
        return -1;
    }
    $info = api_get_user_info_from_username($username);
    $user_id = $info['user_id'];
    $list = UserManager::get_api_keys($user_id, 'dokeos');
    $key = '';
    foreach ($list as $key) {
        break;
    }

    $local_key = $username.$key;

    if (!api_is_valid_secret_key($signature, $local_key)) {
        return -1; // The secret key is incorrect.
    }

    $courses_list = [];
    $courses_list_tmp = CourseManager::get_courses_list_by_user_id($user_id);
    foreach ($courses_list_tmp as $index => $course) {
        $course_info = CourseManager::get_course_information($course['code']);
        $courses_list[] = [
            'code' => $course['code'],
            'title' => api_utf8_encode($course_info['title']),
            'url' => api_get_path(WEB_COURSE_PATH).$course_info['directory'].'/',
            'teacher' => api_utf8_encode($course_info['tutor_name']),
            'language' => $course_info['course_language'],
        ];
    }

    return $courses_list;
}

/* Register WSEventsList function */
// Register the data structures used by the service
$server->wsdl->addComplexType(
    'eventDetails',
    'complexType',
    'struct',
    'all',
    '',
    [
        'name' => 'datestart',
        'type' => 'xsd:string',
        'name' => 'dateend',
        'type' => 'xsd:string',
        'name' => 'title',
        'type' => 'xsd:string',
        'name' => 'link',
        'type' => 'xsd:string',
        'name' => 'coursetitle',
        'type' => 'xsd:string',
    ]
);

$server->wsdl->addComplexType(
    'eventsList',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    [],
    [
        [
            'ref' => 'SOAP-ENC:arrayType',
            'wsdl:arrayType' => 'tns:eventDetails[]',
        ],
    ],
    'tns:eventDetails'
);

// Register the method to expose
$server->register(
    'WSEventsList',
    // method name
    [
        'username' => 'xsd:string',
        'signature' => 'xsd:string',
        'datestart' => 'xsd:int',
        'dateend' => 'xsd:int',
    ],
    // input parameters
    ['return' => 'xsd:Array'],
    // output parameters
    'urn:WSUserInfo',
    // namespace
    'urn:WSUserInfo#WSEventsList',
    // soapaction
    'rpc',
    // style
    'encoded',
    // use
    'This service returns a list of events of the courses the given user is subscribed to'      // documentation
);

/**
 * Get a list of events between two dates for the given username
 * Function registered as service. Returns strings in UTF-8.
 *
 * @param string Username
 * @param string User's API key (the user's API key)
 * @param int    Start date, in YYYYMMDD format
 * @param int    End date, in YYYYMMDD format
 *
 * @return array Events list
 */
function WSEventsList($username, $signature, $datestart = 0, $dateend = 0)
{
    if (empty($username) or empty($signature)) {
        return -1;
    }

    $info = api_get_user_info_from_username($username);
    $user_id = $info['user_id'];
    $list = UserManager::get_api_keys($user_id, 'dokeos');
    $key = '';
    foreach ($list as $key) {
        break;
    }

    $local_key = $username.$key;

    if (!api_is_valid_secret_key($signature, $local_key)) {
        return -1; // The secret key is incorrect.
    }
    $events_list = [];

    $user_id = UserManager::get_user_id_from_username($username);
    if ($user_id === false) {
        return $events_list;
    } // Error in user id recovery.
    $ds = substr($datestart, 0, 4).'-'.substr($datestart, 4, 2).'-'.substr($datestart, 6, 2).' 00:00:00';
    $de = substr($dateend, 0, 4).'-'.substr($dateend, 4, 2).'-'.substr($dateend, 6, 2).' 00:00:00';
    $events_list = Agenda::get_personal_agenda_items_between_dates(
        $user_id,
        $ds,
        $de
    );

    return $events_list;
}

// Use the request to (try to) invoke the service.
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);
