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

require_once '../inc/global.inc.php';

/**
 * Get a list of courses (code, url, title, teacher, language) and return to caller
 * Function registered as service. Returns strings in UTF-8.
 * @param string Security key (the Dokeos install's API key)
 * @param mixed  Array or string. Type of visibility of course (public, public-registered, private, closed)
 * @return array Courses list (code=>[title=>'title',url='http://...',teacher=>'...',language=>''],code=>[...],...)
 */
function courses_list($security_key, $visibilities = 'public') {

	global $_configuration;

   	// Check if this script is launch by server and if security key is ok.
   	if ($security_key != $_configuration['security_key']) {
   		return array('error_msg' => 'Security check failed');
   	}

   	// Libraries
	require_once api_get_path(LIBRARY_PATH).'course.lib.php';

	$charset = api_get_setting('platform_charset');
	$vis = array('public' => '3', 'public-registered' => '2', 'private' => '1', 'closed' => '0');

	$courses_list = array();

	if (!is_array($visibilities)) {
		$tmp = $visibilities;
		$visibilities = array($tmp);
	}
	foreach ($visibilities as $visibility) {
		if (!in_array($visibility, array_keys($vis))) {
   			return array('error_msg' => 'Security check failed');
		}
		$courses_list_tmp = CourseManager::get_courses_list(null, null, null, null, $vis[$visibility]);
		foreach ($courses_list_tmp as $index => $course) {
			$course_info = CourseManager::get_course_information($course['code']);
			$courses_list[$course['code']] = array('title' => api_convert_encoding($course_info['title'], 'UTF-8', $charset), 'url' => api_get_path(WEB_COURSE_PATH).$course_info['directory'].'/', 'teacher' => api_convert_encoding($course_info['tutor_name'], 'UTF-8', $charset), 'language' => $course_info['course_language']);
		}
	}
	return $courses_list;
}

header('Content-Type: text/xml; charset=utf-8');
echo '<?xml version="1.0"?>';
echo '<courseslist>';

if (empty($_POST['security-key']) || empty($_POST['visibility'])) {
	echo '<errormsg>Invalid parameters, this script expects a security-key and a visibility parameters</errormsg>';
} else {
	$courses_list = courses_list($_POST['security-key'], $_POST['visibility']);
	foreach ($courses_list as $code => $cd) {
		echo '<course>';
		echo '<code>' , $code , '</code>';
		echo '<title>' , $cd['title'] , '</title>';
		echo '<url>' , $cd['url'] , '</url>';
		echo '<teacher>' , $cd['teacher'] , '</teacher>';
		echo '<language>' , $cd['language'] , '</language>';
		echo '</course>';
	}
}
echo '</courseslist>';
