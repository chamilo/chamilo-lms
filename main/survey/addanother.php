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
require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");
require (api_get_path(LIBRARY_PATH)."/groupmanager.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");
$status = surveymanager::get_status();
if($status==5)
{
api_protect_admin_script();
}
require_once (api_get_path(LIBRARY_PATH)."/usermanager.lib.php");
//require_once ('../inc/global.inc.php');
$coursePathWeb = api_get_path(WEB_COURSE_PATH);
$coursePathSys = api_get_path(SYS_COURSE_PATH);
$table_user = Database :: get_main_table(MAIN_USER_TABLE);
//$cidReq = $_SESSION[_course][id];
$cidReq = $_GET['cidReq'];
$curr_dbname = $_REQUEST['curr_dbname'];
$group_id=$_GET['newgroupid'];
$table_survey = Database :: get_course_table('survey');
//$table_group=Database::get_course_table('survey_group');
$tool_name = get_lang('New_survey');
$tool_name1 = get_lang('AddAnotherQuestion');
$interbredcrump[] = array ("url" => "survey_list.php", "name" => get_lang('Survey'));
//$interbredcrump[] = array ("url" => "survey.php", "name" => get_lang('CreateSurvey'));
$course_id = $_SESSION['_course']['id'];
if(isset($_REQUEST['surveyid']))
$surveyid=$_REQUEST['surveyid'];
if(isset($_REQUEST['groupid']))
$groupid=$_REQUEST['groupid'];
if(isset($_REQUEST['cidReq']))
$cidReq=$_REQUEST['cidReq'];
if(isset($_REQUEST['newgroupid']))
$groupid=$_REQUEST['newgroupid'];
if(isset($_POST['back']))
{ 
	header("location:select_question_group.php?add_question=$add_question&groupid=$groupid&surveyid=$surveyid&cidReq=$cidReq&curr_dbname=$curr_dbname");
}

if(isset($_POST['next']))
{
	if(isset($_POST['add_question']))
	{
		if(isset($_POST['exiztinggroup']))
		{
			$groupid=$_POST['exiztinggroup'];
			$add_question=$_POST['add_question'];
			$curr_dbname = $_REQUEST['curr_dbname'];
			switch ($_POST['add_question'])
			{
				case get_lang('yesno'):
				header("location:yesno.php?add_question=$add_question&groupid=$groupid&surveyid=$surveyid&cidReq=$cidReq&curr_dbname=$curr_dbname");
				break;
				case get_lang('MultipleChoiceSingle'):
				header("location:mcsa.php?add_question=$add_question&groupid=$groupid&surveyid=$surveyid&cidReq=$cidReq&curr_dbname=$curr_dbname");
				break;
				case get_lang('MultipleChoiceMulti'):
				header("location:mcma.php?add_question=$add_question&groupid=$groupid&surveyid=$surveyid&cidReq=$cidReq&curr_dbname=$curr_dbname");
				break;
				case get_lang('Open'):
				header("location:open.php?add_question=$add_question&groupid=$groupid&surveyid=$surveyid&cidReq=$cidReq&curr_dbname=$curr_dbname");
				break;
				case get_lang('numbered'):
				header("location:numbered.php?add_question=$add_question&groupid=$groupid&surveyid=$surveyid&cidReq=$cidReq&curr_dbname=$curr_dbname");
				break;
				default :
				header("location:select_question_type.php?cidReq=$cidReq");
				break;
			}	
		}
		else
		$error_message = get_lang('PleaseSelectGroup')."<br>";
		
		
		//exit;
	}
	else
	$error_message=get_lang('PleaseSelectQuestionAndGroup');
}
Display::display_header($tool_name1);
$query="SELECT * FROM $table_survey WHERE survey_id='$surveyid'";
$result=api_sql_query($query);
$surveyname=mysql_result($result,0,'title');
//$surveyname=get_lang('SurveyName').$surveyname;
api_display_tool_title(get_lang('SurveyName')."".$surveyname);
api_display_tool_title($tool_name1);
if(isset($error_message))
Display::display_error_message($error_message);
if(isset($group_id))
{
 $table_group =  Database :: get_course_table('survey_group');
 $sql = "SELECT * FROM $table_group WHERE group_id='$group_id'";
 $res = api_sql_query($sql, __FILE__, __LINE__);
 $obj= mysql_fetch_object($res);
 ?>
 <div align="center"><strong><font color="#FF0000"><?php echo "Group";?>&nbsp;&nbsp;<font color="#0000CC"><u><?php echo $obj->groupname;?></u>&nbsp;&nbsp;</font><?php echo get_lang('GroupCreated');?></font></strong></div>
<?
}

?>
<form name="question" method="post" action="<?php echo $_SERVER['PHP_SELF'];?>?cidReq=<?php echo $cidReq; ?>">
<input type="hidden" name="groupid" value="<?php echo $groupid; ?>">
<input type="hidden" name="surveyid" value="<?php echo $surveyid; ?>">
<input type="hidden" name="newgroupid" value="<?php echo $group_id; ?>">
<input type="hidden" name="curr_dbname" value="<?php echo $curr_dbname; ?>">
<!--<input type="hidden" name="cidReq" value="<?php echo $cidReq; ?>">-->
<table>
<tr>
<td>
<?php echo get_lang('SelectQuestionType');?>
</td>
<td>

<select name="add_question" >	
	<option value="<?php echo get_lang('yesno'); ?>" ><?php echo get_lang('yesno');?></option>
	<option value="<?php echo get_lang('MultipleChoiceSingle'); ?>"  ><?php echo get_lang('MultipleChoiceSingle');?></option>
	<option value="<?php echo get_lang('MultipleChoiceMulti'); ?>" ><?php echo get_lang('MultipleChoiceMulti');?></option>
	<option value="<?php echo get_lang('Open');?>" ><?php echo get_lang('Open');?></option>
	<option value="<?php echo get_lang('numbered');?>"><?php echo get_lang('numbered');?></option>
</select>
</td>
</tr>
<tr></tr>
<tr></tr>
<tr></tr>
<tr></tr>
<tr>
<td>
<?php echo get_lang('SelectGroup');?>
</td>
<td>
<?php 
	echo SurveyManager::select_group_list($surveyid, $groupid, $extra_script);
?>
<!--<select name="select_group" >-->

<?php 
	/*$query="SELECT * FROM $table_group WHERE survey_id='$surveyid'";
	//echo $query;
	$result=api_sql_query($query);
	$num=mysql_num_rows($result);

	for($i=0;$i<$num;$i++)
	{
		$groupid=mysql_result($result,$i,'group_id');
		$gname=mysql_result($result,$i,'groupname');
		*/
?>
		<!--<option value="<?echo $groupid;?>" ><?php echo $gname;?></option>-->
<?php 	//}
?>

<!--</select>-->
</td>
</tr>
<tr></tr>
<tr></tr>
<tr></tr>
<tr>
<td>&nbsp;</td>
<td>
	<input type="submit" name="back" value="<?php echo get_lang("back");?>">
	<input type="submit" name="next" value="<?php echo get_lang("next");?>">
</tr>

</table>
</form>
<?php 
	Display :: display_footer();
?>