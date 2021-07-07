<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\TrackEHotspot;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;

/**
 * This file generates a json answer to the question preview.
 *
 * @author Toon Keppens, Julio Montoya adding hotspot "medical" support
 */
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script();

$_course = api_get_course_info();
$questionId = isset($_GET['modifyAnswers']) ? (int) $_GET['modifyAnswers'] : 0;
$exerciseId = isset($_GET['exerciseId']) ? (int) $_GET['exerciseId'] : 0;
$exeId = isset($_GET['exeId']) ? (int) $_GET['exeId'] : 0;
$userId = api_get_user_id();
$courseId = api_get_course_int_id();
$objExercise = new Exercise($courseId);
$debug = false;
if ($debug) {
    error_log("Call to hotspot_answers.as.php");
}
$trackExerciseInfo = $objExercise->get_stat_track_exercise_info_by_exe_id($exeId);

// Check if student has access to the hotspot answers
if (!api_is_allowed_to_edit(null, true)) {
    if (empty($exeId)) {
        api_not_allowed();
    }

    if (empty($trackExerciseInfo)) {
        api_not_allowed();
    }

    // Different exercise
    if ($exerciseId != $trackExerciseInfo['exe_exo_id']) {
        api_not_allowed();
    }

    // Different user
    if ($trackExerciseInfo['exe_user_id'] != $userId) {
        api_not_allowed();
    }
}

$questionRepo = Container::getQuestionRepository();
/** @var CQuizQuestion $objQuestion */
$objQuestion = $questionRepo->find($questionId);
if (empty($objQuestion)) {
    exit;
}

$answer_type = $objQuestion->getType(); //very important
$TBL_ANSWERS = Database::get_course_table(TABLE_QUIZ_ANSWER);

$resourceFile = $objQuestion->getResourceNode()->getResourceFile();
$pictureWidth = $resourceFile->getWidth();
$pictureHeight = $resourceFile->getHeight();
$imagePath = $questionRepo->getHotSpotImageUrl($objQuestion).'?'.api_get_cidreq();

$objExercise->read($exerciseId);

if (empty($objExercise)) {
    exit;
}

$em = Database::getManager();

$data = [];
$data['type'] = 'solution';
$data['lang'] = HotSpot::getLangVariables();
$data['image'] = $imagePath;
$data['image_width'] = $pictureWidth;
$data['image_height'] = $pictureHeight;
$data['hotspots'] = [];

$resultDisable = $objExercise->selectResultsDisabled();
$showTotalScoreAndUserChoicesInLastAttempt = true;
if (in_array(
    $resultDisable, [
        RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT,
        RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK,
        RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK,
    ]
)
) {
    $showOnlyScore = true;
    $showResults = true;
    $lpId = isset($trackExerciseInfo['orig_lp_id']) ? $trackExerciseInfo['orig_lp_id'] : 0;
    $lpItemId = isset($trackExerciseInfo['orig_lp_item_id']) ? $trackExerciseInfo['orig_lp_item_id'] : 0;
    if ($objExercise->attempts > 0) {
        $attempts = Event::getExerciseResultsByUser(
            api_get_user_id(),
            $objExercise->id,
            $courseId,
            api_get_session_id(),
            $lpId,
            $lpItemId,
            'desc'
        );
        $numberAttempts = count($attempts);
        $showTotalScoreAndUserChoicesInLastAttempt = false;

        if ($numberAttempts >= $objExercise->attempts) {
            $showResults = true;
            $showOnlyScore = false;
            $showTotalScoreAndUserChoicesInLastAttempt = true;
        }
    }
}

$hideExpectedAnswer = false;
if (0 == $objExercise->getFeedbackType() &&
    RESULT_DISABLE_SHOW_SCORE_ONLY == $resultDisable
) {
    $hideExpectedAnswer = true;
}

if (in_array(
    $resultDisable, [
        RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT,
        RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK,
    ]
)
) {
    $hideExpectedAnswer = $showTotalScoreAndUserChoicesInLastAttempt ? false : true;
}

if (in_array(
    $resultDisable, [
        RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK,
    ]
)
) {
    $hideExpectedAnswer = false;
}

$hotSpotWithAnswer = [];
$data['answers'] = [];
$rs = $em
    ->getRepository(TrackEHotspot::class)
    ->findBy(
        [
            'hotspotQuestionId' => $questionId,
            'course' => $courseId,
            'hotspotExeId' => $exeId,
        ],
        ['hotspotAnswerId' => 'ASC']
    );

/** @var TrackEHotspot $row */
foreach ($rs as $row) {
    $data['answers'][] = $row->getHotspotCoordinate();

    if ($row->getHotspotCorrect()) {
        $hotSpotWithAnswer[] = $row->getHotspotAnswerId();
    }
}
if (!$hideExpectedAnswer) {
    $qb = $em->createQueryBuilder();
    $qb
        ->select('a')
        ->from('ChamiloCourseBundle:CQuizAnswer', 'a');

    if (HOT_SPOT_DELINEATION == $objQuestion->getType()) {
        $qb
            ->where($qb->expr()->eq('a.cId', $courseId))
            ->andWhere($qb->expr()->eq('a.questionId', $questionId))
            ->andWhere($qb->expr()->neq('a.hotspotType', 'noerror'))
            ->orderBy('a.id', 'ASC');
    } else {
        $qb
            ->where($qb->expr()->eq('a.cId', $courseId))
            ->andWhere($qb->expr()->eq('a.questionId', $questionId))
            ->orderBy('a.position', 'ASC');
    }

    $result = $qb->getQuery()->getResult();

    foreach ($result as $hotSpotAnswer) {
        if (RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK == $resultDisable) {
            if (false === $showTotalScoreAndUserChoicesInLastAttempt) {
                if (!in_array($hotSpotAnswer->getIid(), $hotSpotWithAnswer)) {
                    continue;
                }
            }
        }
        /** @var CQuizAnswer $hotSpotAnswer */
        $hotSpot = [];
        $hotSpot['id'] = $hotSpotAnswer->getIid();
        $hotSpot['answer'] = $hotSpotAnswer->getAnswer();

        switch ($hotSpotAnswer->getHotspotType()) {
            case 'square':
                $hotSpot['type'] = 'square';

                break;
            case 'circle':
                $hotSpot['type'] = 'circle';

                break;
            case 'poly':
                $hotSpot['type'] = 'poly';

                break;
            case 'delineation':
                $hotSpot['type'] = 'delineation';

                break;
            case 'oar':
                $hotSpot['type'] = 'delineation';

                break;
        }
        $hotSpot['coord'] = $hotSpotAnswer->getHotspotCoordinates();
        $data['hotspots'][] = $hotSpot;
    }
}

$data['answers'] = [];

$rs = $em
    ->getRepository(TrackEHotspot::class)
    ->findBy(
        [
            'hotspotQuestionId' => $questionId,
            'course' => $courseId,
            'hotspotExeId' => $exeId,
        ],
        ['hotspotAnswerId' => 'ASC']
    );

/** @var TrackEHotspot $row */
foreach ($rs as $row) {
    $data['answers'][] = $row->getHotspotCoordinate();
}

$data['done'] = 'done';
header('Content-Type: application/json');

echo json_encode($data);

if ($debug) {
    error_log('---------- End call to hotspot_answers.as.php------------');
}
