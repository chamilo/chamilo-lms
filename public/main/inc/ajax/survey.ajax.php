<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../global.inc.php';

$current_user_id = api_get_user_id();
$courseId = api_get_course_int_id();

$action = isset($_GET['a']) ? $_GET['a'] : null;
$surveyId = isset($_REQUEST['survey_id']) ? $_REQUEST['survey_id'] : 0;
$questionId = isset($_REQUEST['question_id']) ? $_REQUEST['question_id'] : 0;

switch ($action) {
    case 'load_question_options':
        if (!api_is_allowed_to_edit(false, true)) {
            exit;
        }
        $question = SurveyManager::get_question($questionId);
        if (!empty($question) && !empty($question['answer_data'])) {
            $optionList = [];
            foreach ($question['answer_data'] as $answer) {
                $optionList[$answer['iid']] = strip_tags($answer['data']);
            }
            echo json_encode($optionList);
        }
        break;
    case 'save_question':
        if (api_is_anonymous()) {
            echo '';
            break;
        }
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
