<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

/**
 * @author unknown, the initial survey that did not make it in 1.8 because of bad code
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup,
 * refactoring and rewriting large parts of the code
 */
require_once __DIR__.'/../inc/global.inc.php';

//$htmlHeadXtra[] = '<script>'.api_get_language_translate_html().'</script>';

/** @todo this has to be moved to a more appropriate place (after the display_header of the code)*/
if (!api_is_allowed_to_edit(false, true)) {
    api_not_allowed(true);
}

$surveyId = isset($_GET['survey_id']) ? (int) $_GET['survey_id'] : 0;
// Getting the survey information
$surveyData = SurveyManager::get_survey($surveyId);
if (empty($surveyData)) {
    api_not_allowed(true);
}

$course_id = api_get_course_int_id();
$urlname = api_substr(api_html_entity_decode($surveyData['title'], ENT_QUOTES), 0, 40);
if (api_strlen(strip_tags($surveyData['title'])) > 40) {
    $urlname .= '...';
}

if (1 == $surveyData['survey_type']) {
    $sql = 'SELECT id FROM '.Database::get_course_table(TABLE_SURVEY_QUESTION_GROUP).'
            WHERE
                c_id = '.$course_id.' AND
                survey_id = '.$surveyId.' LIMIT 1';
    $rs = Database::query($sql);
    if (0 === Database::num_rows($rs)) {
        Display::addFlash(
            Display::return_message(get_lang('You need to create groups'))
        );
        header('Location: '.api_get_path(WEB_CODE_PATH).'survey/survey.php?survey_id='.$surveyId);
        exit;
    }
}

$surveyUrl = api_get_path(WEB_CODE_PATH).'survey/survey.php?survey_id='.$surveyId.'&'.api_get_cidreq();

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'survey/survey_list.php?'.api_get_cidreq(),
    'name' => get_lang('Survey list'),
];
$interbreadcrumb[] = [
    'url' => $surveyUrl,
    'name' => strip_tags($urlname),
];

// Tool name
if ('add' === $_GET['action']) {
    $tool_name = get_lang('Add a question');
}
if ('edit' === $_GET['action']) {
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
    'selectivedisplay',
    'multiplechoiceother',
];

$actions = '<div class="actions">';
$actions .= '<a href="'.$surveyUrl.'">'.
    Display::return_icon('back.png', get_lang('Back to survey'), '', ICON_SIZE_MEDIUM).'</a>';
$actions .= '</div>';
// Checking if it is a valid type
if (!in_array($_GET['type'], $possible_types)) {
    api_not_allowed(true, Display::return_message(get_lang('TypeDoesNotExist'), 'error', false));
}

// Displaying the form for adding or editing the question
$surveyQuestion = survey_question::createQuestion($_GET['type']);

// The defaults values for the form
$formData = [];
$formData['answers'] = ['', ''];

switch ($_GET['type']) {
    case 'selectivedisplay':
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
    $survey = Container::getSurveyRepository()->find($surveyId);
    $surveyQuestion->save($survey, $values, $formData);
}

Display::display_header($tool_name, 'Survey');
echo $surveyQuestion->getForm()->returnForm();
Display::display_footer();
