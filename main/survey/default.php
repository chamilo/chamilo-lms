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
* 	@version $Id: default.php 10680 2007-01-11 21:26:23Z pcool $
*/

// including the global dokeos file
require_once ('../inc/global.inc.php');

// including additional libraries
/** @todo check if these are all needed */
/** @todo check if the starting / is needed. api_get_path probably ends with an / */



$surveyid = $_GET['surveyid'];
$groupid = $_GET['groupid'];
$qtype = $_GET['qtype'];
$qid = $_GET['qid'];
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