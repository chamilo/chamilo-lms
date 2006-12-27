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
* 	@version $Id: select_question_group.php 10559 2006-12-27 10:52:50Z pcool $
*/


/*
==============================================================================
		INIT SECTION
==============================================================================
*/
// name of the language file that needs to be included 
$language_file = 'survey';

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
/*
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
*/
$table_survey = Database :: get_course_table('survey');
$table_group =  Database :: get_course_table('survey_group');
$table_question = Database :: get_course_table('questions');
$table_course = Database::get_main_table(TABLE_MAIN_COURSE);
$cidReq = $_GET['cidReq'];
$db_name = $_REQUEST['db_name'];
$curr_dbname = $_REQUEST['curr_dbname'];
//$table_survey = Database :: get_course_table('survey');
$tool_name1 = get_lang('AddQuestionGroup');
$tool_name = get_lang('AddQuestionGroup');
$interbreadcrumb[] = array ("url" => "survey_list.php", "name" => get_lang('Survey'));
/*if($page = $_REQUEST['page'])
{
 $interbreadcrumb[] = array ("url" => "create_new_survey.php?surveyid=$surveyid&cidReq=$cidReq&curr_dbname=$curr_dbname&page=$page", "name" => get_lang('CreateNewSurvey'));
}
else
{
 $interbreadcrumb[] = array ("url" => "create_new_survey.php?surveyid=$surveyid&cidReq=$cidReq&curr_dbname=$curr_dbname&page=$page", "name" => get_lang('CreateFromExistingSurvey'));
}*/
$coursePathWeb = api_get_path(WEB_COURSE_PATH);
$coursePathSys = api_get_path(SYS_COURSE_PATH);
$default_group = '0';
$new_group = '1';
$existing_group = '2';
$surveyid = $_REQUEST['surveyid'];
$sname = surveymanager::pick_surveyname($surveyid);
$groupid = $_REQUEST['groupid'];
$newgroupid = $_REQUEST['newgroupid'];
$messege = $_REQUEST['messege'];
$flag = $_REQUEST['flag'];
$action1 = $_REQUEST['action1'];
$sort = $_REQUEST['sortby'];

