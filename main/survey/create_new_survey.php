<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2009 Dokeos SPRL

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/

/**
*	@package dokeos.survey
* 	@author unknown, the initial survey that did not make it in 1.8 because of bad code
* 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup, refactoring and rewriting large parts (if not all) of the code
*	@author Julio Montoya Armas <gugli100@gmail.com>, Dokeos: Personality Test modification and rewriting large parts of the code
* 	@version $Id: create_new_survey.php 19829 2009-04-17 13:49:47Z pcool $
*
* 	@todo only the available platform languages should be used => need an api get_languages and and api_get_available_languages (or a parameter)
*/

// name of the language file that needs to be included
$language_file = 'survey';

// including the global dokeos file
require_once ('../inc/global.inc.php');

$htmlHeadXtra[] = '<script type="text/javascript" language="javascript">
		
		function advanced_parameters() {
			if(document.getElementById(\'options\').style.display == \'none\') {
					document.getElementById(\'options\').style.display = \'block\';
					document.getElementById(\'plus_minus\').innerHTML=\'&nbsp;<img src="../img/nolines_minus.gif" alt="" />&nbsp;'.get_lang('AdvancedParameters').'\';
			} else {
					document.getElementById(\'options\').style.display = \'none\';
					document.getElementById(\'plus_minus\').innerHTML=\'&nbsp;<img src="../img/nolines_plus.gif" alt="" />&nbsp;'.get_lang('AdvancedParameters').'\';
			}		
		}
	</script>';	

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
// if user is not teacher or if he's a coach trying to access an element out of his session
if (!api_is_allowed_to_edit())
{
	if(!api_is_course_coach() || (!empty($_GET['survey_id']) && !api_is_element_in_the_session(TOOL_SURVEY,intval($_GET['survey_id']))))
	{
		Display :: display_header();
		Display :: display_error_message(get_lang('NotAllowed'), false);
		Display :: display_footer();
		exit;
	}
}

// getting the survey information
$survey_id  = Security::remove_XSS($_GET['survey_id']);
$survey_data = survey_manager::get_survey($survey_id);

$urlname =strip_tags(substr(html_entity_decode($survey_data['title'],ENT_QUOTES,$charset), 0, 40));
if (strlen(strip_tags($survey_data['title'])) > 40) {
	$urlname .= '...';
}

// breadcrumbs
if ($_GET['action'] == 'add')
{
	$interbreadcrumb[] = array ("url" => "survey_list.php", "name" => get_lang('SurveyList'));
	$tool_name = get_lang('CreateNewSurvey');
}
if ($_GET['action'] == 'edit' && is_numeric($survey_id))
{
	$interbreadcrumb[] = array ("url" => "survey_list.php", "name" => get_lang('SurveyList'));
	$interbreadcrumb[] = array ("url" => "survey.php?survey_id=".$survey_id, "name" => strip_tags($urlname));
	$tool_name = get_lang('EditSurvey');
}

// getting the default values
if ($_GET['action'] == 'edit' AND isset($survey_id) AND is_numeric($survey_id))
{
	$defaults = $survey_data;
	$defaults['survey_id'] = $survey_id;
	/*
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
	*/
	
	$defaults['anonymous'] = $survey_data['anonymous'];
}
else
{
	$defaults['survey_language'] = $_course['language'];
	$defaults['start_date'] = date('d-F-Y H:i');
	$startdateandxdays = time() + 864000; // today + 10 days
	$defaults['end_date'] = date('d-F-Y H:i', $startdateandxdays);
	//$defaults['survey_share']['survey_share'] = 0;
	//$form_share_value = 1;
	$defaults['anonymous'] = 0;
}

// initiate the object
$form = new FormValidator('survey', 'post', api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&survey_id='.$survey_id);

$form->addElement('header', '', $tool_name);

// settting the form elements
if ($_GET['action'] == 'edit' AND isset($survey_id) AND is_numeric($survey_id))
{
	$form->addElement('hidden', 'survey_id');
}

$survey_code = $form->addElement('text', 'survey_code', get_lang('SurveyCode'), array('size' => '40'));
if ($_GET['action'] == 'edit') {
	$survey_code->freeze();
	$form->applyFilter('survey_code', 'strtoupper');
} 

$fck_attribute['Width'] = '100%';
$fck_attribute['Height'] = '200';
$fck_attribute['ToolbarSet'] = 'Survey';
$form->addElement('html_editor', 'survey_title', get_lang('SurveyTitle'));
$fck_attribute['Config']['ToolbarStartExpanded']='false';
$fck_attribute['Height'] = '100';
$form->addElement('html_editor', 'survey_subtitle', get_lang('SurveySubTitle'));
$lang_array = api_get_languages();
foreach ($lang_array['name'] as $key=>$value)
{
	$languages[$lang_array['folder'][$key]] = $value;
}
$form->addElement('select', 'survey_language', get_lang('Language'), $languages);
$form->addElement('datepickerdate', 'start_date', get_lang('StartDate'), array('form_name'=>'survey'));
$form->addElement('datepickerdate', 'end_date', get_lang('EndDate'), array('form_name'=>'survey'));

//$group='';
//$group[] =& HTML_QuickForm::createElement('radio', 'survey_share',null, get_lang('Yes'),$form_share_value);
/** TODO maybe it is better to change this into false instead see line 95 in survey.lib.php */
//$group[] =& HTML_QuickForm::createElement('radio', 'survey_share',null, get_lang('No'),0);

$fck_attribute['Height'] = '130';
//$form->addGroup($group, 'survey_share', get_lang('ShareSurvey'), '&nbsp;');
$form->addElement('checkbox', 'anonymous', get_lang('Anonymous'));
$form->addElement('html_editor', 'survey_introduction', get_lang('SurveyIntroduction'));
$form->addElement('html_editor', 'survey_thanks', get_lang('SurveyThanks'));


/*
// Aditional Parameters
$form -> addElement('html','<div class="row">
<div class="label">&nbsp;</div>
<div class="formw">
	<a href="javascript://" onclick="if(document.getElementById(\'options\').style.display == \'none\'){document.getElementById(\'options\').style.display = \'block\';}else{document.getElementById(\'options\').style.display = \'none\';}"><img src="../img/add_na.gif" alt="" />'.get_lang('AdvancedParameters').'</a>
</div>
</div>');*/

// Personality/Conditional Test Options
$surveytypes[0] = get_lang('Normal');
$surveytypes[1] = get_lang('Conditional');


if ($_GET['action'] == 'add')
{		
	$form->addElement('hidden','survey_type',0);
    $form -> addElement('html','<div id="options" style="display: none;">');		
	require_once(api_get_path(LIBRARY_PATH).'surveymanager.lib.php');
	$survey_tree = new SurveyTree();	
	$list_surveys = $survey_tree->createList($survey_tree->surveylist);	
	$list_surveys[0]=''; 
	$form->addElement('select', 'parent_id', get_lang('ParentSurvey'), $list_surveys);
	$defaults['parent_id']=0;
} 

if ($survey_data['survey_type']==1 || $_GET['action'] == 'add' )
{
	$form->addElement('checkbox', 'one_question_per_page', get_lang('OneQuestionPerPage'));
	$form->addElement('checkbox', 'shuffle', get_lang('ActivateShuffle'));
}

if ((isset($_GET['action']) && $_GET['action'] == 'edit') && !empty($survey_id) )
{	
	if ($survey_data['anonymous']==0  ) {
		// Aditional Parameters
		$form -> addElement('html','<div class="row">
		<div class="label">&nbsp;</div>
		<div class="formw">
			<a href="javascript://" onclick="advanced_parameters()" ><br /><span id="plus_minus">&nbsp;<img src="../img/nolines_plus.gif" alt="" />&nbsp;'.get_lang('AdvancedParameters').'</span></a>
		</div>
		</div>');
		$form -> addElement('html','<div id="options" style="display:none">');		
		$form->addElement('checkbox', 'show_form_profile', get_lang('ShowFormProfile'),'','onclick="javascript:if(this.checked==true){document.getElementById(\'options_field\').style.display = \'block\';}else{document.getElementById(\'options_field\').style.display = \'none\';}"');

		if ($survey_data['show_form_profile']== 1) {
		$form -> addElement('html','<div id="options_field" style="display:block">');
		} else {
			$form -> addElement('html','<div id="options_field" style="display:none">');
		}
		
		$field_list=SurveyUtil::make_field_list();
		if (is_array ($field_list))
		{
			//TODO hide and show the list in a fancy DIV 			
			foreach ($field_list  as $key=> $field)
			{
				if ($field['visibility']==1)
				{
					$form->addElement('checkbox', 'profile_'.$key, ' ','&nbsp;&nbsp;'.$field['name'] );				
					$input_name_list.= 'profile_'.$key.',';
				}
			}
			// necesary to know the fields
			$form->addElement('hidden', 'input_name_list', $input_name_list );
			
			//set defaults form fields		
			if ($survey_data['form_fields'])
			{
				$form_fields=explode('@',$survey_data['form_fields']);
				foreach($form_fields as $field)
				{
					$field_value=explode(':',$field);
					if ($field_value[0]!='' && $field_value[1]!= '')
					{
						$defaults[$field_value[0]]=$field_value[1];
					}
				}
			}
		}		
		$form->addElement('html', '</div></div>');
	}
 	
}
$form -> addElement('html','</div><br />');
if(isset($_GET['survey_id']) && $_GET['action']=='edit') {
	$class="save";
	$text=get_lang('ModifySurvey');
} else {
	$class="add";
	$text=get_lang('CreateSurvey'); 
}
$form->addElement('style_submit_button', 'submit_survey', $text, 'class="'.$class.'"');

// setting the rules
if ($_GET['action'] == 'add')
{
	$form->addRule('survey_code', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');
}
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
	
	/*// deleting the shared survey if the survey is getting unshared (this only happens when editing)	
	if (is_numeric($survey_data['survey_share']) AND $values['survey_share']['survey_share'] == 0 AND $values['survey_id']<>'')
	{
		survey_manager::delete_survey($survey_data['survey_share'], true);
	}
	// storing the already existing questions and options of a survey that gets shared (this only happens when editing)
	if ($survey_data['survey_share']== 0 AND $values['survey_share']['survey_share'] !== 0 AND $values['survey_id']<>'')
	{
		survey_manager::get_complete_survey_structure($return['id']);
	}
	*/
	if($return['type'] == 'error')
	{
		// Displaying the header
		Display::display_header($tool_name);
		
		// display the error
		Display::display_error_message(get_lang($return['message']), false);
		
		// display the form
		$form->display();
	}
	if ($config['survey']['debug'])
	{
		// displaying a feedback message
   		Display::display_confirmation_message($return['message'], false);
	} else {
   		// redirecting to the survey page (whilst showing the return message
   		header('location:survey.php?survey_id='.$return['id'].'&message='.$return['message']);
	}
} else {
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