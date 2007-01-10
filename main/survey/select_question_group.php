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
* 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup, refactoring and rewriting large parts (if not all) of the code
* 	@version $Id: select_question_group.php 10663 2007-01-10 22:49:09Z pcool $
*/

// name of the language file that needs to be included 
$language_file = 'survey';

// including the global dokeos file
require_once ('../inc/global.inc.php');

// including additional libraries
/** @todo check if these are all needed */
/** @todo check if the starting / is needed. api_get_path probably ends with an / */
require_once (api_get_path(LIBRARY_PATH).'/fileManage.lib.php');
require_once (api_get_path(CONFIGURATION_PATH) ."/add_course.conf.php");
require_once (api_get_path(LIBRARY_PATH)."/add_course.lib.inc.php");
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");

/** @todo replace this with the correct code */
/*
$status = surveymanager::get_status();
api_protect_course_script();
if($status==5)
{
	api_protect_admin_script();
}
*/
/** @todo this has to be moved to a more appropriate place (after the display_header of the code)*/
if (!api_is_allowed_to_edit())
{
	Display :: display_header();
	Display :: display_error_message(get_lang('NotAllowedHere'));
	Display :: display_footer();
	exit;
}

// Database table definitions
/** @todo use database constants for the survey tables */
$table_survey 		= Database :: get_course_table('survey');
$table_group 		= Database :: get_course_table('survey_group');
$table_question 	= Database :: get_course_table('questions');
$table_course 		= Database::get_main_table(TABLE_MAIN_COURSE);
$table_survey_group = Database :: get_course_table('survey_group');

// breadcrumbs
$interbreadcrumb[] = array ("url" => "survey_list.php", "name" => get_lang('Survey'));

// Displaying the header
Display::display_header(get_lang('AddQuestionGroup'));

// Displaying the tool title
// api_display_tool_title(get_lang('AddQuestionGroup'));

// The survey we are working on
$survey_data = get_survey($_GET['survey_id']);
api_display_tool_title(get_lang('SurveyTitle').': '.$survey_data['survey_title']);

// Footer
Display :: display_footer();









/**
 * This function retrieves all the survey information 
 * This is a DUPLICATE of the one in create_new_survey.php
 *
 * @param integer $survey_id the id of the survey
 * @return array
 * 
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version januari 2007
 * 
 * @todo move this function to surveymanager.inc.lib.php
 */
function get_survey($survey_id)
{
	$tbl_survey = Database :: get_course_table('survey');
	
	$sql = "SELECT * FROM $tbl_survey WHERE survey_id='".mysql_real_escape_string($survey_id)."'";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	$return = mysql_fetch_assoc($result);
	
	// we do this (temporarily) to have the array match the quickform elements immediately
	// idealiter the fields in the db match the quickform fields
	$return['survey_code'] 			= $return['code'];
	$return['survey_title'] 		= $return['title'];
	$return['survey_subtitle'] 		= $return['subtitle'];
	$return['survey_language'] 		= $return['lang'];
	$return['start_date'] 			= $return['avail_from'];
	$return['end_date'] 			= $return['avail_till'];
	$return['survey_share'] 		= $return['is_shared'];
	$return['survey_introduction'] 	= $return['intro'];
	$return['survey_thanks'] 		= $return['surveythanks'];
	return $return;
}













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



if ($_POST['action'] == 'selectquestion_group')
{
	$surveyid = $_POST['newsurveyid'];	
	$questiongroup = $_POST['question_group'];
	if (isset($questiongroup))
	{	 
		 $exiztinggroup = $_POST['exiztinggroup'];
		 header("Location:existing_surveys_new.php?surveyid=$surveyid");	
		 exit;	
	}
}

//from question_added
if (isset($_POST['back']))
{
	    $groupid = $_POST['groupid'];
	    $surveyid = $_POST['surveyid'];
		$page = $_REQUEST['page'];
		header("Location:create_new_survey.php?surveyid=$surveyid&page=$page");
		//header("Location:select_question_type.php?groupid=$groupid&surveyid=$surveyid");
	    exit;
}

if (isset($_POST['addanother']))
{
	    $groupid = $_POST['groupid'];
	    $surveyid = $_POST['surveyid'];
		header("Location:addanother.php?surveyid=$surveyid");
		//header("Location:select_question_type.php?groupid=$groupid&surveyid=$surveyid");
	    exit;
}
if (isset($_POST['addanotherg']))
{
	    //$groupid = $_POST['groupid'];
	    $surveyid = $_POST['surveyid'];
		header("Location:create_new_group.php?surveyid=$surveyid");
	    exit;
}
if(isset($_REQUEST['delete']))
{
	if(isset($_REQUEST['qid']))
	{
		$endloop=count($_REQUEST['qid']);
		$qid1=$_REQUEST['qid'];
		for($i=0;$i<$endloop;$i++)
		{
			$qid2=$qid1[$i];
			$query="DELETE FROM $table_question WHERE qid='$qid2'";
			api_sql_query($query);
			header("Location:select_question_group.php?surveyid=$surveyid");
			exit;
		}
	}
}