if(!isset($page_nr)||!isset($column)||!isset($per_page))
{
$page_nr =1;
$column =0;
$per_page = 10;
}
else
{
$page_nr = $_REQUEST['page_nr'];
$column = $_REQUEST['column'];
$per_page = $_REQUEST['per_page'];
}
/*
$sql="SELECT * FROM $curr_dbname.questions WHERE gid='$groupid' AND survey_id = '$surveyid'";
$res=api_sql_query($sql,__FILE__,__LINE__);
$obj=mysql_fetch_object($res);
$number=mysql_num_rows($res);
for($i=1;$i<=$number;$i++)
	{		
		$up="up";
		$down="down";		
		if(isset($_POST[$up])||isset($_POST[$down]))
		{			
			$flag=0;
			if(isset($_POST[$up]))
			{
				$tempmutlichkboxtext=$_POST['boxtext'];
				if($tempradiodefault==$i)
					$tempradiodefault--;
				elseif($tempradiodefault==$i-1)
					$tempradiodefault++;
				$tempchkboxpoint=$_POST['chkboxpoint'];
				if($tempradiotrue==$i)
					$tempradiotrue--;
				elseif($tempradiotrue==$i-1)
					$tempradiotrue++;										
				$tempm=	$tempmutlichkboxtext[$i-2];
				$tempchkboxp=$tempchkboxpoint[$i-2];			
				$tempmutlichkboxtext[$i-2]=$tempmutlichkboxtext[$i-1];
				$tempchkboxpoint[$i-2]=$tempchkboxpoint[$i-1];
				$tempmutlichkboxtext[$i-1]=$tempm;
				$tempchkboxpoint[$i-1]=$tempchkboxp;
				$_POST['mutlichkboxtext']=$tempmutlichkboxtext;
				$_POST['chkboxpoint']=$tempchkboxpoint;
			}
			if(isset($_POST[$down]))
			{
				$tempmutlichkboxtext=$_POST['mutlichkboxtext'];
				if($tempradiodefault==$i)
					$tempradiodefault++;
				elseif($tempradiodefault==$i+1)
					$tempradiodefault--;
				$tempchkboxpoint=$_POST['chkboxpoint'];
				if($tempradiotrue==$i)
					$tempradiotrue++;
				elseif($tempradiotrue==$i+1)
					$tempradiotrue--;
				$tempm=	$tempmutlichkboxtext[$i];
				$tempchkboxp=$tempchkboxpoint[$i];
				$tempmutlichkboxtext[$i]=$tempmutlichkboxtext[$i-1];
				$tempchkboxpoint[$i]=$tempchkboxpoint[$i-1];
				$tempmutlichkboxtext[$i-1]=$tempm;
				$tempchkboxpoint[$i-1]=$tempchkboxp;
				$_POST['mutlichkboxtext']=$tempmutlichkboxtext;
				$_POST['chkboxpoint']=$tempchkboxpoint;
			}
			//echo ",while checking up/down end=".$end;
			$jd=0;
			break;		
		}
	}
*/
if ($_POST['action'] == 'selectquestion_group')
{
	$surveyid = $_POST['newsurveyid'];	
	 $questiongroup = $_POST['question_group'];
if (isset($questiongroup))
	{	 
	     $cidReq=$_REQUEST['cidReq'];
		 $exiztinggroup = $_POST['exiztinggroup'];
		 $curr_dbname = $_REQUEST['curr_dbname'];
		 header("Location:existing_surveys_new.php?cidReq=$cidReq&surveyid=$surveyid&curr_dbname=$curr_dbname");	
		 exit;	
	}
}
//from question_added
if (isset($_POST['back']))
{
	    $groupid = $_POST['groupid'];
	    $surveyid = $_POST['surveyid'];
		$cidReq=$_REQUEST['cidReq'];
		$curr_dbname = $_REQUEST['curr_dbname'];
		$page = $_REQUEST['page'];
		header("Location:create_new_survey.php?surveyid=$surveyid&cidReq=$cidReq&curr_dbname=$curr_dbname&page=$page");
		//header("Location:select_question_type.php?groupid=$groupid&surveyid=$surveyid&cidReq=$cidReq");
	    exit;
}
if (isset($_POST['addanother']))
{
	    $groupid = $_POST['groupid'];
	    $surveyid = $_POST['surveyid'];
		$cidReq=$_REQUEST['cidReq'];
		$curr_dbname = $_REQUEST['curr_dbname'];
		header("Location:addanother.php?surveyid=$surveyid&cidReq=$cidReq&curr_dbname=$curr_dbname");
		//header("Location:select_question_type.php?groupid=$groupid&surveyid=$surveyid&cidReq=$cidReq");
	    exit;
}
if (isset($_POST['addanotherg']))
{
	    //$groupid = $_POST['groupid'];
	    $surveyid = $_POST['surveyid'];
		$cidReq=$_REQUEST['cidReq'];
		$curr_dbname = $_REQUEST['curr_dbname'];	
		header("Location:create_new_group.php?surveyid=$surveyid&cidReq=$cidReq&curr_dbname=$curr_dbname");
	    exit;
}
if(isset($_REQUEST['delete']))
{
	$curr_dbname = $_REQUEST['curr_dbname'];
	if(isset($_REQUEST['qid']))
	{
		$endloop=count($_REQUEST['qid']);
		$qid1=$_REQUEST['qid'];
		for($i=0;$i<$endloop;$i++)
		{
			$qid2=$qid1[$i];
			$query="DELETE FROM $curr_dbname.questions WHERE qid='$qid2'";
			api_sql_query($query);
			header("Location:select_question_group.php?surveyid=$surveyid&cidReq=$cidReq&curr_dbname=$curr_dbname");
			exit;
		}
	}
}
if (isset($_POST['finish']))
{
		$cidReq=$_REQUEST['cidReq'];
	    header("Location:survey_list.php?cidReq=$cidReq");
	    exit;
}	

