<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup, refactoring and rewriting large parts of
 *         the code
 * @author Julio Montoya Armas <gugli100@gmail.com>, Chamilo: Personality Test modification and rewriting large parts
 *         of the code
 *
 * @todo   use quickforms for the forms
 */
if (!isset($_GET['cidReq'])) {
    $_GET['cidReq'] = 'none'; // Prevent sql errors
    $cidReset = true;
}

require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_COURSES;
$current_course_tool = TOOL_SURVEY;
$currentUserId = api_get_user_id();

api_protect_course_script(true);
$action = isset($_REQUEST['action']) ? Security::remove_XSS($_REQUEST['action']) : '';

// Tracking
Event::event_access_tool(TOOL_SURVEY);

$logInfo = [
    'tool' => TOOL_SURVEY,
];
Event::registerLog($logInfo);

/** @todo
 * This has to be moved to a more appropriate place (after the display_header
 * of the code)
 */
$courseInfo = api_get_course_info();
$sessionId = api_get_session_id();
$isDrhOfCourse = CourseManager::isUserSubscribedInCourseAsDrh(
    $currentUserId,
    $courseInfo
);

$htmlHeadXtra[] = '<script>'.api_get_language_translate_html().'</script>';

if ($isDrhOfCourse) {
    Display::display_header(get_lang('SurveyList'));
    Display::display_introduction_section('survey', 'left');
    SurveyUtil::displaySurveyListForDrh();
    Display::display_footer();
    exit;
}

if (!api_is_allowed_to_edit(false, true)) {
    // Coach can see this
    Display::display_header(get_lang('SurveyList'));
    Display::display_introduction_section('survey', 'left');
    SurveyUtil::getSurveyList($currentUserId);
    Display::display_footer();
    exit;
}

$extend_rights_for_coachs = api_get_setting('extend_rights_for_coach_on_survey');

Session::erase('answer_count');
Session::erase('answer_list');
$tool_name = get_lang('SurveyList');
// Language variables
if (isset($_GET['search']) && $_GET['search'] === 'advanced') {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'survey/survey_list.php',
        'name' => get_lang('SurveyList'),
    ];
    $tool_name = get_lang('SearchASurvey');
}

$listUrl = api_get_path(WEB_CODE_PATH).'survey/survey_list.php?'.api_get_cidreq();
$surveyId = isset($_GET['survey_id']) ? $_GET['survey_id'] : 0;

// Action handling: performing the same action on multiple surveys
if (isset($_POST['action']) && $_POST['action'] && isset($_POST['id']) && is_array($_POST['id'])) {
    if (!api_is_allowed_to_edit()) {
        api_not_allowed(true);
    }

    foreach ($_POST['id'] as $value) {
        $surveyData = SurveyManager::get_survey($value);
        if (empty($surveyData)) {
            continue;
        }
        $surveyData['title'] = strip_tags($surveyData['title']);

        switch ($action) {
            case 'send_to_tutors':
                $result = SurveyManager::sendToTutors($value);
                if ($result) {
                    Display::addFlash(
                        Display::return_message(get_lang('InvitationHasBeenSent').': '.$surveyData['title'], 'confirmation', false)
                    );
                } else {
                    Display::addFlash(
                        Display::return_message(get_lang('InvitationHasBeenNotSent').': '.$surveyData['title'], 'warning', false)
                    );
                }
                break;
            case 'multiplicate':
                $result = SurveyManager::multiplicateQuestions($surveyData);
                $title = $surveyData['title'];
                if ($result) {
                    Display::addFlash(
                        Display::return_message(
                            sprintf(get_lang('SurveyXMultiplicated'), $title),
                            'confirmation',
                            false
                        )
                    );
                } else {
                    Display::addFlash(
                        Display::return_message(
                            sprintf(get_lang('SurveyXNotMultiplicated'), $title),
                            'warning',
                            false
                        )
                    );
                }
                break;
            case 'delete':
                // if the survey is shared => also delete the shared content
                if (is_numeric($surveyData['survey_share'])) {
                    SurveyManager::delete_survey($surveyData['survey_share'], true);
                }

                // delete the actual survey
                SurveyManager::delete_survey($value);

                Display::addFlash(
                    Display::return_message(get_lang('SurveysDeleted').': '.$surveyData['title'], 'confirmation', false)
                );
                break;
        }

        header('Location: '.$listUrl);
        exit;
    }
}

