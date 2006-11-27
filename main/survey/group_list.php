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
* 	@version $Id: group_list.php 10223 2006-11-27 14:45:59Z pcool $
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/
// name of the language file that needs to be included 
$language_file = 'survey';

//$newsurveyid=11;
require ('../inc/global.inc.php');
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");
$status = surveymanager::get_status();
if($status==5)
{
api_protect_admin_script();
}
//api_protect_admin_script();
require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");
$cidReq = $_REQUEST['cidReq'];
$curr_dbname = $_REQUEST['curr_dbname'];
$table_survey = Database :: get_course_table('survey');
$table_group =  Database :: get_course_table('survey_group');
$table_question = Database :: get_course_table('questions');
$interbredcrump[] = array ("url" => "survey_list.php", "name" => get_lang('Survey'));
$tool_name = get_lang('GroupList');
//Display :: display_header($tool_name);
//api_display_tool_title($tool_name);
Display :: display_header($tool_name);
api_display_tool_title($tool_name);
if(isset($_REQUEST['pls']))
{
	$pls=$_REQUEST['pls'];
	if($pls==1)
	{
		$error_message=$error_message=get_lang("PleaseSelectAChoice");
		Display::display_error_message($error_message);	
	}
}
$nameTools=get_lang('GroupList');
$surveyid=$_GET['surveyid'];
$sid = $_REQUEST['sid'];
$groupid = $_REQUEST['newgroupid'];
$db_name = $_REQUEST['db_name'];
$table_group =  Database :: get_course_table('survey_group');

    $sql = "SELECT * FROM $db_name.survey_group where survey_id='$sid'";
	$parameters = array ();
        $parameters['surveyid']=$surveyid;
		$parameters['sid']=$sid;
		$parameters['newgroupid']=$groupid;
		$parameters['cidReq']=$cidReq;
		$parameters['db_name']=$db_name;
		$res = api_sql_query($sql,__FILE__,__LINE__);
	if (mysql_num_rows($res) > 0)
	{
		$surveys = array ();
		?>		
		<form method="POST" action="question_list_new.php?cidReq=<?php echo $cidReq; ?>">
		<input type="hidden" name="action" value="add_survey">
		<input type="hidden" name="surveyid" value="<?php echo $surveyid; ?>">
		<input type="hidden" name="sid" value="<?php echo $sid; ?>">
		<input type="hidden" name="groupid" value="<?php echo $groupid; ?>">
		<input type="hidden" name="db_name" value="<?php echo $db_name; ?>">
		<input type="hidden" name="curr_dbname" value="<?php echo $curr_dbname;?>">
	<?	
		$gnames = array();
		while ($obj = mysql_fetch_object($res))
		{
			$gid=$obj->group_id;
			$gname = $obj->groupname;
			if ($gid!=$groupid&&$gname!='Imported Group')
			{
				$survey = array ();
				$survey[] = '<input type="checkbox" name="course[]" value="'.$obj->group_id.'"/>';
				if(@in_array($obj->groupname,$gnames))
				{continue;}
				$survey[] = $obj->groupname;
				$gnames[] = $obj->groupname;
				$gid=$obj->group_id;
				$sid=surveymanager::get_surveyid($db_name,$gid);		
				$idd=surveymanager::get_author($db_name,$sid);
				$author=surveymanager::get_survey_author($idd);
				$surveyname=surveymanager::get_surveyname($db_name,$sid);
				$NoOfQuestion=surveymanager::no_of_question($db_name,$gid);
				$survey[] = $NoOfQuestion;
				$survey[] = $surveyname;
				$survey[] = $author;
				$surveys[] = $survey;
			}
		}
		$table_header[] = array (' ', false);
		//$table_header[] = array (get_lang('SNo'), true);
		$table_header[] = array (get_lang('QuesGroup'), true);
		$table_header[] = array (get_lang('NoOfQuestions'), true);
		$table_header[] = array (get_lang('SurveyName1'), true);
		$table_header[] = array (get_lang('author'), true);
		//echo '<form method="post" action="course_list.php">';
		Display :: display_sortable_table($table_header, $surveys, array (), array (), $parameters);
		?>		
		<table>
		<tr>
		<td><input type="submit" name="back1" value="<?php echo get_lang('back');?>"></td>
		<td><input type="submit" name="view" value="<?php echo get_lang('ViewQues');?>"></td>
		<td><input type="submit" name="import" value="<?php echo get_lang('Import');?>"></td>
		</tr>
		</table>
		</form>
<?	}
	else
	{
		echo get_lang('NoSearchResults');
	}
/*
==============================================================================
		FOOTER 
==============================================================================
*/
Display :: display_footer();
?> 




