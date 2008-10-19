<?php //$id: $
/**
 * See license terms in /dokeos_license.txt
 * @author Yannick Warnier <yannick.warnier@dokeos.com>
 */
require_once('../../inc/global.inc.php');
require_once(api_get_path(LIBRARY_PATH).'nusoap/nusoap.php');

/**
 * Get a list of courses (code, url, title, teacher, language) and return to caller
 * Function registered as service. Returns strings in UTF-8.
 * @param string Security key (the Dokeos install's API key)
 * @param string Type of visibility of course (public, public-registered, private, closed)
 * @return array Courses list (code=>[title=>'title',url='http://...',teacher=>'...',language=>''],code=>[...],...)
 */
function courses_list($security_key,$visibility='public') {
	
	global $_configuration;
   	// check if this script is launch by server and if security key is ok
   	if ( $security_key != $_configuration['security_key'] )
   	{
   		return array('error_msg'=>'Security check failed');
   	}
   	
   	
   	// libraries
	require_once (api_get_path(LIBRARY_PATH).'course.lib.php');
	$charset = api_get_setting('platform_charset');
	$vis = array('public'=>'3', 'public-registered'=>'2', 'private'=>'1', 'closed'=>'0');
	if (!in_array($visibility,array_keys($vis))) {
   		return array('error_msg'=>'Security check failed');
	}
	
	$courses_list = array();
	
	$courses_list_tmp = CourseManager::get_courses_list(null,null,null,null,$vis[$visibility]);
	foreach ( $courses_list_tmp as $index => $course )
	{
		$course_info = CourseManager::get_course_information($course['code']);
		$courses_list[$course['code']] = array('title'=>mb_convert_encoding($course_info['title'],'UTF-8',$charset),'url'=>api_get_path(WEB_COURSE_PATH).$course_info['directory'].'/','teacher'=>mb_convert_encoding($course_info['tutor_name'],'UTF-8',$charset),'language'=>$course_info['course_language']);
	}
	
	return $courses_list;
}

$server = new soap_server(); 
// Initialize WSDL support
$server->configureWSDL('courses-list', 'urn:courses-list');
// Register the method to expose
$server->register('courses_list',                // method name
    array('security-key' => 'xsd:string', 'visibility' => 'xsd:string'),        // input parameters
    array('courses-list' => 'xsd:array'),      // output parameters
    'urn:dokeos',                      // namespace
    'urn:dokeos#courses-list',                // soapaction
    'rpc',                                // style
    'encoded',                            // use
    'Returns a list of courses of the given visibility'            // documentation
);

$server->register('courses_list'); 

$http_request = (isset($HTTP_RAW_POST_DATA)?$HTTP_RAW_POST_DATA:''); 
$server->service($http_request);