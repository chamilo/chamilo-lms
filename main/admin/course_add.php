<?php
// $Id: course_add.php 11050 2007-02-02 10:48:43Z elixir_julian $
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

// name of the language file that needs to be included 
$language_file = array('admin','create_course');
$cidReset = true;
require_once ('../inc/global.inc.php');
$this_section=SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
require_once (api_get_path(LIBRARY_PATH).'fileManage.lib.php');
require_once (api_get_path(CONFIGURATION_PATH).'add_course.conf.php');
require_once (api_get_path(LIBRARY_PATH).'add_course.lib.inc.php');
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
$table_course = Database::get_main_table(TABLE_MAIN_COURSE);
$tool_name = get_lang('AddCourse');
$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));

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

// Get all possible teachers
$table_user = Database :: get_main_table(TABLE_MAIN_USER);
$sql = "SELECT user_id,lastname,firstname FROM $table_user WHERE status=1 ORDER BY lastname,firstname";
$res = api_sql_query($sql,__FILE__,__LINE__);
$teachers = array();
while($obj = mysql_fetch_object($res))
{
		$teachers[$obj->user_id] = $obj->lastname.' '.$obj->firstname;
}
// Build the form
$form = new FormValidator('update_course');
$form->add_textfield( 'visual_code', get_lang('CourseCode'),true,array('size'=>'20','maxlength'=>20));
$form->applyFilter('visual_code','strtoupper');
$form->addRule('wanted_code',get_lang('Max'),'maxlength',20);
$form->addElement('select', 'tutor_id', get_lang('CourseTitular'), $teachers);
$form->add_textfield('title', get_lang('Title'),true, array ('size' => '60'));
$form->addElement('select', 'category_code', get_lang('CourseFaculty'), $categories);
$form->add_textfield('department_name', get_lang('CourseDepartment'),false, array ('size' => '60'));
$form->add_textfield('department_url', get_lang('CourseDepartmentURL'),false, array ('size' => '60'));
$form->addElement('select_language', 'course_language', get_lang('CourseLanguage'));
$form->addElement('radio', 'visibility', get_lang("CourseAccess"), get_lang('OpenToTheWorld'), COURSE_VISIBILITY_OPEN_WORLD);
$form->addElement('radio', 'visibility', null, get_lang('OpenToThePlatform'), COURSE_VISIBILITY_OPEN_PLATFORM);
$form->addElement('radio', 'visibility', null, get_lang('Private'), COURSE_VISIBILITY_REGISTERED);
$form->addElement('radio', 'visibility', null, get_lang('CourseVisibilityClosed'), COURSE_VISIBILITY_CLOSED);
$form->addElement('radio', 'subscribe', get_lang('Subscription'), get_lang('Allowed'), 1);
$form->addElement('radio', 'subscribe', null, get_lang('Denied'), 0);
$form->addElement('radio', 'unsubscribe', get_lang('Unsubscription'), get_lang('AllowedToUnsubscribe'), 1);
$form->addElement('radio', 'unsubscribe', null, get_lang('NotAllowedToUnsubscribe'), 0);
$form->add_textfield('disk_quota',get_lang('CourseQuota'));
$form->addRule('disk_quota',get_lang('ThisFieldShouldBeNumeric'),'numeric');
$form->add_progress_bar();
$form->addElement('submit', null, get_lang('Ok'));
// Set some default values
$values['course_language'] = get_setting('platformLanguage');
$values['disk_quota'] = get_setting('default_document_quotum');
$values['visibility'] = COURSE_VISIBILITY_OPEN_PLATFORM;
$values['subscribe'] = 1;
$values['unsubscribe'] = 0;
$form->setDefaults($values);
// Validate form
if( $form->validate())
{
	$course = $form->exportValues();
	$code = $course['visual_code'];
	$tutor_name = $teachers[$course['tutor_id']];
	$teacher_id = $course['tutor_id'];
	$title = $course['title'];
	$category = $course['category_code'];
	$department_name = $course['department_name'];
	$department_url = $course['department_url'];
	$course_language = $course['course_language'];
	$disk_quota = $course['disk_quota'];
	if (!stristr($department_url, 'http://'))
	{
		$department_url = 'http://'.$department_url;
	}
	$keys = define_course_keys($code, "", $_configuration['db_prefix']);
	if (sizeof($keys))
	{
		$currentCourseCode = $keys["currentCourseCode"];
		$currentCourseId = $keys["currentCourseId"];
		$currentCourseDbName = $keys["currentCourseDbName"];
		$currentCourseRepository = $keys["currentCourseRepository"];
		$expiration_date = time() + $firstExpirationDelay;
		prepare_course_repository($currentCourseRepository, $currentCourseId);
		update_Db_course($currentCourseDbName);
		$pictures_array=fill_course_repository($currentCourseRepository);
		fill_Db_course($currentCourseDbName, $currentCourseRepository, $course_language,$pictures_array);
		register_course($currentCourseId, $currentCourseCode, $currentCourseRepository, $currentCourseDbName, $tutor_name, $category, $title, $course_language, $teacher_id, $expiration_date);
		$sql = "UPDATE $table_course SET disk_quota = '".$disk_quota."', visibility = '".mysql_real_escape_string($course['visibility'])."', subscribe = '".mysql_real_escape_string($course['subscribe'])."', unsubscribe='".mysql_real_escape_string($course['unsubscribe'])."' WHERE code = '".$currentCourseId."'";
		api_sql_query($sql,__FILE__,__LINE__);
		header('Location: course_list.php');
		exit ();
	}
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