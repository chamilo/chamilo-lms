<?php
/* For licensing terms, see /license.txt */

/**
 *	@package chamilo.survey
 * 	@author unknown, the initial survey that did not make it in 1.8 because of bad code
 * 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup, refactoring and rewriting large parts of the code
 *	@author Julio Montoya Armas <gugli100@gmail.com>, Chamilo: Personality Test modification and rewriting large parts of the code
 * 	@version $Id: survey_list.php 21933 2009-07-09 06:08:22Z ivantcholakov $
 *
 * 	@todo use quickforms for the forms
 */

// Language file that needs to be included
$language_file = 'survey';
if (!isset ($_GET['cidReq'])){
    $_GET['cidReq'] = 'none'; // Prevent sql errors
    $cidReset = true;
}

// Including the global initialization file
require_once '../inc/global.inc.php';
$this_section = SECTION_COURSES;
$current_course_tool  = TOOL_SURVEY;

api_protect_course_script(true);

// Including additional libraries
require_once 'survey.lib.php';

// Tracking
event_access_tool(TOOL_SURVEY);

/** @todo This has to be moved to a more appropriate place (after the display_header of the code)*/
if (!api_is_allowed_to_edit(false, true)) { // Coach can see this
	Display :: display_header(get_lang('SurveyList'));
	SurveyUtil::survey_list_user($_user['user_id']);
	Display :: display_footer();
	exit;
}

$extend_rights_for_coachs = api_get_setting('extend_rights_for_coach_on_survey');

// Database table definitions
$table_survey 			= Database :: get_course_table(TABLE_SURVEY);
$table_survey_question 	= Database :: get_course_table(TABLE_SURVEY_QUESTION);
$table_course 			= Database :: get_main_table(TABLE_MAIN_COURSE);
$table_user 			= Database :: get_main_table(TABLE_MAIN_USER);

// Language variables
if (isset($_GET['search']) && $_GET['search'] == 'advanced') {
	$interbreadcrumb[] = array('url' => 'survey_list.php', 'name' => get_lang('SurveyList'));
	$tool_name = get_lang('SearchASurvey');
} else {
	$tool_name = get_lang('SurveyList');
}

// Header
Display :: display_header($tool_name, 'Survey');

// Tool introduction
Display::display_introduction_section('survey', 'left');

// Action handling: searching
if (isset ($_GET['search']) && $_GET['search'] == 'advanced') {
	SurveyUtil::display_survey_search_form();
}
// Action handling: deleting a survey
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['survey_id'])) {
	// Getting the information of the survey (used for when the survey is shared)
	$survey_data = survey_manager::get_survey($_GET['survey_id']);
	if (api_is_course_coach() && intval($_SESSION['id_session']) != $survey_data['session_id']) {
		// The coach can't delete a survey not belonging to his session
		api_not_allowed();
		exit;
	}
	// If the survey is shared => also delete the shared content
	if (is_numeric($survey_data['survey_share'])) {
		survey_manager::delete_survey($survey_data['survey_share'], true);
	}
    
	$return = survey_manager :: delete_survey($_GET['survey_id']);
	if ($return) {
		Display :: display_confirmation_message(get_lang('SurveyDeleted'), false);
	} else {
		Display :: display_error_message(get_lang('ErrorOccurred'), false);
	}
}

if (isset($_GET['action']) && $_GET['action'] == 'empty') {
	$mysession = api_get_session_id();
	if ( $mysession != 0 ) {
		if (!((api_is_course_coach() || api_is_platform_admin()) && api_is_element_in_the_session(TOOL_SURVEY,intval($_GET['survey_id'])))) {
			// The coach can't empty a survey not belonging to his session
			api_not_allowed();
			exit;
		}
	} else {
		if (!(api_is_course_admin() || api_is_platform_admin())) {
			api_not_allowed();
			exit;
		}
	}
	$return = survey_manager::empty_survey(intval($_GET['survey_id']));
	if ($return) {
		Display :: display_confirmation_message(get_lang('SurveyEmptied'), false);
	} else {
		Display :: display_error_message(get_lang('ErrorOccurred'), false);
	}
}

// Action handling: performing the same action on multiple surveys
if ($_POST['action']) {
	if (is_array($_POST['id'])) {
		foreach ($_POST['id'] as $key => & $value) {
			// getting the information of the survey (used for when the survey is shared)
			$survey_data = survey_manager::get_survey($value);
			// if the survey is shared => also delete the shared content
			if (is_numeric($survey_data['survey_share'])) {
				survey_manager::delete_survey($survey_data['survey_share'], true);
			}
			// delete the actual survey
			survey_manager::delete_survey($value);
		}
		Display :: display_confirmation_message(get_lang('SurveysDeleted'), false);
	} else {
		Display :: display_error_message(get_lang('NoSurveysSelected'), false);
	}
}
echo $extended_rights_for_coachs;

echo '<div class="actions">';
if (!api_is_course_coach() || $extend_rights_for_coachs == 'true') {
	// Action links
	echo '<a href="create_new_survey.php?'.api_get_cidreq().'&amp;action=add">'.Display::return_icon('new_survey.png', get_lang('CreateNewSurvey'),'',ICON_SIZE_MEDIUM).'</a> ';
}
//echo '<a href="survey_all_courses.php">'.get_lang('CreateExistingSurvey').'</a> ';
echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;search=advanced">'.Display::return_icon('search.png', get_lang('Search'),'',ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

// Load main content
if (api_is_course_coach() && $extend_rights_for_coachs == 'false') {
	SurveyUtil::display_survey_list_for_coach();
} else {
	SurveyUtil::display_survey_list();
}

// Footer
Display :: display_footer();

/* Bypass functions to make direct use from SortableTable possible */

function get_number_of_surveys() {
	return SurveyUtil::get_number_of_surveys();
}

function get_survey_data($from, $number_of_items, $column, $direction) {
	return SurveyUtil::get_survey_data($from, $number_of_items, $column, $direction);
}

function modify_filter($survey_id) {
	return SurveyUtil::modify_filter($survey_id);
}

function get_number_of_surveys_for_coach() {
	return SurveyUtil::get_number_of_surveys_for_coach();
}
function get_survey_data_for_coach($from, $number_of_items, $column, $direction) {
	return SurveyUtil::get_survey_data_for_coach($from, $number_of_items, $column, $direction);
}

function modify_filter_for_coach($survey_id) {
	return SurveyUtil::modify_filter_for_coach($survey_id);
}

function anonymous_filter($anonymous) {
	return SurveyUtil::anonymous_filter($anonymous);
}
