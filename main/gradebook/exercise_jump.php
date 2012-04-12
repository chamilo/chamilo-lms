<?php
/* For licensing terms, see /license.txt */
/**
 * Sets needed course variables and then jumps to the exercises result page.
 * This intermediate page is needed because the user is not inside a course
 * when visiting the gradebook, and several course scripts rely on these
 * variables.
 * Most code here is ripped from /main/course_home/course_home.php
 * @author Bert Steppé
 * @package chamilo.gradebook
 */
/**
 * Init
 */

require_once '../inc/global.inc.php';
api_block_anonymous_users();
$this_section=SECTION_COURSES;

$course_code = api_get_course_id();
$course_info = Database::get_course_info($course_code);
$course_title = $course_info['title'];
$course_code = $return_result['code'];
$gradebook = Security::remove_XSS($_GET['gradebook']);

$session_id = api_get_session_id();

$cidReq = Security::remove_XSS($_GET['cidReq']);

$_course['name'] = $course_title;
$_course['official_code'] = $course_code;

if (isset($_GET['doexercise'])) {
	header('Location: ../exercice/overview.php?session_id='.$session_id.'&cidReq='.$cidReq.'&gradebook='.$gradebook.'&origin=&learnpath_id=&learnpath_item_id=&exerciseId='.intval($_GET['doexercise']));
	exit;
} else {
	if (isset($_GET['gradebook'])) {
		$add_url = '&gradebook=view&exerciseId='.intval($_GET['exerciseId']);
	}
	header('Location: ../exercice/exercice.php??session_id='.$session_id.'&cidReq='.Security::remove_XSS($cidReq).'&show=result'.$add_url);
	exit;
}
