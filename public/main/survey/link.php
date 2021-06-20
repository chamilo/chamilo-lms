<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

require_once __DIR__.'/../inc/global.inc.php';

$surveyId = isset($_REQUEST['i']) ? (int) $_REQUEST['i'] : 0;
$sessionId = isset($_REQUEST['s']) ? (int) $_REQUEST['s'] : 0;
$courseId = isset($_REQUEST['c']) ? (int) $_REQUEST['c'] : 0;

if (empty($surveyId)) {
    api_not_allowed(true);
}
$repo = Container::getSurveyRepository();
$survey = $repo->find($surveyId);
if (null === $survey) {
    api_not_allowed(true);
}

if (!SurveyManager::survey_generation_hash_available()) {
    api_not_allowed(true);
}
$course = api_get_course_entity($courseId);
$hashIsValid = SurveyManager::validate_survey_hash(
    $surveyId,
    $courseId,
    $sessionId,
    $_REQUEST['g'],
    $_REQUEST['h']
);
if ($hashIsValid && $course) {
    $invitationCode = api_get_unique_id();
    $invitation = SurveyUtil::saveInvitation(
        $invitationCode,
        $invitationCode,
        api_get_utc_datetime(time(), false, true),
        $survey,
        $course,
        api_get_session_entity()
    );
    if ($invitation) {
        $link = SurveyUtil::generateFillSurveyLink($survey, $invitationCode, $course, $sessionId);
        header('Location: '.$link);
        exit;
    }
} else {
    api_not_allowed(true);
}
