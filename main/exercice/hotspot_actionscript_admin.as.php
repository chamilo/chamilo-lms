<?php
/* For licensing terms, see /license.txt */

/**
* This file generates the ActionScript variables code used by the HotSpot .swf
* @package chamilo.exercise
* @author Toon Keppens
*/
require_once '../inc/global.inc.php';

// set vars
$questionId = intval($_GET['modifyAnswers']);
$objQuestion = Question::read($questionId);
$_course = api_get_course_info();

$documentPath  = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';

$picturePath = $documentPath.'/images';
$pictureName = $objQuestion->selectPicture();
$pictureSize = getimagesize($picturePath.'/'.$objQuestion->selectPicture());
$pictureWidth = $pictureSize[0];
$pictureHeight = $pictureSize[1];

$data = [];
$data['hotspot_lang'] = $_course['language'];
$data['hotspot_image'] = $pictureName;
$data['hotspot_image_width'] = $pictureWidth;
$data['hotspot_image_height'] = $pictureHeight;
$data['courseCode'] = $_course['path'];
$data['hotspots'] = [];

// Init
$i = 0;
$nmbrTries = 0;
$answer_type = $objQuestion->type;

$answers = $_SESSION['tmp_answers'];
$nbrAnswers = count($answers['answer']);

for ($i=1;$i <= $nbrAnswers; $i++) {
    $hotSpot = [];
    $hotSpot['answer']= $answers['answer'][$i];

    if ($answer_type == HOT_SPOT_DELINEATION) {
        if ($i==1) {
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
