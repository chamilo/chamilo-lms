<?php
/* For licensing terms, see /license.txt */

/**
 * This file generates the ActionScript variables code used by the
 * HotSpot .swf
 * @package chamilo.exercise
 * @author Toon Keppens, Julio Montoya adding hotspot "medical" support
 */
include '../inc/global.inc.php';

// Set vars
$questionId = intval($_GET['modifyAnswers']);
$exe_id = intval($_GET['exe_id']);

$objQuestion = Question::read($questionId);
$trackExerciseInfo = ExerciseLib::get_exercise_track_exercise_info($exe_id);
$objExercise = new Exercise(api_get_course_int_id());
$objExercise->read($trackExerciseInfo['exe_exo_id']);
$em = Database::getManager();
$documentPath = api_get_path(SYS_COURSE_PATH) . $_course['path'] . '/document';
$picturePath = $documentPath . '/images';
$pictureName = $objQuestion->selectPicture();
$pictureSize = getimagesize($picturePath . '/' . $objQuestion->selectPicture());
$pictureWidth = $pictureSize[0];
$pictureHeight = $pictureSize[1];
$course_id = api_get_course_int_id();

// Init
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
    'DelineationStatus1' => get_lang('DelineationStatus1')
];
$data['image'] = $objQuestion->selectPicturePath();
$data['image_width'] = $pictureWidth;
$data['image_height'] = $pictureHeight;
$data['courseCode'] = $_course['path'];
$data['hotspots'] = [];

if ($objExercise->results_disabled != RESULT_DISABLE_SHOW_SCORE_ONLY) {
    $qb = $em->createQueryBuilder();
    $qb
        ->select('a')
        ->from('ChamiloCourseBundle:CQuizAnswer', 'a');

    if ($objQuestion->selectType() == HOT_SPOT_DELINEATION) {
        $qb
            ->where($qb->expr()->eq('a.cId', $course_id))
            ->andWhere($qb->expr()->eq('a.questionId', intval($questionId)))
            ->andWhere($qb->expr()->neq('a.hotspotType', 'noerror'));
    } else {
        $qb
            ->where($qb->expr()->eq('a.cId', $course_id))
            ->andWhere($qb->expr()->eq('a.questionId', intval($questionId)));
    }

    $result = $qb
        ->orderBy('a.id', 'ASC')
        ->getQuery()
        ->getResult();

    foreach ($result as $hotspotAnswer) {
        $hotSpot = [];
        $hotSpot['id'] = $hotspotAnswer->getId();
        $hotSpot['answer'] = $hotspotAnswer->getAnswer();

        switch ($hotspotAnswer->getHotspotType()) {
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

        $hotSpot['coord'] = $hotspotAnswer->getHotspotCoordinates();

        $data['hotspots'][] = $hotSpot;
    }
}

$data['answers'] = [];

$rs = $em
    ->getRepository('ChamiloCoreBundle:TrackEHotspot')
    ->findBy(
        [
            'hotspotQuestionId' => $questionId,
            'cId' => $course_id,
            'hotspotExeId' => $exe_id
        ],
        ['hotspotId' => 'ASC']
    );

foreach ($rs as $row) {
    $data['answers'][] = $row->getHotspotCoordinate();
}

$data['done'] = 'done';

header('Content-Type: application/json');

echo json_encode($data);
