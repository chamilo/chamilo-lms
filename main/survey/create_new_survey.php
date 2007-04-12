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
* 	@author unknown, the initial survey that did not make it in 1.8 because of bad code
* 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup, refactoring and rewriting large parts (if not all) of the code
* 	@version $Id: create_new_survey.php 11997 2007-04-12 19:18:32Z pcool $
*
* 	@todo only the available platform languages should be used => need an api get_languages and and api_get_available_languages (or a parameter)
*/

// name of the language file that needs to be included
$language_file = 'survey';

// including the global dokeos file
require_once ('../inc/global.inc.php');

// including additional libraries
/** @todo check if these are all needed */
/** @todo check if the starting / is needed. api_get_path probably ends with an / */
//require_once (api_get_path(LIBRARY_PATH)."/survey.lib.php");
require_once('survey.lib.php');
require_once (api_get_path(LIBRARY_PATH).'/fileManage.lib.php');
require_once (api_get_path(CONFIGURATION_PATH) ."/add_course.conf.php");
require_once (api_get_path(LIBRARY_PATH)."/add_course.lib.inc.php");
require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/groupmanager.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/usermanager.lib.php");
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

// Database table definitions
$table_survey 				= Database :: get_course_table(TABLE_SURVEY);
$table_user 				= Database :: get_main_table(TABLE_MAIN_USER);
$table_course 				= Database :: get_main_table(TABLE_MAIN_COURSE);
$table_course_survey_rel 	= Database :: get_main_table(TABLE_MAIN_COURSE_SURVEY);

/** @todo this has to be moved to a more appropriate place (after the display_header of the code)*/
if (!api_is_allowed_to_edit())
{
	Display :: display_header();
	Display :: display_error_message(get_lang('NotAllowedHere'), false);
	Display :: display_footer();
	exit;
}

// getting the survey information
$survey_data = survey_manager::get_survey($_GET['survey_id']);
$urlname = substr(strip_tags($survey_data['title']), 0, 40);
if (strlen(strip_tags($survey_data['title'])) > 40)
{
	$urlname .= '...';
}

// breadcrumbs
if ($_GET['action'] == 'add')
{
	$interbreadcrumb[] = array ("url" => "survey_list.php", "name" => get_lang('SurveyList'));
	$tool_name = get_lang('CreateNewSurvey');
}
if ($_GET['action'] == 'edit' AND is_numeric($_GET['survey_id']))
{
	$interbreadcrumb[] = array ("url" => "survey_list.php", "name" => get_lang('SurveyList'));
	$interbreadcrumb[] = array ("url" => "survey.php?survey_id=".$_GET['survey_id'], "name" => $urlname);
	$tool_name = get_lang('EditSurvey');
}

// getting the default values
if ($_GET['action'] == 'edit' AND isset($_GET['survey_id']) AND is_numeric($_GET['survey_id']))
{
	$defaults = $survey_data;
	$defaults['survey_id'] = $_GET['survey_id'];
	$defaults['survey_share'] = array();
	$defaults['survey_share']['survey_share'] = $survey_data['survey_share'];
	if (!is_numeric($survey_data['survey_share']) OR $survey_data['survey_share'] == 0)
	{
		$form_share_value = 'true';
	}
	else
	{
		$form_share_value = $defaults['survey_share']['survey_share'];
	}
}
else
{
	$defaults['survey_language'] = $_course['language'];
	$defaults['start_date'] = date('d-F-Y H:i');
	$startdateandxdays = time() + 864000; // today + 10 days
	$defaults['end_date'] = date('d-F-Y H:i', $startdateandxdays);
	$defaults['survey_share']['survey_share'] = 0;
	$form_share_value = 1;
}

// initiate the object
$form = new FormValidator('survey', 'post', $_SERVER['PHP_SELF'].'?action='.$_GET['action'].'&survey_id='.$_GET['survey_id']);

// settting the form elements
if ($_GET['action'] == 'edit' AND isset($_GET['survey_id']) AND is_numeric($_GET['survey_id']))
{
	$form->addElement('hidden', 'survey_id');
}
$form->addElement('text', 'survey_code', get_lang('SurveyCode'), array('size' => '40'));
$fck_attribute['Width'] = '100%';
$fck_attribute['Height'] = '100';
$fck_attribute['ToolbarSet'] = 'Survey';
$form->addElement('html_editor', 'survey_title', get_lang('SurveyTitle'));
$form->addElement('html_editor', 'survey_subtitle', get_lang('SurveySubTitle'));
$lang_array = api_get_languages();
foreach ($lang_array['name'] as $key=>$value)
{
	$languages[$lang_array['folder'][$key]] = $value;
}
$form->addElement('select', 'survey_language', get_lang('Language'), $languages);
$form->addElement('datepicker', 'start_date', get_lang('StartDate'), array('form_name'=>'survey'));
$form->addElement('datepicker', 'end_date', get_lang('EndDate'), array('form_name'=>'survey'));
$group='';
$group[] =& HTML_QuickForm::createElement('radio', 'survey_share',null, get_lang('Yes'),$form_share_value);
/** @todo maybe it is better to change this into false instead see line 95 in survey.lib.php */
$group[] =& HTML_QuickForm::createElement('radio', 'survey_share',null, get_lang('No'),0);
$fck_attribute['Height'] = '200';
$form->addGroup($group, 'survey_share', get_lang('ShareSurvey'), '&nbsp;');
$form->addElement('html_editor', 'survey_introduction', get_lang('SurveyIntroduction'));
$form->addElement('html_editor', 'survey_thanks', get_lang('SurveyThanks'));
$form->addElement('submit', 'submit_survey', get_lang('Ok'));

// setting the rules
$form->addRule('survey_code', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('survey_title', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('start_date', get_lang('InvalidDate'), 'date');
$form->addRule('end_date', get_lang('InvalidDate'), 'date');
$form->addRule(array ('start_date', 'end_date'), get_lang('StartDateShouldBeBeforeEndDate'), 'date_compare', 'lte');

// setting the default values
$form->setDefaults($defaults);

// The validation or display
if( $form->validate() )
{
	// exporting the values
	$values = $form->exportValues();
	// storing the survey
	$return = survey_manager::store_survey($values);
	// deleting the shared survey if the survey is getting unshared (this only happens when editing)
	if (is_numeric($survey_data['survey_share']) AND $values['survey_share']['survey_share'] == 0 AND $values['survey_id']<>'')
	{
		survey_manager::delete_survey($survey_data['survey_share'], true);
	}
	// storing the already existing questions and options of a survey that gets shared (this only happens when editing)
	if ($survey_data['survey_share']== 0 AND $values['survey_share']['survey_share'] !== 0 AND $values['survey_id']<>'')
	{
		survey_manager::get_complete_survey_structure($return['id']);
	}

	if ($config['survey']['debug'])
	{
		// displaying a feedback message
   		Display::display_confirmation_message($return['message'], false);
	}
	else
	{
   		// redirecting to the survey page (whilst showing the return message
   		header('location:survey.php?survey_id='.$return['id'].'&message='.$return['message']);
	}
}
else
{
	// Displaying the header
	Display::display_header($tool_name);

	// Displaying the tool title
	//api_display_tool_title($tool_name);

	// display the form
	$form->display();
}

// Footer
Display :: display_footer();
?>