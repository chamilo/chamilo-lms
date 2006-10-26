<?php
// $Id: user_information.php 9246 2006-09-25 13:24:53Z bmol $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert

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
	@author Bart Mollet
*	@package dokeos.admin
============================================================================== 
*/
/*
==============================================================================
		INIT SECTION
==============================================================================
*/
$langFile = 'admin';
$cidReset = true;
require ('../inc/global.inc.php');
$this_section=SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
require_once(api_get_path(LIBRARY_PATH).'course.lib.php');
//$interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ("url" => 'user_list.php', "name" => get_lang('UserList'));
if( ! isset($_GET['user_id']))
{
	api_not_allowed();	
}
$user = api_get_user_info($_GET['user_id']);
$tool_name = $user['firstName'].' '.$user['lastName'].(empty($user['official_code'])?'':' ('.$user['official_code'].')');
Display::display_header($tool_name);
$table_course_user = Database :: get_main_table(MAIN_COURSE_USER_TABLE);
$table_course = Database :: get_main_table(MAIN_COURSE_TABLE);
if( isset($_GET['action']) )
{
	switch($_GET['action'])
	{
		case 'unsubscribe':
			if( CourseManager::get_user_in_course_status($_GET['user_id'],$_GET['course_code']) == STUDENT)
			{
				CourseManager::unsubscribe_user($_GET['user_id'],$_GET['course_code']);
				Display::display_normal_message('UserUnsubscribed');
			}
			else
			{
				Display::display_error_message('CannotUnsubscribeUserFromCourse');	
			}
			break;	
	}	
}
api_display_tool_title($tool_name);
if ($user['picture_uri'] != '')
{
	echo '<p><img src="'.api_get_path(WEB_CODE_PATH).'upload/users/'.$user['picture_uri'].'" style="width:150px;"/></p>';
}
echo '<p>'. ($user['status'] == 1 ? get_lang('Teacher') : get_lang('Student')).'</p>';
echo '<p>'.Display :: encrypted_mailto_link($user['mail'], $user['mail']).'</p>';
/**
 * Show the courses in which this user is subscribed
 */
$sql = 'SELECT * FROM '.$table_course_user.' cu, '.$table_course.' c WHERE cu.user_id = '.$user['user_id'].' AND cu.course_code = c.code';
$res = api_sql_query($sql,__FILE__,__LINE__);
if (mysql_num_rows($res) > 0)
{
	$header[] = array (get_lang('Code'), true);
	$header[] = array (get_lang('Title'), true);
	$header[] = array (get_lang('Status'), true);
	$header[] = array ('', false);
	$data = array ();
	while ($course = mysql_fetch_object($res))
	{
		$row = array ();
		$row[] = $course->code;
		$row[] = $course->title;
		$row[] = $course->status == STUDENT ? get_lang('Student') : get_lang('Teacher');
		$tools = '<a href="course_information.php?code='.$course->code.'"><img src="../img/info_small.gif" border="0" style="vertical-align: middle" /></a>'.
				'<a href="'.api_get_path(WEB_COURSE_PATH).$course->directory.'"><img src="../img/home_small.gif" border="0" style="vertical-align: middle" /></a>' .
				'<a href="course_edit.php?course_code='.$course->code.'"><img src="../img/edit.gif" border="0" style="vertical-align: middle" title="'.get_lang('Edit').'" alt="'.get_lang('Edit').'"/></a>';
		if( $course->status == STUDENT )
		{
			$tools .= '<a href="user_information.php?action=unsubscribe&course_code='.$course->code.'&user_id='.$user['user_id'].'"><img src="../img/delete.gif"/></a>';
				
		}
		$row[] = $tools;
		$data[] = $row;
	}
	echo '<p><b>'.get_lang('Courses').'</b></p>';
	echo '<blockquote>';
	Display :: display_sortable_table($header, $data, array (), array (), array ('user_id' => $_GET['user_id']));
	echo '</blockquote>';
}
else
{
	echo '<p>'.get_lang('NoCoursesForThisUser').'</p>';
}
/**
 * Show the classes in which this user is subscribed
 */
$table_class_user = Database :: get_main_table(MAIN_CLASS_USER_TABLE);
$table_class = Database :: get_main_table(MAIN_CLASS_TABLE);
$sql = 'SELECT * FROM '.$table_class_user.' cu, '.$table_class.' c WHERE cu.user_id = '.$user['user_id'].' AND cu.class_id = c.id';
$res = api_sql_query($sql,__FILE__,__LINE__);
if (mysql_num_rows($res) > 0)
{
	$header = array();
	$header[] = array (get_lang('ClassName'), true);
	$header[] = array ('', false);
	$data = array ();
	while ($class = mysql_fetch_object($res))
	{
		$row = array();
		$row[] = $class->name;
		$row[] = '<a href="class_information.php?id='.$class->id.'"><img src="../img/info_small.gif" border="0" style="vertical-align: middle" /></a>';
		$data[] = $row;
	}
	echo '<p><b>'.get_lang('Classes').'</b></p>';
	echo '<blockquote>';
	Display :: display_sortable_table($header, $data, array (), array (), array ('user_id' => $_GET['user_id']));
	echo '</blockquote>';
}
else
{
	echo '<p>'.get_lang('NoClassesForThisUser').'</p>';
}
/*
==============================================================================
		FOOTER 
==============================================================================
*/ 
Display::display_footer();
?> 

