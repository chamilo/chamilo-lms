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
*	This file generates the ActionScript code used by the HotSpot .swf
*	@package dokeos.exercise
* 	@author Toon Keppens
* 	@version $Id: admin.php 10680 2007-01-11 21:26:23Z pcool $
*/


include('exercise.class.php');
include('question.class.php');
include('answer.class.php');
include('../inc/global.inc.php');

$TBL_ANSWER = Database::get_course_table(TABLE_QUIZ_ANSWER);
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
		}
		else
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