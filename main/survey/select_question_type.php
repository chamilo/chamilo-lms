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
* 	@version $Id: select_question_type.php 10549 2006-12-24 16:08:47Z pcool $
*/

// name of the language file that needs to be included 
$language_file = 'survey';
	
	require_once ('../inc/global.inc.php');
	api_protect_admin_script();
	$add_question12=$_REQUEST['add_question'];
	require_once ('select_question.php');
	require_once (api_get_path(LIBRARY_PATH).'/fileManage.lib.php');
	require_once (api_get_path(CONFIGURATION_PATH) ."/add_course.conf.php");
	require_once (api_get_path(LIBRARY_PATH)."/add_course.lib.inc.php");
	require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");
	$cidReq=$_GET['cidReq'];
	$tool_name = get_lang('AddQuestion');
	$interbreadcrumb[] = array ("url" => "survey.php", "name" => get_lang('CreateSurvey'));
	$group_name=$_GET['groupname'];
	$surveyid=$_REQUEST['surveyid'];
	$groupid=$_REQUEST['groupid'];
	$questtype=$_POST['add_question'];
	Display::display_header($tool_name);
	api_display_tool_title($tool_name);
	select_question_type($add_question12,$groupid,$surveyid,$cidReq);
	Display :: display_footer();
?>