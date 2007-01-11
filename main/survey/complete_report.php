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
* 	@version $Id: complete_report.php 10680 2007-01-11 21:26:23Z pcool $
*/

// name of the language file that needs to be included 
$language_file = 'survey';

// including the global dokeos file
require_once ('../inc/global.inc.php');


// including additional libraries
/** @todo check if these are all needed */
/** @todo check if the starting / is needed. api_get_path probably ends with an / */
require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/groupmanager.lib.php");
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
$tbl_user_survey 		= Database :: get_main_table(TABLE_MAIN_SURVEY_USER);
$tbl_questions 			= Database :: get_course_table(TABLE_SURVEY_QUESTION);
$tbl_questions_groups 	= Database :: get_course_table(TABLE_SURVEY_GROUP);
$tbl_answers 			= Database :: get_course_table(TABLE_SURVEY_REPORT);

// Path variables
/** @todo these variables are probably not used here */

// Language variables

// breadcrumbs
$interbreadcrumb[] = array ("url" => "survey_list.php", "name" => get_lang('Survey'));

// $_GET and $_POST
/** @todo replace $_REQUEST with $_GET or $_POST */
$surveyid	= $_REQUEST['surveyid'];

$tool_name = get_lang('SurveyReporting');

Display::display_header($tool_name);

$users = SurveyManager::listUsers($surveyid, $db_name);

$questions = SurveyManager::listQuestions($surveyid);

$excel_content = '';
$excel_file_name = 'export_survey-'.$surveyid.$db_name.'.csv';
echo '<a href="../course_info/download.php?archive='.$excel_file_name.'"><img border="0" src="../img/xls.gif" align="middle"/>'.get_lang('ExportInExcel').'</a><br /><br/>';


echo '<div style="overflow:scroll;">
		<table width="2000" height="300" style="">
			<tr style="font-weight:bold">
				<td valign="top" align="left" width="120">'.get_lang('LastName').'</td>
				<td valign="top" align="left" width="120">'.get_lang('FirstName').'</td>
				<td valign="top" align="left" width="200">'.get_lang('EmailAddress').'</td>
				<td valign="top" align="left" width="200">'.get_lang('Organisation').'</td>';
$excel_content .= get_lang('LastName').';'.get_lang('FirstName').';'.get_lang('EmailAddress').';'.get_lang('Organisation').';';

foreach($questions as $question){
	$question['caption'] = eregi_replace('^<p[^>]*>(.*)</p>','\\1', $question['caption']);
	$question['caption'] = eregi_replace('(<[^ ]*) (style=."."[^>]*)(>)','\\1\\3', $question['caption']);
	$question['caption'] = eregi_replace('(<[^ ]*) (style=.""[^>]*)(>)','\\1\\3', $question['caption']);
	$question['caption'] = eregi_replace('(<[^ ]*)( style=."[^"]*")([^>]*)(>)','\\1\\2\\4', $question['caption']);
	$excel_content .= stripslashes($question['caption']).';';
	echo '<td valign="top" align="left" width="200">'.stripslashes($question['caption']).'</td>';
}
$excel_content .= "\r\n";
$color = '#FFCCCC';
echo '<td style="background-color:#FFF">&nbsp;</td></tr><tr style="background-color:'.$color.'">';

$lastEmail = $users[O]['email'];

foreach($users as $user){
	
	if($user['email']!=$lastEmail){
		$excel_content .= "\r\n";
		$color = ($color == '#FFCCCC') ? '#CCCCFF' : '#FFCCCC';
		echo '<td style="background-color:#FFF">&nbsp;</td></tr><tr style="background-color:'.$color.'">';
		$lastEmail = $user['email'];
		
		$excel_content .= str_replace(array("\r","\n"),array("",""),$user['lastname'].';'.$user['firstname'].';'.$user['email'].';'.$user['organization'].';');
		
		echo '	<td valign="top" align="left" width="120">'.$user['lastname'].'</td>
				<td valign="top" align="left" width="120">'.$user['firstname'].'</td>
				<td valign="top" align="left" width="200">'.$user['email'].'</td>
				<td valign="top" align="left" width="200">'.nl2br($user['organization']).'</td>';
	}
	
	foreach($questions as $question){
		$sql = 'SELECT answer 
				FROM '.$tbl_answers.' as answers
				WHERE qid='.$question['qid'].'
				AND user_id='.$user['user_id'];
		
		$rs = api_sql_query($sql, __FILE__, __LINE__);
		$answer = mysql_result($rs, 'answer');
		
		if(in_array($answer,array('a1','a2','a3','a4','a5','a6','a7','a8','a9','a10'))){
			$answer = $question[$answer];
		}
		
		if($question['qtype']=='Numbered'){
			$answers = explode(',',$answer);
			$final_answer = '';
			for($i=0;$i<count($answers);$i++){
				$final_answer.= $question['a'.($i+1)].' : '.$answers[$i].'<br />';
			}
			$answer = $final_answer;
		}
		
		if($question['qtype']=='Multiple Choice (multiple answer)'){
			$answers = explode(',',$answer);
			$final_answer = '';
			for($i=0;$i<count($answers);$i++){
				$final_answer.= $question['a'.($i+1)].'<br />';
			}
			$answer = $final_answer;
		}
		
		if(empty($answer))
			$answer = '-';
			
		$excel_content .= stripslashes(str_replace(array("\r","\n","<br />"),array("",""," - "),($answer))).';';
		echo '<td valign="top" align="left" width="200">'.stripslashes($answer).'</td>';
	}	
	
}

echo '</table></div>';

$archivePath=api_get_path(SYS_PATH).$archiveDirName.'/';
$handle = fopen($archivePath.$excel_file_name, 'w');
fwrite($handle, $excel_content);
fclose($handle);
chmod($archivePath.$excel_file_name, 0755);

// Display the footer
Display :: display_footer();
?>