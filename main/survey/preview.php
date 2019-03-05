<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.survey
 *
 * @author unknown, the initial survey that did not make it in 1.8 because of bad code
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup, refactoring and rewriting code
 * @author Julio Montoya <gugli100@gmail.com>, Chamilo: Personality Test modifications
 *
 * @version $Id: survey_list.php 10680 2007-01-11 21:26:23Z pcool $
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
$courseInfo = $course_id ? api_get_course_info_by_id($course_id) : [];
$userId = api_get_user_id();
$surveyId = (int) $_GET['survey_id'];
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
    api_not_allowed(
        true,
        Display::return_message(get_lang('InvallidSurvey'), 'error', false)
    );
}

// Getting the survey information
$survey_data = SurveyManager::get_survey($surveyId);

if (empty($survey_data)) {
    api_not_allowed(
        true,
        Display::return_message(get_lang('InvallidSurvey'), 'error', false)
    );
}

$urlname = strip_tags($survey_data['title']);
if (api_is_allowed_to_edit()) {
    // Breadcrumbs
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'survey/survey_list.php?'.api_get_cidreq(),
        'name' => get_lang('SurveyList'),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'survey/survey.php?survey_id='.$surveyId.'&'.api_get_cidreq(),
        'name' => $urlname,
    ];
}
$courseCode = isset($_GET['cidReq']) ? $_GET['cidReq'] : null;
$surveyAnonymous = SurveyManager::get_survey($surveyId, 0, $courseCode);
$surveyAnonymous = $surveyAnonymous['anonymous'];
if ($surveyAnonymous == 0 && api_is_anonymous()) {
    api_not_allowed(true);
} elseif ($surveyAnonymous == 0 && $userInvited == 0) {
    if (!api_is_allowed_to_edit()) {
        api_not_allowed(true);
    }
}

Display::display_header(get_lang('SurveyPreview'));

