<?php
/* For licensing terms, see /license.txt */

/**
 *	@package chamilo.survey
 * 	@author unknown, the initial survey that did not make it in 1.8 because of bad code
 * 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup, refactoring and rewriting large parts of the code
 *	@author Julio Montoya Armas <gugli100@gmail.com>, Chamilo: Personality Test modifications
 * 	@version $Id: survey_list.php 10680 2007-01-11 21:26:23Z pcool $
 */

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_COURSES;

// Database table definitions
$table_survey = Database::get_course_table(TABLE_SURVEY);
$table_survey_question = Database::get_course_table(TABLE_SURVEY_QUESTION);
$table_survey_question_option = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);
$table_course = Database::get_main_table(TABLE_MAIN_COURSE);
$table_user = Database::get_main_table(TABLE_MAIN_USER);
$table_survey_invitation = Database::get_course_table(TABLE_SURVEY_INVITATION);

$course_id = api_get_course_int_id();
$userId = api_get_user_id();
$surveyId = intval($_GET['survey_id']);
$userInvited = 0;
$userAnonymous = 0;

//query to ask if logged user is allowed to see the preview (if he is invited of he is a teacher)
$sql = "SELECT survey_invitation.user
        FROM $table_survey_invitation survey_invitation
        LEFT JOIN $table_survey survey
        ON survey_invitation.survey_code = survey.code
        WHERE
            survey_invitation.c_id = $course_id AND
            survey.survey_id = $surveyId AND
            survey_invitation.user = $userId";
$result = Database::query($sql);
if (Database::num_rows($result) > 0) {
    $userInvited = 1;
}

// We exit here if there is no valid $_GET parameter
if (!isset($_GET['survey_id']) || !is_numeric($_GET['survey_id'])) {
    api_not_allowed(true, Display::return_message(get_lang('InvallidSurvey'), 'error', false));
	exit;
}

// Getting the survey information
$survey_id = intval($_GET['survey_id']);
$survey_data = SurveyManager::get_survey($survey_id);

if (empty($survey_data)) {
    api_not_allowed(true, Display::return_message(get_lang('InvallidSurvey'), 'error', false));
	exit;
}

$urlname = strip_tags($survey_data['title']);
if (api_is_allowed_to_edit()) {
	// Breadcrumbs
	$interbreadcrumb[] = array(
        'url' => api_get_path(WEB_CODE_PATH).'survey/survey_list.php?'.api_get_cidreq(),
        'name' => get_lang('SurveyList')
    );
    $interbreadcrumb[] = array(
        'url' => api_get_path(WEB_CODE_PATH).'survey/survey.php?survey_id='.$survey_id.'&'.api_get_cidreq(),
        'name' => $urlname,
    );
}
$courseCode = isset($_GET['cidReq']) ? $_GET['cidReq'] : null;
$surveyAnonymous = SurveyManager::get_survey($survey_id, 0, $courseCode);
$surveyAnonymous = $surveyAnonymous['anonymous'];
if ($surveyAnonymous == 0 && api_is_anonymous()) {
    api_not_allowed(true);
} elseif ($surveyAnonymous == 0 && $userInvited == 0) {
    if (!api_is_allowed_to_edit()) {
        api_not_allowed(true);
    }
}
// Header
Display :: display_header(get_lang('SurveyPreview'));

// We exit here is the first or last question is a pagebreak (which causes errors)
SurveyUtil::check_first_last_question($survey_id, false);
$counter_question = 0;
// Only a course admin is allowed to preview a survey: you are a course admin
if (api_is_course_admin() ||
	(api_is_course_admin() && $_GET['isStudentView'] == 'true') ||
	api_is_allowed_to_session_edit(false, true)
) {
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

    $questions = array();

	if (isset($_GET['show'])) {
		// Getting all the questions for this page and add them to a
		// multidimensional array where the first index is the page.
		// as long as there is no pagebreak fount we keep adding questions to the page
		$questions_displayed = array();
		$paged_questions = array();
		$counter = 0;
		$sql = "SELECT * FROM $table_survey_question
		        WHERE c_id = $course_id AND survey_id = '".intval($survey_id)."'
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
			$sql = "SELECT
                        survey_question.question_id,
                        survey_question.survey_id,
                        survey_question.survey_question,
                        survey_question.display,
                        survey_question.sort,
                        survey_question.type,
                        survey_question.max_value,
						survey_question_option.question_option_id,
						survey_question_option.option_text,
						survey_question_option.sort as option_sort
					FROM $table_survey_question survey_question
					LEFT JOIN $table_survey_question_option survey_question_option
					ON
						survey_question.question_id = survey_question_option.question_id AND
						survey_question_option.c_id = $course_id
					WHERE
					    survey_question.survey_id = '".intval($survey_id)."' AND
						survey_question.question_id IN (".Database::escape_string(implode(',', $paged_questions[$_GET['show']]), null, false).") AND
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
				} else {
					// If the type is a pagebreak we are finished loading the questions for this page
					break;
				}
				$counter_question++;
			}
		}
	}

	// Selecting the maximum number of pages
	$sql = "SELECT * FROM $table_survey_question
	        WHERE
	            c_id = $course_id AND
	            type='".Database::escape_string('pagebreak')."' AND
	            survey_id='".intval($survey_id)."'";
	$result = Database::query($sql);
	$numberofpages = Database::num_rows($result) + 1;

	// Displaying the form with the questions
	if (isset($_GET['show'])) {
		$show = (int) $_GET['show'] + 1;
	} else {
		$show = 0;
	}

	$url = api_get_self().'?survey_id='.Security::remove_XSS($survey_id).'&show='.$show;
	$form = new FormValidator('question', 'post', $url);

	if (is_array($questions) && count($questions) > 0) {
		foreach ($questions as $key => & $question) {
			$ch_type = 'ch_'.$question['type'];
			/** @var survey_question $display */
			$display = new $ch_type;
			$form->addHtml('<div class="survey_question_wrapper"><div class="survey_question">');
			$form->addHtml($question['survey_question']);
			$display->render($form, $question);
			$form->addHtml('</div></div>');
		}
	}

	if (($show < $numberofpages) || (!$_GET['show'] && count($questions) > 0)) {
        if ($show == 0) {
			$form->addButton('next_survey_page', get_lang('StartSurvey'), 'arrow-right', 'success', 'large');
        } else {
			$form->addButton('next_survey_page', get_lang('NextQuestion'), 'arrow-right');
        }
	}
	if ($show >= $numberofpages && $_GET['show'] || (isset($_GET['show']) && count($questions) == 0)) {
		if ($questions_exists == false) {
			echo '<p>'.get_lang('ThereAreNotQuestionsForthisSurvey').'</p>';
		}
		$form->addButton('finish_survey', get_lang('FinishSurvey'), 'arrow-right');
	}
	$form->display();
} else {
	Display :: display_error_message(get_lang('NotAllowed'), false);
}

Display :: display_footer();
