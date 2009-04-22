<?php
// $Id: add_course.php 19993 2009-04-22 20:18:15Z iflorespaz $
/* For licensing terms, see /dokeos_license.txt */
/**
==============================================================================
* This script allows professors and administrative staff to create course sites.
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @author Roan Embrechts, refactoring
* @package dokeos.create_course
==============================================================================
*/

// name of the language file that needs to be included 
$language_file = "create_course";

//delete the globals["_cid"] we don't need it here 
$cidReset = true; // Flag forcing the 'current course' reset

// including the global file
include ('../inc/global.inc.php');

// section for the tabs
$this_section=SECTION_COURSES;

// include configuration file
include (api_get_path(CONFIGURATION_PATH).'add_course.conf.php');

// include additional libraries
include_once (api_get_path(LIBRARY_PATH).'add_course.lib.inc.php');
include_once (api_get_path(LIBRARY_PATH).'course.lib.php');
include_once (api_get_path(LIBRARY_PATH).'debug.lib.inc.php');
include_once (api_get_path(LIBRARY_PATH).'fileManage.lib.php');
include_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
include_once (api_get_path(CONFIGURATION_PATH).'course_info.conf.php');

$interbreadcrumb[] = array('url'=>api_get_path(WEB_PATH).'user_portal.php', 'name'=> get_lang('MyCourses'));
// Displaying the header
$tool_name = get_lang('CreateSite');

if (api_get_setting('allow_users_to_create_courses')=='false' && !api_is_platform_admin()) {
	api_not_allowed(true);
}
Display :: display_header($tool_name);
// Displaying the tool title
echo '<div class="actions-title">';
echo $tool_name;
echo '</div>';
// Check access rights
if (!api_is_allowed_to_create_course()) {
	Display :: display_error_message(get_lang("NotAllowed"));
	Display::display_footer();
	exit;
}
// Get all course categories
$table_course_category = Database :: get_main_table(TABLE_MAIN_CATEGORY);
$table_course = Database :: get_main_table(TABLE_MAIN_COURSE);

global $_configuration;
$dbnamelength = strlen($_configuration['db_prefix']);
//Ensure the database prefix + database name do not get over 40 characters
$maxlength = 40 - $dbnamelength;

// Build the form
$categories = array();
$form = new FormValidator('add_course');
$form->add_textfield('title',get_lang('Title'),true,array('size'=>'60'));
$form->addElement('static',null,null,get_lang('Ex'));
$categories_select = $form->addElement('select', 'category_code', get_lang('Fac'), $categories);
CourseManager::select_and_sort_categories($categories_select);
$form->addElement('static',null,null, get_lang('TargetFac'));
$form->add_textfield('wanted_code', get_lang('Code'),false,array('size'=>'$maxlength','maxlength'=>$maxlength));
$form->addRule('wanted_code',get_lang('Max'),'maxlength',$maxlength);
$titular= &$form->add_textfield('tutor_name', get_lang('Professors'),true,array('size'=>'60'));
$form->addElement('select_language', 'course_language', get_lang('Ln'));
$form->addElement('style_submit_button', null, get_lang('CreateCourseArea'), 'class="add"');
$form->add_progress_bar();

// Set default values
if (isset($_user["language"]) && $_user["language"]!="") {
	$values['course_language'] = $_user["language"];
} else {
	$values['course_language'] = get_setting('platformLanguage');
}

$values['tutor_name'] = $_user['firstName']." ".$_user['lastName'];
$form->setDefaults($values);
// Validate the form
if ($form->validate()) {
	$course_values = $form->exportValues();
	$wanted_code = $course_values['wanted_code'];
	$tutor_name = $course_values['tutor_name'];
	$category_code = $course_values['category_code'];
	$title = $course_values['title'];
	$course_language = $course_values['course_language'];
	
	if (trim($wanted_code) == '') {
		$wanted_code = generate_course_code(substr($title,0,$maxlength));
	}
	
	$keys = define_course_keys($wanted_code, "", $_configuration['db_prefix']);
	
	$sql_check = sprintf('SELECT * FROM '.$table_course.' WHERE visual_code = "%s"',Database :: escape_string($wanted_code));
	$result_check = api_sql_query($sql_check,__FILE__,__LINE__); //I don't know why this api function doesn't work...
	if ( Database::num_rows($result_check)<1 ) {
		if (sizeof($keys)) {
			$visual_code = $keys["currentCourseCode"];
			$code = $keys["currentCourseId"];
			$db_name = $keys["currentCourseDbName"];
			$directory = $keys["currentCourseRepository"];
			$expiration_date = time() + $firstExpirationDelay;
			prepare_course_repository($directory, $code);
			update_Db_course($db_name);
			$pictures_array=fill_course_repository($directory);
			fill_Db_course($db_name, $directory, $course_language,$pictures_array);
			register_course($code, $visual_code, $directory, $db_name, $tutor_name, $category_code, $title, $course_language, api_get_user_id(), $expiration_date);
		}
        $link = api_get_path(WEB_COURSE_PATH).$directory.'/';
		$message = get_lang('JustCreated');
		$message .= ' <a href="'.$link.'">'.$visual_code."</a>";
		$message .= "<br /><br /><br />";
		$message .= '<a class="bottom-link" href="'.api_get_path(WEB_PATH).'user_portal.php">'.get_lang('Enter').'</a>';
		Display :: display_confirmation_message($message,false);
	} else {
		Display :: display_error_message(get_lang('CourseCodeAlreadyExists'),false);
		$form->display();
		//echo '<p>'.get_lang('CourseCodeAlreadyExistExplained').'</p>';
	}
		
} else {
	// Display the form
	$form->display();
	Display::display_normal_message(get_lang('Explanation'));
}
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display :: display_footer();