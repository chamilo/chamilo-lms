<?php
/* For licensing terms, see /license.txt */

/**
*	@package chamilo.survey
* 	@author unknown, the initial survey that did not make it in 1.8 because of bad code
* 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup, refactoring and rewriting large parts of the code
* 	@version $Id: question.php 21734 2009-07-02 17:12:41Z cvargas1 $
*/

// Language file that needs to be included
$language_file = 'survey';

// Including the global initialization file
require_once '../inc/global.inc.php';

// Including additional libraries
require_once 'survey.lib.php';

$htmlHeadXtra[] = '<script type="text/javascript">
						$(document).ready( function() {
							$("button").click(function() {
								$("#is_executable").attr("value",$(this).attr("name"));
							});
		 				} ); </script>';

/** @todo this has to be moved to a more appropriate place (after the display_header of the code)*/
if (!api_is_allowed_to_edit(false, true)) {
	Display :: display_header();
	Display :: display_error_message(get_lang('NotAllowed'), false);
	Display :: display_footer();
	exit;
}

// Is valid request
$is_valid_request = $_REQUEST['is_executable'];
if ($request_index != $is_valid_request) {
	if ($request_index == 'save_question') {
		unset($_POST[$request_index]);
	} elseif ($request_index == 'add_answer') {
		unset($_POST[$request_index]);
	} elseif($request_index == 'remove_answer') {
		unset($_POST[$request_index]);
	}
}

// Database table definitions
$table_survey 					= Database :: get_course_table(TABLE_SURVEY);
$table_survey_question 			= Database :: get_course_table(TABLE_SURVEY_QUESTION);
$table_survey_question_option 	= Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);

$table_course 					= Database :: get_main_table(TABLE_MAIN_COURSE);
$table_user 					= Database :: get_main_table(TABLE_MAIN_USER);

$course_id = api_get_course_int_id();

// Getting the survey information
$survey_data = survey_manager::get_survey($_GET['survey_id']);
if (empty($survey_data)) {
	Display :: display_header(get_lang('ToolSurvey'));
	Display :: display_error_message(get_lang('InvallidSurvey'), false);
	Display :: display_footer();
	exit;
}


$urlname = api_substr(api_html_entity_decode($survey_data['title'], ENT_QUOTES), 0, 40);
if (api_strlen(strip_tags($survey_data['title'])) > 40) {
	$urlname .= '...';
}

if ($survey_data['survey_type'] == 1) {
	$sql = 'SELECT id FROM '.Database :: get_course_table(TABLE_SURVEY_QUESTION_GROUP).' WHERE c_id = '.$course_id.' AND survey_id = '.(int)$_GET['survey_id'].' LIMIT 1';
	$rs = Database::query($sql);
	if(Database::num_rows($rs)===0) {
		header('Location: survey.php?survey_id='.(int)$_GET['survey_id'].'&message='.'YouNeedToCreateGroups');
		exit;
	}
}

// Breadcrumbs
$interbreadcrumb[] = array ('url' => 'survey_list.php', 'name' => get_lang('SurveyList'));
$interbreadcrumb[] = array ('url' => 'survey.php?survey_id='.Security::remove_XSS($_GET['survey_id']), 'name' => strip_tags($urlname));

// Tool name
if ($_GET['action'] == 'add') {
	$tool_name = get_lang('AddQuestion');
}
if ($_GET['action'] == 'edit') {
	$tool_name = get_lang('EditQuestion');
}

// The possible question types
$possible_types = array('personality', 'yesno', 'multiplechoice', 'multipleresponse', 'open', 'dropdown', 'comment', 'pagebreak', 'percentage', 'score');

// Actions
$actions = '<div class="actions">';
$actions .= '<a href="survey.php?survey_id='.Security::remove_XSS($_GET['survey_id']).'">'.Display::return_icon('back.png', get_lang('BackToSurvey'),'',ICON_SIZE_MEDIUM).'</a>';
$actions .= '</div>';
// Checking if it is a valid type
if (!in_array($_GET['type'], $possible_types)) {
	Display :: display_header($tool_name,'Survey');
	echo $actions;
	Display :: display_error_message(get_lang('TypeDoesNotExist'), false);
	Display :: display_footer();
}

// Displaying the form for adding or editing the question
if (empty($_POST['save_question']) && in_array($_GET['type'], $possible_types)) {
	if (!isset($_POST['save_question'])) {
		// Displaying the header
		Display::display_header($tool_name, 'Survey');
		echo $actions;
		$error_message = '';
		// Displys message if exists
		if (isset($_SESSION['temp_sys_message'])) {
			$error_message=$_SESSION['temp_sys_message'];
			unset($_SESSION['temp_sys_message']);
			if ($error_message == 'PleaseEnterAQuestion' || $error_message == 'PleasFillAllAnswer'|| $error_message == 'PleaseChooseACondition'|| $error_message == 'ChooseDifferentCategories') {
				Display::display_error_message(get_lang($error_message), true);
			}
		}
	}
	$ch_type = 'ch_'.$_GET['type'];
	$form = new $ch_type;

	// The defaults values for the form
	$form_content['answers'] = array('', '');

	if ($_GET['type'] == 'yesno') {
		$form_content['answers'][0] = get_lang('Yes');
		$form_content['answers'][1] = get_lang('No');
	}

	if ($_GET['type'] == 'personality') {
		$form_content['answers'][0] = 1;
		$form_content['answers'][1] = 2;
		$form_content['answers'][2] = 3;
		$form_content['answers'][3] = 4;
		$form_content['answers'][4] = 5;

		$form_content['values'][0] = 0;
		$form_content['values'][1] = 0;
		$form_content['values'][2] = 1;
		$form_content['values'][3] = 2;
		$form_content['values'][4] = 3;
	}

	// We are editing a question
	if (isset($_GET['question_id']) && !empty($_GET['question_id'])) {
		$form_content = survey_manager::get_question($_GET['question_id']);
	}

	// An action has been performed (for instance adding a possible answer, moving an answer, ...)
	if ($_POST) {
		$form_content = $_POST;
		$form_content = $form->handle_action($form_content);
	}

	if ($error_message != '') {
		$form_content['question'] = $_SESSION['temp_user_message'];
		$form_content['answers'] = $_SESSION['temp_answers'];
		$form_content['values'] = $_SESSION['temp_values'];
		$form_content['horizontalvertical'] = $_SESSION['temp_horizontalvertical'];

		unset($_SESSION['temp_user_message']);
		unset($_SESSION['temp_answers']);
		unset($_SESSION['temp_values']);
		unset($_SESSION['temp_horizontalvertical']);
	}

	$form->create_form($form_content);
	$form->render_form();
} else {
	$form_content = $_POST;
	$form = new survey_question();
	$form->handle_action($form_content);
}

// Footer
Display :: display_footer();
