<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\TrackEHotspot;
use Chamilo\CourseBundle\Entity\CQuizAnswer;

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

$objQuestion = Question::read($questionId, $objExercise->course);
$objExercise->read($exerciseId);

if (empty($objQuestion) || empty($objExercise)) {
    exit;
}

$em = Database::getManager();
$documentPath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';
$picturePath = $documentPath.'/images';
$pictureName = $objQuestion->getPictureFilename();
$pictureSize = getimagesize($picturePath.'/'.$pictureName);
$pictureWidth = $pictureSize[0];
$pictureHeight = $pictureSize[1];

$data = [];
$data['type'] = 'solution';
$data['lang'] = [
    'Square' => get_lang('Square'),
    'Ellipse' => get_lang('Ellipse'),
    'Polygon' => get_lang('Polygon'),
    'HotspotStatus1' => get_lang('HotspotStatus1'),
    'HotspotStatus2Polygon' => get_lang('HotspotStatus2Polygon'),
    'HotspotStatus2Other' => get_lang('HotspotStatus2Other'),
    'HotspotStatus3' => get_lang('HotspotStatus3'),
    'HotspotShowUserPoints' => get_lang('HotspotShowUserPoints'),
    'ShowHotspots' => get_lang('ShowHotspots'),
    'Triesleft' => get_lang('Triesleft'),
    'HotspotExerciseFinished' => get_lang('HotspotExerciseFinished'),
    'NextAnswer' => get_lang('NextAnswer'),
    'Delineation' => get_lang('Delineation'),
    'CloseDelineation' => get_lang('CloseDelineation'),
    'Oar' => get_lang('Oar'),
    'ClosePolygon' => get_lang('ClosePolygon'),
    'DelineationStatus1' => get_lang('DelineationStatus1'),
];
$data['image'] = $objQuestion->selectPicturePath();
$data['image_width'] = $pictureWidth;
$data['image_height'] = $pictureHeight;
$data['courseCode'] = $_course['path'];
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
            $objExercise->iid,
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
if ($objExercise->getFeedbackType() == 0 &&
    $resultDisable == RESULT_DISABLE_SHOW_SCORE_ONLY
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
    ->getRepository('ChamiloCoreBundle:TrackEHotspot')
    ->findBy(
        [
            'hotspotQuestionId' => $questionId,
            'cId' => $courseId,
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

    if ($objQuestion->selectType() == HOT_SPOT_DELINEATION) {
        $qb
            ->where($qb->expr()->eq('a.questionId', $questionId))
            ->andWhere("a.hotspotType != 'noerror'")
            ->orderBy('a.iid', 'ASC');
    } else {
        $qb
            ->where($qb->expr()->eq('a.questionId', $questionId))
            ->orderBy('a.position', 'ASC');
    }

    $result = $qb->getQuery()->getResult();
    /** @var CQuizAnswer $hotSpotAnswer */
    foreach ($result as $hotSpotAnswer) {
        if (RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK == $resultDisable) {
            if (false === $showTotalScoreAndUserChoicesInLastAttempt) {
                if (!in_array($hotSpotAnswer->getId(), $hotSpotWithAnswer)) {
                    continue;
                }
            }
        }

        $hotSpot = [];
        $hotSpot['id'] = $hotSpotAnswer->getId();
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

$data['done'] = 'done';
header('Content-Type: application/json');

echo json_encode($data);

if ($debug) {
    error_log("---------- End call to hotspot_answers.as.php------------");
}
