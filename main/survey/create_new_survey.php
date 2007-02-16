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
* 	@version $Id: create_new_survey.php 11134 2007-02-16 14:39:59Z pcool $
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
	$survey_data = survey_manager::get_survey($_GET['survey_id']);
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
   $return = survey_manager::store_survey($values);
   Display::display_confirmation_message($return['message']);
}
else
{
	$form->display();
}

// Footer
Display :: display_footer();
?>