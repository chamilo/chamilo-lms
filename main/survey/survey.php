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
/*
-----------------------------------------------------------
	Including necessary files
-----------------------------------------------------------
*/
require_once ('../inc/global.inc.php');
require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/groupmanager.lib.php");
require_once (api_get_path(LIBRARY_PATH).'/fileManage.lib.php');
require_once (api_get_path(CONFIGURATION_PATH)."/add_course.conf.php");
require_once (api_get_path(LIBRARY_PATH)."/add_course.lib.inc.php");
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");

$cidReq = $_GET['cidReq'];
/*
-----------------------------------------------------------
	Table definitions
-----------------------------------------------------------
*/
$table_survey = Database :: get_course_table('survey');
//$table_category = Database :: get_course_table(MAIN_CATEGORY_TABLE);
//$table_survey = Database :: get_course_table(MAIN_SURVEY_TABLE);
//$table_course = Database::get_course_table(MAIN_COURSE_TABLE);

/*
-----------------------------------------------------------
	some permissions stuff (?)
-----------------------------------------------------------
*/
$status = surveymanager::get_status();
api_protect_course_script();
if($status==5)
{
api_protect_admin_script();
}

/*
-----------------------------------------------------------
	Breadcrumbs and toolname
-----------------------------------------------------------
*/
$tool_name = get_lang('CreateSurvey');
$interbreadcrumb[] = array ("url" => "survey_list.php", "name" => get_lang('Survey'));

/*
-----------------------------------------------------------
	some variables
-----------------------------------------------------------
*/
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

/*
-----------------------------------------------------------
	Action Handling
-----------------------------------------------------------
*/
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


/*
-----------------------------------------------------------
	Header
-----------------------------------------------------------
*/
Display::display_header($tool_name);
api_display_tool_title($tool_name);

?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>?cidReq=<?php echo $cidReq; ?>" name="mainForm">
<input type="hidden" name="cidReq" value="<?php echo $cidReq; ?>" />
<input type="hidden" name="newsurveyid" value="<?php echo $newsurvey_id; ?>" />
<input class="checkbox" checked type="radio" name="survey" id="new_survey" value="<?php echo $newsurvey; ?>" /> <label for="new_survey"><?php echo get_lang("Newsurvey"); ?></label><br/>
<input class="checkbox" type="radio" name="survey" id="existing_survey" value="<?php echo $existingsurvey; ?>" /> <label for="existing_survey"><?php echo  get_lang("Existingsurvey"); ?></label><br />
<input type="submit" name="back" value="<?php echo get_lang('Back');?>" />&nbsp;
<input type="submit" name="action" value="<?php echo get_lang('Ok1'); ?>" />
</form>
<?php
/*
-----------------------------------------------------------
	Footer
-----------------------------------------------------------
*/
Display :: display_footer();
?>