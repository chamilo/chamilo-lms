<?php //$id:$
/* For licensing terms, see /dokeos_license.txt */
/**
*	This file generates the ActionScript variables code used by the HotSpot .swf
*	@package dokeos.exercise
* 	@author Toon Keppens
* 	@version $Id: admin.php 10680 2007-01-11 21:26:23Z pcool $
*/


include('exercise.class.php');
include('question.class.php');
include('answer.class.php');
include('../inc/global.inc.php');

// set vars
$userId        = $_user['user_id'];
$questionId    = $_GET['modifyAnswers'];
$exe_id    = $_GET['exe_id'];
$from_db = isset($_GET['from_db']) ? $_GET['from_db'] : 0;
$objQuestion = Question :: read($questionId);
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
$sql = "SELECT id, answer, hotspot_coordinates, hotspot_type FROM $TBL_ANSWERS WHERE question_id = '".Database::escape_string($questionId)."' ORDER BY id";
$result = api_sql_query($sql,__FILE__,__LINE__);

// Init
$output = "hotspot_lang=$courseLang&hotspot_image=$pictureName&hotspot_image_width=$pictureWidth&hotspot_image_height=$pictureHeight&courseCode=$coursePath";
$i = 0;

while ($hotspot = mysql_fetch_array($result))
{
	$output .= "&hotspot_".$hotspot['id']."=true";
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

	$output .= "&hotspot_".$hotspot['id']."_coord=".$hotspot['hotspot_coordinates']."";

	$i++;
}

// Generate empty (the maximum number of points is 12 - it is said so in the user interface)
$i++;
for ($i; $i <= 12; $i++) {
	$output .= "&hotspot_".$i."=false";
}

// set vars
$questionId    = $_GET['modifyAnswers'];
$course_code = $_course['id'];

// Get clicks
if(isset($_SESSION['exerciseResultCoordinates']) && $from_db==0) {
	foreach ($_SESSION['exerciseResultCoordinates'][$questionId] as $coordinate) {
		$output2 .= $coordinate."|";
	}
} else {
	// get it from db
	$tbl_track_e_hotspot = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_HOTSPOT);
	$sql = 'SELECT hotspot_coordinate '.
			' FROM '.$tbl_track_e_hotspot.
			' WHERE hotspot_question_id = '.intval($questionId).
			' AND hotspot_course_code = "'.Database::escape_string($course_code).'"'.
			' AND hotspot_exe_id='.intval($exe_id);
	$rs = @api_sql_query($sql); // don't output error because we are in Flash execution.
	while($row = Database :: fetch_array($rs)) {
		$output2 .= $row['hotspot_coordinate']."|";
	}
}

$output .= "&p_hotspot_answers=".substr($output2,0,-1)."&done=done";

$explode = explode('&', $output);

echo $output;
?>