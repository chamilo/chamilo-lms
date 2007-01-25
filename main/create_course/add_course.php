<?php
// $Id: add_course.php 10907 2007-01-25 15:42:27Z elixir_julian $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2005 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) 2005 Bart Mollet, Hogeschool Gent

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
* This script allows professors and administrative staff to create course sites.
* @author X X main author
* @author Roan Embrechts, refactoring
* @package dokeos.create_course
==============================================================================
*/
/*
==============================================================================
		INIT SECTION
==============================================================================
*/
// name of the language file that needs to be included 
$language_file = "create_course";
include ('../inc/global.inc.php');

$this_section=SECTION_COURSES;

include (api_get_path(CONFIGURATION_PATH).'add_course.conf.php');
/*
-----------------------------------------------------------
	Libraries
-----------------------------------------------------------
*/

include_once (api_get_path(LIBRARY_PATH).'add_course.lib.inc.php');
include_once (api_get_path(LIBRARY_PATH).'debug.lib.inc.php');
include_once (api_get_path(LIBRARY_PATH).'fileManage.lib.php');
include_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
include_once (api_get_path(CONFIGURATION_PATH).'course_info.conf.php');

/*
==============================================================================
		MAIN CODE
==============================================================================
*/
$tool_name = get_lang('CreateSite');
Display :: display_header($tool_name);
api_display_tool_title($tool_name);
// Check access rights
if (!api_is_allowed_to_create_course())
{
	Display :: display_normal_message(get_lang("NotAllowed"));
	Display::display_footer();
	exit;
}
// Get all course categories
$table_course_category = Database :: get_main_table(TABLE_MAIN_CATEGORY);
$sql = "SELECT code,name FROM ".$table_course_category." WHERE auth_course_child ='TRUE' ORDER BY tree_pos";
$res = api_sql_query($sql, __FILE__, __LINE__);
while ($cat = mysql_fetch_array($res))
{
	$categories[$cat['code']] = '('.$cat['code'].') '.$cat['name'];
}
// Build the form
$form = new FormValidator('add_course');
$form->add_textfield('title',get_lang('Title'),true,array('size'=>'60'));
$form->addElement('static',null,null,get_lang('Ex'));
$form->addElement('select', 'category_code', get_lang('Fac'), $categories);
$form->addElement('static',null,null, get_lang('TargetFac'));
$form->add_textfield('wanted_code', get_lang('Code'),true,array('size'=>'20','maxlength'=>20));
$form->addRule('wanted_code',get_lang('Max'),'maxlength',20);
$titular= &$form->add_textfield('tutor_name', get_lang('Professors'),true,array('size'=>'60', 'readonly'=>'true'));
$form->addElement('select_language', 'course_language', get_lang('Ln'));
$form->addElement('submit', null, get_lang('Ok'));
$form->add_progress_bar();

// Set default values
if(isset($_user["language"]) && $_user["language"]!=""){
	$values['course_language'] = $_user["language"];
}
else{
	$values['course_language'] = get_setting('platformLanguage');
}

$values['tutor_name'] = $_user['lastName']." ".$_user['firstName'];
$form->setDefaults($values);
// Validate the form
if($form->validate())
{
	$course_values = $form->exportValues();
	$wanted_code = $course_values['wanted_code'];
	$tutor_name = $course_values['tutor_name'];
	$category_code = $course_values['category_code'];
	$title = $course_values['title'];
	$course_language = $course_values['course_language'];
	$keys = define_course_keys($wanted_code, "", $_configuration['db_prefix']);
	if (sizeof($keys))
	{
		$visual_code = $keys["currentCourseCode"];
		$code = $keys["currentCourseId"];
		$db_name = $keys["currentCourseDbName"];
		$directory = $keys["currentCourseRepository"];
		$expiration_date = time() + $firstExpirationDelay;
		prepare_course_repository($directory, $code);
		update_Db_course($db_name);
		fill_course_repository($directory);
		fill_Db_course($db_name, $directory, $course_language);
		register_course($code, $visual_code, $directory, $db_name, $tutor_name, $category_code, $title, $course_language, $_user['user_id'], $expiration_date);
	}
	$message = get_lang('JustCreated');
	$message .= " <strong>".$course_values['wanted_code']."</strong>";
	$message .= "<br/><br/>";
	$message .= '<a href="'.api_get_path(WEB_PATH).'user_portal.php">'.get_lang('Enter').'</a>';
	Display :: display_normal_message($message);
}
else
{
	// Display the form
	$form->display();
	echo '<p>'.get_lang('Explanation').'</p>';
}
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display :: display_footer();
?>