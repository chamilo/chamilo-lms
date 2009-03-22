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
$server->configureWSDL('WSCourseList', 'urn:WSCourseList');

/* Register DokeosWSCourseList function */
// Register the data structures used by the service

$server->wsdl->addComplexType(
    'courseList',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'username' => array('name' => 'username', 'type' => 'xsd:string'),          
        'signature' => array('name' => 'signature', 'type' => 'xsd:string'),          
        'visibilities' => array('name' => 'visibilities', 'type' => 'xsd:string'),
    )
);

// Register the method to expose
$server->register('DokeosWSCourseList',         // method name
    array('username' => 'xsd:string',
          'signature' => 'xsd:string',
          'visibilities' => 'xsd:string'),    // input parameters
    array('return' => 'xsd:array'),            // output parameters
    'urn:WSCourseList',                         // namespace
    'urn:WSCourseList#DokeosWSCourseList',      // soapaction
    'rpc',                                      // style
    'encoded',                                  // use
    'This service returns a list of courses'    // documentation
);


/**
 * Get a list of courses (code, url, title, teacher, language) and return to caller
 * Function registered as service. Returns strings in UTF-8.
 * @param string User name in Dokeos
 * @param string Signature (composed of the sha1(username+apikey)
 * @param mixed  Array or string. Type of visibility of course (public, public-registered, private, closed)
 * @return array Courses list (code=>[title=>'title',url='http://...',teacher=>'...',language=>''],code=>[...],...)
 */
function DokeosWSCourseList($username, $signature, $visibilities='public') {
	if (empty($username) or empty($signature)) { return -1; }

    require_once (api_get_path(LIBRARY_PATH).'course.lib.php');
    require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
    global $_configuration;
    
    $info = api_get_user_info_from_username($username);
    $user_id = $info['user_id'];
    if (!UserManager::is_admin($user_id)) { return -1; }
    
    $list = UserManager::get_api_keys($user_id,'dokeos');
    $key = $list[0];
    
    $local_key = sha1($username.$key);

    if (!api_is_valid_secret_key($signature, $local_key)) {
        return -1; //secret key is incorrect
    }
    
   	
   	// libraries
	require_once (api_get_path(LIBRARY_PATH).'course.lib.php');
	$charset = api_get_setting('platform_charset');
	$vis = array('public'=>'3', 'public-registered'=>'2', 'private'=>'1', 'closed'=>'0');
	
	$courses_list = array();
	
	if (!is_array($visibilities)) {
		$visibilities = split(',',$visibilities);
	}
	foreach ($visibilities as $visibility) {
		if (!in_array($visibility,array_keys($vis))) {
   			return array('error_msg'=>'Security check failed');
		}
		$courses_list_tmp = CourseManager::get_courses_list(null,null,null,null,$vis[$visibility]);
		foreach ( $courses_list_tmp as $index => $course )
		{
			$course_info = CourseManager::get_course_information($course['code']);
			$courses_list[$course['code']] = array('title'=>mb_convert_encoding($course_info['title'],'UTF-8',$charset),'url'=>api_get_path(WEB_COURSE_PATH).$course_info['directory'].'/','teacher'=>mb_convert_encoding($course_info['tutor_name'],'UTF-8',$charset),'language'=>$course_info['course_language']);
		}
	}
	return $courses_list;
}
// Use the request to (try to) invoke the service
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);