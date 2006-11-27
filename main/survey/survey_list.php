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
* 	@version $Id: survey_list.php 10223 2006-11-27 14:45:59Z pcool $
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/
// name of the language file that needs to be included 
$language_file = 'survey';
require ('../inc/global.inc.php');
//require_once('../survey/send_mail.php');
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");
$status = surveymanager::get_status();
api_protect_course_script();
if($status==5)
{
api_protect_admin_script();
}
$cidReq = $_SESSION[_course][id];
require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");
$table_survey = Database :: get_course_table('survey');
$table_group =  Database :: get_course_table('survey_group');
$table_question = Database :: get_course_table('questions');
$table_course = Database :: get_main_table(TABLE_MAIN_COURSE);
$sql = "SELECT * FROM $table_course WHERE code = '$cidReq'";
$res = api_sql_query($sql,__FILE__,__LINE__);
$obj=@mysql_fetch_object($res);
$db_name = $obj->db_name ;
$published = $_REQUEST['published'];
$surveyid=$_REQUEST['surveyid'];
//$interbredcrump[] = array ("url" => "survey_list.php", "name" => get_lang('SurveyList'));
if (isset ($_REQUEST['action']))
{
	 $cidReq=$_REQUEST['cidReq'];
	 $table_survey = Database :: get_course_table('survey');
	switch ($_REQUEST['action'])
	{		
		case 'delete_surveys' :

			$survey_codes = $_REQUEST['survey_delete'];
			if (count($survey_codes) > 0)
			{
				foreach ($survey_codes as $index => $survey_code)
				{
					SurveyManager::delete_survey($survey_code,$table_survey,$table_group,$table_question);
				}
			}
			break;
	}
	
   if (isset($_POST['newsurvey']))
      {
	    header("Location:survey.php");
	    exit;
      }
}
if (isset ($_GET['search']) && $_GET['search'] == 'advanced')
{
	$db_name = $_GET['db_name'];
	$sql = "SELECT * FROM $db_name.survey";
	$res = api_sql_query($sql,__FILE__,__LINE__);
	$titles = array ();
	while ($title = mysql_fetch_array($res, MYSQL_ASSOC))
	{
		$titles[] = $title;
	}
	//$interbredcrump[] = array ("url" => "index.php", "name" => get_lang('Survey'));
	//$interbredcrump[] = array ("url" => 'survey_list.php', "name" => get_lang('SurveyList'));
	$tool_name = get_lang('SearchASurvey');
	Display :: display_header($tool_name);
	api_display_tool_title($tool_name);
?>
	<form method="get" action="survey_list.php">
	<input type="hidden" name="cidReq" value="<?php echo $cidReq; ?>">
	<input type="hidden" name="db_name" value="<?php echo $db_name; ?>">
	<table>
	<tr>
	<td>
	<?php echo get_lang('Title'); ?>
	</td>
	<td>
	<input type="text" name="keyword_title"/>
	</td>
	</tr>
	<tr>
	<td>
	<?php echo get_lang('Code'); ?>
	</td>
	<td>
	<input type="text" name="keyword_code"/>
	</td>
	</tr>	
	<tr>
	<td>
	<?php echo get_lang('Language'); ?>
	</td>
	<td>
	<select name="keyword_language">
	<option value="%"><?php echo get_lang('All') ?></option>
	<?php
	$languages = api_get_languages();
	foreach ($languages['name'] as $index => $name)
	{
		echo '<option value="'.$languages['folder'][$index].'">'.$name.'</option>';
	}
?>
	</select>
	</td>
	</tr>	
	<tr>
	<td>
	</td>
	<td>
	<input type="submit" value="<?php echo get_lang('Ok'); ?>"/>
	</td>
	</table>
	</form>
	<?php
	}
