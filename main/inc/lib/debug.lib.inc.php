<?php
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	
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
* This is the debug library for Dokeos.
* Include/require it in your code to use its functionality.
*
* debug functions
*
* @package dokeos.library
============================================================================== 
*/

/**
 * This function displays the contend of a variable, array or object in a nicely formatted way.
 * @param $variable a variable, array or object
 * @return html code;
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version November 2006
 */
function debug($variable)
{
	echo '<pre>';
	print_r($variable);
	echo '</pre>';
}

/**
 * This function displays all the information of the dokeos $_course array
 * This array stores all the information of the current course if the user is in a course.
 * This is why this array is used to check weither the user is currently is in the course.
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version November 2006
 */
function debug_course()
{
	global $_course;
	debug($_course);
}

/**
 * This function displays all the information of the dokeos $_user array
 * This array stores all the information of the current user.
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version November 2006
 */
function debug_user()
{
	global $_user;
	debug($_user);
}

/**
 * This function displays an overview of the different path constants that can be used with the api_get_path function
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version November 2006
 */
function debug_paths()
{
	echo 'WEB_PATH :'.api_get_path(WEB_PATH).'<br />';
	echo 'SYS_PATH :'.api_get_path(SYS_PATH).'<br />';
	echo 'REL_PATH :'.api_get_path(REL_PATH).'<br />';
	echo 'WEB_COURSE_PATH :'.api_get_path(WEB_COURSE_PATH).'<br />';
	echo 'SYS_COURSE_PATH :'.api_get_path(SYS_COURSE_PATH).'<br />';
	echo 'REL_COURSE_PATH :'.api_get_path(REL_COURSE_PATH).'<br />';
	echo 'REL_CLARO_PATH :'.api_get_path(REL_CODE_PATH).'<br />';
	echo 'WEB_CODE_PATH :'.api_get_path(WEB_CODE_PATH).'<br />';
	echo 'SYS_CODE_PATH :'.api_get_path(SYS_CODE_PATH).'<br />';
	echo 'SYS_LANG_PATH :'.api_get_path(SYS_LANG_PATH).'<br />';
	echo 'WEB_IMG_PATH :'.api_get_path(WEB_IMG_PATH).'<br />';
	echo 'GARBAGE_PATH :'.api_get_path(GARBAGE_PATH).'<br />';
	echo 'PLUGIN_PATH :'.api_get_path(PLUGIN_PATH).'<br />';
	echo 'SYS_ARCHIVE_PATH :'.api_get_path(SYS_ARCHIVE_PATH).'<br />';
	echo 'INCLUDE_PATH :'.api_get_path(INCLUDE_PATH).'<br />';
	echo 'LIBRARY_PATH :'.api_get_path(LIBRARY_PATH).'<br />';
	echo 'CONFIGURATION_PATH :'.api_get_path(CONFIGURATION_PATH).'<br />';
	
}


function printVar($var, $varName = "@")
{
	GLOBAL $DEBUG;
	if ($DEBUG)
	{
		echo "<blockquote>\n";
		echo "<b>[$varName]</b>";
		echo "<hr noshade size=\"1\" style=\"color:blue\">";
		echo "<pre style=\"color:red\">\n";
		var_dump($var);
		echo "</pre>\n";
		echo "<hr noshade size=\"1\" style=\"color:blue\">";
		echo "</blockquote>\n";
	}
	else
	{
		echo "<!-- DEBUG is OFF -->";
		echo "DEBUG is OFF";
	}
}
?>