switch ($action) {
    case 'send_to_tutors':
        if (!api_is_allowed_to_edit()) {
            api_not_allowed(true);
        }
        $result = SurveyManager::sendToTutors($surveyId);
        if ($result) {
            Display::addFlash(Display::return_message(get_lang('Updated'), 'confirmation', false));
        }

        header('Location: '.$listUrl);
        exit;
        break;
    case 'multiplicate':
        if (!api_is_allowed_to_edit()) {
            api_not_allowed(true);
        }
        $surveyData = SurveyManager::get_survey($surveyId);
        if (!empty($surveyData)) {
            SurveyManager::multiplicateQuestions($surveyData);
            Display::cleanFlashMessages();
            Display::addFlash(Display::return_message(get_lang('Updated'), 'confirmation', false));
        }
        header('Location: '.$listUrl);
        exit;
        break;
    case 'remove_multiplicate':
        if (!api_is_allowed_to_edit()) {
            api_not_allowed(true);
        }
        $surveyData = SurveyManager::get_survey($surveyId);
        if (!empty($surveyData)) {
            SurveyManager::removeMultiplicateQuestions($surveyData);
            Display::addFlash(Display::return_message(get_lang('Updated'), 'confirmation', false));
        }
        header('Location: '.$listUrl);
        exit;
        break;
    case 'copy_survey':
        if (!empty($surveyId) && api_is_allowed_to_edit()) {
            SurveyManager::copy_survey($surveyId);
            Display::addFlash(Display::return_message(get_lang('SurveyCopied'), 'confirmation', false));
            header('Location: '.$listUrl);
            exit;
        }
        break;
    case 'delete':
        if (!empty($surveyId)) {
            // Getting the information of the survey (used for when the survey is shared)
            $survey_data = SurveyManager::get_survey($surveyId);
            if (api_is_session_general_coach() && $sessionId != $survey_data['session_id']) {
                // The coach can't delete a survey not belonging to his session
                api_not_allowed();
            }
            // If the survey is shared => also delete the shared content
            if (isset($survey_data['survey_share']) &&
                is_numeric($survey_data['survey_share'])
            ) {
                SurveyManager::delete_survey($survey_data['survey_share'], true);
            }

            $return = SurveyManager::delete_survey($surveyId);

            if ($return) {
                Display::addFlash(Display::return_message(get_lang('SurveyDeleted'), 'confirmation', false));
            } else {
                Display::addFlash(Display::return_message(get_lang('ErrorOccurred'), 'error', false));
            }
            header('Location: '.$listUrl);
            exit;
        }
        break;
    case 'empty':
        $mysession = api_get_session_id();
        if ($mysession != 0) {
            if (!((api_is_session_general_coach() || api_is_platform_admin()) &&
                api_is_element_in_the_session(TOOL_SURVEY, $surveyId))) {
                // The coach can't empty a survey not belonging to his session
                api_not_allowed();
            }
        } else {
            if (!(api_is_course_admin() || api_is_platform_admin())) {
                api_not_allowed();
            }
        }
        $return = SurveyManager::empty_survey($surveyId);
        if ($return) {
            Display::addFlash(Display::return_message(get_lang('SurveyEmptied'), 'confirmation', false));
        } else {
            Display::addFlash(Display::return_message(get_lang('ErrorOccurred'), 'error', false));
        }
        header('Location: '.$listUrl);
        exit;
        break;
}

Display::display_header($tool_name, 'Survey');
Display::display_introduction_section('survey', 'left');

// Action handling: searching
if (isset($_GET['search']) && $_GET['search'] === 'advanced') {
    SurveyUtil::display_survey_search_form();
}

echo '<div class="actions">';
if (!api_is_session_general_coach() || $extend_rights_for_coachs === 'true') {
    // Action links
    echo '<a href="'.api_get_path(WEB_CODE_PATH).'survey/create_new_survey.php?'.api_get_cidreq().'&amp;action=add">'.
        Display::return_icon('new_survey.png', get_lang('CreateNewSurvey'), '', ICON_SIZE_MEDIUM).'</a> ';
    $url = api_get_path(WEB_CODE_PATH).'survey/create_meeting.php?'.api_get_cidreq();
    echo Display::url(
        Display::return_icon('add_doodle.png', get_lang('CreateNewSurveyDoodle'), '', ICON_SIZE_MEDIUM),
        $url
    );
}
echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;search=advanced">'.
    Display::return_icon('search.png', get_lang('Search'), '', ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

// Load main content
if (api_is_session_general_coach() && $extend_rights_for_coachs == 'false') {
    SurveyUtil::display_survey_list_for_coach();
} else {
    SurveyUtil::display_survey_list();
}

Display::display_footer();

/* Bypass functions to make direct use from SortableTable possible */
function get_number_of_surveys()
{
    return SurveyUtil::get_number_of_surveys();
}

function get_survey_data($from, $number_of_items, $column, $direction)
{
    return SurveyUtil::get_survey_data($from, $number_of_items, $column, $direction);
}

function modify_filter($survey_id)
{
    return SurveyUtil::modify_filter($survey_id, false);
}

function modify_filter_drh($survey_id)
{
    return SurveyUtil::modify_filter($survey_id, true);
}

function get_number_of_surveys_for_coach()
{
    return SurveyUtil::get_number_of_surveys_for_coach();
}

function get_survey_data_for_coach($from, $number_of_items, $column, $direction)
{
    return SurveyUtil::get_survey_data_for_coach($from, $number_of_items, $column, $direction);
}

function modify_filter_for_coach($survey_id)
{
    return SurveyUtil::modify_filter_for_coach($survey_id);
}

function anonymous_filter($anonymous)
{
    return SurveyUtil::anonymous_filter($anonymous);
}

function get_survey_data_drh($from, $number_of_items, $column, $direction)
{
    return SurveyUtil::get_survey_data($from, $number_of_items, $column, $direction, true);
}
