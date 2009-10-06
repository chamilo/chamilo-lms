<?php // $Id: course_description.php 2009-08-26 14:12:48 darkvela $
/* For licensing terms, see /dokeos_license.txt */
/**
==============================================================================
*	This script lists the course description in Ajax.
*	This script is for all users in general.
*
*	@author Ronny Velasquez
*	@package dokeos.auth
==============================================================================
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/
	// name of the language file that needs to be included
	$language_file = array ('course_description');

	require_once '../inc/global.inc.php';
	require_once api_get_path(LIBRARY_PATH).'course.lib.php';
	include_once('../inc/reduced_header.inc.php');

/*
==============================================================================
		MAIN CODE
==============================================================================
*/

	function show_course_description() {
		global $charset;

		// get the name of the database course
		$database_course = CourseManager::get_name_database_course($_GET['code']);
		$tbl_course_description = Database::get_course_table(TABLE_COURSE_DESCRIPTION, $database_course);

		$sql = "SELECT * FROM $tbl_course_description ORDER BY id";
		$result = Database::query($sql, __FILE__, __LINE__);
		while ($description = Database::fetch_object($result)) {
			$descriptions[$description->id] = $description;
		}

		// function that displays the details of the course description in html
		$course_description_html = CourseManager::get_details_course_description_html($descriptions, $charset, false);
		return $course_description_html;
	}

	echo show_course_description();

?>