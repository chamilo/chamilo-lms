<?php
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
	$interbredcrump[] = array ("url" => "survey.php", "name" => get_lang('CreateSurvey'));
	$group_name=$_GET['groupname'];
	$surveyid=$_REQUEST['surveyid'];
	$groupid=$_REQUEST['groupid'];
	$questtype=$_POST['add_question'];
	Display::display_header($tool_name);
	api_display_tool_title($tool_name);
	select_question_type($add_question12,$groupid,$surveyid,$cidReq);
	Display :: display_footer();
?>