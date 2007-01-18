<?php
/*
    DOKEOS - elearning and course management software

    For a full list of contributors, see documentation/credits.html

    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.
    See "documentation/licence.html" more details.

    Contact:
		Dokeos
		Rue des Palais 44 Paleizenstraat
		B-1030 Brussels - Belgium
		Tel. +32 (2) 211 34 56
*/


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


$courseCode   = $_GET['coursecode'];
$questionId   = $_GET['questionId'];
$coordinates  = $_GET['coord'];
$objExcercise = $_SESSION['objExercise'];
$hotspotId	  = $_GET['hotspotId'];
$exerciseId   = $objExcercise->selectId();
if ($_GET['answerId'] == "0") // click is NOT on a hotspot
{
	$hit = 0;
	$answerId = $hotspotId;

	// remove from session
	unset($_SESSION['exerciseResult'][$questionId][$answerId]);

	// Save clicking order
	//$answerOrderId = count($_SESSION['exerciseResult'][$questionId]['order'])+1;
	//$_SESSION['exerciseResult'][$questionId]['order'][$answerOrderId] = $answerId;
}
else // user clicked ON a hotspot
{
	$hit = 1;
	$answerId = $hotspotId;

	// Save into session
	$_SESSION['exerciseResult'][$questionId][$answerId] = $hit;

	// Save clicking order
	//$answerOrderId = count($_SESSION['exerciseResult'][$questionId]['order'])+1;
	//$_SESSION['exerciseResult'][$questionId]['order'][$answerOrderId] = $answerId;
}

$TBL_TRACK_E_HOTSPOT   = Database::get_statistic_table(STATISTIC_TRACK_E_HOTSPOTS);

// update db
$update_id = $_SESSION['exerciseResult'][$questionId]['ids'][$answerId];
$sql = "UPDATE $TBL_TRACK_E_HOTSPOT SET `coordinate` = '".$coordinates."' WHERE `id` =$update_id LIMIT 1 ;;";
$result = api_sql_query($sql,__FILE__,__LINE__);
?>