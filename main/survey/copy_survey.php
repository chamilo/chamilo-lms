<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

$current_course_tool = TOOL_COURSE_MAINTENANCE;

api_protect_course_script(true);

// Notice for unauthorized people.
if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

// Breadcrumbs
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'survey/survey_list.php?'.api_get_cidreq(),
    'name' => get_lang('SurveyList'),
];

// The section (for the tabs)
$this_section = SECTION_COURSES;

$surveyId = isset($_GET['survey_id']) ? (int) $_GET['survey_id'] : 0;

if (empty($surveyId)) {
    api_not_allowed(true);
}

$survey = SurveyManager::get_survey($surveyId);
if (empty($survey)) {
    api_not_allowed(true);
}

$surveyTitle = str_replace('&nbsp;', '', strip_tags($survey['title'].' ('.$survey['code'].') '));

$form = new FormValidator('copy_survey', 'post', api_get_self().'?survey_id='.$surveyId.'&'.api_get_cidreq());
$form->addElement(
    'text',
    'survey_title',
    get_lang('Survey'),
    ['value' => $surveyTitle, 'disabled' => 'disabled']
);
$form->addSelectAjax(
    'destination_course',
    get_lang('SelectDestinationCourse'),
    null,
    [
        'url' => api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=get_my_courses_and_sessions&'.api_get_cidreq(),
    ]
);

$form->addButtonCopy(get_lang('CopySurvey'));

// Add Security token
$token = Security::get_existing_token();
$form->addElement('hidden', 'sec_token');
$form->setConstants(['sec_token' => $token]);

// If a CourseSelectForm is posted or we should copy all resources, then copy them
if ($form->validate() && Security::check_token('post')) {
    // Clear token
    Security::clear_token();
    $values = $form->getSubmitValues();
    $courseKey = $values['destination_course'];
    $courseParts = explode('_', $courseKey);
    $courseId = $courseParts[0];
    $sessionId = $courseParts[1];

    // Copy the survey to the target course
    $surveyCopyId = SurveyManager::copySurveySession($surveyId, $courseId, $sessionId);
    if ($surveyCopyId) {
        // Empty the copied survey
        SurveyManager::emptySurveyFromId($surveyCopyId);
        Display::addFlash(Display::return_message(get_lang('SurveyCopied')));
    } else {
        Display::addFlash(Display::return_message(get_lang('ThereWasAnError'), 'warning'));
    }

    header('Location: '.api_get_self().'?'.api_get_cidreq().'&survey_id='.$surveyId);
    exit;
}

Display::display_header(get_lang('CopySurvey'));
echo Display::page_header(get_lang('CopySurvey'));
$form->display();

Display::display_footer();
