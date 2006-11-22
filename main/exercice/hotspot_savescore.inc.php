<?php
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert
	
	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".
	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	See the GNU General Public License for more details.
	
	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
============================================================================== 
*/
/**
============================================================================== 
*	This file saves every click in the hotspot tool into track_e_hotspots
*
*	@author Toon Keppens
*	@package dokeos.exercise
============================================================================== 
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
		$answerId = substr($_GET['answerId'],22,2);
		
		// Save into session
		$_SESSION['exerciseResult'][$questionId][$answerId] = $hit;
		
	}
	
	$TBL_TRACK_E_HOTSPOT = Database::get_statistic_table(STATISTIC_TRACK_E_HOTSPOTS);
	
	// Save into db
	$sql = "INSERT INTO $TBL_TRACK_E_HOTSPOT (`user_id` , `course_id` , `quiz_id` , `question_id` , `answer_id` , `correct` , `coordinate` ) VALUES ('".$_user['user_id']."', '$courseCode', '$exerciseId', '$questionId', '$answerId', '$hit', '$coordinates')";
	
	$result = api_sql_query($sql,__FILE__,__LINE__);
	
	// Save insert id into session if users changes answer.
	$insert_id = mysql_insert_id();
	$_SESSION['exerciseResult'][$questionId]['ids'][$answerOrderId] = $insert_id;
?>