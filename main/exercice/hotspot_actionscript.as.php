<?php
/* For licensing terms, see /license.txt */

/**
*	This file generates the ActionScript variables code used by the HotSpot .swf
*	@package chamilo.exercise
* 	@author Toon Keppens
* 	@version $Id: admin.php 10680 2007-01-11 21:26:23Z pcool $
*/

session_cache_limiter("none");

include('../inc/global.inc.php');

// set vars
$questionId    = intval($_GET['modifyAnswers']);
$objQuestion   = Question::read($questionId);
$answer_type   = $objQuestion->selectType(); //very important
$TBL_ANSWERS   = Database::get_course_table(TABLE_QUIZ_ANSWER);
$documentPath  = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';
$picturePath   = $documentPath.'/images';
$pictureName   = $objQuestion->selectPicture();
$pictureSize   = getimagesize($picturePath.'/'.$objQuestion->selectPicture());
$pictureWidth  = $pictureSize[0];
$pictureHeight = $pictureSize[1];

$course_id = api_get_course_int_id();

// Query db for answers
if ($answer_type==HOT_SPOT_DELINEATION) {
	$sql = "SELECT id, answer, hotspot_coordinates, hotspot_type, ponderation FROM $TBL_ANSWERS
	        WHERE c_id = $course_id AND question_id = ".intval($questionId)." AND hotspot_type = 'delineation' ORDER BY id";
} else {
	$sql = "SELECT id, answer, hotspot_coordinates, hotspot_type, ponderation FROM $TBL_ANSWERS
	        WHERE c_id = $course_id AND question_id = ".intval($questionId)." ORDER BY id";
}
$result = Database::query($sql);

$data = [];
$data['lang'] = [
    'Square' => get_lang('square'),
    'Circle' => get_lang('circle'),
    'Poly' => get_lang('poly'),
    'Status1' => get_lang('status1'),
    'Status2_poly' => get_lang('status2_poly'),
    'Status2_other' => get_lang('status2_other'),
    'Status3' => get_lang('status3'),
    'ShowUserPoints' => get_lang('showUserPoints'),
    'LabelPolyMenu' => get_lang('labelPolyMenu'),
    'Triesleft' => get_lang('triesleft'),
    'ExeFinished' => get_lang('exeFinished'),
    'NextAnswer' => get_lang('nextAnswer'),
    'Delineation' => get_lang('delineation'),
    'LabelDelineationMenu' => get_lang('labelDelineationMenu'),
    'Oar' => get_lang('oar')
];
$data['image'] = $objQuestion->selectPicturePath();
$data['image_width'] = $pictureWidth;
$data['image_height'] = $pictureHeight;
$data['courseCode'] = $_course['path'];
$data['hotspots'] = [];

$i = 0;
$nmbrTries = 0;

while ($hotspot = Database::fetch_assoc($result))
{
    $hotSpot = [];
    $hotSpot['id'] = $hotspot['id'];
    $hotSpot['answer'] = $hotspot['answer'];

	// Square or rectancle
	if ($hotspot['hotspot_type'] == 'square' )
	{
        $hotSpot['type'] = 'square';
	}
	// Circle or ovale
	if ($hotspot['hotspot_type'] == 'circle')
	{
        $hotSpot['type'] = 'circle';
	}
	// Polygon
	if ($hotspot['hotspot_type'] == 'poly')
	{
        $hotSpot['type'] = 'poly';
	}
	// Delineation
	if ($hotspot['hotspot_type'] == 'delineation')
	{
        $hotSpot['type'] = 'delineation';
	}
	// No error
	if ($hotspot['hotspot_type'] == 'noerror')
	{
        $hotSpot['type'] = 'noerror';
	}

	// This is a good answer, count + 1 for nmbr of clicks
	if ($hotspot['hotspot_type'] > 0)
	{
		$nmbrTries++;
	}
    unset($hotSpot['type']);
    //$hotSpot['coord'] = $hotspot['hotspot_coordinates'];
	$i++;

    $data['hotspots'][] = $hotSpot;
}

// Generate empty
$i++;

$data['nmbrTries'] = $nmbrTries;
$data['done'] = 'done';

header('Content-Type: application/json');

echo json_encode($data);
