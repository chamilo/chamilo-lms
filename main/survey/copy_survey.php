<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.backup
 */

// Setting the global file that gets the general configuration, the databases, the languages, ...
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_COURSE_MAINTENANCE;
api_protect_course_script(true);

// Notice for unauthorized people.
if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

// Breadcrumbs
$interbreadcrumb[] = array('url' => '../course_info/maintenance.php', 'name' => get_lang('Maintenance'));

// The section (for the tabs)
$this_section = SECTION_COURSES;

// Display the header
Display::display_header(get_lang('CopySurvey'));
echo Display::page_header(get_lang('CopySurvey'));

/* MAIN CODE */

// If a CourseSelectForm is posted or we should copy all resources, then copy them
if (Security::check_token('post')) {
    // Clear token
    Security::clear_token();
    $surveyId = intval($_GET['survey_id']);
    $arraySent = json_decode(Security::remove_XSS($_POST['destination_course']));
    $courseId = $arraySent->courseId;
    $sessionId = $arraySent->sessionId;
    // Copy the survey to the target course
    $surveyCopyId = SurveyManager::copySurveySession($surveyId, $courseId, $sessionId);
    // Empty the copied survey
    SurveyManager::emptySurveyFromId($surveyCopyId);
    Display::display_confirmation_message(get_lang('SurveyCopied'));
}


$survey = SurveyManager::get_survey($_GET['survey_id']);
$courses = CourseManager::getAllCoursesArray();
// Survey
$options = array();
$currentCourseId = api_get_course_int_id();
$currentSessionId = api_get_session_id();
$option = str_replace("&nbsp;", '', strip_tags($survey['title']));
$options = array();
foreach ($courses as $course) {
    if (($course['id'] != $currentCourseId || $course['session_id'] != $currentSessionId) &&
        (api_is_global_platform_admin() || (CourseManager::is_course_teacher(api_get_user_id(), $course['code'])
                && $course['session_id'] == 0) || api_is_coach($course['session_id'], $course['id']))) {
        $value = array("courseId" => $course['id'], "sessionId" => $course['session_id']);
        if (isset($course['session_name'])) {
            $options[json_encode($value)] = $course['title'].' ['.$course['session_name'].']';
        } else {
            $options[json_encode($value)] = $course['title'];
        }
    }
}

$form = new FormValidator('copy_survey', 'post', 'copy_survey.php?survey_id='.$_GET['survey_id'].api_get_cidreq());
if (!$survey) {
    Display::display_error_message(get_lang('NoSurveyAvailable'));
}
if (count($courses) < 1 || count($options) < 1) {
    Display::display_error_message(get_lang('CourseListNotAvailable'));
}
if ($survey && count($courses) >= 1 && count($options) >= 1) {
    $form->addElement('text', 'survey_title', get_lang('Survey'), array('value' => $option, 'disabled' => 'disabled'));
    $form->addElement('select', 'destination_course', get_lang('SelectDestinationCourse'), $options);
    $form->addButtonCopy(get_lang('CopySurvey'));
}


// Add Security token
$token = Security::get_token();
$form->addElement('hidden', 'sec_token');
$form->setConstants(array('sec_token' => $token));

$form->display();

Display::display_footer();