// We exit here is the first or last question is a pagebreak (which causes errors)
SurveyUtil::check_first_last_question($surveyId, false);
$counter_question = 0;
// Only a course admin is allowed to preview a survey: you are a course admin
if (api_is_course_admin() ||
    (api_is_course_admin() && $_GET['isStudentView'] == 'true') ||
    api_is_allowed_to_session_edit(false, true)
) {
    // Survey information
    echo '<div class="page-header"><h2>'.$survey_data['survey_title'].'</h2></div>';
    if (!empty($survey_data['survey_subtitle'])) {
        echo '<div id="survey_subtitle">'.$survey_data['survey_subtitle'].'</div>';
    }

    // Displaying the survey introduction
    if (!isset($_GET['show'])) {
        if (!empty($survey_data['survey_introduction'])) {
            echo '<div class="survey_content">'.$survey_data['survey_introduction'].'</div>';
        }
        $limit = 0;
    }

    // Displaying the survey thanks message
    if (isset($_POST['finish_survey'])) {
        echo Display::return_message(get_lang('SurveyFinished'), 'confirm');
        echo $survey_data['survey_thanks'];
        Display::display_footer();
        exit;
    }

    $questions = [];
    if (isset($_GET['show'])) {
        // Getting all the questions for this page and add them to a
        // multidimensional array where the first index is the page.
        // as long as there is no pagebreak fount we keep adding questions to the page
        $questions_displayed = [];
        $paged_questions = [];
        $counter = 0;
        $sql = "SELECT * FROM $table_survey_question
                WHERE
                  survey_question NOT LIKE '%{{%' AND 
                  c_id = $course_id AND 
                  survey_id = '".$surveyId."'
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

        $sql = "SELECT count(survey_question.question_id) as count                        
                FROM $table_survey_question survey_question
                WHERE 
                  survey_question.survey_id = '".$surveyId."' AND
                  survey_question.c_id = $course_id AND 
                  survey_question LIKE '%{{%' ";
        $result = Database::query($sql);
        $sourceQuestions = Database::fetch_array($result, 'ASSOC');
        $sourceQuestions = $sourceQuestions['count'];

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
                        survey_question.survey_id = '".$surveyId."' AND
                        survey_question.question_id IN (".Database::escape_string(implode(',', $paged_questions[$_GET['show']]), null, false).") AND
                        survey_question.c_id = $course_id AND
                        survey_question NOT LIKE '%{{%'                        
                    ORDER BY survey_question.sort, survey_question_option.sort ASC";

            $result = Database::query($sql);
            $question_counter_max = Database::num_rows($result);
            $limit = 0;

            while ($row = Database::fetch_array($result)) {
                // If the type is not a pagebreak we store it in the $questions array
                if ($row['type'] != 'pagebreak') {
                    $sort = $row['sort'];
                    $questions[$sort]['question_id'] = $row['question_id'];
                    $questions[$sort]['survey_id'] = $row['survey_id'];
                    $questions[$sort]['survey_question'] = $row['survey_question'];
                    $questions[$sort]['display'] = $row['display'];
                    $questions[$sort]['type'] = $row['type'];
                    $questions[$sort]['options'][intval($row['option_sort'])] = $row['option_text'];
                    $questions[$sort]['maximum_score'] = $row['max_value'];
                } else {
                    // If the type is a pagebreak we are finished loading the questions for this page
                    break;
                }
                $counter_question++;
            }
        }
    }

    $before = 0;
    if (isset($_GET['show']) && isset($paged_questions[$_GET['show'] - 1])) {
        $before = count($paged_questions[$_GET['show'] - 1]);
    }

    // Selecting the maximum number of pages
    $sql = "SELECT * FROM $table_survey_question
            WHERE
                survey_question NOT LIKE '%{{%' AND 
                c_id = $course_id AND
                type = 'pagebreak' AND
                survey_id = '".$surveyId."'";
    $result = Database::query($sql);
    $numberofpages = Database::num_rows($result) + 1;

    // Displaying the form with the questions
    $show = 0;
    if (isset($_GET['show'])) {
        $show = (int) $_GET['show'] + 1;
    }
    $originalShow = isset($_GET['show']) ? (int) $_GET['show'] : 0;

    $url = api_get_self().'?survey_id='.$surveyId.'&show='.$show.'&'.api_get_cidreq();
    $form = new FormValidator(
        'question-survey',
        'post',
        $url,
        null,
        null,
        FormValidator::LAYOUT_INLINE
    );

    if (is_array($questions) && count($questions) > 0) {
        $counter = 1;
        if (!empty($originalShow)) {
            $before = 0;
            foreach ($paged_questions as $keyQuestion => $list) {
                if ($originalShow > $keyQuestion) {
                    $before += count($list);
                }
            }
            $counter = $before + 1;
        }
        foreach ($questions as $key => &$question) {
            $ch_type = 'ch_'.$question['type'];
            /** @var survey_question $display */
            $display = new $ch_type();
            $form->addHtml('<div class="survey_question '.$ch_type.'">');
            $form->addHtml('<h5 class="title">'.$counter.'. '.strip_tags($question['survey_question']).'</h5>');
            $display->render($form, $question);
            $form->addHtml('</div>');
            $counter++;
        }
    }
    $form->addHtml('<div class="start-survey">');
    if (($show < $numberofpages) || (!$_GET['show'] && count($questions) > 0)) {
        if ($show == 0) {
            $form->addButton(
                'next_survey_page',
                get_lang('StartSurvey'),
                'arrow-right',
                'success'
            );
        } else {
            $form->addButton(
                'next_survey_page',
                get_lang('NextQuestion'),
                'arrow-right',
                'success'
            );
        }
    }
    if ($show >= $numberofpages && $_GET['show'] ||
        (isset($_GET['show']) && count($questions) == 0)
    ) {
        if ($questions_exists == false) {
            echo '<p>'.get_lang('ThereAreNotQuestionsForthisSurvey').'</p>';
        }
        $form->addButton(
            'finish_survey',
            get_lang('FinishSurvey'),
            'arrow-right',
            'success'
        );
    }
    $form->addHtml('</div>');
    $form->display();

    if ($courseInfo) {
        echo Display::toolbarButton(
            get_lang('ReturnToCourseHomepage'),
            api_get_course_url($courseInfo['code']),
            'home'
        );
    }
} else {
    echo Display::return_message(get_lang('NotAllowed'), 'error', false);
}

Display::display_footer();
