<?php
// $Id: group_list.php,v 1.15.2.1 2006/02/13 09:15:57 surbhi Exp $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 University of Ghent (UGent)
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
	@author Bart Mollet
*	@package dokeos.admin
============================================================================== 
*/
/*
==============================================================================
		INIT SECTION
==============================================================================
*/
$langFile = 'survey';

require ('../inc/global.inc.php');
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");
$status = surveymanager::get_status();
if($status==5)
{
api_protect_admin_script();
}
//api_protect_admin_script();
$cidReq = $_REQUEST['cidReq'];
$curr_dbname = $_REQUEST['curr_dbname'];
$groupid=$_REQUEST['groupid'];
$surveyid=$_REQUEST['surveyid'];
$qid=$_REQUEST['qid'];
$qtype=$_REQUEST['qtype'];
require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");
$table_survey = Database :: get_course_table('survey');
$table_group =  Database :: get_course_table('survey_group');
$table_question = Database :: get_course_table('questions');

if($qtype=="Yes/No")
{
	header("location:yesno_edit.php?qid=$qid&cidReq=$cidReq&qtype=$qtype&groupid=$groupid&surveyid=$surveyid&curr_dbname=$curr_dbname");
	exit;
}
if($qtype=="Numbered")
{
	header("location:numbered_edit.php?qid=$qid&cidReq=$cidReq&qtype=$qtype&groupid=$groupid&surveyid=$surveyid&curr_dbname=$curr_dbname");
	exit;
}

if($qtype=="Multiple Choice (single answer)")
{
	header("location:mcsa_edit.php?qid=$qid&cidReq=$cidReq&qtype=$qtype&groupid=$groupid&surveyid=$surveyid&curr_dbname=$curr_dbname");
	exit;
}

if($qtype=="Multiple Choice (multiple answer)")
{
	header("location:mcma_edit.php?qid=$qid&cidReq=$cidReq&qtype=$qtype&groupid=$groupid&surveyid=$surveyid&curr_dbname=$curr_dbname");
	exit;
}

if($qtype=="Open Answer")
{
 header("location:open_edit.php?qid=$qid&cidReq=$cidReq&qtype=$qtype&groupid=$groupid&surveyid=$surveyid&curr_dbname=$curr_dbname");
	exit;
}

Display :: display_footer();
?>