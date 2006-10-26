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
*	This file generates the ActionScript code used by the HotSpot .swf
*
*	@author Toon Keppens
*	@package dokeos.exercise
============================================================================== 
*/

	include('exercise.class.php');
	include('question.class.php');
	include('answer.class.php');
	
	include('../inc/global.inc.php');
	
	$TBL_ANSWER = $_GET['dbNameGlu']."quiz_answer";
	$questionId = $_GET['questionId'];
	$answerId = $_GET['answerId'];

	if ($_GET['type'] == "square" || $_GET['type'] == "circle")
	{
		$hotspot_type = $_GET['type'];
		$hotspot_coordinates = $_GET['x'].";".$_GET['y']."|".$_GET['width']."|".$_GET['height'];
	}
	if ($_GET['type'] == "poly")
	{
		$hotspot_type = $_GET['type'];
		$tmp_coord = explode(",",$_GET['co']);
		$i = 0;
		$hotspot_coordinates = "";
		foreach ($tmp_coord as $coord)
		{
			if ($i%2 == 0)
			{
				$delimiter = ";";
			} else
			{
				$delimiter = "|";
			}
			$hotspot_coordinates .= $coord.$delimiter;
			$i++;
		}
		$hotspot_coordinates = substr($hotspot_coordinates,0,-2);
	}
	$sql = "UPDATE `$TBL_ANSWER` SET `hotspot_coordinates` = '$hotspot_coordinates',`hotspot_type` = '$hotspot_type' WHERE `id` =$answerId AND `question_id` =$questionId LIMIT 1 ;";
	$result = api_sql_query($sql,__FILE__,__LINE__);
	
	echo "done=done";
?>