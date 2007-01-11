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
* 	@version $Id: import.php 10680 2007-01-11 21:26:23Z pcool $
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/
// name of the language file that needs to be included 
$language_file = 'survey';

require ('../inc/global.inc.php');
api_protect_admin_script();
require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");
$interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('AdministrationTools'));
$tool_name = get_lang('SelectQuestion');
$Sname = get_lang('SurveyName');
$GName = get_lang('GroupName');
$Author = get_lang('Author');
$surveyid=$_REQUEST['surveyid'];
$newgroupid = $_REQUEST['newgroupid'];
$groupid=$_REQUEST['groupid'];
$surveyname =surveymanager::get_surveyname($surveyid);
$table_survey_question 	= Database::get_course_table(TABLE_SURVEY_QUESTION);
$table_group 			= Database :: get_course_table(TABLE_SURVEY_GROUP);
if(isset($groupid)){
			
			surveymanager::insert_groups($surveyid,$newgroupid,$groupid,$table_group,$table_survey_question);
			header("location:select_question_group.php?surveyid=$surveyid");
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