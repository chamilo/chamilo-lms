<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.survey
 *
 * @author  Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @author  Julio Montoya <gugli100@gmail.com>
 */
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);

if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

$surveyId = (int) $_GET['survey_id'];

if (empty($surveyId)) {
    api_not_allowed(true);
}

// Getting the survey information
$survey_data = SurveyManager::get_survey($surveyId);

if (empty($survey_data)) {
    api_not_allowed(true);
}

$this_section = SECTION_COURSES;

$table_survey_question = Database::get_course_table(TABLE_SURVEY_QUESTION);
$table_survey_question_option = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);

$course_id = api_get_course_int_id();
$courseInfo = api_get_course_info();
$allowRequiredSurveyQuestions = api_get_configuration_value('allow_required_survey_questions');

// Breadcrumbs
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'survey/survey_list.php?'.api_get_cidreq(),
    'name' => get_lang('Survey list'),
];
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'survey/survey.php?survey_id='.$surveyId.'&'.api_get_cidreq(),
    'name' => strip_tags($survey_data['title']),
];

$show = 0;
Display::display_header(get_lang('Survey preview'));

// We exit here is the first or last question is a pagebreak (which causes errors)
SurveyUtil::check_first_last_question($surveyId, false);

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
}

// Displaying the survey thanks message
if (isset($_POST['finish_survey'])) {
    echo Display::return_message(get_lang('You have finished this survey.'), 'confirm');
    echo $survey_data['survey_thanks'];
    Display::display_footer();
    exit;
}

$questions = [];
if (isset($_GET['show'])) {
    // Getting all the questions for this page and add them to a
    // multidimensional array where the first index is the page.
    // as long as there is no pagebreak fount we keep adding questions to the page
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
            if ($survey_data['one_question_per_page'] == 1) {
                if ($row['type'] != 'pagebreak') {
                    $paged_questions[$counter][] = $row['question_id'];
                    $counter++;
                    continue;
                }
            } else {
                if ($row['type'] == 'pagebreak') {
                    $counter++;
                } else {
                    $paged_questions[$counter][] = $row['question_id'];
                }
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
                    ".($allowRequiredSurveyQuestions ? ', survey_question.is_required' : '')."
                FROM $table_survey_question survey_question
                LEFT JOIN $table_survey_question_option survey_question_option
                ON
                    survey_question.question_id = survey_question_option.question_id AND
                    survey_question_option.c_id = survey_question.c_id
                WHERE
                    survey_question.survey_id = '".$surveyId."' AND
                    survey_question.question_id IN (".Database::escape_string(implode(',', $paged_questions[$_GET['show']]), null, false).") AND
                    survey_question.c_id = $course_id AND
                    survey_question NOT LIKE '%{{%'                        
                ORDER BY survey_question.sort, survey_question_option.sort ASC";

        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            // If the type is not a pagebreak we store it in the $questions array
            if ($row['type'] != 'pagebreak') {
                $sort = $row['sort'];
                $questions[$sort]['question_id'] = $row['question_id'];
                $questions[$sort]['survey_id'] = $row['survey_id'];
                $questions[$sort]['survey_question'] = $row['survey_question'];
                $questions[$sort]['display'] = $row['display'];
                $questions[$sort]['type'] = $row['type'];
                $questions[$sort]['options'][$row['question_option_id']] = $row['option_text'];
                $questions[$sort]['maximum_score'] = $row['max_value'];
                $questions[$row['sort']]['is_required'] = $allowRequiredSurveyQuestions && $row['is_required'];
            }
        }
    }
}

$numberOfPages = SurveyManager::getCountPages($survey_data);

// Displaying the form with the questions
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
        $display = survey_question::createQuestion($question['type']);
        $form->addHtml('<div class="survey_question '.$ch_type.'">');
        $form->addHtml('<div style="float:left; font-weight: bold; margin-right: 5px;"> '.$counter.'. </div>');
        $form->addHtml('<div>'.Security::remove_XSS($question['survey_question']).'</div> ');
        $display->render($form, $question);
        $form->addHtml('</div>');
        $counter++;
    }
}
$form->addHtml('<div class="start-survey">');

if ($show < $numberOfPages) {
    if ($show == 0) {
        $form->addButton(
            'next_survey_page',
            get_lang('Start the Survey'),
            'arrow-right',
            'success'
        );
    } else {
        $form->addButton(
            'next_survey_page',
            get_lang('Next question'),
            'arrow-right',
            'success'
        );
    }
}

if (isset($_GET['show'])) {
    if ($show >= $numberOfPages || count($questions) == 0) {
        if ($questions_exists == false) {
            echo '<p>'.get_lang('There are not questions for this survey').'</p>';
        }
        $form->addButton(
            'finish_survey',
            get_lang('Finish survey'),
            'arrow-right',
            'success'
        );
    }
}

$form->addHtml('</div>');
$form->display();

echo Display::toolbarButton(
    get_lang('Return to Course Homepage'),
    api_get_course_url($courseInfo['code']),
    'home'
);

Display::display_footer();
