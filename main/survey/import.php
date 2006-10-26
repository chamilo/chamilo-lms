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
api_protect_admin_script();
require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");
$cidReq = $_REQUEST['cidReq'];
$interbredcrump[] = array ("url" => "index.php", "name" => get_lang('AdministrationTools'));
$tool_name = get_lang('SelectQuestion');
$Sname = get_lang('SurveyName');
$GName = get_lang('groupname');
$Author = get_lang('Author');
$surveyid=$_REQUEST['surveyid'];
$newgroupid = $_REQUEST['newgroupid'];
$groupid=$_REQUEST['groupid'];
$surveyname =surveymanager::get_surveyname($surveyid);
$table_question = Database::get_course_table('questions');
$table_group = Database :: get_course_table('survey_group');
if(isset($groupid)){
			
			surveymanager::insert_groups($surveyid,$newgroupid,$groupid,$table_group,$table_question);
			//surveymanager::display_imported_group($surveyid,$table_group,$table_question,$groupid,$cidReq);
			header("location:select_question_group.php?surveyid=$surveyid&cidReq=$cidReq");
			exit;
}else{
	echo "<font color=red size=+1>Error : No Group</font>";
}

/*
==============================================================================
		FOOTER 
==============================================================================
*/
Display :: display_footer();
?> 