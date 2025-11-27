<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CSurvey;

/**
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

$repo = Container::getSurveyRepository();

/** @var CSurvey $survey */
$survey = $repo->find($surveyId);
if (null === $survey) {
    api_not_allowed(true);
}

$this_section = SECTION_COURSES;
$table_survey_question = Database::get_course_table(TABLE_SURVEY_QUESTION);
$table_survey_question_option = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);

$course_id = api_get_course_int_id();
$courseInfo = api_get_course_info();
$allowRequiredSurveyQuestions = true;

// -----------------------------------------------------------------------------
// Breadcrumb
// -----------------------------------------------------------------------------
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'survey/survey_list.php?'.api_get_cidreq(),
    'name' => get_lang('Survey list'),
];
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'survey/survey.php?survey_id='.$surveyId.'&'.api_get_cidreq(),
    // Strip HTML from the title safely
    'name' => Security::remove_XSS(strip_tags($survey->getTitle())),
];

$htmlHeadXtra[] = ch_selectivedisplay::getJs();
$htmlHeadXtra[] = survey_question::getJs();
Display::display_header(get_lang('Survey preview'));

// -----------------------------------------------------------------------------
// Page header
// -----------------------------------------------------------------------------
echo '<div class="mx-auto mt-8 bg-white shadow rounded-2xl p-6 border border-gray-50">';
echo '<h2 class="text-2xl font-bold text-gray-800 mb-2">'.Security::remove_XSS($survey->getTitle()).'</h2>';

if (!empty($survey->getSubtitle())) {
    echo '<p class="text-gray-600 mb-4">'.Security::remove_XSS($survey->getSubtitle()).'</p>';
}

// Introduction
if (!isset($_GET['show']) && !empty($survey->getIntro())) {
    echo '<div class="prose prose-slate max-w-none mb-6">'.Security::remove_XSS($survey->getIntro()).'</div>';
}

// -----------------------------------------------------------------------------
// End message if finished
// -----------------------------------------------------------------------------
if (isset($_POST['finish_survey'])) {
    echo Display::return_message(get_lang('You have finished this survey.'), 'confirm');
    echo '<div class="prose prose-slate">'.Security::remove_XSS($survey->getSurveythanks()).'</div>';
    Display::display_footer();

    exit;
}

// -----------------------------------------------------------------------------
// Build questions per page
// -----------------------------------------------------------------------------
$questions = [];
$pageBreakText = [];
if (isset($_GET['show'])) {
    $paged_questions = [];
    $counter = 0;
    $sql = "SELECT * FROM $table_survey_question
            WHERE survey_question NOT LIKE '%{{%' AND survey_id = $surveyId
            ORDER BY sort ASC";
    $result = Database::query($sql);
    $questions_exists = true;
    if (Database::num_rows($result)) {
        while ($row = Database::fetch_array($result)) {
            if (1 == $survey->getOneQuestionPerPage()) {
                if ('pagebreak' !== $row['type']) {
                    $paged_questions[$counter][] = $row['iid'];
                    $counter++;

                    continue;
                }
            } else {
                if ('pagebreak' === $row['type']) {
                    $counter++;
                    $pageBreakText[$counter] = $row['survey_question'];
                } else {
                    $paged_questions[$counter][] = $row['iid'];
                }
            }
        }
    } else {
        $questions_exists = false;
    }

    if (array_key_exists($_GET['show'], $paged_questions)) {
        $select = 'survey_question.parent_id, survey_question.parent_option_id,';
        $sql = "SELECT
                    survey_question.iid question_id,
                    survey_question.survey_id,
                    survey_question.survey_question,
                    survey_question.display,
                    survey_question.sort,
                    survey_question.type,
                    survey_question.max_value,
                    survey_question_option.iid as question_option_id,
                    survey_question_option.option_text,
                    $select
                    survey_question_option.sort as option_sort
                    ".($allowRequiredSurveyQuestions ? ', survey_question.is_required' : '')."
                FROM $table_survey_question survey_question
                LEFT JOIN $table_survey_question_option survey_question_option
                ON survey_question.iid = survey_question_option.question_id
                WHERE survey_question.survey_id = '".$surveyId."'
                    AND survey_question.iid IN (".Database::escape_string(implode(',', $paged_questions[$_GET['show']]), null, false).")
                    AND survey_question NOT LIKE '%{{%'
                ORDER BY survey_question.sort, survey_question_option.sort ASC";

        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            if ('pagebreak' !== $row['type']) {
                $sort = $row['sort'];
                $questions[$sort]['question_id'] = $row['question_id'];
                $questions[$sort]['survey_id'] = $row['survey_id'];
                $questions[$sort]['survey_question'] = Security::remove_XSS($row['survey_question']);
                $questions[$sort]['display'] = $row['display'];
                $questions[$sort]['type'] = $row['type'];
                $questions[$sort]['options'][$row['question_option_id']] = Security::remove_XSS($row['option_text']);
                $questions[$sort]['maximum_score'] = $row['max_value'];
                $questions[$sort]['parent_id'] = $row['parent_id'] ?? 0;
                $questions[$sort]['parent_option_id'] = $row['parent_option_id'] ?? 0;
                $questions[$row['sort']]['is_required'] = $allowRequiredSurveyQuestions && $row['is_required'];
            }
        }
    }
}

