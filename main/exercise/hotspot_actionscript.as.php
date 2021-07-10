<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This file generates the ActionScript variables code used by the HotSpot .swf.
 *
 * @package chamilo.exercise
 *
 * @author Toon Keppens
 *
 * @version $Id: admin.php 10680 2007-01-11 21:26:23Z pcool $
 */
session_cache_limiter('none');

require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);

$_course = api_get_course_info();
require api_get_path(LIBRARY_PATH).'geometry.lib.php';

// set vars
$questionId = intval($_GET['modifyAnswers']);
$exerciseId = isset($_GET['exe_id']) ? intval($_GET['exe_id']) : 0;
$objQuestion = Question::read($questionId);
$answer_type = $objQuestion->selectType(); //very important
$TBL_ANSWERS = Database::get_course_table(TABLE_QUIZ_ANSWER);
$documentPath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';
$picturePath = $documentPath.'/images';
$pictureName = $objQuestion->getPictureFilename();
$pictureSize = getimagesize($picturePath.'/'.$pictureName);
$pictureWidth = $pictureSize[0];
$pictureHeight = $pictureSize[1];
$course_id = api_get_course_int_id();

// Query db for answers
if ($answer_type == HOT_SPOT_DELINEATION) {
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
$result = Database::query($sql);

$data = [];
$data['type'] = 'user';
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
$data['answers'] = [];

$nmbrTries = 0;

while ($hotspot = Database::fetch_assoc($result)) {
    $hotSpot = [];
    $hotSpot['iid'] = $hotspot['iid'];
    $hotSpot['answer'] = $hotspot['answer'];

    // Square or rectancle
    if ($hotspot['hotspot_type'] == 'square') {
        $hotSpot['type'] = 'square';
    }
    // Circle or ovale
    if ($hotspot['hotspot_type'] == 'circle') {
        $hotSpot['type'] = 'circle';
    }
    // Polygon
    if ($hotspot['hotspot_type'] == 'poly') {
        $hotSpot['type'] = 'poly';
    }
    // Delineation
    if ($hotspot['hotspot_type'] == 'delineation') {
        $hotSpot['type'] = 'delineation';
    }
    // No error
    if ($hotspot['hotspot_type'] == 'noerror') {
        $hotSpot['type'] = 'noerror';
    }

    // This is a good answer, count + 1 for nmbr of clicks
    if ($hotspot['hotspot_type'] > 0) {
        $nmbrTries++;
    }

    $hotSpot['coord'] = $hotspot['hotspot_coordinates'];
    $data['hotspots'][] = $hotSpot;
}

$attemptInfo = Database::select(
    'exe_id',
    Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES),
    [
        'where' => [
            'exe_exo_id = ? AND c_id = ? AND exe_user_id = ? AND status = ?' => [
                (int) $exerciseId,
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

$data['nmbrTries'] = $nmbrTries;
$data['done'] = 'done';

if (Session::has("hotspot_ordered$questionId")) {
    $tempHotspots = [];
    $hotspotOrdered = Session::read("hotspot_ordered$questionId");

    foreach ($hotspotOrdered as $hotspotOrder) {
        foreach ($data['hotspots'] as $hotspot) {
            if ($hotspot['iid'] != $hotspotOrder) {
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
