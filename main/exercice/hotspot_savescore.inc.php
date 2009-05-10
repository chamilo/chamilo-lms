<?php //$id:$
/* For licensing terms, see /dokeos_license.txt */

//error_log(__FILE__);
/**
*	This file saves every click in the hotspot tool into track_e_hotspots
*	@package dokeos.exercise
* 	@author Toon Keppens
* 	@version $Id: admin.php 10680 2007-01-11 21:26:23Z pcool $
*/

include('exercise.class.php');
include('question.class.php');
include('answer.class.php');
include('../inc/global.inc.php');
include('../inc/lib/database.lib.php');
$courseCode   = $_GET['coursecode'];
$questionId   = $_GET['questionId'];
$coordinates  = $_GET['coord'];
$objExcercise = $_SESSION['objExercise'];
$exerciseId   = $objExcercise->selectId();
// Save clicking order
$answerOrderId = count($_SESSION['exerciseResult'][$questionId]['ids'])+1;
if ($_GET['answerId'] == "0") // click is NOT on a hotspot
{
	$hit = 0;
	$answerId = NULL;
}
else // user clicked ON a hotspot
{
	$hit = 1;
	$answerId = api_substr($_GET['answerId'],22,2);
	// Save into session
	$_SESSION['exerciseResult'][$questionId][$answerId] = $hit;
}
//round-up the coordinates
$coords = explode('/',$coordinates);
$coordinates = '';
foreach ($coords as $coord) {
    list($x,$y) = explode(';',$coord);
    $coordinates .= round($x).';'.round($y).'/';
}
$coordinates = substr($coordinates,0,-1);

$TBL_TRACK_E_HOTSPOT = Database::get_statistic_table(STATISTIC_TRACK_E_HOTSPOTS);
// Save into db
$sql = "INSERT INTO $TBL_TRACK_E_HOTSPOT (`user_id` , `course_id` , `quiz_id` , `question_id` , `answer_id` , `correct` , `coordinate` ) VALUES (
			'".Database::escape_string($_user['user_id'])."', 
			'".Database::escape_string($courseCode)."', 
			'".Database::escape_string($exerciseId)."', 
			'".Database::escape_string($questionId)."', 
			'".Database::escape_string($answerId)."', 
			'".Database::escape_string($hit)."', 
			'".Database::escape_string($coordinates)."')";
$result = api_sql_query($sql,__FILE__,__LINE__);
// Save insert id into session if users changes answer.
$insert_id = mysql_insert_id();
$_SESSION['exerciseResult'][$questionId]['ids'][$answerOrderId] = $insert_id;
?>
