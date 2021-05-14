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
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(false);

$isAllowedToEdit = api_is_allowed_to_edit(null, true);

if (!$isAllowedToEdit) {
    api_not_allowed();
}

$_course = api_get_course_info();
$questionId = isset($_GET['modifyAnswers']) ? (int) $_GET['modifyAnswers'] : 0;
$questionRepo = Container::getQuestionRepository();
/** @var CQuizQuestion $objQuestion */
$objQuestion = $questionRepo->find($questionId);
if (!$objQuestion) {
    api_not_allowed();
}
if (!$objQuestion->getResourceNode()->hasResourceFile()) {
    api_not_allowed();
}
$resourceFile = $objQuestion->getResourceNode()->getResourceFile();
$pictureWidth = $resourceFile->getWidth();
$pictureHeight = $resourceFile->getHeight();
$imagePath = $questionRepo->getHotSpotImageUrl($objQuestion).'?'.api_get_cidreq();

$data = [];
$data['type'] = 'admin';
$data['lang'] = HotSpot::getLangVariables();
$data['image'] = $imagePath;
$data['image_width'] = $pictureWidth;
$data['image_height'] = $pictureHeight;
//$data['courseCode'] = $_course['path'];
$data['hotspots'] = [];

$i = 0;
$nmbrTries = 0;
$answer_type = $objQuestion->getType();
$answers = Session::read('tmp_answers');
$nbrAnswers = count($answers['answer']);

for ($i = 1; $i <= $nbrAnswers; $i++) {
    $hotSpot = [];
    $hotSpot['id'] = null;
    $hotSpot['answer'] = $answers['answer'][$i];

    if (HOT_SPOT_DELINEATION == $answer_type) {
        if (1 == $i) {
            $hotSpot['type'] = 'delineation';
        } else {
            $hotSpot['type'] = 'oar';
        }
    } else {
        // Square or rectangle
        if ('square' === $answers['hotspot_type'][$i]) {
            $hotSpot['type'] = 'square';
        }

        // Circle or oval
        if ('circle' === $answers['hotspot_type'][$i]) {
            $hotSpot['type'] = 'circle';
        }

        // Polygon
        if ('poly' === $answers['hotspot_type'][$i]) {
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
