<?php
/*
    DOKEOS - elearning and course management software

    For a full list of contributors, see documentation/credits.html
   
    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.
    See "documentation/licence.html" more details.
 
    Contact: 
		Dokeos
		Rue des Palais 44 Paleizenstraat
		B-1030 Brussels - Belgium
		Tel. +32 (2) 211 34 56
*/

/**
*	@package dokeos.survey
* 	@author unknown
* 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup, refactoring and rewriting large parts (if not all) of the code
* 	@version $Id: create_new_survey.php 10659 2007-01-10 22:41:53Z pcool $
* 
* 	@todo rename this file to survey.php
* 	@todo try to understand the template stuff and implement it (if needed)
* 	@todo check if the code is really unique (when adding or editing)
* 	@todo only the available platform languages should be used => need an api get_languages and and api_get_available_languages (or a parameter)
*/

// name of the language file that needs to be included 
$language_file = 'survey';

// including the global dokeos file
require_once ('../inc/global.inc.php');

// including additional libraries
/** @todo check if these are all needed */
/** @todo check if the starting / is needed. api_get_path probably ends with an / */
require_once (api_get_path(LIBRARY_PATH).'/fileManage.lib.php');
require_once (api_get_path(CONFIGURATION_PATH) ."/add_course.conf.php");
require_once (api_get_path(LIBRARY_PATH)."/add_course.lib.inc.php");
require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/groupmanager.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/usermanager.lib.php");
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

// Database table definitions
/** @todo use database constants for the survey tables */
$table_survey 				= Database :: get_course_table('survey');
$table_group 				= Database :: get_course_table('survey_group');
$table_user 				= Database :: get_main_table(TABLE_MAIN_USER);
$table_course 				= Database :: get_main_table(TABLE_MAIN_COURSE);
$table_course_survey_rel 	= Database :: get_main_table(TABLE_MAIN_COURSE_SURVEY);


/** @todo replace this with the correct code */
/*
$status = surveymanager::get_status();
api_protect_course_script();
if($status==5)
{
	api_protect_admin_script();
}
*/
/** @todo this has to be moved to a more appropriate place (after the display_header of the code)*/
if (!api_is_allowed_to_edit())
{
	Display :: display_header();
	Display :: display_error_message(get_lang('NotAllowedHere'));
	Display :: display_footer();
	exit;
}

// breadcrumbs
if ($_GET['action'] == 'add')
{
	$interbreadcrumb[] = array ("url" => "survey_list.php", "name" => get_lang('Survey'));
	$tool_name = get_lang('CreateNewSurvey');
}
if ($_GET['action'] == 'edit' AND is_numeric($_GET['survey_id']))
{
	$interbreadcrumb[] = array ("url" => "survey_list.php", "name" => get_lang('Survey'));
	$tool_name = get_lang('EditSurvey');	
}

// Displaying the header
Display::display_header($tool_name);

// Displaying the tool title
//api_display_tool_title($tool_name);

// initiate the object
$form = new FormValidator('forumcategory', 'post', $_SERVER['PHP_SELF'].'?action='.$_GET['action'].'&survey_id='.$_GET['survey_id']);

// settting the form elements
if ($_GET['action'] == 'edit' AND isset($_GET['survey_id']) AND is_numeric($_GET['survey_id']))
{
	$form->addElement('hidden', 'survey_id');
}
$form->addElement('text', 'survey_code', get_lang('SurveyCode'));
$form->addElement('text', 'survey_title', get_lang('SurveyTitle'));
$form->addElement('text', 'survey_subtitle', get_lang('SurveySubTitle'));
$lang_array = api_get_languages();
foreach ($lang_array['name'] as $key=>$value) 
{
	$languages[$lang_array['folder'][$key]] = $value; 
}
$form->addElement('select', 'survey_language', get_lang('Language'), $languages);
$form->addElement('datepicker', 'start_date', get_lang('StartDate'));
$form->addElement('datepicker', 'end_date', get_lang('EndDate'));
$group='';
$group[] =& HTML_QuickForm::createElement('radio', 'survey_share',null, get_lang('Yes'),1);
$group[] =& HTML_QuickForm::createElement('radio', 'survey_share',null, get_lang('No'),0);
$form->addGroup($group, 'survey_share', get_lang('ShareSurvey'), '&nbsp;');	
$form->addElement('html_editor', 'survey_introduction', get_lang('SurveyIntroduction'));
$form->addElement('html_editor', 'survey_thanks', get_lang('SurveyThanks'));
$form->addElement('submit', 'submit_survey', get_lang('Ok'));