if (isset($_POST['finish']))
{
	    header("Location:survey_list.php");
	    exit;
}	

if(isset($action1))
{
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
		$sql_update2="UPDATE $table_question SET sortby='".$sort."' WHERE qid='".$pre_qid."'";
		mysql_query($sql_update2);
		$sql_update1="UPDATE $table_question SET sortby='".$pre_sort."' WHERE qid='".$qid."'";
		mysql_query($sql_update1);
	}
	else
	{
	$sql_update1="UPDATE $table_question SET sortby='".$sort."' WHERE qid='".$post_qid."'";
	mysql_query($sql_update1);
	$sql_update2="UPDATE $table_question SET sortby='".$post_sort."' WHERE qid='".$qid."'";
	mysql_query($sql_update2);
	}
 	//surveymanager::move_question($direction,$qid,$pre_sort,$sort,$post_sort,$curr_dbname);
}

Display::display_header(get_lang('AddQuestionGroup'));
api_display_tool_title("Survey Name : ".$sname);
api_display_tool_title(get_lang('AddQuestionGroup'));
if($flag==1)
{
	?>
	<div align="center"><strong><font color="#FF0000"><?php echo get_lang('AlreadyImported');?></font></strong></div>
	<?php
}
if(isset($messege) && $messege )
{
	?>
	<div align="center"><strong><font color="#FF0000"><?php echo get_lang('AlreadyImported');?></font></strong></div>
	<?php
}
?>

