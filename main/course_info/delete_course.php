<?php
// $Id: delete_course.php 10204 2006-11-26 20:46:53Z pcool $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier brouckaert
	Copyright (c) Roan Embrechts
	Copyright (c) Bart Mollet, Hogeschool Gent

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	This script is about deleting a course.
*	It displays a message box ('are you sure you wish to delete this course')
*	and deletes the course if the user answers affirmatively
*
*	@package dokeos.course_info
==============================================================================
*/
// name of the language file that needs to be included
$language_file = 'course_info';

include ('../inc/global.inc.php');
include (api_get_path(LIBRARY_PATH).'course.lib.php');

$this_section = SECTION_COURSES;

$currentCourseCode = $_course['official_code'];
$currentCourseName = $_course['name'];

if (!api_is_allowed_to_edit())
{
	api_not_allowed();
}
$tool_name = get_lang('DelCourse');
if (isset($_GET['delete']) && $_GET['delete'] == 'yes')
{
	CourseManager :: delete_course($_course['sysCode']);
	// DELETE CONFIRMATION MESSAGE
	unset ($_course);
	unset ($_cid);
	$noPHP_SELF = true;
	$message = get_lang('Course')." &quot;".$currentCourseName."&quot; "."(".$currentCourseCode.") ".get_lang('HasDel');
	$message .=  "<br /><br /><a href=\"../../index.php\">".get_lang('BackHome')." ".get_setting('siteName')."</a>";

} // end if $delete
else
{
	$message = "&quot;".$currentCourseName."&quot; "."(".$currentCourseCode.") "."<p>".get_lang("ByDel")."</p>"."<p>"."<a href=\"infocours.php\">".get_lang("N")."</a>"."&nbsp;&nbsp;|&nbsp;&nbsp;"."<a href=\"".$_SERVER['PHP_SELF']."?delete=yes\">".get_lang("Y")."</a>"."</p>";
	$interbreadcrumb[] = array ("url" => "infocours.php", "name" => get_lang('ModifInfo'));
} 
Display :: display_header($tool_name, "Settings");
api_display_tool_title($tool_name);
Display::display_normal_message($message);
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display :: display_footer();
?>