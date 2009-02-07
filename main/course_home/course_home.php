<?php // $Id: course_home.php 18319 2009-02-07 00:03:42Z herodoto $

/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2005 Dokeos S.A.
	Copyright (c) 2003 Ghent University
	Copyright (c) 2001 Universite Catholique de Louvain
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*         HOME PAGE FOR EACH COURSE
*
*	This page, included in every course's index.php is the home
*	page. To make administration simple, the teacher edits his
*	course from the home page. Only the login detects that the
*	visitor is allowed to activate, deactivate home page links,
*	access to the teachers tools (statistics, edit forums...).
*
* Edit visibility of tools
*
*   visibility = 1 - everybody
*   visibility = 0 - course admin (teacher) and platform admin
*
* Who can change visibility ?
*
*   admin = 0 - course admin (teacher) and platform admin
*   admin = 1 - platform admin
*
* Show message to confirm that a tools must be hide from available tools
*
*   visibility 0,1
*
*
*	@package dokeos.course_home
==============================================================================
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/

// name of the language file that needs to be included
$language_file[] = "course_home";
$use_anonymous = true;
// inlcuding the global file
include('../../main/inc/global.inc.php');

if(!isset($cidReq))
{
	$cidReq = api_get_course_id(); // to provide compatibility. with previous system

	global $error_msg,$error_no;
	$classError = "init";	
	$error_no[$classError][] = "2";
	$error_level[$classError][] = "info";
	$error_msg[$classError][] = "[".__FILE__."][".__LINE__."] cidReq was Missing $cidReq take $dbname;";
}

if(isset($_SESSION['_gid'])){
	unset($_SESSION['_gid']);
}

// The section for the tabs
$this_section=SECTION_COURSES;

/*
-----------------------------------------------------------
	Libraries
-----------------------------------------------------------
*/
include_once(api_get_path(LIBRARY_PATH) . 'course.lib.php');
include_once(api_get_path(LIBRARY_PATH) . 'debug.lib.inc.php');

/*
-----------------------------------------------------------
	Constants
-----------------------------------------------------------
*/
define ("TOOL_PUBLIC", "Public");
define ("TOOL_PUBLIC_BUT_HIDDEN", "PublicButHide");
define ("TOOL_COURSE_ADMIN", "courseAdmin");
define ("TOOL_PLATFORM_ADMIN", "platformAdmin");
define ("TOOL_AUTHORING", "toolauthoring");
define ("TOOL_INTERACTION", "toolinteraction");
define ("TOOL_ADMIN", "tooladmin");
define ("TOOL_ADMIN_PLATEFORM", "tooladminplatform");
// ("TOOL_ADMIN_PLATFORM_VISIBLE", "tooladminplatformvisible");
//define ("TOOL_ADMIN_PLATFORM_INVISIBLE", "tooladminplatforminvisible");
//define ("TOOL_ADMIN_COURS_INVISIBLE", "tooladmincoursinvisible");
define ("TOOL_STUDENT_VIEW", "toolstudentview");
define ("TOOL_ADMIN_VISIBLE", "tooladminvisible");
/*

-----------------------------------------------------------
	Virtual course support code
-----------------------------------------------------------
*/
$user_id = api_get_user_id();
$course_code = $_course["sysCode"];
$course_info = Database::get_course_info($course_code);

$return_result = CourseManager::determine_course_title_from_course_info($_user['user_id'], $course_info);
$course_title = $return_result["title"];
$course_code = $return_result["code"];

$_course["name"] = $course_title;
$_course['official_code'] = $course_code;

api_session_unregister('toolgroup');

/*
-----------------------------------------------------------
	Is the user allowed here?
-----------------------------------------------------------
*/
if($is_allowed_in_course == false) 
{
	api_not_allowed(true);
}
/*
-----------------------------------------------------------
	Header
-----------------------------------------------------------
*/
Display::display_header($course_title, "Home");




/*
-----------------------------------------------------------
	STATISTICS
-----------------------------------------------------------
*/
if(!isset($coursesAlreadyVisited[$_cid]) )
{
	include(api_get_path(LIBRARY_PATH) . 'events.lib.inc.php');
	event_access_course();
	$coursesAlreadyVisited[$_cid] = 1;
	api_session_register('coursesAlreadyVisited');
}

$tool_table = Database::get_course_table(TABLE_TOOL_LIST);

$temps=time();
$reqdate="&reqdate=$temps";


/*
==============================================================================
		MAIN CODE
==============================================================================
*/
//display course title for course home page (similar to toolname for tool pages)
//echo '<h3>'.api_display_tool_title($nameTools) . '</h3>';
/*
-----------------------------------------------------------
	Introduction section
	(editable by course admins)
-----------------------------------------------------------
*/

$fck_attribute['Width'] = '100%';
$fck_attribute['Height'] = '300';
$fck_attribute['ToolbarSet'] = 'Introduction';

// A special case, some default settings do not fit. These settings should stay.
$fck_attribute['Config']['CreateDocumentWebDir'] = api_get_path('WEB_COURSE_PATH').api_get_course_path().'/document/';
$fck_attribute['Config']['CreateDocumentDir'] = 'document/';
$fck_attribute['Config']['BaseHref'] = api_get_path('WEB_COURSE_PATH').api_get_course_path().'/';

Display::display_introduction_section(TOOL_COURSE_HOMEPAGE);

$fck_attribute = null; // Clearing this global variable immediatelly after it has been used.

/*
-----------------------------------------------------------
	SWITCH TO A DIFFERENT HOMEPAGE VIEW
	the setting homepage_view is adjustable through
	the platform administration section
-----------------------------------------------------------
*/
if(get_setting('homepage_view') == "activity")
{
	include('activity.php');
}
elseif(get_setting('homepage_view') == "2column")
{
	include('2column.php');
}
elseif(get_setting('homepage_view') == "3column")
{
	include('3column.php');
}

/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
?>