<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
<input type="hidden" name="action" value="selectquestion_group">
<input type="hidden" name="newsurveyid" value="<?php echo $surveyid;?>">
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
    $sql="SELECT * FROM $table_survey_group WHERE survey_id='$surveyid' ORDER BY sortby";	
	$res = api_sql_query($sql,__FILE__,__LINE__);
	$num=mysql_num_rows($res);	
	$parameters['surveyid']=$surveyid;
	//$table_header[] = array (' ', false);
	//$table_header[] = array (get_lang('SNo'), true);
	
	$table_header[] = array (get_lang('Questions'), true);
    $table_header[] = array (get_lang('ChangeOrder'), true);	
	$table_header[] = array (get_lang('Type'), true);	
	$table_header[] = array (get_lang('Group'), true);
	$table_header[]=array('',true);
	$courses = array ();
	$question_number = 0;
	if($num)
	{
		for($i=0;$i<$num;$i++)
		{
			$groupid=@mysql_result($res,$i,'group_id');
			$gname=@mysql_result($res,$i,'groupname');
			$sql="SELECT * FROM $table_question WHERE gid='$groupid' AND survey_id = '$surveyid' order by `sortby` asc";
			
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
				$course[] = '<a href='.$_SERVER['PHP_SELF'].'?gid='.$groupid.'&pre_sort='.$pre_sort.'&sortby='.$sort.'&post_sort='.$post_sort.'&surveyid='.$surveyid.'&pre_qid='.$pre_qid.'&qid='.$qid.'&post_qid='.$post_qid.'&page_nr='.$page_nr.'&per_page='.$per_page.'&column='.$column.'&action1=moveitem&direction=down></a>&nbsp;&nbsp;&nbsp;'.'<a href='.$_SERVER['PHP_SELF'].'?gid='.$groupid.'&pre_sort='.$pre_sort.'&sortby='.$sort.'&post_sort='.$post_sort.'&surveyid='.$surveyid.'&pre_qid='.$pre_qid.'&qid='.$qid.'&post_qid='.$post_qid.'&page_nr='.$page_nr.'&per_page='.$per_page.'&column='.$column.'&action1=moveitem&direction=up></a>';				
				}
				elseif($k==0){
				$course[] = '<a href='.$_SERVER['PHP_SELF'].'?gid='.$groupid.'&pre_sort='.$pre_sort.'&sortby='.$sort.'&post_sort='.$post_sort.'&surveyid='.$surveyid.'&pre_qid='.$pre_qid.'&qid='.$qid.'&post_qid='.$post_qid.'&page_nr='.$page_nr.'&per_page='.$per_page.'&column='.$column.'&action1=moveitem&direction=down><img src="../img/down.gif" border="0" title="lang_move_down"></a>&nbsp;&nbsp;&nbsp;'.'<a href='.$_SERVER['PHP_SELF'].'?gid='.$groupid.'&pre_sort='.$pre_sort.'&sortby='.$sort.'&post_sort='.$post_sort.'&surveyid='.$surveyid.'&pre_qid='.$pre_qid.'&qid='.$qid.'&post_qid='.$post_qid.'&page_nr='.$page_nr.'&per_page='.$per_page.'&column='.$column.'&action1=moveitem&direction=up></a>';
				}elseif($k==$num1-1){
				$course[] = '<a href='.$_SERVER['PHP_SELF'].'?gid='.$groupid.'&pre_sort='.$pre_sort.'&sortby='.$sort.'&post_sort='.$post_sort.'&surveyid='.$surveyid.'&pre_qid='.$pre_qid.'&qid='.$qid.'&post_qid='.$post_qid.'&page_nr='.$page_nr.'&per_page='.$per_page.'&column='.$column.'&action1=moveitem&direction=down></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.'<a href='.$_SERVER['PHP_SELF'].'?gid='.$groupid.'&pre_sort='.$pre_sort.'&sortby='.$sort.'&post_sort='.$post_sort.'&surveyid='.$surveyid.'&pre_qid='.$pre_qid.'&qid='.$qid.'&post_qid='.$post_qid.'&page_nr='.$page_nr.'&per_page='.$per_page.'&column='.$column.'&action1=moveitem&direction=up><img src="../img/up.gif" border="0" title="lang_move_up"></a>';
				}
				else{
				$course[] = '<a href='.$_SERVER['PHP_SELF'].'?gid='.$groupid.'&pre_sort='.$pre_sort.'&sortby='.$sort.'&post_sort='.$post_sort.'&surveyid='.$surveyid.'&pre_qid='.$pre_qid.'&qid='.$qid.'&post_qid='.$post_qid.'&page_nr='.$page_nr.'&per_page='.$per_page.'&column='.$column.'&action1=moveitem&direction=down><img src="../img/down.gif" border="0" title="lang_move_down"></a>&nbsp;&nbsp;&nbsp;'.'<a href='.$_SERVER['PHP_SELF'].'?gid='.$groupid.'&pre_sort='.$pre_sort.'&sortby='.$sort.'&post_sort='.$post_sort.'&surveyid='.$surveyid.'&pre_qid='.$pre_qid.'&qid='.$qid.'&post_qid='.$post_qid.'&page_nr='.$page_nr.'&per_page='.$per_page.'&column='.$column.'&action1=moveitem&direction=up><img src="../img/up.gif" border="0" title="lang_move_up"></a>';
				}				
				$course[] = mysql_result($res1,$k,'qtype');//$obj->qtype;
				$course[] = @mysql_result($res,$i,'groupname');	
				$course[]='<a href="question_edit.php?qid='.$qid.'&qtype='.$q_type.'&groupid='.$groupid.'&surveyid='.$surveyid.'"><img src="../img/edit.gif" border="0" align="absmiddle" alt="'.get_lang('Edit').'"/></a>'.'<a href="select_question_group.php?delete=1&qid[]='.$qid.'&qtype='.$q_type.'&groupid='.$groupid.'&surveyid='.$surveyid.'" onclick="javascript:if(!confirm('."'".addslashes(htmlentities(get_lang('ConfirmYourChoice')))."'".')) return false;"><img src="../img/delete.gif" border="0" align="absmiddle" alt="'.get_lang('Delete').'"/></a>';
				
				$courses[] = $course;
				$x++;
			}
		}
	}
?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
<input type="hidden" name="groupid" value="<?php echo $groupid; ?>">
<input type="hidden" name="surveyid" value="<?php echo $surveyid; ?>">
<input type="hidden" name="page" value="<?php echo $page; ?>">
<?php
if(!empty($courses))
{

	/** @todo remove $curr_dbname from the parameters. This is not used. */
	SurveyManager :: display_sortable_table($groupid,$surveyid,$curr_dbname,$table_header, $courses, array (), array (), $parameters);

?>	
	<!--<input type="submit" name="delete" value="<?php echo get_lang('Delete');?>">-->
	<!--<input type=button value="Back" onClick="history.go(-1)">-->
	<input type="submit" name="addanother" value="<?php echo get_lang('AddAnotherQuestion');?>">
	<input type="submit" name="addanotherg" value="<?php echo get_lang('AddNewGroup');?>">
	<input type="submit" name="finish" value="<?php echo get_lang('FinishSurvey');?>">
<?php
}
else
{
?>
    <input type="submit" name="back" value="<?php echo get_lang('Back');?>">
	<input type="submit" name="addanother" value="<?php echo get_lang('AddAnotherQuestion');?>">
	<input type="submit" name="addanotherg" value="<?php echo get_lang('AddNewGroup');?>">
<?php
}	
?>
</form>
<?php
Display :: display_footer();
?>