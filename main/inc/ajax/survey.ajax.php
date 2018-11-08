<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../global.inc.php';

$action = isset($_GET['a']) ? $_GET['a'] : null;

$current_user_id = api_get_user_id();
$courseId = api_get_course_int_id();

switch ($action) {
    case 'save_question':
        if (api_is_anonymous()) {
            echo '';
            break;
        }
        $surveyId = isset($_GET['survey_id']) ? $_GET['survey_id'] : null;
        $questionId = isset($_GET['question_id']) ? $_GET['question_id'] : null;
        $status = isset($_GET['status']) ? (int) $_GET['status'] : null;
        $userId = api_get_user_id();

        $surveyData = SurveyManager::get_survey($surveyId);

        if (empty($surveyData)) {
            exit;
        }

        SurveyUtil::remove_answer(
            $userId,
            $surveyId,
            $questionId,
            $courseId
        );

        SurveyUtil::store_answer(
            $userId,
            $surveyId,
            $questionId,
            1,
            $status,
            $surveyData
        );

        break;
}
exit;
