<?php //$id:$
/* For licensing terms, see /dokeos_license.txt */
/**
*	This file saves every click in the hotspot tool into track_e_hotspots
*	@package chamilo.exercise
* 	@author Toon Keppens
* 	@version $Id: admin.php 10680 2007-01-11 21:26:23Z pcool $
*/
/**
 * Code
 */
include 'exercise.class.php';
include 'question.class.php';
include 'answer.class.php';
include '../inc/global.inc.php';


$courseCode   = $_GET['coursecode'];
$questionId   = $_GET['questionId'];
$coordinates  = $_GET['coord'];
$objExcercise = $_SESSION['objExercise'];
$hotspotId	  = $_GET['hotspotId'];
$exerciseId   = $objExcercise->selectId();
if ($_GET['answerId'] == "0") { // click is NOT on a hotspot
	$hit = 0;
	$answerId = $hotspotId;

	// remove from session
	unset($_SESSION['exerciseResult'][$questionId][$answerId]);

	// Save clicking order
	//$answerOrderId = count($_SESSION['exerciseResult'][$questionId]['order'])+1;
	//$_SESSION['exerciseResult'][$questionId]['order'][$answerOrderId] = $answerId;
} else { // user clicked ON a hotspot
	$hit = 1;
	$answerId = $hotspotId;

	// Save into session
	$_SESSION['exerciseResult'][$questionId][$answerId] = $hit;

	// Save clicking order
	//$answerOrderId = count($_SESSION['exerciseResult'][$questionId]['order'])+1;
	//$_SESSION['exerciseResult'][$questionId]['order'][$answerOrderId] = $answerId;
}

//round-up the coordinates
$coords = explode('/',$coordinates);
$coordinates = '';
foreach ($coords as $coord) {
    list($x,$y) = explode(';',$coord);
    $coordinates .= round($x).';'.round($y).'/';
}
$coordinates = substr($coordinates,0,-1);

$TBL_TRACK_E_HOTSPOT   = Database::get_statistic_table(STATISTIC_TRACK_E_HOTSPOTS);

// update db
$update_id = $_SESSION['exerciseResult'][$questionId]['ids'][$answerId];
$sql = "UPDATE $TBL_TRACK_E_HOTSPOT SET coordinate = '".Database::escape_string($coordinates)."' WHERE id ='".Database::escape_string($update_id)."' LIMIT 1 ;;";
$result = Database::query($sql);
