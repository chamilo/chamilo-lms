<?php

/**
 * Sets needed course variables and then jumps to the exercises result page.
 * This intermediate page is needed because the user is not inside a course
 * when visiting the gradebook, and several course scripts rely on these
 * variables.
 * Most code here is ripped from /main/course_home/course_home.php
 * @author Bert Steppé
 */


$cidReq = $_GET['cid'];

include ('../inc/global.inc.php');

api_block_anonymous_users();

$this_section=SECTION_COURSES;

include_once (api_get_path(LIBRARY_PATH).'course.lib.php');

$course_code = $_course['sysCode'];
$course_info = Database::get_course_info($course_code);
$return_result = CourseManager::determine_course_title_from_course_info($_user['user_id'], $course_info);
$course_title = $return_result['title'];
$course_code = $return_result['code'];

$dbname = $course_info['db_name'];

$_course['name'] = $course_title;
$_course['official_code'] = $course_code;

if (isset($_GET['doexercise']))
{
	header('Location: ../exercice/exercice_submit.php?cidReq='.$cidReq.'&origin=&learnpath_id=&learnpath_item_id=&exerciseId='.$_GET['doexercise']);
	exit;
}
else
{
	header('Location: ../exercice/exercice.php?show=result');
	exit;
}

?>
