<?php
/* For licensing terms, see /license.txt */

/**
 *	@package chamilo.survey
 * 	@author unknown, the initial survey that did not make it in 1.8 because of bad code
 * 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup, refactoring and rewriting large parts (if not all) of the code
 *	@author Julio Montoya Armas <gugli100@gmail.com>, Chamilo: Personality Test modification and rewriting large parts of the code
 * 	@version $Id: create_new_survey.php 22297 2009-07-22 22:08:30Z cfasanando $
 *
 * 	@todo only the available platform languages should be used => need an api get_languages and and api_get_available_languages (or a parameter)
 */

// Language file that needs to be included
$language_file = 'survey';

// Including the global initialization file
require_once '../inc/global.inc.php';

$this_section = SECTION_COURSES;

// Including additional libraries
/** @todo check if these are all needed */
/** @todo check if the starting / is needed. api_get_path probably ends with an / */
//require_once api_get_path(LIBRARY_PATH).'survey.lib.php';
require_once 'survey.lib.php';
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once api_get_path(CONFIGURATION_PATH).'add_course.conf.php';
require_once api_get_path(LIBRARY_PATH).'add_course.lib.inc.php';
require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/gradebook_functions.inc.php';

$htmlHeadXtra[] = '<script type="text/javascript" language="javascript">
		function advanced_parameters() {
			if(document.getElementById(\'options\').style.display == \'none\') {
					document.getElementById(\'options\').style.display = \'block\';
					document.getElementById(\'plus_minus\').innerHTML=\'&nbsp;'.Display::return_icon('div_hide.gif', get_lang('Hide'), array('style' => 'vertical-align:middle')).'&nbsp;'.get_lang('AdvancedParameters').'\';
			} else {
					document.getElementById(\'options\').style.display = \'none\';
					document.getElementById(\'plus_minus\').innerHTML=\'&nbsp;'.Display::return_icon('div_show.gif', get_lang('Show'), array('style' => 'vertical-align:middle')).'&nbsp;'.get_lang('AdvancedParameters').'\';
			}
		}

		function setFocus(){
		  $("#surveycode_title").focus();
		}
		$(document).ready(function () {
		  setFocus();
		});
	</script>';

// Database table definitions
$table_survey 				= Database :: get_course_table(TABLE_SURVEY);
$table_user 				= Database :: get_main_table(TABLE_MAIN_USER);
$table_course 				= Database :: get_main_table(TABLE_MAIN_COURSE);
$table_course_survey_rel 	= Database :: get_main_table(TABLE_MAIN_COURSE_SURVEY);
$table_gradebook_link 		= Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);

/** @todo this has to be moved to a more appropriate place (after the display_header of the code)*/
// If user is not teacher or if he's a coach trying to access an element out of his session
if (!api_is_allowed_to_edit()) {
	if (!api_is_course_coach() || (!empty($_GET['survey_id']) && !api_is_element_in_the_session(TOOL_SURVEY, intval($_GET['survey_id'])))) {
		Display :: display_header();
		Display :: display_error_message(get_lang('NotAllowed'), false);
		Display :: display_footer();
		exit;
	}
}

// Getting the survey information
$survey_id = Security::remove_XSS($_GET['survey_id']);
$survey_data = survey_manager::get_survey($survey_id);

// Additional information
$course_id = api_get_course_id();
$session_id = api_get_session_id();
$gradebook_link_type = 8; // LINK_SURVEY

$urlname = strip_tags(api_substr(api_html_entity_decode($survey_data['title'], ENT_QUOTES), 0, 40));
if (api_strlen(strip_tags($survey_data['title'])) > 40) {
	$urlname .= '...';
}

// Breadcrumbs
if ($_GET['action'] == 'add') {
	$interbreadcrumb[] = array('url' => 'survey_list.php', 'name' => get_lang('SurveyList'));
	$tool_name = get_lang('CreateNewSurvey');
}
if ($_GET['action'] == 'edit' && is_numeric($survey_id))
{
	$interbreadcrumb[] = array('url' => 'survey_list.php', 'name' => get_lang('SurveyList'));
	$interbreadcrumb[] = array('url' => 'survey.php?survey_id='.$survey_id, 'name' => strip_tags($urlname));
	$tool_name = get_lang('EditSurvey');
}

// Getting the default values
if ($_GET['action'] == 'edit' && isset($survey_id) && is_numeric($survey_id)) {
	$defaults = $survey_data;
	$defaults['survey_id'] = $survey_id;
	$defaults['anonymous'] = $survey_data['anonymous'];

	$gradebook_link_id = is_resource_in_course_gradebook($course_id, $gradebook_link_type, $survey_id, $session_id);
	if ($gradebook_link_id) {
		if ($sql_result_array = Database::fetch_array(Database::query('SELECT weight FROM '.$table_gradebook_link.' WHERE id='.$gradebook_link_id))) {
			$defaults['survey_qualify_gradebook'] = $gradebook_link_id;
			$defaults['survey_weight'] = number_format($sql_result_array['weight'], 2, '.', '');
		}
	}
} else {
	$defaults['survey_language'] = $_course['language'];
	$defaults['start_date'] = date('d-F-Y H:i');
	$startdateandxdays = time() + 864000; // today + 10 days
	$defaults['end_date'] = date('d-F-Y H:i', $startdateandxdays);
	//$defaults['survey_share']['survey_share'] = 0;
	//$form_share_value = 1;
	$defaults['anonymous'] = 0;
}

// Initialize the object
$form = new FormValidator('survey', 'post', api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&survey_id='.$survey_id);

$form->addElement('header', '', $tool_name);

// Settting the form elements
if ($_GET['action'] == 'edit' && isset($survey_id) && is_numeric($survey_id)) {
	$form->addElement('hidden', 'survey_id');
}

$survey_code = $form->addElement('text', 'survey_code', get_lang('SurveyCode'), array('size' => '20', 'maxlength' => '20', 'id' => 'surveycode_title'));
//$form->applyFilter('survey_code', 'html_filter');

if ($_GET['action'] == 'edit') {
	$survey_code->freeze();
	$form->applyFilter('survey_code', 'api_strtoupper');
}

$form->addElement('html_editor', 'survey_title', get_lang('SurveyTitle'), null, array('ToolbarSet' => 'Survey', 'Width' => '100%', 'Height' => '200'));
$form->addElement('html_editor', 'survey_subtitle', get_lang('SurveySubTitle'), null, array('ToolbarSet' => 'Survey', 'Width' => '100%', 'Height' => '100', 'ToolbarStartExpanded' => false));

/*
//Language selection has been disabled. If you want to re-enable, please
//disable the following line (hidden language field).
$lang_array = api_get_languages();
foreach ($lang_array['name'] as $key => $value) {
	$languages[$lang_array['folder'][$key]] = $value;
}
$form->addElement('select', 'survey_language', get_lang('Language'), $languages);
*/

// Pass the language of the survey in the form
$form->addElement('hidden', 'survey_language');
$form->addElement('datepickerdate', 'start_date', get_lang('StartDate'), array('form_name'=>'survey'));
$form->addElement('datepickerdate', 'end_date', get_lang('EndDate'), array('form_name'=>'survey'));

//$group = '';
//$group[] =& HTML_QuickForm::createElement('radio', 'survey_share', null, get_lang('Yes'), $form_share_value);
/** TODO Maybe it is better to change this into false instead see line 95 in survey.lib.php */
//$group[] =& HTML_QuickForm::createElement('radio', 'survey_share', null, get_lang('No'), 0);

//$form->addGroup($group, 'survey_share', get_lang('ShareSurvey'), '&nbsp;');
$form->addElement('checkbox', 'anonymous', get_lang('Anonymous'));
$form->addElement('html_editor', 'survey_introduction', get_lang('SurveyIntroduction'), null, array('ToolbarSet' => 'Survey', 'Width' => '100%', 'Height' => '130', 'ToolbarStartExpanded' => false));
$form->addElement('html_editor', 'survey_thanks', get_lang('SurveyThanks'), null, array('ToolbarSet' => 'Survey', 'Width' => '100%', 'Height' => '130', 'ToolbarStartExpanded' => false));

// Aditional Parameters
$form->addElement('html', '
<div class="row">
	<div class="label">
		<a href="javascript: void(0);" onclick="javascript: advanced_parameters();" ><span id="plus_minus">&nbsp;'.Display::return_icon('div_show.gif',null,array('style'=>'vertical-align:middle')).'&nbsp;'.get_lang('AdvancedParameters').'</span></a>
	</div>
	<div class="formw">
		&nbsp;
	</div>
</div>');

$form -> addElement('html', '<div id="options" style="display: none;">');

// An option: Qualify the fact that survey has been answered in the gradebook
$form->addElement('checkbox', 'survey_qualify_gradebook', '', get_lang('QualifyInGradebook'), 'onclick="javascript: if (this.checked) { document.getElementById(\'gradebook_options\').style.display = \'block\'; } else { document.getElementById(\'gradebook_options\').style.display = \'none\'; }"');
$form->addElement('html', '<div id="gradebook_options"'.($gradebook_link_id ? '' : ' style="display:none"').'>');
$form->addElement('text', 'survey_weight', get_lang('QualifyWeight'), 'value="0.00" style="width: 40px;" onfocus="javascript: this.select();"');
$form->applyFilter('survey_weight', 'html_filter');
$form->addElement('html', '</div>');

// Personality/Conditional Test Options
$surveytypes[0] = get_lang('Normal');
$surveytypes[1] = get_lang('Conditional');

if ($_GET['action'] == 'add') {
	$form->addElement('hidden', 'survey_type', 0);
	require_once api_get_path(LIBRARY_PATH).'surveymanager.lib.php';
	$survey_tree = new SurveyTree();
	$list_surveys = $survey_tree->createList($survey_tree->surveylist);
	$list_surveys[0] = '';
	$form->addElement('select', 'parent_id', get_lang('ParentSurvey'), $list_surveys);
	$defaults['parent_id'] = 0;
}

if ($survey_data['survey_type'] == 1 || $_GET['action'] == 'add') {
	$form->addElement('checkbox', 'one_question_per_page', get_lang('OneQuestionPerPage'));
	$form->addElement('checkbox', 'shuffle', get_lang('ActivateShuffle'));
}

if ((isset($_GET['action']) && $_GET['action'] == 'edit') && !empty($survey_id)) {
	if ($survey_data['anonymous'] == 0) {

		$form->addElement('checkbox', 'show_form_profile', get_lang('ShowFormProfile'), '', 'onclick="javascript: if(this.checked){document.getElementById(\'options_field\').style.display = \'block\';}else{document.getElementById(\'options_field\').style.display = \'none\';}"');

		if ($survey_data['show_form_profile'] == 1) {
			$form -> addElement('html', '<div id="options_field" style="display:block">');
		} else {
			$form -> addElement('html', '<div id="options_field" style="display:none">');
		}

		$field_list = SurveyUtil::make_field_list();
		if (is_array($field_list)) {
			// TODO hide and show the list in a fancy DIV
			foreach ($field_list  as $key => & $field) {
				if ($field['visibility'] == 1) {
					$form->addElement('checkbox', 'profile_'.$key, ' ','&nbsp;&nbsp;'.$field['name'] );
					$input_name_list.= 'profile_'.$key.',';
				}
			}
			// Necesary to know the fields
			$form->addElement('hidden', 'input_name_list', $input_name_list );

			// Set defaults form fields
			if ($survey_data['form_fields']) {
				$form_fields = explode('@', $survey_data['form_fields']);
				foreach ($form_fields as & $field) {
					$field_value = explode(':', $field);
					if ($field_value[0] != '' && $field_value[1] != '') {
						$defaults[$field_value[0]] = $field_value[1];
					}
				}
			}
		}

		$form->addElement('html', '</div>');
	}
}

$form -> addElement('html', '</div><br />');

if (isset($_GET['survey_id']) && $_GET['action'] == 'edit') {
	$class = 'save';
	$text = get_lang('ModifySurvey');
} else {
	$class = 'add';
	$text = get_lang('CreateSurvey');
}
$form->addElement('style_submit_button', 'submit_survey', $text, 'class="'.$class.'"');

// Setting the rules
if ($_GET['action'] == 'add') {
	$form->addRule('survey_code', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');
	$form->addRule('survey_code', '', 'maxlength', 20);
}
$form->addRule('survey_title', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('start_date', get_lang('InvalidDate'), 'date');
$form->addRule('end_date', get_lang('InvalidDate'), 'date');
$form->addRule(array('start_date', 'end_date'), get_lang('StartDateShouldBeBeforeEndDate'), 'date_compare', 'lte');

// Setting the default values
$form->setDefaults($defaults);

// The validation or display
if ($form->validate()) {
	// Exporting the values
	$values = $form->exportValues();
	// Storing the survey
	$return = survey_manager::store_survey($values);

	/*// Deleting the shared survey if the survey is getting unshared (this only happens when editing)
	if (is_numeric($survey_data['survey_share']) && $values['survey_share']['survey_share'] == 0 && $values['survey_id'] != '') {
		survey_manager::delete_survey($survey_data['survey_share'], true);
	}
	// Storing the already existing questions and options of a survey that gets shared (this only happens when editing)
	if ($survey_data['survey_share'] == 0 && $values['survey_share']['survey_share'] !== 0 && $values['survey_id'] != '') {
		survey_manager::get_complete_survey_structure($return['id']);
	}
	*/
	if ($return['type'] == 'error') {

		// Displaying the header
		Display::display_header($tool_name);

		// Display the error
		Display::display_error_message(get_lang($return['message']), false);

		// Display the form
		$form->display();

	} else {

		$gradebook_option = $values['survey_qualify_gradebook'] > 0;
		if ($gradebook_option) {
			$survey_id = intval($return['id']);
			if ($survey_id > 0) {

				$title_gradebook = ''; // Not needed here.
				$description_gradebook = ''; // Not needed here.
				$survey_weight = floatval($_POST['survey_weight']);
				$max_score = 1;
				$date = time(); // TODO: Maybe time zones implementation is needed here.
				$visible = 1;	// 1 = visible

				$gradebook_link_id = is_resource_in_course_gradebook($course_id, $gradebook_link_type, $survey_id, $session_id);
				if (!$gradebook_link_id) {
					add_resource_to_course_gradebook($course_id, $gradebook_link_type, $survey_id, $title_gradebook, $survey_weight, $max_score, $description_gradebook, time(), 1, $session_id);
				} else {
					Database::query('UPDATE '.$table_gradebook_link.' SET weight='.$survey_weight.' WHERE id='.$gradebook_link_id);
				}
			}
		}
	}
	if ($config['survey']['debug']) {
		// Displaying a feedback message
   		Display::display_confirmation_message($return['message'], false);
	} else {
   		// Redirecting to the survey page (whilst showing the return message)
   		@header('location:survey.php?survey_id='.$return['id'].'&message='.$return['message']);
	}
} else {
	// Displaying the header
	Display::display_header($tool_name);
	// Displaying the tool title
	//api_display_tool_title($tool_name);
	// Display the form
	$form->display();
}

// Footer
Display :: display_footer();
