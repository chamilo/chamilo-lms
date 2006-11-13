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
//api_protect_admin_script();
require_once (api_get_path(LIBRARY_PATH).'/fileManage.lib.php');
require_once (api_get_path(CONFIGURATION_PATH) ."/add_course.conf.php");
require_once (api_get_path(LIBRARY_PATH)."/add_course.lib.inc.php");
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");
$status = surveymanager::get_status();
if($status==5)
{
api_protect_admin_script();
}
$table_category = Database :: get_main_table(MAIN_CATEGORY_TABLE);
$table_survey = Database :: get_main_table(MAIN_SURVEY_TABLE);
$table_group =  Database :: get_main_table(MAIN_GROUP_TABLE);
$table_question = Database :: get_main_table(MAIN_SURVEYQUESTION_TABLE);
$tool_name = get_lang('ViewQuestions');
$header1 = get_lang('SurveyName');
$header2 = get_lang('GroupName');
$header3 = get_lang('Type');
$interbredcrump[] = array ("url" => "index.php", "name" => get_lang('Survey'));
$coursePathWeb = api_get_path(WEB_COURSE_PATH);
$coursePathSys = api_get_path(SYS_COURSE_PATH);
$questionid = '1';
$surveyid = $_REQUEST['surveyid'];
$groupid = $_REQUEST['groupid'];
$qid = 'Yes/No';
if(isset($_REQUEST['back']))
{
 $surveyid = $_REQUEST['surveyid'];
 $groupid = $_REQUEST['groupid'];
 header("location:mcma.php?groupid=$groupid&surveyid=$surveyid");
 exit;
}
/*
-----------------------------------------------------------
	Libraries
-----------------------------------------------------------
*/
/*
==============================================================================
		FUNCTIONS
==============================================================================
*/
/*
==============================================================================
		MAIN CODE
==============================================================================
*/

Display::display_header($tool_name);
$ques_id = $_GET['qid'];
$gname=surveymanager::ques_id_group_name($ques_id);
$ques_type = $_GET['qtype'];
?>


<form name="question" method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
<input type="hidden" name="action" value="add_question">
<input type="hidden" name="groupid" value="<?=$groupid?>">
<input type="hidden" name="surveyid" value="<?=$surveyid?>">
<table>
<tr>
  <td><?php api_display_tool_title($header1) ?></td>
  <?php $surveyname = surveymanager::get_surveyname($surveyid); ?>
  <td><?php api_display_tool_title($surveyname)?></td>
  </tr>
  <tr>
  <td><?php  api_display_tool_title($header2); ?></td>
   <?php $groupname = surveymanager::get_groupname($groupid); ?>
  <td><?php api_display_tool_title($groupname); ?></td>
  </tr>
  <tr>
  <td><?php api_display_tool_title($header3); ?></td>
   <td><?php api_display_tool_title($qid); ?></td>
  </tr>
<tr>
  <td><?php echo get_lang('question'); ?></td>
  </tr>
<tr>
<td><textarea  cols="50" rows="6" name="questions"> <?echo $enterquestion;?></textarea></td>
</tr>
<tr>
  <td></br><?php echo get_lang('answer'); ?></td>
  </tr>
   <tr>
  <?
	for($i=1;$i<=2;$i++)
	{	
		?><tr><td><textarea cols="50" rows="3" name="yes"><?echo $mutlichkboxtext[$i];?></textarea></td></tr>
<?		
	}  
  ?>
<tr>
  <td></br><input type="submit" name="back" value="<?php  echo get_lang('back'); ?> "></td>
 <!-- <td></br><input type="submit" value="<?php  echo get_lang('import'); ?>"></td>-->
</tr>
</table>
</form>	
<?php
//<textarea  rows="4" name="comment"></textarea>
/*
==============================================================================
		FOOTER 
==============================================================================
*/
Display :: display_footer();
?>