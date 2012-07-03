<?php
/* For licensing terms, see /license.txt */

/**
 *	@package chamilo.survey
 * 	@author unknown, the initial survey that did not make it in 1.8 because of bad code
 * 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup, refactoring and rewriting large parts of the code
 *	@author Julio Montoya Armas <gugli100@gmail.com>, Chamilo: Personality Test modifications
 * 	@version $Id: survey_list.php 10680 2007-01-11 21:26:23Z pcool $
 *
 * 	@todo use quickforms for the forms
 */

// Language file that needs to be included
$language_file = 'survey';

// Including the global initialization file
require '../inc/global.inc.php';
require_once 'survey.lib.php';

$this_section = SECTION_COURSES;

// Database table definitions
$table_survey 					= Database :: get_course_table(TABLE_SURVEY);
$table_survey_question 			= Database :: get_course_table(TABLE_SURVEY_QUESTION);
$table_survey_question_option 	= Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
$table_course 					= Database :: get_main_table(TABLE_MAIN_COURSE);
$table_user 					= Database :: get_main_table(TABLE_MAIN_USER);

$course_id = api_get_course_int_id();

// We exit here if ther is no valid $_GET parameter
if (!isset($_GET['survey_id']) || !is_numeric($_GET['survey_id'])){
	Display :: display_header(get_lang('SurveyPreview'));
	Display :: display_error_message(get_lang('InvallidSurvey'), false);
	Display :: display_footer();
	exit;
}

// Getting the survey information
$survey_id = intval($_GET['survey_id']);
$survey_data = survey_manager::get_survey($survey_id);

if (empty($survey_data)) {
	Display :: display_header(get_lang('SurveyPreview'));
	Display :: display_error_message(get_lang('InvallidSurvey'), false);
	Display :: display_footer();
	exit;
}

/*$urlname = strip_tags(api_substr(api_html_entity_decode($survey_data['title'], ENT_QUOTES), 0, 40));
if (api_strlen(strip_tags($survey_data['title'])) > 40) {
	$urlname .= '...';
}*/

$urlname = strip_tags($survey_data['title']);

// Breadcrumbs
$interbreadcrumb[] = array('url' => 'survey_list.php', 'name' => get_lang('SurveyList'));
$interbreadcrumb[] = array('url' => 'survey.php?survey_id='.$survey_id, 'name' => $urlname);

// Header
Display :: display_header(get_lang('SurveyPreview'));

// We exit here is the first or last question is a pagebreak (which causes errors)
SurveyUtil::check_first_last_question($survey_id, false);

// Only a course admin is allowed to preview a survey: you are NOT a course admin => error message

/*
if (!api_is_allowed_to_edit(false, true)) {
	Display :: display_error_message(get_lang('NotAllowed'), false);
}*/

