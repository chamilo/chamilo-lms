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
* 	@version $Id: question_added.php 10680 2007-01-11 21:26:23Z pcool $
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/
// name of the language file that needs to be included 
$language_file = 'survey';

require ('../inc/global.inc.php');
api_protect_admin_script();
require_once (api_get_path(LIBRARY_PATH).'/fileManage.lib.php');
require_once (api_get_path(CONFIGURATION_PATH) ."/add_course.conf.php");
require_once (api_get_path(LIBRARY_PATH)."/add_course.lib.inc.php");
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");
$table_survey = Database :: get_course_table(TABLE_SURVEY);
$table_group =  Database :: get_course_table(TABLE_SURVEY_GROUP);
$table_survey_question = Database :: get_course_table(TABLE_SURVEY_QUESTION);
$interbreadcrumb[] = array ("url" => "survey.php", "name" => get_lang('CreateSurvey'));
$groupid=$_REQUEST['groupid'];
$surveyid=$_REQUEST['surveyid'];
$qdeleted=0;
if (isset($_POST['addanother']))
{
	    $groupid = $_POST['groupid'];
	    $surveyid = $_POST['surveyid'];
		header("Location:addanother.php?groupid=$groupid&surveyid=$surveyid");
		//header("Location:select_question_type.php?groupid=$groupid&surveyid=$surveyid");
	    exit;
}
if (isset($_POST['addanotherg']))
{
	    $groupid = $_POST['groupid'];
	    $surveyid = $_POST['surveyid'];
		header("Location:create_new_group.php?groupid=$groupid&surveyid=$surveyid");
	    exit;
}
if(isset($_POST['delete']))
{
	if(isset($_POST['qid']))
	{
		$endloop=count($_POST['qid']);
		$qid1=$_POST['qid'];
		for($i=0;$i<$endloop;$i++)
		{
			$qid2=$qid1[$i];
			$query="DELETE FROM $table_survey_question WHERE qid='$qid2'";
			api_sql_query($query);
		}
		$qdeleted=1;
	}

}
if (isset($_POST['finish']))
      {
	    header("Location:survey_list.php");
	    exit;
      }	
	$query="SELECT * FROM $table_survey WHERE survey_id='$surveyid'";
	$result=api_sql_query($query);
	$survey_name=@mysql_result($result,0,'title');
	$author=@mysql_result($result,0,'author');
	$tool_name = get_lang('QuestionsAdded');
	Display :: display_header($tool_name);
	$survey_name=get_lang('SurveyName').$survey_name;
	$author=get_lang('Author').$author;	
	api_display_tool_title($survey_name);	
	api_display_tool_title($tool_name);	
if($qdeleted)
{
?>	
<h2><?php echo "Question(s) Deleted";?></h2>
<?php
}
?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
<input type="hidden" name="groupid" value="<?php echo $groupid; ?>">
<input type="hidden" name="surveyid" value="<?php echo $surveyid; ?>">
<?php
    $sql="SELECT * FROM $table_group WHERE survey_id='$surveyid'";	
	$res = api_sql_query($sql,__FILE__,__LINE__);
	$num=mysql_num_rows($res);
	$table_header[] = array (' ', false);
	//$table_header[] = array (get_lang('SNo'), true);
	$table_header[] = array (get_lang('questions'), true);
	$table_header[] = array (get_lang('Group'), true);
	$table_header[] = array (get_lang('Type'), true);	
	$courses = array ();
	for($i=0;$i<$num;$i++)
	{
		$groupid=@mysql_result($res,$i,'group_id');
		$gname=@mysql_result($res,$i,'groupname');
		$sql="SELECT * FROM $table_survey_question WHERE gid='$groupid'";
		$res1=api_sql_query($sql,__FILE__,__LINE__);		
		while ($obj = mysql_fetch_object($res1))
		{
			$course = array ();
			$course[] = '<input type="checkbox" name="qid[]" value="'.$obj->qid.'"/>';
			//$course[] = $j++;//$obj->visual_code;
			$course[] = $obj->caption;//tab-header-questions
			$course[] = @mysql_result($res,$i,'groupname');//$obj->course_language;
			$course[] = $obj->qtype;			
			$courses[] = $course;
		}

	}
	//echo '<form method="post" action="questionsadded1.php">';
			Display :: display_sortable_table($table_header, $courses, array (), array (), $parameters);
		//echo '</form>';	
?>	
	<input type="submit" name="delete" value="<?php  echo get_lang('Delete');?>">
	<input type="submit" name="addanother" value="<?php echo get_lang('AddAnotherQuestion');?>">
	<input type="submit" name="addanotherg" value="<?php echo get_lang('AddAnotherGroup');?>">
	<input type="submit" name="finish" value="<?php echo get_lang('FinishSurvey');?>">
</form>	
<?php
/*
==============================================================================
		FOOTER 
==============================================================================
*/
Display :: display_footer();
?>