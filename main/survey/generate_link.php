<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

if (!api_is_allowed_to_edit(false, true)) {
    api_not_allowed(true);
}

$survey_id = isset($_REQUEST['survey_id']) ? (int) $_REQUEST['survey_id'] : null;

if (empty($survey_id)) {
    api_not_allowed(true);
}

$survey_data = SurveyManager::get_survey($survey_id);

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'survey/survey_list.php?'.api_get_cidreq(),
    'name' => get_lang('SurveyList'),
];
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'survey/survey.php?survey_id='.$survey_id.'&'.api_get_cidreq(),
    'name' => strip_tags($survey_data['title']),
];

Display::display_header(get_lang('Survey'), 'Survey');

if (!SurveyManager::survey_generation_hash_available()) {
    api_not_allowed(true);
}

$link = SurveyManager::generate_survey_link(
    $survey_id,
    api_get_course_int_id(),
    api_get_session_id(),
    api_get_group_id()
);
echo '<div class="row">';
    echo '<div class="col-md-12">';
    echo Display::url(get_lang('GoToSurvey'), $link, ['class' => 'btn btn-primary btn-large']);
    echo '</div>';
    echo '<div class="col-md-12">';
    echo get_lang('GenerateSurveyAccessLinkExplanation');

    echo '<pre>';
    echo $link;
    echo '</pre>';

    echo '</div>';
echo '</div>';
