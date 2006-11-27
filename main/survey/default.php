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
*	@package dokeos.survey
* 	@author 
* 	@version $Id: default.php 10223 2006-11-27 14:45:59Z pcool $
*/

require_once ('../inc/global.inc.php');
$surveyid = $_GET['surveyid'];
$groupid = $_GET['groupid'];
$cidReq = $_GET['cidReq'];
$qtype = $_GET['qtype'];
$qid = $_GET['qid'];
$db_name = $_REQUEST['db_name'];
switch($qtype)
{
case "Yes/No":
	include("question.php");
	break;
case "Multiple Choice (single answer)":
	include("mcsa_view.php");	
	break;
case "Multiple Choice (multiple answer)":
    include("mcma_view.php");	
	break;
case "Open Answer":
	include("open_view.php");	
	break;
case "Numbered":
	include("numbered_view.php");
	break;
}
?>