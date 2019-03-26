<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\TrackEExercises;

require_once __DIR__.'/../inc/global.inc.php';

$isAllowedToEdit = api_is_allowed_to_edit(true, true);

if (!$isAllowedToEdit) {
    exit;
}

if (!isset($_REQUEST['user'], $_REQUEST['exercise'], $_REQUEST['id'])) {
    exit;
}

$courseId = api_get_course_int_id();
$sessionId = api_get_session_id();
$em = Database::getManager();

/** @var TrackEExercises $trackedExercise */
$trackedExercise = $em->getRepository('ChamiloCoreBundle:TrackEExercises')->find($_REQUEST['id']);

if (empty($trackedExercise)) {
    exit;
}

$studentId = $trackedExercise->getExeUserId();
$exerciseId = $trackedExercise->getExeExoId();
$exeId = $trackedExercise->getExeId();

if ($studentId != intval($_REQUEST['user']) ||
    $exerciseId != intval($_REQUEST['exercise'])
) {
    exit;
}

$questionList = $trackedExercise->getDataTracking();

if (empty($questionList)) {
    exit;
}

$questionList = explode(',', $questionList);

$exercise = new Exercise($courseId);
$exercise->read($exerciseId);
$totalScore = 0;
$totalWeight = 0;

$useEvaluationPlugin = false;
$pluginEvaluation = QuestionOptionsEvaluationPlugin::create();

if ('true' === $pluginEvaluation->get(QuestionOptionsEvaluationPlugin::SETTING_ENABLE)) {
    $formula = $pluginEvaluation->getFormulaForExercise($exerciseId);

    if (!empty($formula)) {
        $useEvaluationPlugin = true;
    }
}

if (!$useEvaluationPlugin) {
    foreach ($questionList as $questionId) {
        $question = Question::read($questionId, $courseId);
        $totalWeight += $question->selectWeighting();

        // We're inside *one* question. Go through each possible answer for this question
        if ($question->type === MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY) {
            $result = $exercise->manage_answer(
                $exeId,
                $questionId,
                [],
                'exercise_result',
                [],
                false,
                true,
                false,
                $exercise->selectPropagateNeg(),
                [],
                [],
                true
            );
        } else {
            $result = $exercise->manage_answer(
                $exeId,
                $questionId,
                [],
                'exercise_result',
                [],
                false,
                true,
                false,
                $exercise->selectPropagateNeg(),
                [],
                [],
                true
            );
        }

        //  Adding the new score.
        $totalScore += $result['score'];
    }

    $remindList = $trackedExercise->getQuestionsToCheck();
    if (!empty($remindList)) {
        $remindList = explode(',', $remindList);
    }
} else {
    $totalScore = $pluginEvaluation->getResultWithFormula($exeId, $formula);
    $totalWeight = $pluginEvaluation->getMaxScore();
}

$table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);

$sql = "UPDATE $table SET
          exe_result = '$totalScore',
          exe_weighting = '$totalWeight'
        WHERE exe_id = $exeId";
Database::query($sql);

echo $totalScore.'/'.$totalWeight;
