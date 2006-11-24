<?php

// $Id: course_edit.php 10191 2006-11-24 08:09:14Z pcool $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert
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
include ('../inc/global.inc.php');
$this_section=SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
include (api_get_path(LIBRARY_PATH).'fileManage.lib.php');
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
$course_table = Database::get_main_table(TABLE_MAIN_COURSE);
$course_code = isset($_GET['course_code']) ? $_GET['course_code'] : $_POST['code'];
$noPHP_SELF = true;
$tool_name = get_lang('ModifyCourseInfo');
//$interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ("url" => "course_list.php", "name" => get_lang('AdminCourses'));
/*
-----------------------------------------------------------
	Libraries
-----------------------------------------------------------
*/
/*
==============================================================================
		FUNCTIONS
==============================================================================
*/

/*
==============================================================================
		MAIN CODE
==============================================================================
*/
// Get all course categories
$table_course_category = Database :: get_main_table(TABLE_MAIN_CATEGORY);
$sql = "SELECT code,name FROM ".$table_course_category." WHERE auth_course_child ='TRUE' ORDER BY tree_pos";
$res = api_sql_query($sql, __FILE__, __LINE__);
while ($cat = mysql_fetch_array($res))
{
	$categories[$cat['code']] = '('.$cat['code'].') '.$cat['name'];
}

// Build the form
$form = new FormValidator('update_course');
$form->addElement('hidden','code',$course_code);
$form->add_textfield('visual_code', get_lang('CourseCode'));
$form->applyFilter('visual_code','strtoupper');
$form->add_textfield('tutor_name', get_lang('CourseTitular'));
$form->add_textfield( 'title', get_lang('Title'),true, array ('size' => '60'));
$form->addElement('select', 'category_code', get_lang('CourseFaculty'), $categories);
$form->add_textfield( 'department_name', get_lang('CourseDepartment'), false,array ('size' => '60'));
$form->add_textfield( 'department_url', get_lang('CourseDepartmentURL'),false, array ('size' => '60'));
$form->addElement('select_language', 'course_language', get_lang('CourseLanguage'));
$form->addElement('radio', 'visibility', get_lang("CourseAccess"), get_lang('OpenToTheWorld'), COURSE_VISIBILITY_OPEN_WORLD);
$form->addElement('radio', 'visibility', null, get_lang('OpenToThePlatform'), COURSE_VISIBILITY_OPEN_PLATFORM);
$form->addElement('radio', 'visibility', null, get_lang('Private'), COURSE_VISIBILITY_REGISTERED);
$form->addElement('radio', 'visibility', null, get_lang('CourseVisibilityClosed'), COURSE_VISIBILITY_CLOSED);
$form->addElement('radio', 'subscribe', get_lang('Subscription'), get_lang('Allowed'), 1);
$form->addElement('radio', 'subscribe', null, get_lang('Denied'), 0);
$form->addElement('radio', 'unsubscribe', get_lang('Unsubscription'), get_lang('AllowedToUnsubscribe'), 1);
$form->addElement('radio', 'unsubscribe', null, get_lang('NotAllowedToUnsubscribe'), 0);
$form->addElement('text','disk_quota',get_lang('CourseQuota'));
$form->addRule('disk_quota', get_lang('ThisFieldIsRequired'),'required');
$form->addRule('disk_quota',get_lang('ThisFieldShouldBeNumeric'),'numeric');
$form->addElement('submit', null, get_lang('Ok'));
// Set some default values
$sql = "SELECT * FROM $course_table WHERE code='".mysql_real_escape_string($course_code)."'";
$result = api_sql_query($sql, __FILE__, __LINE__);
if (mysql_num_rows($result) != 1)
{
	header('Location: course_list.php');
	exit ();
}
$course = mysql_fetch_array($result,MYSQL_ASSOC);
$course_db_name = $course['db_name'];
$form->setDefaults($course);
// Validate form
if( $form->validate())
{
	$course = $form->exportValues();
	$dbName = $_POST['dbName'];
	$course_code = $course['code'];
	$visual_code = $course['visual_code'];
	$tutor_name = $course['tutor_name'];
	$title = $course['title'];
	$category_code = $course['category_code'];
	$department_name = $course['department_name'];
	$department_url = $course['department_url'];
	$course_language = $course['course_language'];
	$disk_quota = $course['disk_quota'];
	$visibility = $course['visibility'];
	$subscribe = $course['subscribe'];
	$unsubscribe = $course['unsubscribe'];
	if (!stristr($department_url, 'http://'))
	{
		$department_url = 'http://'.$department_url;
	}
	$sql = "UPDATE $course_table SET course_language='".mysql_real_escape_string($course_language)."',
								title='".mysql_real_escape_string($title)."',
								category_code='".mysql_real_escape_string($category_code)."',
								tutor_name='".mysql_real_escape_string($tutor_name)."',
								visual_code='".mysql_real_escape_string($visual_code)."',
								department_name='".mysql_real_escape_string($department_name)."',
								department_url='".mysql_real_escape_string($department_url)."',
								disk_quota='".mysql_real_escape_string($disk_quota)."',
								visibility = '".mysql_real_escape_string($visibility)."', 
								subscribe = '".mysql_real_escape_string($subscribe)."',
								unsubscribe='".mysql_real_escape_string($unsubscribe)."'
							WHERE code='".mysql_real_escape_string($course_code)."'";
	api_sql_query($sql, __FILE__, __LINE__);
	$forum_config_table = Database::get_course_table(TOOL_FORUM_CONFIG_TABLE,$course_db_name);
	$sql = "UPDATE ".$forum_config_table." SET default_lang='".mysql_real_escape_string($course_language)."'";
	header('Location: course_list.php');
	exit ();
}
Display::display_header($tool_name);
//api_display_tool_title($tool_name);
// Display the form
$form->display();
/*
==============================================================================
		FOOTER 
==============================================================================
*/
Display :: display_footer();
?>