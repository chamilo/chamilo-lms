<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CSurveyAnswer;
use Chamilo\CourseBundle\Entity\CSurveyQuestionOption;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_course_script(true);
api_protect_teacher_script();

$surveyId = isset($_GET['survey']) ? (int) $_GET['survey'] : 0;
$surveyData = SurveyManager::get_survey($surveyId);
$courseId = api_get_course_int_id();

if (empty($surveyData)) {
    api_not_allowed(true);
}

$plugin = SurveyExportCsvPlugin::create();
$allowExportIncomplete = 'true' === $plugin->get('export_incomplete');

if ($plugin->get('enabled') !== 'true') {
    api_not_allowed(true);
}

$questionsData = SurveyManager::get_questions($surveyId, $courseId);
// Sort questions by their "sort" field
$questionsData = array_filter(
    $questionsData,
    function ($questionData) {
        return in_array($questionData['type'], ['yesno', 'multiplechoice', 'open']);
    }
);
$numberOfQuestions = count($questionsData);

usort(
    $questionsData,
    function ($qL, $qR) {
        if ($qL['sort'] == $qR['sort']) {
            return 0;
        }

        return $qL['sort'] < $qR['sort'] ? -1 : 1;
    }
);

$content = [];
$content[] = firstRow($questionsData);

$surveyAnswers = getSurveyAnswers($courseId, $surveyId);

// Process answers
$i = 1;
foreach ($surveyAnswers as $answer) {
    $row = otherRow($questionsData, $answer['user'], $courseId);

    if (!$allowExportIncomplete && count($row) < $numberOfQuestions) {
        continue;
    }

    array_unshift($row, $i);

    $content[] = $row;
    $i++;
}

// Generate file
$fileName = md5($surveyId.time());

Export::arrayToCsv($content, $fileName, false, "'");

/**
 * Generate the first row for file.
 *
 * @param $questions
 *
 * @return array
 */
function firstRow($questions)
{
    array_pop($questions);
    $positions = array_keys($questions);

    $row = ['DATID'];

    foreach ($positions as $position) {
        $row[] = sprintf("P%02d", $position + 1);
    }

    $row[] = 'DATOBS';

    return $row;
}

/**
 * Get unique answer for surveys by users.
 *
 * @param int $courseId
 * @param int $surveyId
 *
 * @return array
 */
function getSurveyAnswers($courseId, $surveyId)
{
    $surveyAnswers = Database::getManager()
        ->createQuery(
            'SELECT sa.user, MIN(sa.iid) AS id FROM ChamiloCourseBundle:CSurveyAnswer sa
            WHERE sa.cId = :course AND sa.surveyId = :survey
            GROUP BY sa.user ORDER BY id ASC'
        )
        ->setParameters(['course' => $courseId, 'survey' => $surveyId])
        ->getResult();

    return $surveyAnswers;
}

/**
 * @param string $user
 * @param int    $courseId
 * @param int    $surveyId
 * @param int    $questionId
 *
 * @return array
 */
function getQuestionOptions($user, $courseId, $surveyId, $questionId)
{
    $options = Database::getManager()
        ->createQuery(
            'SELECT sqo FROM ChamiloCourseBundle:CSurveyQuestionOption sqo
            INNER JOIN ChamiloCourseBundle:CSurveyAnswer sa
                WITH
                    sqo.cId = sa.cId
                    AND sqo.questionId = sa.questionId
                    AND sqo.surveyId = sa.surveyId
                    AND sqo.iid = sa.optionId
            WHERE sa.user = :user AND sa.cId = :course AND sa.surveyId = :survey AND sa.questionId = :question'
        )
        ->setMaxResults(1)
        ->setParameters(
            [
                'user' => $user,
                'course' => $courseId,
                'survey' => $surveyId,
                'question' => $questionId,
            ]
        )
        ->getResult();

    return $options;
}

/**
 * @param int    $questionId
 * @param int    $surveyId
 * @param int    $courseId
 * @param string $user
 *
 * @throws \Doctrine\ORM\NonUniqueResultException
 *
 * @return CSurveyAnswer|null
 */
function getOpenAnswer($questionId, $surveyId, $courseId, $user)
{
    $answer = Database::getManager()
        ->createQuery(
            'SELECT sa FROM ChamiloCourseBundle:CSurveyAnswer sa
            WHERE sa.cId = :course AND sa.surveyId = :survey AND sa.questionId = :question AND sa.user = :user'
        )
        ->setParameters(['course' => $courseId, 'survey' => $surveyId, 'question' => $questionId, 'user' => $user])
        ->getOneOrNullResult();

    return $answer;
}

/**
 * Generate the content rows for file.
 *
 * @param array  $questions
 * @param string $user
 * @param int    $courseId
 *
 * @throws \Doctrine\ORM\NonUniqueResultException
 *
 * @return array
 */
function otherRow($questions, $user, $courseId)
{
    $row = [];

    foreach ($questions as $question) {
        if ('open' === $question['type']) {
            $answer = getOpenAnswer($question['question_id'], $question['survey_id'], $courseId, $user);

            if ($answer) {
                $row[] = Security::remove_XSS($answer->getOptionId());
            }
        } else {
            $options = getQuestionOptions(
                $user,
                $courseId,
                $question['survey_id'],
                $question['question_id']
            );
            /** @var CSurveyQuestionOption|null $option */
            $option = end($options);

            if ($option) {
                $value = $option->getSort();
                $row[] = '"'.$value.'"';
            }
        }
    }

    return $row;
}
