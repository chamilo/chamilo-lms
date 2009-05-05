<?php //$id:$
/* For licensing terms, see /dokeos_license.txt */
//error_log(__FILE__);
/**
*	This file generates the ActionScript variables code used by the HotSpot .swf
*	@package dokeos.exercise
* 	@author Toon Keppens
* 	@version $Id: admin.php 10680 2007-01-11 21:26:23Z pcool $
*/
session_cache_limiter("none");

include('exercise.class.php');
include('question.class.php');
include('answer.class.php');

include('../inc/global.inc.php');

// set vars
$questionId    = $_GET['modifyAnswers'];
$objQuestion = Question::read($questionId);
$TBL_ANSWERS   = Database::get_course_table(TABLE_QUIZ_ANSWER);
$documentPath  = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';
$picturePath   = $documentPath.'/images';
$pictureName   = $objQuestion->selectPicture();
$pictureSize   = getimagesize($picturePath.'/'.$objQuestion->selectPicture());
$pictureWidth  = $pictureSize[0];
$pictureHeight = $pictureSize[1];

$courseLang = $_course['language'];
$courseCode = $_course['sysCode'];
$coursePath = $_course['path'];

// Query db for answers
$sql = "SELECT id, answer, hotspot_coordinates, hotspot_type, ponderation FROM $TBL_ANSWERS WHERE question_id = '".Database::escape_string($questionId)."' ORDER BY id";
$result = api_sql_query($sql,__FILE__,__LINE__);

// Init
$output = "hotspot_lang=$courseLang&hotspot_image=$pictureName&hotspot_image_width=$pictureWidth&hotspot_image_height=$pictureHeight&courseCode=$coursePath";
$i = 0;
$nmbrTries = 0;

while ($hotspot = mysql_fetch_assoc($result))
{
   	$output .= "&hotspot_".$hotspot['id']."=true";
	$output .= "&hotspot_".$hotspot['id']."_answer=".str_replace('&','{amp}',$hotspot['answer']);
	// Square or rectancle
	if ($hotspot['hotspot_type'] == 'square' )
	{
		$output .= "&hotspot_".$hotspot['id']."_type=square";
	}
	// Circle or ovale
	if ($hotspot['hotspot_type'] == 'circle')
	{
		$output .= "&hotspot_".$hotspot['id']."_type=circle";
	}
	// Polygon
	if ($hotspot['hotspot_type'] == 'poly')
	{
		$output .= "&hotspot_".$hotspot['id']."_type=poly";
	}
	// Delineation
	if ($hotspot['hotspot_type'] == 'delineation')
	{
		$output .= "&hotspot_".$hotspot['id']."_type=delineation";
	}

	// This is a good answer, count + 1 for nmbr of clicks
	if ($hotspot['hotspot_type'] > 0)
	{
		$nmbrTries++;
	}

	$output .= "&hotspot_".$hotspot['id']."_coord=".$hotspot['hotspot_coordinates']."";
	$i++;

}

// Generate empty
$i++;
for ($i; $i <= 12; $i++)
{
	$output .= "&hotspot_".$i."=false";
}

// Output
echo $output."&nmbrTries=".$nmbrTries."&done=done";
?>
