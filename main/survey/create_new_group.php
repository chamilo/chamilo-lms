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
require_once (api_get_path(LIBRARY_PATH)."/usermanager.lib.php");
$cidReq=$_GET['cidReq'];
$curr_dbname = $_REQUEST['curr_dbname'];
$table_group = Database :: get_course_table('survey_group');
$table_user = Database :: get_main_table(MAIN_USER_TABLE);
$tool_name1 = get_lang('createnewgroup1');
$tool_name = get_lang('createnewgroup');
$header1 = get_lang('GroupList');
$interbredcrump[] = array ("url" => "survey_list.php", "name" => get_lang('Survey'));
$surveyid = $_GET['surveyid'];
$surveyname = SurveyManager::pick_surveyname($surveyid);

if(isset($_GET['direction'])){
	$sql = 'SELECT * 
			FROM '.$table_group.' 
			WHERE group_id='.intval($_GET['id_group']);
	
	$rs = api_sql_query($sql, __FILE__, __LINE__);
	$group = mysql_fetch_object($rs);
	if(is_object($group)){
		$_GET['direction'] == 'up' ? $operateur = '-' : $operateur = '+';
		
		$sql = 'UPDATE '.$table_group.' SET 
				sortby='.$group->sortby.'
				WHERE sortby='.$group->sortby.$operateur.'1' ;
		
		$rs = api_sql_query($sql,__FILE__,__LINE__);
		$sql = 'UPDATE '.$table_group.' SET 
				sortby='.$group->sortby.$operateur.'1'.'
				WHERE group_id='.intval($_GET['id_group']);
		$rs = api_sql_query($sql,__FILE__,__LINE__);
	}
}

if(isset($_REQUEST['delete']))
 {
   $group_id = $_REQUEST['group_delete'];
   $surveyid = $_REQUEST['surveyid'];
   $curr_dbname = $_REQUEST['curr_dbname'];
   SurveyManager::delete_group($group_id,$curr_dbname);
   header("Location:create_new_group.php?surveyid=$surveyid&cidReq=$cidReq&curr_dbname=$curr_dbname");
   exit;		
 }
if ($_POST['action'] == 'new_group')
{
	$surveyid = $_POST['surveyid'];
	$groupname = $_POST['groupname'];
	$curr_dbname = $_REQUEST['curr_dbname'];
	$surveyintroduction = $_POST['content'];
	 if(isset($_POST['back']))
	   { 
		 header("location:select_question_group.php?surveyid=$surveyid&cidReq=$cidReq&curr_dbname=$curr_dbname");
		 exit;
	   } 
	
	$groupname=trim($groupname);
	if(empty ($groupname))
	{
			$error_message = get_lang('PleaseEnterGroupName');      
	}	
	else
	{
		$groupid = SurveyManager::create_group($surveyid,$groupname,$surveyintroduction,$table_group);
		$cidReq = $_GET['cidReq'];

		if(isset($_POST['next']) && $groupid)
		{ 
		
		 header("location:addanother.php?surveyid=$surveyid&newgroupid=$groupid&cidReq=$cidReq&curr_dbname=$curr_dbname");
		 exit;
		
		}elseif(isset($_POST['saveandexit']) && $groupid){
		
		 header("location:survey_list.php?cidReq=$cidReq");
		 exit;
		
		}
		else
		{ 
		
		  $error_message = "This Group Already Exists !";
		
		} 
	}	
}
Display::display_header($tool_name1);
?>
<tr>
<td><?php api_display_tool_title($header1); ?></td>
</tr>
<?
if( isset($error_message) )
{
	Display::display_error_message($error_message);	
}

