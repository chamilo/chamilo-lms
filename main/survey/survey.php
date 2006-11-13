<?php
// $Id: course_add.php,v 1.10 2005/05/30 11:46:48 bmol Exp $
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
*	@package dokeos.admin
==============================================================================
*/
/*
==============================================================================
		INIT SECTION
==============================================================================
*/
$langFile = 'survey';
require_once ('../inc/global.inc.php');
require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");
require (api_get_path(LIBRARY_PATH)."/groupmanager.lib.php");
$cidReq = $_REQUEST['cidReq'];
$table_survey = Database :: get_course_table('survey');
/*
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
exit;
*/
//api_protect_admin_script();
require_once (api_get_path(LIBRARY_PATH).'/fileManage.lib.php');
require_once (api_get_path(CONFIGURATION_PATH)."/add_course.conf.php");
require_once (api_get_path(LIBRARY_PATH)."/add_course.lib.inc.php");
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");
$status = surveymanager::get_status();
api_protect_course_script();
if($status==5)
{
api_protect_admin_script();
}
//$table_category = Database :: get_course_table(MAIN_CATEGORY_TABLE);
//$table_survey = Database :: get_course_table(MAIN_SURVEY_TABLE);
//$table_course = Database::get_course_table(MAIN_COURSE_TABLE);
$tool_name = get_lang('CreateSurvey');
$interbredcrump[] = array ("url" => "survey_list.php", "name" => get_lang('Survey'));
$newsurvey = '0';
$existingsurvey = '1';
$aaa = '11';
//$page = 'new';
//$page1= 'ex';
if($_POST['back'])
{
 $cidReq=$_GET['cidReq'];
 header("location:survey_list.php?cidReq=$cidReq");
 exit;
}
if (!empty($_POST['action']))
{	
	$surveyid=$_POST['exiztingsurvey'];
	$survey = $_POST['survey'];
	$cidReq=$_REQUEST['cidReq'];
    //$existingsurvey = trim(stripslashes($_POST['exiztingsurvey']));
	if ($survey==0)
	{
		 header("location:create_new_survey.php?cidReq=$cidReq");
		 exit;
	}
	else
	{		
		 //header("location:create_from_existing_survey.php?surveyid=$surveyid&cidReq=$cidReq");
		 header("location:survey_all_courses.php?cidReq=$cidReq");
		 exit;
	}
}
//$survey_list=SurveyManager::select_survey_list('',$extra_script);
/*
if(!$survey_list){
		 header("location:create_new_survey.php?cidReq=$cidReq");
		 exit;
}
*/
Display::display_header($tool_name);
api_display_tool_title($tool_name);
//echo "<pre>";
//print_r($_SESSION);
//echo "</pre>";
//echo $survey_table = Database :: get_course_table('survey');
?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>?cidReq=<?php echo $cidReq; ?>" name="mainForm">
<input type="hidden" name="cidReq" value="<?php echo $cidReq; ?>">
<!--<input type="hidden"  value="add_survey">-->
<table>
<tr>
<td valign="top"></td>
<td>
<input class="checkbox" checked type="radio" name="survey" id="new_survey" value="<?php echo $newsurvey ?>"> <label for="visibility_open_world"><?php echo get_lang("Newsurvey") ?></label>
<?php
$extra_script = "OnChange=\"javascript:document.mainForm.survey[1].checked=true;\"";
//$survey_list=SurveyManager::select_survey_list('',$extra_script);
//if($survey_list){
?>
	<br/>
	<input class="checkbox" type="radio" name="survey" id="Existing_survey" value="<?php echo $existingsurvey ?>"> <label for="visibility_open_platform"><?php echo  get_lang("Existingsurvey") ?></label>&nbsp;<?php ?></td></tr>
	<tr>
	 <td></td>
	 <td></td>
	</tr>

<tr>
  <td></td>
  <td><input type="submit" name="back" value="<?php echo get_lang('back');?>">&nbsp;<input type="submit" name="action" value="<?php echo get_lang('Ok1'); ?>"></td>
  <td><input type="hidden" name="newsurveyid" value="<?php echo $newsurvey_id; ?>"></td>  
  <td></td> 
</tr>
</table>
</form>
<?php
Display :: display_footer();
?>