// setting the rules
$form->addRule('survey_code', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('survey_title', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('start_date', get_lang('InvalidDate'), 'date');
$form->addRule('end_date', get_lang('InvalidDate'), 'date');
/** @todo add a rule that checks if the end_date > start_date */

// setting the default values
if ($_GET['action'] == 'edit' AND isset($_GET['survey_id']) AND is_numeric($_GET['survey_id']))
{
	$survey_data = get_survey($_GET['survey_id']);
	$defaults = $survey_data;
	$defaults['survey_id'] = $_GET['survey_id'];
}
else 
{
	$defaults['survey_language'] = $_course['language'];
	$defaults['start_date']['d'] = date('d');
	$defaults['start_date']['F'] = date('F');
	$defaults['start_date']['Y'] = date('Y');
	$defaults['start_date']['H'] = date('H');
	$defaults['start_date']['i'] = date('i');
	$defaults['end_date']['d'] = date('d');
	$defaults['end_date']['F'] = date('F');
	$defaults['end_date']['Y'] = date('Y');
	$defaults['end_date']['H'] = date('H');
	$defaults['end_date']['i'] = date('i');
	$defaults['survey_share']['survey_share'] = 0;
}
$form->setDefaults($defaults);

// The validation or display
if( $form->validate() )
{
   $values = $form->exportValues();
   $return = store_survey($values);
   Display::display_confirmation_message($return['message']);
}
else
{
	$form->display();
}





// Footer
Display :: display_footer();

/**
 * This function stores a survey in the database
 *
 * @param array $values
 * @return array $return the type of return message that has to be displayed and the message in it
 * 
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version januari 2007
 * 
 * @todo move this function to surveymanager.inc.lib.php
 */
function store_survey($values)
{
	global $_user; 
	
	// table defnitions
	$table_survey 		= Database :: get_course_table('survey');
	$table_survey_group = Database :: get_course_table('survey_group');
	
	if (!$values['survey_id'] OR !is_numeric($values['survey_id']))
	{
		$sql = "INSERT INTO $table_survey (code, title, subtitle, author, lang, avail_from, avail_till, is_shared, template, intro, surveythanks, creation_date) VALUES (
					'".mysql_real_escape_string($values['survey_code'])."',
					'".mysql_real_escape_string($values['survey_title'])."',
					'".mysql_real_escape_string($values['survey_subtitle'])."',
					'".mysql_real_escape_string($_user['user_id'])."',
					'".mysql_real_escape_string($values['survey_language'])."',
					'".mysql_real_escape_string($values['start_date'])."',
					'".mysql_real_escape_string($values['end_date'])."',
					'".mysql_real_escape_string($values['survey_share']['survey_share'])."',
					'".mysql_real_escape_string('template')."',
					'".mysql_real_escape_string($values['survey_introduction'])."',
					'".mysql_real_escape_string($values['survey_thanks'])."',
					'".date()."')";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		$survey_id = mysql_insert_id();
		
		$sql = "INSERT INTO $table_survey_group (group_id, survey_id, groupname, introduction) VALUES (
					'', '$survey_id', '".get_lang('NoGroup')."','".get_lang('ThisIsYourDefaultGroup')."')";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		
		$return['message'] = get_lang('SurveyCreatedSuccesfully').'<br />'.get_lang('YouCanNowAddQuestionToYourSurvey').': ';
		$return['message'] .= '<a href="select_question_group.php?survey_id='.$survey_id.'">'.get_lang('ClickHere').'</a>'; 
		$return['type'] = 'confirmation';
	}
	else 
	{
		$sql = "UPDATE $table_survey SET 
						code 			= '".mysql_real_escape_string($values['survey_code'])."',
						title 			= '".mysql_real_escape_string($values['survey_title'])."',
						subtitle 		= '".mysql_real_escape_string($values['survey_subtitle'])."',
						author 			= '".mysql_real_escape_string($_user['user_id'])."',
						lang 			= '".mysql_real_escape_string($values['survey_language'])."',
						avail_from 		= '".mysql_real_escape_string($values['start_date'])."',
						avail_till		= '".mysql_real_escape_string($values['end_date'])."',
						is_shared		= '".mysql_real_escape_string($values['survey_share']['survey_share'])."',
						template 		= '".mysql_real_escape_string('template')."',
						intro			= '".mysql_real_escape_string($values['survey_introduction'])."',
						surveythanks	= '".mysql_real_escape_string($values['survey_thanks'])."' 
				WHERE survey_id = '".mysql_real_escape_string($values['survey_id'])."'";	
		$result = api_sql_query($sql, __FILE__, __LINE__);
		
		$return['message'] = get_lang('SurveyUpdatedSuccesfully').'<br />'.get_lang('YouCanNowAddQuestionToYourSurvey').': ';
		$return['message'] .= '<a href="select_question_group.php?survey_id='.$values['survey_id'].'">'.get_lang('Here').'</a>'; 
		$return['message'] .= get_lang('OrReturnToSurveyOverview').'<a href="survey_list.php">'.get_lang('Here').'</a>'; 
		$return['type'] = 'confirmation';
	}
	
	return $return;
}

/**
 * This function retrieves all the survey information 
 *
 * @param integer $survey_id the id of the survey
 * @return array
 * 
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version januari 2007
 * 
 * @todo move this function to surveymanager.inc.lib.php
 */
function get_survey($survey_id)
{
	$tbl_survey = Database :: get_course_table('survey');
	
	$sql = "SELECT * FROM $tbl_survey WHERE survey_id='".mysql_real_escape_string($survey_id)."'";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	$return = mysql_fetch_assoc($result);
	
	// we do this (temporarily) to have the array match the quickform elements immediately
	// idealiter the fields in the db match the quickform fields
	$return['survey_code'] 			= $return['code'];
	$return['survey_title'] 		= $return['title'];
	$return['survey_subtitle'] 		= $return['subtitle'];
	$return['survey_language'] 		= $return['lang'];
	$return['start_date'] 			= $return['avail_from'];
	$return['end_date'] 			= $return['avail_till'];
	$return['survey_share'] 		= $return['is_shared'];
	$return['survey_introduction'] 	= $return['intro'];
	$return['survey_thanks'] 		= $return['surveythanks'];
	return $return;
}
?>