else
{
	//$interbredcrump[] = array ("url" => "index.php", "name" => get_lang('Survey'));
	$tool_name = get_lang('Survey');
	Display :: display_header($tool_name);
	api_display_tool_title($tool_name);
	if(isset($published))
	{
	$sname = surveymanager::pick_surveyname($surveyid);
	$error_message = get_lang('YourSurveyhasbeenPublished');
	Display::display_error_message("Survey "."'".$sname."'"." ".$error_message);	
	}
	if (isset ($_GET['delete_course']))
	{
		CourseManager :: delete_course($_GET['delete_course']);
	}
?>
	<form method="get" action="survey_list.php?cidReq=<?php echo $cidReq; ?>">
	<input type="hidden" name="cidReq" value="<?php echo $cidReq; ?>">
	<input type="hidden" name="db_name" value="<?php echo $db_name; ?>">
	<input type="text" name="keyword" value="<?php echo $_GET['keyword']; ?>"/>
	<input type="submit" value="<?php echo get_lang('Search'); ?>"/>
	<a href="survey_list.php?cidReq=<?php echo $cidReq; ?>&search=advanced&db_name=<?php echo $db_name; ?>"><?php echo get_lang('AdvancedSearch'); ?></a>
	</form>
<?php
	$table_survey = Database :: get_course_table('survey');
	if (isset ($_GET['keyword']))
	{
		$keyword = addslashes($_GET['keyword']);
		$sql = "SELECT * FROM ".$table_survey." WHERE title LIKE '%".$keyword."%' OR code LIKE '%".$keyword."%'";
		$parameters = array ('keyword' => $_GET['keyword']);
		$parameters['surveyid']=$surveyid;
		$parameters['newgroupid']=$groupid;
		$parameters['cidReq']=$cidReq;
	}
	elseif (isset ($_GET['keyword_title']))
	{
		$keyword_title = addslashes($_GET['keyword_title']);
		$keyword_code = addslashes($_GET['keyword_code']);
		$keyword_language = addslashes($_GET['keyword_language']);
		$sql = "SELECT * FROM ".$table_survey." WHERE title LIKE '%".$keyword_title."%' AND code LIKE '%".$keyword_code."%' AND lang LIKE '%".$keyword_language."%'";
		$parameters['keyword_title'] = $_GET['keyword_title'];
		$parameters['keyword_code'] = $_GET['keyword_code'];
		$parameters['keyword_language'] = $_GET['keyword_language'];
		$parameters['surveyid']=$surveyid;
		$parameters['newgroupid']=$groupid;
		$parameters['cidReq']=$cidReq;
	}
	else
	{
		$sql = "SELECT * FROM ".$table_survey;
		$parameters = array ();
		$parameters['surveyid']=$surveyid;
		$parameters['newgroupid']=$groupid;
		$parameters['cidReq']=$cidReq;
	}
	$res = api_sql_query($sql,__FILE__,__LINE__);
	if (mysql_num_rows($res) > 0)
	{
		$user_info = Database::get_main_table(TABLE_MAIN_SURVEY_REMINDER);
		$courses = array ();
		while ($obj = mysql_fetch_object($res))
		{
			$template=$obj->template;
			$surveyid = $obj->survey_id;
			if($template=='template1'){$view='white';}
			elseif($template=='template2'){$view='blue';}
			elseif($template=='template3'){$view='brown';}
			elseif($template=='template4'){$view='gray';}
			else{$view=='blank';}
			$sql_sent="	SELECT DISTINCT user_info.* 
							FROM $user_info as user_info
							INNER JOIN $table_survey as survey
							ON user_info.sid = survey.survey_id
							AND survey.code = '".$obj->code."'";
			
			$res_sent=api_sql_query($sql_sent);
			$sent=mysql_num_rows($res_sent);
			
			$attempted=0;
			
			$sqlAttempt = '	SELECT DISTINCT *
							FROM '.Database::get_main_table(TABLE_MAIN_SURVEY_USER).'
							WHERE survey_id='.$obj->survey_id.' AND db_name="'.$db_name.'"';
			$res_attempt=api_sql_query($sqlAttempt);
			$attempted=mysql_num_rows($res_attempt);
			
			/*while($object=mysql_fetch_object($res_attempt))
			{
				if($object->access=='1' && $object->sid==$obj->survey_id)
					$attempted++;
			}*/
			if($sent=='0')
			{$ratio=$attempted."/".$sent." "."(Not Published)";}
			else
				$ratio=$attempted."/".$sent;
			$survey = array ();
			$survey[] = '<input type="checkbox" name="survey_delete[]" value="'.$obj->survey_id.'">';
			$survey[] = $obj->title;
			$survey[] = $obj->code;
			$idd=surveymanager::get_author($db_name,$surveyid);		
			$author=surveymanager::get_survey_author($idd);
			$survey[] = $author;
			$survey[] = $obj->lang;
			$survey[] = $obj->avail_from ;
			$survey[] = $obj->avail_till ;	
			$survey[] = $ratio;
			//$NoOfQuestion=surveymanager::no_of_question($gid);
			//$language=surveymanager::no_of_question($sid);
			$survey[] = '<a href="survey_edit.php?surveyid='.$obj->survey_id.'&cidReq='.$cidReq.'"><img src="../img/edit.gif" border="0" align="absmiddle" alt="'.get_lang('Edit1').'"/></a>'.'<a href="survey_list.php?cidReq='.$cidReq.'&action=delete_surveys&survey_delete[]='.$obj->survey_id.'&delete_survey='.$obj->survey_id.'"  onclick="javascript:if(!confirm('."'".addslashes(htmlentities(get_lang("ConfirmYourChoice")))."'".')) return false;"><img src="../img/delete.gif" border="0" align="absmiddle" alt="'.get_lang('Delete1').'"/></a>'.'<a href="create_survey_in_another_language.php?cidReq='.$cidReq.'&id_survey='.$obj->survey_id.'"><img width="28" src="../img/copy.gif" border="0" align="absmiddle" alt="'.get_lang('CreateInAnotherLanguage').'" title="'.get_lang('CreateInAnotherLanguage').'" /></a>'.'<a href="survey_white.php?surveyid='.$surveyid.'&db_name='.$db_name.'&cidReq='.$cidReq.'&temp='.$template.'">&nbsp;<img src="../img/visible.gif" border="0" align="absmiddle" alt="'.get_lang('ViewSurvey').'"></a>'.'<a href="../announcements/announcements.php?action=add&cidReq='.$cidReq.'&db_name='.$db_name.'&publish_survey='.$obj->survey_id.'">&nbsp;<img src="../img/survey_publish.gif" border="0" align="absmiddle" alt="'.get_lang('publish').'"></a>'.'<a href="reporting.php?action=reporting&cidReq='.$cidReq.'&db_name='.$db_name.'&surveyid='.$obj->survey_id.'">&nbsp;<img src="../img/surveyreporting.gif" border="0" align="absmiddle" alt="'.get_lang('Reporting').'"></a>';
			$surveys[] = $survey;
		}
		$table_header[] = array (' ', false);
		$table_header[] = array (get_lang('SurveyName1'), true);
		$table_header[] = array (get_lang('SurveyCode'), true);
		$table_header[] = array (get_lang('author'), true);
		$table_header[] = array (get_lang('Language'), true);
		$table_header[] = array (get_lang('AvailableFrom'), true);
		$table_header[] = array (get_lang('AvailableTill'), true);
		$table_header[] = array (get_lang('AnsTarget'), true);
		$table_header[] = array (' ', false);
		echo '<form method="get" action="survey_list.php" name="frm">';
		Display :: display_sortable_table($table_header, $surveys, array (), array (), $parameters);
		echo '<select name="action">';
		echo '<option value="delete_surveys">'.get_lang('DeleteSurvey').'</option>';
		echo '</select>';
		echo '<input type="hidden" name="cidReq" value="'.$cidReq.'">';
		
		echo '&nbsp;&nbsp;<input type="submit" value="'.get_lang('Ok1').'" onclick="return validate(\'frm\');"/>';
		echo '</form>';
	}
	else
	{
		if((isset ($_GET['keyword'])) || (isset ($_GET['keyword_title']))){		
		echo get_lang('NoSearchResults') ;
		}
		else{
			$nosurvey=get_lang("NoSurveyAvailableinthelist");
			api_display_tool_title($nosurvey);		
		}
	}
}
if(!isset ($_GET['search']))
{
?>
<form action="survey.php?cidReq=<?php echo $_SESSION[_course][id]; ?>" method="post">
<input type="submit" name="newsurvey" value="<?php echo get_lang('CreateSurvey'); ?>">
<input type="hidden" name="cidReq" value="<?php echo $cidReq; ?>">
<input type="hidden" name="db_name" value="<?php echo $db_name; ?>">
</form>
<?php 
}
/*
==============================================================================
		FOOTER 
==============================================================================
*/
Display :: display_footer();
?>
<script language="javascript">
function validate(form)
{
	var count = 0;
	if(typeof eval("document."+form+"['survey_delete[]'].length")=="undefined")
	{
		if(eval("document."+form+"['survey_delete[]'].checked"))
		{
			count=1;
		}
		else
		{
			count=0;
		}
	}
	else
	for(i=0;i<eval("document."+form+"['survey_delete[]'].length");i++)
	{
		var box = (eval("document."+form+"['survey_delete[]']["+i+"]"));
		if (box.checked == true) 
		{
			count++;
		}
	}
	if(count<1)
	{
			alert("Please Select at least one Survey for deletion");
			return false;
	}
	else
	{
		if(!confirm("Please Confirm Your Choice"))
			return false;
		else
			return true;
	}
}
</script>