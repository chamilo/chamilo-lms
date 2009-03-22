<?php //$id: $
/**
 * This script provides the caller service with a list
 * of courses that have a certain level of visibility
 * on this dokeos portal.
 * It is set to work with the Dokeos module for Drupal:
 * http://drupal.org/project/dokeos
 * 
 * See license terms in /dokeos_license.txt
 * @author Yannick Warnier <yannick.warnier@dokeos.com>
 */
require_once('../inc/global.inc.php');
$libpath = api_get_path(LIBRARY_PATH);
require_once($libpath.'nusoap/nusoap.php');

// Create the server instance
$server = new soap_server();
// Initialize WSDL support
$server->configureWSDL('WSUserInfo', 'urn:WSUserInfo');

/* Register DokeosWSCourseListOfUser function */
// Register the data structures used by the service

$server->wsdl->addComplexType(
    'courseListOfUser',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'username' => array('name' => 'username', 'type' => 'xsd:string'),          
        'signature' => array('name' => 'signature', 'type' => 'xsd:string'),          
    )
);

// Register the method to expose
$server->register('DokeosWSCourseListOfUser',       // method name
    array('username' => 'xsd:string',
          'signature' => 'xsd:string'),    // input parameters
    array('return' => 'xsd:array'),                 // output parameters
    'urn:WSUserInfo',                       // namespace
    'urn:WSUserInfo#DokeosWSCourseListOfUser',    // soapaction
    'rpc',                                      // style
    'encoded',                                  // use
    'This service returns a list of courses the given user is subscribed to directly'      // documentation
);
/**
 * Get a list of courses (code, url, title, teacher, language) for a specific 
 * user and return to caller
 * Function registered as service. Returns strings in UTF-8.
 * @param string User name in Dokeos
 * @param string Signature (composed of the sha1(username+apikey)
 * @return array Courses list (code=>[title=>'title',url='http://...',teacher=>'...',language=>''],code=>[...],...)
 */
function DokeosWSCourseListOfUser($username, $signature) {
    if (empty($username) or empty($signature)) { return -1; }

    require_once (api_get_path(LIBRARY_PATH).'course.lib.php');
    require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
    global $_configuration;
    
    $info = api_get_user_info_from_username($username);
    $user_id = $info['user_id'];
    $list = UserManager::get_api_keys($user_id,'dokeos');
    $key = $list[0];
    
    $local_key = sha1($username.$key);

    if (!api_is_valid_secret_key($signature, $local_key)) {
        return -1; //secret key is incorrect
    }
    
    
    // libraries
    require_once (api_get_path(LIBRARY_PATH).'course.lib.php');
    $charset = api_get_setting('platform_charset');
    
    $courses_list = array();
    $courses_list_tmp = CourseManager::get_courses_list_by_user_id($user_id);
    foreach ( $courses_list_tmp as $index => $course )
    {
        $course_info = CourseManager::get_course_information($course['code']);
        $courses_list[$course['code']] = array('title'=>mb_convert_encoding($course_info['title'],'UTF-8',$charset),'url'=>api_get_path(WEB_COURSE_PATH).$course_info['directory'].'/','teacher'=>mb_convert_encoding($course_info['tutor_name'],'UTF-8',$charset),'language'=>$course_info['course_language']);
    }
    return $courses_list;
}


$server->wsdl->addComplexType(
    'agendaEvents',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'username' => array('name' => 'username', 'type' => 'xsd:string'),          
        'signature' => array('name' => 'signature', 'type' => 'xsd:string'),
        'datestart' => array('name' => 'datestart', 'type' => 'xsd:int'),
        'dateend' => array('name' => 'dateend', 'type' => 'xsd:int'),                  
    )
);

// Register the method to expose
$server->register('DokeosWSEventsList',       // method name
    array('username' => 'xsd:string',
          'signature' => 'xsd:string',
          'datestart' => 'xsd:int',
          'dateend'   => 'xsd:int'),    // input parameters
    array('return' => 'xsd:array'),                 // output parameters
    'urn:WSUserInfo',                       // namespace
    'urn:WSUserInfo#DokeosWSEventsList',    // soapaction
    'rpc',                                      // style
    'encoded',                                  // use
    'This service returns a list of courses the given user is subscribed to directly'      // documentation
);

/**
 * Get a list of events between two dates for the given username
 * Function registered as service. Returns strings in UTF-8.
 * @param string Username
 * @param string User's API key (the user's API key)
 * @param int    Start date, in YYYYMMDD format
 * @param int    End date, in YYYYMMDD format
 * @return array Courses list (code=>[title=>'title',url='http://...',teacher=>'...',language=>''],code=>[...],...)
 */
function DokeosWSEventsList($username,$signature,$datestart=0,$dateend=0) {
    
    if (empty($username) or empty($signature)) { return -1; }

    require_once (api_get_path(LIBRARY_PATH).'course.lib.php');
    require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
    global $_configuration;
    
    $info = api_get_user_info_from_username($username);
    $user_id = $info['user_id'];
    $list = UserManager::get_api_keys($user_id,'dokeos');
    $key = $list[0];
    
    $local_key = sha1($username.$key);

    if (!api_is_valid_secret_key($signature, $local_key)) {
        return -1; //secret key is incorrect
    }    
    
    // libraries
    require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
    $charset = api_get_setting('platform_charset');
    
    $events_list = array();
    
    $user_id = UserManager::get_user_id_from_username($username);
    if ($user_id === false) { return $events_list; } //error in user id recovery
    require_once '../calendar/myagenda.inc.php';
    $ds = substr($datestart,0,4).'-'.substr($datestart,4,2).'-'.substr($datestart,6,2).' 00:00:00';
    $de = substr($dateend,0,4).'-'.substr($dateend,4,2).'-'.substr($dateend,6,2).' 00:00:00';
    $events_list = get_personal_agenda_items_between_dates($user_id, $ds, $de);
    foreach ( $events_list as $i => $event ) {
        $events_list[$i]['title'] = mb_convert_encoding($event['title'],'UTF-8',$charset);
        $events_list[$i]['coursetitle'] = mb_convert_encoding($event['coursetitle'],'UTF-8',$charset);
    }
    return $events_list;
}
// Use the request to (try to) invoke the service
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);