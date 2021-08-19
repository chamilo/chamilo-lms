<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This file generates the ActionScript variables code used by the HotSpot .swf.
 *
 * @package chamilo.exercise
 *
 * @author Toon Keppens
 */
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(false);

$isAllowedToEdit = api_is_allowed_to_edit(null, true);

if (!$isAllowedToEdit) {
    api_not_allowed(true);
    exit;
}

$_course = api_get_course_info();
$questionId = isset($_GET['modifyAnswers']) ? (int) $_GET['modifyAnswers'] : 0;
$objQuestion = Question::read($questionId);
$documentPath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';
$picturePath = $documentPath.'/images';
$pictureName = $objQuestion->getPictureFilename();

$pictureSize = getimagesize($picturePath.'/'.$pictureName);
$pictureWidth = $pictureSize[0];
$pictureHeight = $pictureSize[1];

$data = [];
$data['type'] = 'admin';
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

$i = 0;
$nmbrTries = 0;
$answer_type = $objQuestion->type;
$answers = Session::read('tmp_answers');
$nbrAnswers = count($answers['answer']);

for ($i = 1; $i <= $nbrAnswers; $i++) {
    $hotSpot = [];
    $hotSpot['iid'] = null;
    $hotSpot['answer'] = $answers['answer'][$i];

    if ($answer_type == HOT_SPOT_DELINEATION) {
        if ($i == 1) {
            $hotSpot['type'] = 'delineation';
        } else {
            $hotSpot['type'] = 'oar';
        }
    } else {
        // Square or rectancle
        if ($answers['hotspot_type'][$i] == 'square') {
            $hotSpot['type'] = 'square';
        }

        // Circle or ovale
        if ($answers['hotspot_type'][$i] == 'circle') {
            $hotSpot['type'] = 'circle';
        }

        // Polygon
        if ($answers['hotspot_type'][$i] == 'poly') {
            $hotSpot['type'] = 'poly';
        }
        /*// Delineation
        if ($answers['hotspot_type'][$i] == 'delineation')
        {
            $output .= "&hotspot_".$i."_type=delineation";
        }*/
    }

    // This is a good answer, count + 1 for nmbr of clicks
    if ($answers['weighting'][$i] > 0) {
        $nmbrTries++;
    }

    $hotSpot['coord'] = $answers['hotspot_coordinates'][$i];
    $data['hotspots'][] = $hotSpot;
}

// Output
$data['nmbrTries'] = $nmbrTries;
$data['done'] = 'done';

header('Content-Type: application/json');

echo json_encode($data);