if(isset($action1))
{
 $curr_dbname = $_REQUEST['curr_dbname'];
 $groupid = $_REQUEST['gid'];
 $surveyid = $_REQUEST['surveyid'];
 $qid = $_GET['qid'];
 $pre_qid = $_GET['pre_qid'];
 $post_qid = $_GET['post_qid'];
 $direction = $_GET['direction'];
 $pre_sort = $_GET['pre_sort'];
 $sort = $_GET['sortby'];
 $post_sort = $_GET['post_sort'];
 if($direction=="up")
 {
	$sql_update2="UPDATE $curr_dbname.questions SET sortby='".$sort."' WHERE qid='".$pre_qid."'";
	mysql_query($sql_update2);
	$sql_update1="UPDATE $curr_dbname.questions SET sortby='".$pre_sort."' WHERE qid='".$qid."'";
	mysql_query($sql_update1);
		 
 }
else
{
$sql_update1="UPDATE $curr_dbname.questions SET sortby='".$sort."' WHERE qid='".$post_qid."'";
mysql_query($sql_update1);
$sql_update2="UPDATE $curr_dbname.questions SET sortby='".$post_sort."' WHERE qid='".$qid."'";
mysql_query($sql_update2);
}

 //surveymanager::move_question($direction,$qid,$pre_sort,$sort,$post_sort,$curr_dbname);
}
Display::display_header($tool_name1);
api_display_tool_title("Survey Name : ".$sname);
api_display_tool_title($tool_name);
if($flag==1)
{
?>
<div align="center"><strong><font color="#FF0000"><?echo get_lang('AlreadyImported');?></font></strong></div>
<?
}
if(isset($messege) && $messege )
{
?>
<div align="center"><strong><font color="#FF0000">Already Imported !</font></strong></div>
<?
}
?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>?cidReq=<?php echo $cidReq; ?>">
<input type="hidden" name="action" value="selectquestion_group">
<input type="hidden" name="newsurveyid" value="<?php echo $surveyid;?>">
<input type="hidden" name="curr_dbname" value="<?php echo $curr_dbname; ?>">
<input type="hidden" name="cidReq" value="<?php echo $_REQUEST['cidReq']; ?>">
<!--<input type="hidden" name="qid" value="<?php echo $_REQUEST['qid']; ?>">
<input type="hidden" name="direction" value="<?php echo $_REQUEST['direction']; ?>">-->
<table>
<tr>
<td valign="top"></td>
<td>
</td></tr>
<tr>
<td></td>
<td></td>
</tr>
<tr>
<td></td>  
</tr>
<tr>
  <td>&nbsp;</td>
  <td><input type="submit" name="question_group" value="<?php echo get_lang('ImportQuestionsFromExistingGroup'); ?>"></td>
