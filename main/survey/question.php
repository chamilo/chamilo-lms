<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.survey
 *
 * @author unknown, the initial survey that did not make it in 1.8 because of bad code
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup,
 * refactoring and rewriting large parts of the code
 */
require_once __DIR__.'/../inc/global.inc.php';

$htmlHeadXtra[] = '<script>
$(function() {
    $("button").click(function() {
        $("#is_executable").attr("value",$(this).attr("name"));
    });
} ); </script>';

/** @todo this has to be moved to a more appropriate place (after the display_header of the code)*/
if (!api_is_allowed_to_edit(false, true)) {
    api_not_allowed(true);
}

// Getting the survey information
$surveyData = SurveyManager::get_survey($_GET['survey_id']);
if (empty($surveyData)) {
    api_not_allowed(true);
}

$course_id = api_get_course_int_id();
$urlname = api_substr(api_html_entity_decode($surveyData['title'], ENT_QUOTES), 0, 40);
if (api_strlen(strip_tags($surveyData['title'])) > 40) {
    $urlname .= '...';
}

if ($surveyData['survey_type'] == 1) {
    $sql = 'SELECT id FROM '.Database::get_course_table(TABLE_SURVEY_QUESTION_GROUP).'
            WHERE
                c_id = '.$course_id.' AND
                survey_id = '.(int) $_GET['survey_id'].' LIMIT 1';
    $rs = Database::query($sql);
    if (Database::num_rows($rs) === 0) {
        Display::addFlash(
            Display::return_message(get_lang('You need to create groups'))
        );
        header('Location: '.api_get_path(WEB_CODE_PATH).'survey/survey.php?survey_id='.(int) $_GET['survey_id']);
        exit;
    }
}

// Breadcrumbs
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'survey/survey_list.php',
    'name' => get_lang('Survey list'),
];
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'survey/survey.php?survey_id='.intval($_GET['survey_id']),
    'name' => strip_tags($urlname),
];

// Tool name
if ($_GET['action'] == 'add') {
    $tool_name = get_lang('Add a question');
}
if ($_GET['action'] == 'edit') {
    $tool_name = get_lang('Edit question');
}

// The possible question types
$possible_types = [
    'personality',
    'yesno',
    'multiplechoice',
    'multipleresponse',
    'open',
    'dropdown',
    'comment',
    'pagebreak',
    'percentage',
    'score',
];

// Actions
$actions = '<div class="actions">';
$actions .= '<a href="'.api_get_path(WEB_CODE_PATH).'survey/survey.php?survey_id='.intval($_GET['survey_id']).'">'.
    Display::return_icon('back.png', get_lang('Back to survey'), '', ICON_SIZE_MEDIUM).'</a>';
$actions .= '</div>';
// Checking if it is a valid type
if (!in_array($_GET['type'], $possible_types)) {
    Display :: display_header($tool_name, 'Survey');
    echo $actions;
    echo Display::return_message(get_lang('This type does not exist'), 'error', false);
    Display::display_footer();
}

// Displaying the form for adding or editing the question
$surveyQuestion = survey_question::createQuestion($_GET['type']);

// The defaults values for the form
$formData = [];
$formData['answers'] = ['', ''];

switch ($_GET['type']) {
    case 'yesno':
        $formData['answers'][0] = get_lang('Yes');
        $formData['answers'][1] = get_lang('No');
        break;
    case 'personality':
        $formData['answers'][0] = 1;
        $formData['answers'][1] = 2;
        $formData['answers'][2] = 3;
        $formData['answers'][3] = 4;
        $formData['answers'][4] = 5;

        $formData['values'][0] = 0;
        $formData['values'][1] = 0;
        $formData['values'][2] = 1;
        $formData['values'][3] = 2;
        $formData['values'][4] = 3;
        break;
    case 'open':
        Display::addFlash(Display::return_message(get_lang('You can use the tags {{class_name}} and {{student_full_name}} in the question to be able to multiplicate questions.')));
        break;
}

// We are editing a question
if (isset($_GET['question_id']) && !empty($_GET['question_id'])) {
    $formData = SurveyManager::get_question($_GET['question_id']);
}

$formData = $surveyQuestion->preSave($formData);
$surveyQuestion->createForm($surveyData, $formData);
$surveyQuestion->getForm()->setDefaults($formData);
$surveyQuestion->renderForm();

if ($surveyQuestion->getForm()->validate()) {
    $values = $surveyQuestion->getForm()->getSubmitValues();
    $surveyQuestion->save($surveyData, $values);
}

Display::display_header($tool_name, 'Survey');
echo $surveyQuestion->getForm()->returnForm();
Display::display_footer();
