<?php

/**
 *	This file holds the configuration constants and variables
 *	for the course info tool.
 *
 *	@package chamilo.configuration
 */

$course_info_is_editable = true;
/*
//if (basename($_SERVER['SCRIPT_FILENAME']) == basename(__FILE__)) die('Va voir ailleurs');
$showLinkToExportThisCourse			= true;
$showLinkToBackupThisCourse			= true;
$showLinkToRecycleThisCourse		= true;
$showLinkToRestoreCourse			= true;
$showLinkToCopyThisCourse			= true;
*/
// If true, these fileds  keep the previous content.
/*
$canBeEmpty['screenCode']			= false;
$canBeEmpty['course_title']			= false;
$canBeEmpty['course_category']		= true;
$canBeEmpty['description']			= true;
$canBeEmpty['visibility']			= false;
$canBeEmpty['titulary']				= false;
$canBeEmpty['course_language']		= false;
$canBeEmpty['department_name']		= true;
$canBeEmpty['department_url']		= true;
*/

$showDiskQuota = true;
//$showDiskUse						= true;
//$showLinkToChangeDiskQuota		= true;
$showExpirationDate = true;
$showCreationDate = true;
$showLastEdit = true;
$showLastVisit = true;
$canReportExpirationDate = true; // Needs to be true
// if ScriptToReportExpirationDate
// is not automaticly called
//$linkToChangeDiskQuota			= 'changeQuota.php';
$urlScriptToReportExpirationDate = 'postpone.php'; // external script to postpone the expiration of course.
