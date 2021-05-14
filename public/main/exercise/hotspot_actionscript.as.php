<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use ChamiloSession as Session;

/**
 * This file generates the ActionScript variables code used by the HotSpot .swf.
 *
 * @author Toon Keppens
 */
session_cache_limiter('none');

require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);

$_course = api_get_course_info();
require api_get_path(LIBRARY_PATH).'geometry.lib.php';

// set vars
$questionId = (int) $_GET['modifyAnswers'];
$exerciseId = isset($_GET['exe_id']) ? (int) $_GET['exe_id'] : 0;
$questionRepo = Container::getQuestionRepository();
/** @var CQuizQuestion $objQuestion */
$objQuestion = $questionRepo->find($questionId);
if (!$objQuestion) {
    api_not_allowed();
}

$answer_type = $objQuestion->getType(); //very important
$TBL_ANSWERS = Database::get_course_table(TABLE_QUIZ_ANSWER);

if (!$objQuestion->getResourceNode()->hasResourceFile()) {
    api_not_allowed();
}
$resourceFile = $objQuestion->getResourceNode()->getResourceFile();
$pictureWidth = $resourceFile->getWidth();
$pictureHeight = $resourceFile->getHeight();
$imagePath = $questionRepo->getHotSpotImageUrl($objQuestion).'?'.api_get_cidreq();
$course_id = api_get_course_int_id();
$answers = $objQuestion->getAnswers();

// Query db for answers
/*if (HOT_SPOT_DELINEATION == $answer_type) {
    $sql = "SELECT iid, answer, hotspot_coordinates, hotspot_type, ponderation
	        FROM $TBL_ANSWERS
	        WHERE
	            c_id = $course_id AND
	            question_id = $questionId AND
	            hotspot_type = 'delineation'
            ORDER BY iid";
} else {
    $sql = "SELECT iid, answer, hotspot_coordinates, hotspot_type, ponderation
	        FROM $TBL_ANSWERS
	        WHERE c_id = $course_id AND question_id = $questionId
	        ORDER BY position";
}
$result = Database::query($sql);*/

$data = [];
$data['type'] = 'user';
$data['lang'] = HotSpot::getLangVariables();
$data['image'] = $imagePath;
$data['image_width'] = $pictureWidth;
$data['image_height'] = $pictureHeight;
$data['hotspots'] = [];
$data['answers'] = [];

$numberOfTries = 0;
foreach ($answers as $hotspot) {
    $type = $hotspot->getHotspotType();
    if (HOT_SPOT_DELINEATION == $answer_type) {
        if ('delineation' !== $type) {
            continue;
        }
    }
    $hotSpot = [];
    $hotSpot['id'] = $hotspot->getIid();
    $hotSpot['iid'] = $hotspot->getIid();
    $hotSpot['answer'] = $hotspot->getAnswer();

    // Square or rectangle
    if ('square' === $type) {
        $hotSpot['type'] = 'square';
    }
    // Circle or oval
    if ('circle' === $type) {
        $hotSpot['type'] = 'circle';
    }
    // Polygon
    if ('poly' === $type) {
        $hotSpot['type'] = 'poly';
    }
    // Delineation
    if ('delineation' === $type) {
        $hotSpot['type'] = 'delineation';
    }
    // No error
    if ('noerror' === $type) {
        $hotSpot['type'] = 'noerror';
    }

    // This is a good answer, count + 1 for nmbr of clicks
    if ($type > 0) {
        $numberOfTries++;
    }

    $hotSpot['coord'] = $hotspot->getHotspotCoordinates();
    $data['hotspots'][] = $hotSpot;
}

$attemptInfo = Database::select(
    'exe_id',
    Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES),
    [
        'where' => [
            'exe_exo_id = ? AND c_id = ? AND exe_user_id = ? AND status = ?' => [
                $exerciseId,
                $course_id,
                api_get_user_id(),
                'incomplete',
            ],
        ],
        'order' => 'exe_id DESC',
        'limit' => 1,
    ],
    'first'
);

if (empty($attemptInfo)) {
    exit(0);
}

$attemptList = Event::getAllExerciseEventByExeId($attemptInfo['exe_id']);

if (!empty($attemptList)) {
    if (isset($attemptList[$questionId])) {
        $questionAttempt = $attemptList[$questionId][0];
        if (!empty($questionAttempt['answer'])) {
            $coordinates = explode('|', $questionAttempt['answer']);

            foreach ($coordinates as $coordinate) {
                $data['answers'][] = Geometry::decodePoint($coordinate);
            }
        }
    }
}

$data['nmbrTries'] = $numberOfTries;
$data['done'] = 'done';

if (Session::has("hotspot_ordered$questionId")) {
    $tempHotspots = [];
    $hotspotOrdered = Session::read("hotspot_ordered$questionId");

    foreach ($hotspotOrdered as $hotspotOrder) {
        foreach ($data['hotspots'] as $hotspot) {
            if ($hotspot['id'] != $hotspotOrder) {
                continue;
            }

            $tempHotspots[] = $hotspot;
        }
    }

    $data['hotspots'] = $tempHotspots;

    Session::erase("hotspot_ordered$questionId");
}

header('Content-Type: application/json');

echo json_encode($data);
