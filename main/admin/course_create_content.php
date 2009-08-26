<?php
// $Id: course_create_content.php 10811 2007-01-22 08:26:40Z elixir_julian $
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Bart Mollet (bart.mollet@hogent.be)
	
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
 * ==============================================================================
 * This script allows a platform admin to add dummy content to a course.
 * 
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package dokeos.admin
 * ==============================================================================
 */
/*
==============================================================================
		INIT SECTION
==============================================================================
*/ 

// name of the language file that needs to be included 
$language_file = 'admin';
include ('../inc/global.inc.php');
$this_section=SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
$tool_name = get_lang('DummyCourseCreator');
$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
Display::display_header($tool_name);
//api_display_tool_title($tool_name);
if(api_get_setting('server_type') != 'test')
{
	echo get_lang('DummyCourseOnlyOnTestServer');	
}
elseif( isset($_POST['action']))
{
	require_once('../coursecopy/classes/DummyCourseCreator.class.php');
	$dcc = new DummyCourseCreator();
	$dcc->create_dummy_course($_POST['course_code']);
	echo get_lang('Done');
}
else
{
	echo get_lang('DummyCourseDescription');
	echo '<form method="post"><input type="hidden" name="course_code" value="'.$_GET['course_code'].'"/><input type="submit" name="action" value="'.get_lang('Ok').'"/></form>';
}
/*
==============================================================================
		FOOTER 
==============================================================================
*/
Display::display_footer();
?>