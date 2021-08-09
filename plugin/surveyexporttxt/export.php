<?php
/* For licensing terms, see /license.txt */

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

$plugin = SurveyExportTxtPlugin::create();
$allowExportIncomplete = 'true' === $plugin->get('export_incomplete');

if ($plugin->get('enabled') !== 'true') {
    api_not_allowed(true);
}

$questionsData = SurveyManager::get_questions($surveyId, $courseId);

// Sort questions by their "sort" field
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

$parts = [];
$indexPart = 0;

$numberOfQuestions = 0;

// Separate questions in introduction and main blocks
foreach ($questionsData as $questionData) {
    if (!in_array($questionData['type'], ['yesno', 'pagebreak', 'multiplechoice'])) {
        continue;
    }

    if ('pagebreak' === $questionData['type']) {
        $indexPart++;

        continue;
    }

    $numberOfQuestions++;

    if (0 === $indexPart) {
        $parts[0][] = $questionData;

        continue;
    }

    $parts[$indexPart][] = $questionData;
}

if (count($parts) < 2) {
    api_not_allowed(
        true,
        Display::return_message(get_lang('NoData'), 'warning')
    );
}

// Process introduction questions to show
foreach ($parts[0] as $key => $introQuestion) {
    $content[] = chr($key + 97).'. '.strip_tags($introQuestion['question']);

    foreach ($introQuestion['answers'] as $answer) {
        $content[] = '>'.strip_tags($answer);
    }
}

$content[] = str_repeat('*', 40);

// Get surveys by users
$surveyAnswers = Database::getManager()
    ->createQuery(
        'SELECT sa.user, MIN(sa.iid) AS id FROM ChamiloCourseBundle:CSurveyAnswer sa
        WHERE sa.cId = :course AND sa.surveyId = :survey
        GROUP BY sa.user ORDER BY id ASC'
    )
    ->setParameters(['course' => $courseId, 'survey' => $surveyId])
    ->getResult();

// Process answers
$i = 1;

foreach ($surveyAnswers as $answer) {
    $userAnswersCount = 0;
    $surveyLine = '';

    // Show answers for introduction questions
    foreach ($parts[0] as $introQuestion) {
        $options = getQuestionOptions(
            $answer['user'],
            $courseId,
            $introQuestion['survey_id'],
            $introQuestion['question_id']
        );
        $userAnswersCount += count($options);

        /** @var CSurveyQuestionOption $option */
        foreach ($options as $option) {
            $surveyLine .= '('.str_pad($option->getSort(), 2, '0', STR_PAD_LEFT).')';
        }
    }

    $surveyLine .= '","';

    foreach ($parts as $z => $part) {
        if (0 === $z) {
            continue;
        }

        // Show answers for main questions
        foreach ($part as $mainQuestion) {
            $options = getQuestionOptions(
                $answer['user'],
                $courseId,
                $mainQuestion['survey_id'],
                $mainQuestion['question_id']
            );
            $userAnswersCount += count($options);

            /** @var CSurveyQuestionOption $option */
            foreach ($options as $option) {
                $surveyLine .= $option->getSort();
            }
        }
    }

    $surveyLine .= '"';

    if (!$allowExportIncomplete && $userAnswersCount < $numberOfQuestions) {
        continue;
    }

    $content[] = '"","'.$surveyLine.',"'.str_pad($i, 4, '0', STR_PAD_LEFT).'"';
    $i++;
}

// Add EOL to lines
$fileContent = array_map(
    function ($line) {
        return html_entity_decode($line).PHP_EOL;
    },
    $content
);

// Generate file
$fileName = api_get_path(SYS_ARCHIVE_PATH).md5($surveyId.time()).'.txt';

file_put_contents($fileName, $fileContent);

DocumentManager::file_send_for_download($fileName, true);

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
