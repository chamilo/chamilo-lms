<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyQuestion;

require_once __DIR__.'/../global.inc.php';

$current_user_id = api_get_user_id();
$courseId = api_get_course_int_id();

$repo = Container::getSurveyRepository();
$repoQuestion = Container::getSurveyQuestionRepository();

$action = $_GET['a'] ?? null;
$surveyId = (int) ($_REQUEST['survey_id'] ?? 0);
$questionId = (int) ($_REQUEST['question_id'] ?? 0);

switch ($action) {
    case 'load_question_options':
        if (!api_is_allowed_to_edit(false, true)) {
            exit;
        }
        // The survey must be one of the current course and the question one of that survey.
        if (!SurveyManager::surveyBelongsToCurrentCourse($surveyId) ||
            !SurveyManager::surveyQuestionBelongsToSurvey($questionId, $surveyId)
        ) {
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
        /** @var CSurvey $survey */
        $survey = $repo->find($surveyId);

        /** @var CSurveyQuestion $survey */
        $question = $repoQuestion->find($questionId);

        if (null === $survey || null === $question ||
            !SurveyManager::surveyBelongsToCurrentCourse($surveyId) ||
            !SurveyManager::surveyQuestionBelongsToSurvey($questionId, $surveyId) ||
            !SurveyManager::userIsInvitedToSurvey((int) $userId, $surveyId, (int) $courseId)
        ) {
            exit;
        }

        // The fourth argument of remove_answer() is the learning path item, not the course.
        SurveyUtil::remove_answer(
            $userId,
            $survey->getIid(),
            $questionId
        );

        SurveyUtil::saveAnswer(
            $userId,
            $survey,
            $question,
            1,
            $status
        );

        break;
}
exit;