</tr>
</table>
</form>
<?php
/*$query="SELECT * FROM $curr_dbname.survey WHERE survey_id='$surveyid'";
$result=api_sql_query($query);*/
    $sql="SELECT * FROM $curr_dbname.survey_group WHERE survey_id='$surveyid' ORDER BY sortby";	
	$res = api_sql_query($sql,__FILE__,__LINE__);
	$num=mysql_num_rows($res);	
	$parameters['curr_dbname']=$curr_dbname;
	$parameters['surveyid']=$surveyid;
	$parameters['cidReq']=$cidReq;
	//$table_header[] = array (' ', false);
	//$table_header[] = array (get_lang('SNo'), true);
	
	$table_header[] = array (get_lang('Questions'), true);
    $table_header[] = array (get_lang('ChangeOrder'), true);	
	$table_header[] = array (get_lang('Type'), true);	
	$table_header[] = array (get_lang('Group'), true);
	$table_header[]=array('',true);
	$courses = array ();
	$question_number = 0;
	if($num){
		
		for($i=0;$i<$num;$i++)
		{
			$groupid=@mysql_result($res,$i,'group_id');
			$gname=@mysql_result($res,$i,'groupname');
			$sql="SELECT * FROM $curr_dbname.questions WHERE gid='$groupid' AND survey_id = '$surveyid' order by `sortby` asc";
			
			$res1=api_sql_query($sql,__FILE__,__LINE__);
			$num1=mysql_num_rows($res1);
			$x=1;
			for($k=0;$k<$num1;$k++)
			//while ($obj = mysql_fetch_object($res1))
			{	
				$question_number++;
				$qid=mysql_result($res1,$k,'qid');//$obj->qid;
				$q_type=mysql_result($res1,$k,'qtype');//$obj->qid;
				$pre_qid=mysql_result($res1,$k-1,'qid');
				$post_qid=mysql_result($res1,$k+1,'qid');
				$question=stripslashes(mysql_result($res1,$k,'caption'));//$obj->caption;
				
				$question = eregi_replace('^<p[^>]*>(.*)</p>','\\1', $question);
				$question = eregi_replace('(<[^ ]*) (style=."."[^>]*)(>)','\\1\\3', $question);
				$question = eregi_replace('(<[^ ]*) (style=""[^>]*)(>)','\\1\\3', $question);
				$question = eregi_replace('(<[^ ]*)( style=."[^"]*")([^>]*)(>)','\\1\\2\\4', $question);
				
				$sort=mysql_result($res1,$k,'sortby');//$obj->sortby;				
				$pre_sort=$k==0?mysql_result($res1,$k,'sortby'):mysql_result($res1,$k-1,'sortby');//$obj->sortby;
				$post_sort=$k==$num1?mysql_result($res1,$k,'sortby'):mysql_result($res1,$k+1,'sortby');//$obj->sortby;				
				$course = array ();
				
				$course[] = $question_number.' - '.$question;
				if($num1==1)
				{
				$course[] = '<a href='.$_SERVER['PHP_SELF'].'?gid='.$groupid.'&pre_sort='.$pre_sort.'&sortby='.$sort.'&post_sort='.$post_sort.'&surveyid='.$surveyid.'&pre_qid='.$pre_qid.'&qid='.$qid.'&post_qid='.$post_qid.'&curr_dbname='.$curr_dbname.'&cidReq='.$cidReq.'&page_nr='.$page_nr.'&per_page='.$per_page.'&column='.$column.'&action1=moveitem&direction=down></a>&nbsp;&nbsp;&nbsp;'.'<a href='.$_SERVER['PHP_SELF'].'?gid='.$groupid.'&pre_sort='.$pre_sort.'&sortby='.$sort.'&post_sort='.$post_sort.'&surveyid='.$surveyid.'&pre_qid='.$pre_qid.'&qid='.$qid.'&post_qid='.$post_qid.'&curr_dbname='.$curr_dbname.'&cidReq='.$cidReq.'&page_nr='.$page_nr.'&per_page='.$per_page.'&column='.$column.'&action1=moveitem&direction=up></a>';				
				}
				elseif($k==0){
				$course[] = '<a href='.$_SERVER['PHP_SELF'].'?gid='.$groupid.'&pre_sort='.$pre_sort.'&sortby='.$sort.'&post_sort='.$post_sort.'&surveyid='.$surveyid.'&pre_qid='.$pre_qid.'&qid='.$qid.'&post_qid='.$post_qid.'&curr_dbname='.$curr_dbname.'&cidReq='.$cidReq.'&page_nr='.$page_nr.'&per_page='.$per_page.'&column='.$column.'&action1=moveitem&direction=down><img src="../img/down.gif" border="0" title="lang_move_down"></a>&nbsp;&nbsp;&nbsp;'.'<a href='.$_SERVER['PHP_SELF'].'?gid='.$groupid.'&pre_sort='.$pre_sort.'&sortby='.$sort.'&post_sort='.$post_sort.'&surveyid='.$surveyid.'&pre_qid='.$pre_qid.'&qid='.$qid.'&post_qid='.$post_qid.'&curr_dbname='.$curr_dbname.'&cidReq='.$cidReq.'&page_nr='.$page_nr.'&per_page='.$per_page.'&column='.$column.'&action1=moveitem&direction=up></a>';
				}elseif($k==$num1-1){
				$course[] = '<a href='.$_SERVER['PHP_SELF'].'?gid='.$groupid.'&pre_sort='.$pre_sort.'&sortby='.$sort.'&post_sort='.$post_sort.'&surveyid='.$surveyid.'&pre_qid='.$pre_qid.'&qid='.$qid.'&post_qid='.$post_qid.'&curr_dbname='.$curr_dbname.'&cidReq='.$cidReq.'&page_nr='.$page_nr.'&per_page='.$per_page.'&column='.$column.'&action1=moveitem&direction=down></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.'<a href='.$_SERVER['PHP_SELF'].'?gid='.$groupid.'&pre_sort='.$pre_sort.'&sortby='.$sort.'&post_sort='.$post_sort.'&surveyid='.$surveyid.'&pre_qid='.$pre_qid.'&qid='.$qid.'&post_qid='.$post_qid.'&curr_dbname='.$curr_dbname.'&cidReq='.$cidReq.'&page_nr='.$page_nr.'&per_page='.$per_page.'&column='.$column.'&action1=moveitem&direction=up><img src="../img/up.gif" border="0" title="lang_move_up"></a>';
				}
				else{
				$course[] = '<a href='.$_SERVER['PHP_SELF'].'?gid='.$groupid.'&pre_sort='.$pre_sort.'&sortby='.$sort.'&post_sort='.$post_sort.'&surveyid='.$surveyid.'&pre_qid='.$pre_qid.'&qid='.$qid.'&post_qid='.$post_qid.'&curr_dbname='.$curr_dbname.'&cidReq='.$cidReq.'&page_nr='.$page_nr.'&per_page='.$per_page.'&column='.$column.'&action1=moveitem&direction=down><img src="../img/down.gif" border="0" title="lang_move_down"></a>&nbsp;&nbsp;&nbsp;'.'<a href='.$_SERVER['PHP_SELF'].'?gid='.$groupid.'&pre_sort='.$pre_sort.'&sortby='.$sort.'&post_sort='.$post_sort.'&surveyid='.$surveyid.'&pre_qid='.$pre_qid.'&qid='.$qid.'&post_qid='.$post_qid.'&curr_dbname='.$curr_dbname.'&cidReq='.$cidReq.'&page_nr='.$page_nr.'&per_page='.$per_page.'&column='.$column.'&action1=moveitem&direction=up><img src="../img/up.gif" border="0" title="lang_move_up"></a>';
				}				
				$course[] = mysql_result($res1,$k,'qtype');//$obj->qtype;
				$course[] = @mysql_result($res,$i,'groupname');	
				$course[]='<a href="question_edit.php?qid='.$qid.'&cidReq='.$cidReq.'&curr_dbname='.$curr_dbname.'&qtype='.$q_type.'&groupid='.$groupid.'&surveyid='.$surveyid.'"><img src="../img/edit.gif" border="0" align="absmiddle" alt="'.get_lang('Edit').'"/></a>'.'<a href="select_question_group.php?delete=1&qid[]='.$qid.'&cidReq='.$cidReq.'&curr_dbname='.$curr_dbname.'&qtype='.$q_type.'&groupid='.$groupid.'&surveyid='.$surveyid.'" onclick="javascript:if(!confirm('."'".addslashes(htmlentities(get_lang('ConfirmYourChoice')))."'".')) return false;"><img src="../img/delete.gif" border="0" align="absmiddle" alt="'.get_lang('Delete').'"/></a>';
				
				$courses[] = $course;
				$x++;
			}
		}
	}
