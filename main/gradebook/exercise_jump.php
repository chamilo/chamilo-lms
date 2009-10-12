<?php //$id $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2008 Dokeos Latinoamerica SAC
	Copyright (c) 2006 Dokeos SPRL
	Copyright (c) 2006 Ghent University (UGent)
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/**
 * Sets needed course variables and then jumps to the exercises result page.
 * This intermediate page is needed because the user is not inside a course
 * when visiting the gradebook, and several course scripts rely on these
 * variables.
 * Most code here is ripped from /main/course_home/course_home.php
 * @author Bert Steppé
 */

require_once ('../inc/global.inc.php');
api_block_anonymous_users();
$this_section=SECTION_COURSES;

require_once (api_get_path(LIBRARY_PATH).'course.lib.php');

$course_code = api_get_course_id();
$course_info = Database::get_course_info($course_code);
$course_title = $course_info['title'];
$course_code = $return_result['code'];
$gradebook=Security::remove_XSS($_GET['gradebook']);

$dbname = $course_info['db_name'];

$_course['name'] = $course_title;
$_course['official_code'] = $course_code;

if (isset($_GET['doexercise'])) {
	header('Location: ../exercice/exercice_submit.php?cidReq='.$cidReq.'&gradebook='.$gradebook.'&origin=&learnpath_id=&learnpath_item_id=&exerciseId='.Security::remove_XSS($_GET['doexercise']));
	exit;
} else {
	if (isset($_GET['gradebook'])) {
		$add_url = '&gradebook=view&exerciseId='.Security::remove_XSS((int)$_GET['exerciseId']);
	}
	header('Location: ../exercice/exercice.php?cidReq='.Security::remove_XSS($cidReq).'&show=result'.$add_url);
	exit;
}