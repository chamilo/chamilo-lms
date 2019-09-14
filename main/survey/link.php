<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

$surveyId = isset($_REQUEST['i']) ? (int) $_REQUEST['i'] : 0;
$sessionId = isset($_REQUEST['s']) ? (int) $_REQUEST['s'] : 0;
$courseId = isset($_REQUEST['c']) ? (int) $_REQUEST['c'] : 0;

if (empty($surveyId)) {
    api_not_allowed(true);
}
if (!SurveyManager::survey_generation_hash_available()) {
    api_not_allowed(true);
}
$courseInfo = api_get_course_info_by_id($courseId);
$hashIsValid = SurveyManager::validate_survey_hash(
    $surveyId,
    $courseId,
    $sessionId,
    $_REQUEST['g'],
    $_REQUEST['h']
);
if ($hashIsValid && $courseInfo) {
    $survey_data = SurveyManager::get_survey(
        $surveyId,
        null,
        $courseInfo['code']
    );

    $invitation_code = api_get_unique_id();
    $params = [
        'c_id' => $courseId,
        'session_id' => $sessionId,
        'user' => $invitation_code,
        'survey_code' => $survey_data['code'],
        'invitation_code' => $invitation_code,
        'invitation_date' => api_get_utc_datetime(),
    ];
    $invitation_id = SurveyUtil::save_invitation($params);

    if ($invitation_id) {
        $link = SurveyUtil::generateFillSurveyLink($invitation_code, $courseInfo['code'], $sessionId);
        header('Location: '.$link);
        exit;
    }
} else {
    api_not_allowed(true);
}
