<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2008 Dokeos SPRL

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/

/**
==============================================================================
*	This file holds the configuration constants and variables
*	for the course info tool.
*
*	@package dokeos.configuration
==============================================================================
*/

$course_info_is_editable = true;
/*
//if (basename($_SERVER["SCRIPT_FILENAME"])==basename(__FILE__)) die("Va voir ailleurs");
$showLinkToExportThisCourse = TRUE;
$showLinkToBackupThisCourse = TRUE;
$showLinkToRecycleThisCourse = TRUE;
$showLinkToRestoreCourse	= TRUE;
$showLinkToCopyThisCourse 	= TRUE; 
*/
// If true, these fileds  keep the previous content.
/*
$canBeEmpty["screenCode"] 	= FALSE;
$canBeEmpty["course_title"] 			= FALSE;
$canBeEmpty["course_category"] 		= TRUE;
$canBeEmpty["description"] 	= TRUE;
$canBeEmpty["visibility"]	= FALSE;
$canBeEmpty["titulary"] 	= FALSE;
$canBeEmpty["course_language"]= FALSE;
$canBeEmpty["department_name"]	= TRUE;
$canBeEmpty["department_url"] 	= TRUE;
*/

$showDiskQuota									= TRUE;
//$showDiskUse									= TRUE;
//$showLinkToChangeDiskQuota					= TRUE;
$showExpirationDate 							= TRUE;
$showCreationDate 								= TRUE;
$showLastEdit 									= TRUE;
$showLastVisit 									= TRUE;
$canReportExpirationDate 						= TRUE; // need to be true 
														// if ScriptToReportExpirationDate 
														// is not automaticly called
//$linkToChangeDiskQuota						= "changeQuota.php";
$urlScriptToReportExpirationDate 				= "postpone.php"; // external script to postpone the expiration of course.
?>
