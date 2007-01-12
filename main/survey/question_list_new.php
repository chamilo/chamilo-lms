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
* 	@version $Id: question_list_new.php 10705 2007-01-12 22:40:01Z pcool $
*/


/*
==============================================================================
		INIT SECTION
==============================================================================
*/
// name of the language file that needs to be included 
$language_file = 'survey';

require ('../inc/global.inc.php');
//api_protect_admin_script();
require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");
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

$table_group 			=  Database :: get_course_table(TABLE_SURVEY_GROUP);
$table_survey_question 	= Database :: get_course_table(TABLE_SURVEY_QUESTION);

$interbreadcrumb[] = array ("url" => "survey_list.php", "name" => get_lang('Survey'));
$tool_name = get_lang('SelectQuestion');
$Sname = get_lang('SurveyName');
$GName = get_lang('GroupName');
$Author = get_lang('Author');
$surveyid=$_REQUEST['surveyid'];
$groupid=$_REQUEST['groupid'];
$sid = $_REQUEST['sid'];

$gide=$_POST['course'];
$arraycount=0;
$arraycount=count($_REQUEST['course']);
if($_REQUEST['gid1'])
{
   $gid1=explode(",",$_REQUEST['gid1']);
}
else
{
  $gid1=$_REQUEST['course'];
} 
for($r=0;$r<count($gid1);$r++)
{
	  if($r<count($gid1)-1)
	  $str.=$gid1[$r].",";
	  else
      $str.=$gid1[$r]; 	       
}
$arraycount=0;
$arraycount=count($_REQUEST['course']);
if(isset($_POST['back1']))
  {
    header("location:existing_surveys_new.php?surveyid=$surveyid&groupid=$groupid");
	exit;
  }
if(isset($_POST['view']))
  {
     if($arraycount<=0)
      {
		$pls=1;
		header("location:group_list.php?surveyid=$surveyid&groupid=$groupid&pls=$pls&sid=$sid");
		exit;	
      }
   }
if(isset($_POST['back']))
{	
	$pls=0;
	$sid = $_REQUEST['sid'];
	$surveyid=$_REQUEST['surveyid'];
	header("location:group_list.php?surveyid=$surveyid&sid=$sid&pls=$pls");
	exit;
}
if(isset($_POST['import']))
{
 if($arraycount<=0)
  {
		$pls=1;
		header("location:group_list.php?surveyid=$surveyid&groupid=$groupid&pls=$pls&sid=$sid");
		exit;	
  }
 else
  {
	$gid_arr = $_REQUEST['course'];
	$gids = implode(",",$gid_arr);
	$surveyid = $_POST['surveyid'];
	$groupid = $_POST['groupid'];
	$flag = surveymanager::import_group($surveyid,$gids,$db_name,$curr_dbname);
	header("location:select_question_group.php?surveyid=$surveyid&flag=$flag");
	exit;
  }
}
if(isset($_POST['importquestion']))
{
  $gidnext=$_POST['gid1'];
  $surveyid = $_POST['surveyid'];
  $groupid = $_POST['groupid'];
  $selectcount=count($_POST['question']);	
  if($selectcount<=0)
   {
	  	 $error_message=get_lang('PleaseSelectAChoice');
   }
 else
  {
	$qid_arr = $_REQUEST['question'];
	$qids = implode(",",$qid_arr);
	$message_me=surveymanager::question_import($surveyid,$qids,$db_name,$curr_dbname);
	if(isset($message_me) && $message_me)
	  {
		 header("location:select_question_group.php?surveyid=$surveyid&messege=$message_me");
	     exit;
	  }
	  else
      {		
     	header("location:select_question_group.php?surveyid=$surveyid&messege=$message_me");
	    exit;
	  }
  }
}
Display :: display_header($tool_name);
if( isset($error_message) )
{
	Display::display_error_message($error_message);	
}
?>
<SCRIPT LANGUAGE="JavaScript">
function displayTemplate(url) {
	window.open(url, 'popup', 'width=600,height=800,scrollbars=yes,toolbar = no, status = no');
}
</script>

<table>
<tr>
<td><strong><?php echo get_lang('SurveyName'); ?></strong></td>
<td><?php echo $sname;?></td>
</tr>
<tr><td></td></tr>
<tr><td></td></tr>
</table>
<?php	
	echo get_lang('SelectQuestion');
?>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
		<input type="hidden" name="action" value="add_survey"/>
		<input type="hidden" name="surveyid" value="<?php echo $surveyid; ?>">
		<input type="hidden" name="sid" value="<?php echo $sid; ?>">
		<input type="hidden" name="groupid" value="<?php echo $groupid; ?>">
		<input type="hidden" name="gid1" value="<?php echo $str; ?>">	
		<input type="hidden" name="db_name" value="<?php echo $db_name; ?>">
		<input type="hidden" name="curr_dbname" value="<?php echo $curr_dbname;?>">
<?php 
		$questions = array ();	
		$endloop=count($gid1);
		$datacount=0;
		$parameters = array (); 
		$parameters['surveyid']=$surveyid;
		$parameters['sid']=$sid;
		$parameters['groupid']=$groupid;
		$parameters['gid1']=$str;
		for($i=0;$i<$endloop;$i++)
		{
			$gidi=$gid1[$i];
	        $sql = "SELECT * FROM $table_survey_question WHERE gid='$gidi' AND survey_id = '$sid'";
			$res = api_sql_query($sql,__FILE__,__LINE__);	
			while ($obj = mysql_fetch_object($res))
			{
				$question = array ();
				$question[] = '<input type="checkbox" name="question[]" value="'.$obj->qid.'"/>';
				$question[] = $obj->caption;
				$question[] = $obj->qtype;
				$groupid = $obj->gid;
				$GName = surveymanager::get_groupname($db_name,$groupid);
				$question[] = $GName;
				$sid = surveymanager::get_surveyid($db_name,$groupid);
				$sname = surveymanager::get_surveyname($db_name,$sid);
				$question[] = $sname;
				/**********for displaying the 'edit' 'delete' etc. buttons***********/
				$url = "default.php?qid=".$obj->qid."&qtype=".$obj->qtype."&surveyid=".$surveyid."&groupid=".$groupid."&db_name=".$db_name;
				$question[] = "<a href=\"#\" onClick=\"displayTemplate('".$url."')\"><img src=\"../img/info_small.gif\" border=\"0\" align=\"absmiddle\" alt=\"".get_lang('Info')."\" /></a>";
				$questions[] = $question;
				$datacount++;
			}
		}
		$table_header[] = array (' ', false);
		$table_header[] = array (get_lang('Question'), true);
		$table_header[] = array (get_lang('QuestionType'), true);
		$table_header[] = array (get_lang('Group'),true);
		$table_header[] = array	(get_lang('SurveyName'),true);
		$table_header[] = array('', false);
		if($datacount>0)
		Display :: display_sortable_table($table_header, $questions, array (), array (), $parameters);
		else
		{
			$noquestions=get_lang('NoQuestionAvailableInThisGroup');
			api_display_tool_title($noquestions);
		}
?>
		<table>
		<tr>		
		<td><input type="submit" name="back" value="<?php echo get_lang('Back');?>"></td>
		<td><input type="submit" name="importquestion" value="<?php echo get_lang('ImportQuestion');?>"></td>
		</tr>
		</table>
		</form>	
<?php
	Display :: display_footer();
?> 