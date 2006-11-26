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
// name of the language file that needs to be included 
$language_file = 'survey';

require_once ('../inc/global.inc.php');
require_once (api_get_path(LIBRARY_PATH).'/fileManage.lib.php');
require_once (api_get_path(CONFIGURATION_PATH) ."/add_course.conf.php");
require_once (api_get_path(LIBRARY_PATH)."/add_course.lib.inc.php");
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/usermanager.lib.php");
$status = surveymanager::get_status();
if($status==5)
{
api_protect_admin_script();
}
$cidReq=$_GET['cidReq'];
$curr_dbname = $_REQUEST['curr_dbname'];
$table_group = Database :: get_course_table('survey_group');
$table_user = Database :: get_main_table(TABLE_MAIN_USER);
$tool_name1 = get_lang('createnewgroup1');
$tool_name = get_lang('ModifyGroupInformation');
$interbredcrump[] = array ("url" => "survey_list.php?", "name" => get_lang('Survey'));
$coursePathWeb = api_get_path(WEB_COURSE_PATH);
$coursePathSys = api_get_path(SYS_COURSE_PATH);
$groupid = $_GET['groupid'];
$surveyid = $_GET['surveyid'];
if($_POST['action'] == 'new_group')
{
	if(isset($_POST['back']))
   { 
	 $cidReq = $_REQUEST['cidReq'];
	 $surveyid = $_REQUEST['surveyid'];	
	 $curr_dbname = $_REQUEST['curr_dbname'];
     header("location:create_new_group.php?surveyid=$surveyid&cidReq=$cidReq&curr_dbname=$curr_dbname");
	 exit;
   } 
}
if ($_POST['action'] == 'new_group')
{
	$surveyid = $_POST['surveyid'];
	$groupid = $_POST['groupid'];
	$groupname = $_POST['groupname'];
	$groupname=trim($groupname);
	if(empty ($groupname))
	{
			$error_message = get_lang('PleaseEnterGroupName');      
	}
	$introduction = $_REQUEST['content'];
	$curr_dbname = $_REQUEST['curr_dbname'];
  if(isset($_POST['next']))
    {
	$cidReq = $_GET['cidReq'];
	$curr_dbname = $_REQUEST['curr_dbname'];
	$groupname=trim($groupname);
	if(empty ($groupname))
	{
			$error_message = get_lang('PleaseEnterGroupName');      
	}
	else
	{
    SurveyManager::update_group($groupid,$surveyid,$groupname,$introduction,$curr_dbname);
	header("location:select_question_group.php?surveyid=$surveyid&cidReq=$cidReq&curr_dbname=$curr_dbname");
	exit;
	}
	} 
   if(isset($_POST['saveandexit']))
   { 
	 $cidReq = $_GET['cidReq'];
	 $groupname=trim($groupname);
	 if(empty ($groupname))
	 {
			$error_message = get_lang('PleaseEnterGroupName');      
	 }
	 else
	 {
	 SurveyManager::update_group($groupid,$surveyid,$groupname,$introduction,$curr_dbname);
     header("location:survey_list.php?cidReq=$cidReq");
	 exit;
	 }
   }  	
}
Display::display_header($tool_name1);
api_display_tool_title($tool_name);
if( isset($error_message) )
{
	Display::display_error_message($error_message);	
}
?>
<?php
$sql = "select * from $table_group where group_id='$groupid'";
$res = api_sql_query($sql);
$obj = mysql_fetch_object($res);
$groupname= $obj->groupname;
$introduction = $obj->introduction;
?>
<form name="new_calendar_item" method="post" action="<?php echo $_SERVER['PHP_SELF'];?>?cidReq=<?php echo $cidReq; ?>">
<input type="hidden" name="action" value="new_group">
<input type="hidden" name="surveyid" value="<?php echo $surveyid; ?>">
<input type="hidden" name="groupid" value="<?php echo $groupid; ?>">
<input type="hidden" name="curr_dbname" value="<?php echo $curr_dbname; ?>">
<!--<input type="hidden" name="cidReq" value="<?php echo $_REQUEST['cidReq']; ?>">-->
<table>
<tr>
  <td><?php echo get_lang('groupname'); ?></td>
  <td><input type="text" name="groupname" size="40" value="<?php echo $groupname ?>"></td>
</tr>
	   <tr><td valign="top"><?php echo get_lang('GroupIntroduction'); ?>&nbsp;</td>
        <td>
   <?php
         api_disp_html_area('content',$introduction,'300px');
   ?>
          <br>
        </td>
      </tr>
</table>
<tr>
  <td>&nbsp;</td>
  <td><input type="submit" name="back" value="<?php echo get_lang('back'); ?>"></td>
  <td><input type="submit" name="saveandexit" value="<?php echo get_lang('saveandexit'); ?>"></td>
  <td><input type="submit" name="next" value="<?php echo get_lang('next'); ?>"></td>
</tr>
</form>
<?php
Display :: display_footer();
?>