?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>?cidReq=<?php echo $cidReq; ?>">
<input type="hidden" name="groupid" value="<?php echo $groupid; ?>">
<input type="hidden" name="surveyid" value="<?php echo $surveyid; ?>">
<input type="hidden" name="curr_dbname" value="<?php echo $curr_dbname;?>">
<input type="hidden" name="page" value="<?php echo $page; ?>">
<?
if(!empty($courses))
{

				SurveyManager :: display_sortable_table($groupid,$surveyid,$curr_dbname,$table_header, $courses, array (), array (), $parameters);

?>	
	<!--<input type="submit" name="delete" value="<?php echo get_lang('Delete');?>">-->
	<!--<input type=button value="Back" onClick="history.go(-1)">-->
	<input type="submit" name="addanother" value="<?php echo get_lang('AddAnotherQuestion');?>">
	<input type="submit" name="addanotherg" value="<?php echo get_lang('AddNewGroup');?>">
	<input type="submit" name="finish" value="<?php echo get_lang('FinishSurvey');?>">
<?
}
else
{
?>
    <input type="submit" name="back" value="<?echo get_lang('Back');?>">
	<input type="submit" name="addanother" value="<?echo get_lang('AddAnotherQuestion');?>">
	<input type="submit" name="addanotherg" value="<?echo get_lang('AddNewGroup');?>">
<?
}	
?>
</form>
<!-- question_added-->
<?php
/*
==============================================================================
		FOOTER 
==============================================================================
*/
Display :: display_footer();
?>