$table_group =  Database :: get_course_table('survey_group');
		
		$sql = "SELECT * FROM $curr_dbname.survey_group Where survey_id='$surveyid' ORDER BY sortby ASC";
		
		$parameters = array ();
		$parameters['curr_dbname']=$curr_dbname;
        $parameters['surveyid']=$surveyid;
		$parameters['groupid']=$groupid;
		$parameters['cidReq']=$cidReq;		
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$countGroups = mysql_num_rows($res);
	if ($countGroups > 0)
	{	
		$i=0;
		//$surveys = array ();		
	while ($obj = mysql_fetch_object($res))
		{
			$gid=$obj->group_id;
			$gname = $obj->groupname;
			$survey = array ();
			$survey[] = $obj->groupname;
			$gid=$obj->group_id;
				$idd=surveymanager::pick_author($surveyid);
				$author=surveymanager::get_survey_author($idd);
				$surveyname=surveymanager::pick_surveyname($surveyid);
				$survey[] = $surveyname;
				$survey[] = $author;
				$directions = '<table cellpadding="0" cellspacing="0" border="0" style="border:0px"><tr>';
				if($i < $countGroups-1){
					$directions .= '<td><a href="'.$_SERVER['PHP_SELF'].'?surveyid='.$surveyid.'&cidReq='.$cidReq.'&curr_dbname='.$curr_dbname.'&direction=down&id_group='.$gid.'"><img src="../img/down.gif" border="0" title="lang_move_down"></a></td>';
				}
				else {
					$directions .= '<td width="20"></td>';
				}
				if($i > 0){
					$directions .= '<td><a href="'.$_SERVER['PHP_SELF'].'?surveyid='.$surveyid.'&cidReq='.$cidReq.'&curr_dbname='.$curr_dbname.'&direction=up&id_group='.$gid.'"><img src="../img/up.gif" border="0" title="lang_move_up"></a></td>';
				}
				else {
					$directions .= '<td></td>';
				}
				$directions .= '</tr></table>';
				$survey[] = $directions;
				$survey[] =  '<a href="group_edit.php?groupid='.$obj->group_id.'&cidReq='.$cidReq.'&curr_dbname='.$curr_dbname.'&surveyid='.$surveyid.'"><img src="../img/edit.gif" border="0" align="absmiddle" alt="'.get_lang('Edit').'"/></a>'
				.'<a href="create_new_group.php?cidReq='.$cidReq.'&curr_dbname='.$curr_dbname.'&delete=1&group_delete='.$gid.'&surveyid='.$surveyid.'"  onclick="javascript:if(!confirm('."'".addslashes(htmlentities(get_lang("ConfirmYourChoice")))."'".')) return false;"><img src="../img/delete.gif" border="0" align="absmiddle" alt="'.get_lang('Delete').'"/></a>'
				;
               $surveys[] = $survey;
               $i++;
		
		}
		//$table_header[] = array (' ', false);
		//$table_header[] = array (get_lang('SNo'), true);
		$table_header[] = array (get_lang('QuesGroup'), true);
		$table_header[] = array (get_lang('SurveyName1'), true);
		$table_header[] = array (get_lang('author'), true);
		$table_header[] = array (get_lang('OrderBy'), true);
		$table_header[] = array (' ', false);
		Display :: display_sortable_table($table_header, $surveys, array ('column'=>get_lang('OrderBy')), array (), $parameters);
	}
	else
	{
		echo get_lang('NoSearchResults');
	}
	echo '<a href="select_question_group.php?surveyid='.$surveyid.'&cidReq='.$cidReq.'&curr_dbname='.$curr_dbname.'">'.get_lang('BackToQuestions').'</a><br><br>';
api_display_tool_title($tool_name);
?>
<script src=tbl_change.js type="text/javascript" language="javascript"></script>
<form name="new_calendar_item" method="post" action="<?php echo $_SERVER['PHP_SELF'];?>?cidReq=<?php echo $cidReq; ?>">
<input type="hidden" name="action" value="new_group">
<input type="hidden" name="surveyid" value="<?php echo $surveyid; ?>">
<input type="hidden" name="groupid" value="<?php echo $groupid; ?>">
<input type="hidden" name="curr_dbname" value="<?php echo $curr_dbname; ?>">
<!--<input type="hidden" name="cidReq" value="<?php echo $_REQUEST['cidReq']; ?>">-->
<table>
<tr>
  <td><?php echo get_lang('groupname'); ?></td>
  <td><input type="text" name="groupname" size="40" maxlength="39" value="<?php echo $code ?>"></td>
</tr>
	   <tr><td valign="top"><?php echo get_lang('GroupIntroduction'); ?>&nbsp;</td>
        <td>
   <?php
         api_disp_html_area('content',$content,'300px');
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
/*
==============================================================================
		FOOTER 
==============================================================================
*/
Display :: display_footer();
?>