$counter_question = 0;
// Only a course admin is allowed to preview a survey: you are a course admin
if (api_is_course_admin() || (api_is_course_admin() && $_GET['isStudentView'] == 'true') || api_is_allowed_to_session_edit(false, true)) {
	// Survey information
	echo '<div id="survey_title">'.$survey_data['survey_title'].'</div>';
	echo '<div id="survey_subtitle">'.$survey_data['survey_subtitle'].'</div>';

	// Displaying the survey introduction
	if (!isset($_GET['show'])) {
        if (!empty($survey_data['survey_introduction'])) {
            echo '<div id="survey_content" class="survey_content">'.$survey_data['survey_introduction'].'</div>';
        }
		$limit = 0;
	}

	// Displaying the survey thanks message
	if (isset($_POST['finish_survey'])) {
		Display::display_confirmation_message(get_lang('SurveyFinished'));
        echo $survey_data['survey_thanks'];
		Display :: display_footer();
		exit;
	}

	if (isset($_GET['show'])) {
		// Getting all the questions for this page and add them to a multidimensional array where the first index is the page.
		// as long as there is no pagebreak fount we keep adding questions to the page
		$questions_displayed = array();
		$paged_questions = array();
		$counter = 0;
		$sql = "SELECT * FROM $table_survey_question 
		        WHERE c_id = $course_id AND survey_id = '".Database::escape_string($survey_id)."'
				ORDER BY sort ASC";
		$result = Database::query($sql);
        $questions_exists = true;
        if (Database::num_rows($result)) {
    		while ($row = Database::fetch_array($result)) {
    			if ($row['type'] == 'pagebreak') {
    				$counter++;
    			} else {
    				$paged_questions[$counter][] = $row['question_id'];
    			}
    		}
        } else {
            $questions_exists = false;
        }
		
		if (array_key_exists($_GET['show'], $paged_questions)) {
			$sql = "SELECT survey_question.question_id, survey_question.survey_id, survey_question.survey_question, survey_question.display, survey_question.sort, survey_question.type, survey_question.max_value,
							survey_question_option.question_option_id, survey_question_option.option_text, survey_question_option.sort as option_sort
					FROM $table_survey_question survey_question LEFT JOIN $table_survey_question_option survey_question_option
					ON survey_question.question_id = survey_question_option.question_id AND survey_question_option.c_id = $course_id 
					WHERE 	survey_question.survey_id = '".Database::escape_string($survey_id)."' AND 
							survey_question.question_id IN (".Database::escape_string(implode(',',$paged_questions[$_GET['show']])).") AND							
							survey_question.c_id =  $course_id 
					ORDER BY survey_question.sort, survey_question_option.sort ASC";

			$result = Database::query($sql);
			$question_counter_max = Database::num_rows($result);
			$limit = 0;
			while ($row = Database::fetch_array($result)) {
				// If the type is not a pagebreak we store it in the $questions array
				if ($row['type'] != 'pagebreak') {
					$questions[$row['sort']]['question_id'] = $row['question_id'];
					$questions[$row['sort']]['survey_id'] = $row['survey_id'];
					$questions[$row['sort']]['survey_question'] = $row['survey_question'];
					$questions[$row['sort']]['display'] = $row['display'];
					$questions[$row['sort']]['type'] = $row['type'];
					$questions[$row['sort']]['options'][intval($row['option_sort'])] = $row['option_text'];
					$questions[$row['sort']]['maximum_score'] = $row['max_value'];
				}
				// If the type is a pagebreak we are finished loading the questions for this page
				else {
					break;
				}
				$counter_question++;
			}
		}
	}
	// Selecting the maximum number of pages
	$sql = "SELECT * FROM $table_survey_question WHERE c_id = $course_id AND type='".Database::escape_string('pagebreak')."' AND survey_id='".Database::escape_string($survey_id)."'";
	$result = Database::query($sql);
	$numberofpages = Database::num_rows($result) + 1;
	// Displaying the form with the questions
	if (isset($_GET['show'])) {
		$show = (int)$_GET['show'] + 1;
	} else {
		$show = 0;
	}
	echo '<form id="question" name="question" method="post" action="'.api_get_self().'?survey_id='.Security::remove_XSS($survey_id).'&show='.$show.'">';

	if (is_array($questions) && count($questions) > 0) {
		foreach ($questions as $key => & $question) {
			$ch_type = 'ch_'.$question['type'];
			$display = new $ch_type;
			$display->render_question($question);
		}
	}

	if (($show < $numberofpages) || (!$_GET['show'] && count($questions) > 0)) {
		echo '<br /><button type="submit" name="next_survey_page" class="next">'.get_lang('NextQuestion').'   </button>';
	}
	if ($show >= $numberofpages && $_GET['show'] || (isset($_GET['show']) && count($questions) == 0)) {
		if ($questions_exists == false) {
			echo '<p>'.get_lang('ThereAreNotQuestionsForthisSurvey').'</p>';
		}
		echo '<button type="submit" name="finish_survey" class="next">'.get_lang('FinishSurvey').'  </button>';
	}
	echo '</form>';
} else {
	Display :: display_error_message(get_lang('NotAllowed'), false);
}

// Footer
Display :: display_footer();