$numberOfPages = SurveyManager::getCountPages($survey);
$show = isset($_GET['show']) ? (int) $_GET['show'] + 1 : 0;
$originalShow = isset($_GET['show']) ? (int) $_GET['show'] : 0;
$url = api_get_self().'?survey_id='.$surveyId.'&show='.$show.'&'.api_get_cidreq();

$form = new FormValidator('question-survey', 'post', $url, null, null, FormValidator::LAYOUT_HORIZONTAL);

// -----------------------------------------------------------------------------
// Question rendering
// -----------------------------------------------------------------------------
if (!empty($questions)) {
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

    $showNumber = !SurveyManager::hasDependency($survey);
    $js = '';

    if (isset($pageBreakText[$originalShow]) && !empty(strip_tags($pageBreakText[$originalShow]))) {
        $form->addHtml('<div class="mb-4 p-3 bg-gray-50 rounded">'.Security::remove_XSS($pageBreakText[$originalShow]).'</div>');
    }

    foreach ($questions as $key => &$question) {
        $ch_type = 'ch_'.$question['type'];
        $display = survey_question::createQuestion($question['type']);
        $parent = $question['parent_id'];
        $parentClass = '';

        if (!empty($parent)) {
            $parentClass = ' with_parent with_parent_'.$question['question_id'];
            $parents = survey_question::getParents($question['question_id']);
            if (!empty($parents)) {
                foreach ($parents as $parentId) {
                    $parentClass .= ' with_parent_only_hide_'.$parentId;
                }
            }
        }

        $js .= survey_question::getQuestionJs($question);
        $form->addHtml('<div class="survey_question '.$ch_type.' '.$parentClass.' mb-6 p-4 bg-gray-10 rounded-lg border border-gray-50">');
        if ($showNumber && $survey->isDisplayQuestionNumber()) {
            $form->addHtml('<div class="font-semibold text-blue-700 mb-1"> '.$counter.'. </div>');
        }
        $form->addHtml('<div class="text-gray-800 mb-2">'.Security::remove_XSS($question['survey_question']).'</div>');
        $display->render($form, $question);
        $form->addHtml('</div>');
        $counter++;
    }

    $form->addHtml($js);
}

// -----------------------------------------------------------------------------
// Navigation buttons
// -----------------------------------------------------------------------------
$form->addHtml('<div class="flex justify-between mt-6 gap-3">');

// "Previous" button
if (
    isset($_GET['show'])
    && $_GET['show'] > 0
    && 'true' === api_get_setting('survey.survey_backwards_enable')
    && 1 === (int) $survey->getOneQuestionPerPage()
) {
    $prevShow = (int) $_GET['show'] - 1;
    $prevUrl = api_get_self().'?survey_id='.$surveyId.'&show='.$prevShow.'&'.api_get_cidreq();

    $form->addHtml(
        '<a href="'.$prevUrl.'" class="btn btn--plain-outline">
            <i class="mdi mdi-arrow-left mr-2"></i>'.get_lang('Previous question').'
        </a>'
    );
}

if ($show < $numberOfPages) {
    $label = 0 == $show ? get_lang('Start the Survey') : get_lang('Next question');
    $form->addButton('next_survey_page', $label, 'arrow-right', 'success');
}

if (isset($_GET['show']) && ($show >= $numberOfPages || empty($questions))) {
    if (false == $questions_exists) {
        echo '<p class="text-gray-600">'.get_lang('There are no questions for this survey').'</p>';
    }
    $form->addButton('finish_survey', get_lang('Finish survey'), 'check', 'success');
}

$form->addHtml('</div>');

// -----------------------------------------------------------------------------
// Render form + footer
// -----------------------------------------------------------------------------
$form->display();
echo '</div>'; // container
Display::display_footer();
