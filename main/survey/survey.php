<?php // $Id: survey.php 19829 2009-04-17 13:49:47Z pcool $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2009 Dokeos SPRL
	Copyright (c) 2003 Ghent University (UGent)

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, rue Notre Dame, 152, B-1140 Evere, Belgium, info@dokeos.com
==============================================================================
*/
/**
*	@package dokeos.survey
* 	@author unknown
* 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup, refactoring and rewriting large parts of the code
* 	@version $Id: survey.php 19829 2009-04-17 13:49:47Z pcool $
*
* 	@todo use quickforms for the forms
*/

// name of the language file that needs to be included
$language_file = 'survey';

// including the global dokeos file
require ('../inc/global.inc.php');

// including additional libraries
//require_once (api_get_path(LIBRARY_PATH)."/survey.lib.php");
require_once('survey.lib.php');
require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");

/** @todo this has to be moved to a more appropriate place (after the display_header of the code)*/
// coach can't view this page
$extend_rights_for_coachs = api_get_setting('extend_rights_for_coach_on_survey');
if (!api_is_allowed_to_edit(false,true) || (api_is_course_coach() && $extend_rights_for_coachs=='false'))
{
	Display :: display_header(get_lang('Survey'));
	Display :: display_error_message(get_lang('NotAllowed'), false);
	Display :: display_footer();
	exit;
}

// Database table definitions
$table_survey 					= Database :: get_course_table(TABLE_SURVEY);
$table_survey_question 			= Database :: get_course_table(TABLE_SURVEY_QUESTION);
$table_survey_question_option 	= Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
$table_survey_question_group    = Database :: get_course_table(TABLE_SURVEY_QUESTION_GROUP);
$table_course 					= Database :: get_main_table(TABLE_MAIN_COURSE);
$table_user 					= Database :: get_main_table(TABLE_MAIN_USER);
$user_info 						= Database :: get_main_table(TABLE_MAIN_SURVEY_REMINDER);

// breadcrumbs
$interbreadcrumb[] = array ("url" => "survey_list.php", "name" => get_lang('SurveyList'));

// getting the survey information
$survey_data = survey_manager::get_survey($_GET['survey_id']);

if (substr($survey_data['title'],0,3)!='<p>'){
	$tool_name = strip_tags(substr(html_entity_decode($survey_data['title'],ENT_QUOTES,$charset), 0, 40));
}else{
	$tool_name = strip_tags(substr(html_entity_decode(substr($survey_data['title'],3,-4),ENT_QUOTES,$charset), 0, 40));
}
$is_survey_type_1 = ($survey_data['survey_type']==1)?true:false;
if (strlen(strip_tags($survey_data['title'])) > 40)
{
	$tool_name .= '...';
}

if($is_survey_type_1 && ($_GET['action']=='addgroup')||($_GET['action']=='deletegroup'))
{
	$_POST['name'] = trim($_POST['name']);
	
	if(($_GET['action']=='addgroup'))
	{
		if(!empty($_POST['group_id']))
		{
			api_sql_query('UPDATE '.$table_survey_question_group.' SET description = \''.Database::escape_string($_POST['description']).'\' WHERE id = \''.Database::escape_string($_POST['group_id']).'\'');
			$sendmsg = 'GroupUpdatedSuccessfully';
		} 
		elseif(!empty($_POST['name']))
		{
			api_sql_query('INSERT INTO '.$table_survey_question_group.' (name,description,survey_id) values (\''.Database::escape_string($_POST['name']).'\',\''.Database::escape_string($_POST['description']).'\',\''.Database::escape_string($_GET['survey_id']).'\') ');	
			$sendmsg = 'GroupCreatedSuccessfully';
		} else {
			$sendmsg = 'GroupNeedName';
		}
	}	
		
	if($_GET['action']=='deletegroup'){
		api_sql_query('DELETE FROM '.$table_survey_question_group.' WHERE id = '.Database::escape_string($_GET['gid']).' and survey_id = '.Database::escape_string($_GET['survey_id']));
		$sendmsg = 'GroupDeletedSuccessfully';	
	}
	
	header('Location:survey.php?survey_id='.(int)$_GET['survey_id'].'&sendmsg='.$sendmsg);
	exit;
}

// Displaying the header
Display::display_header($tool_name,'Survey'); 

// Action handling
$my_action_survey		= Security::remove_XSS($_GET['action']);
$my_question_id_survey  = Security::remove_XSS($_GET['question_id']);
$my_survey_id_survey    = Security::remove_XSS($_GET['survey_id']);
$message_information    = Security::remove_XSS($_GET['message']);
if (isset($_GET['action'])) {
	if (($_GET['action'] == 'moveup' OR $_GET['action'] == 'movedown') AND isset($_GET['question_id'])) {
		survey_manager::move_survey_question($my_action_survey,$my_question_id_survey,$my_survey_id_survey);
		Display::display_confirmation_message(get_lang('SurveyQuestionMoved'));
	}
	if ($_GET['action'] == 'delete' AND is_numeric($_GET['question_id'])) {
		survey_manager::delete_survey_question($my_survey_id_survey, $my_question_id_survey, $survey_data['is_shared']);
	}
}
if (isset($_GET['message'])) {
	// we have created the survey or updated the survey
	if (in_array($_GET['message'], array('SurveyUpdatedSuccesfully','SurveyCreatedSuccesfully'))) {
		Display::display_confirmation_message(get_lang($message_information).','.PHP_EOL.strtolower(get_lang('YouCanNowAddQuestionToYourSurvey')));
	}
	// we have added a question
	if (in_array($_GET['message'], array('QuestionAdded','QuestionUpdated'))) {
		Display::display_confirmation_message(get_lang($message_information));
	}
	
	if (in_array($_GET['message'], array('YouNeedToCreateGroups'))) {
		Display::display_warning_message(get_lang($message_information), false);
	}
}
if(!empty($survey_data['survey_version'])) echo '<b>'.get_lang('Version').': '.$survey_data['survey_version'].'</b>';
// We exit here is the first or last question is a pagebreak (which causes errors)
SurveyUtil::check_first_last_question($_GET['survey_id']);

// Action links
$survey_actions = '<a href="create_new_survey.php?'.api_get_cidreq().'&amp;action=edit&amp;survey_id='.$_GET['survey_id'].'">'.Display::return_icon('edit.gif', get_lang('Edit')).' '.get_lang('EditSurvey').'</a>';
$survey_actions .= '<a href="survey_list.php?'.api_get_cidreq().'&amp;action=delete&amp;survey_id='.$_GET['survey_id'].'" onclick="javascript:if(!confirm(\''.addslashes(htmlentities(get_lang("DeleteSurvey").'?',ENT_QUOTES,$charset)).'\')) return false;">'.Display::return_icon('delete.gif', get_lang('Delete')).' '.get_lang('DeleteSurvey').'</a>';
//$survey_actions .= '<a href="create_survey_in_another_language.php?id_survey='.$_GET['survey_id'].'">'.Display::return_icon('copy.gif', get_lang('Copy')).'</a>';
$survey_actions .= '<a href="preview.php?'.api_get_cidreq().'&amp;survey_id='.$_GET['survey_id'].'">'.Display::return_icon('preview.gif', get_lang('Preview')).' '.get_lang('Preview').'</a>';
$survey_actions .= '<a href="survey_invite.php?'.api_get_cidreq().'&amp;survey_id='.$_GET['survey_id'].'">'.Display::return_icon('survey_publish.gif', get_lang('Publish')).' '.get_lang('Publish').'</a>';
$survey_actions .= '<a href="reporting.php?'.api_get_cidreq().'&amp;survey_id='.$_GET['survey_id'].'">'.Display::return_icon('statistics.gif', get_lang('Reporting')).' '.get_lang('Reporting').'</a>';
echo '<div class="actions">'.$survey_actions.'</div>';

if ($survey_data['survey_type']==0) {	
	echo '<div class="actionsbig">';	
	echo '<a style="padding-left:0px;" href="question.php?'.api_get_cidreq().'&amp;action=add&type=yesno&amp;survey_id='.$_GET['survey_id'].'"><img src="../img/yesno.gif" /><br/>'.get_lang('YesNo').'</a>';	 
	echo '<a href="question.php?'.api_get_cidreq().'&amp;action=add&type=multiplechoice&amp;survey_id='.$_GET['survey_id'].'"><img src="../img/mcua.gif" /><br />'.get_lang('MultipleChoice').'</a>';	
	echo '<a href="question.php?'.api_get_cidreq().'&amp;action=add&type=multipleresponse&amp;survey_id='.$_GET['survey_id'].'"><img src="../img/mcma.gif" /><br />'.get_lang('MultipleResponse').'</a>';
	echo '<a href="question.php?'.api_get_cidreq().'&amp;action=add&type=open&amp;survey_id='.$_GET['survey_id'].'"><img src="../img/open_answer.gif" /><br />'.get_lang('Open').'</a>';
	echo '<a href="question.php?'.api_get_cidreq().'&amp;action=add&type=dropdown&amp;survey_id='.$_GET['survey_id'].'"><img src="../img/dropdown.gif" /><br />'.get_lang('Dropdown').'</a>';	
	echo '<a href="question.php?'.api_get_cidreq().'&amp;action=add&type=percentage&amp;survey_id='.$_GET['survey_id'].'"><img src="../img/percentagequestion.gif" /><br />'.get_lang('Percentage').'</a>';
	echo '<a href="question.php?'.api_get_cidreq().'&amp;action=add&type=score&amp;survey_id='.$_GET['survey_id'].'"><img src="../img/scorequestion.gif" /><br />'.get_lang('Score').'</a>';
	echo '<a href="question.php?'.api_get_cidreq().'&amp;action=add&type=comment&amp;survey_id='.$_GET['survey_id'].'"><img src="../img/commentquestion.gif" /><br />'.get_lang('Comment').'</a>';
	echo '<a href="question.php?'.api_get_cidreq().'&amp;action=add&type=pagebreak&amp;survey_id='.$_GET['survey_id'].'"><img src="../img/page_end.gif" /><br />'.get_lang('Pagebreak').'</a>';
	echo '</div>';
} else {	
	echo '<div class="actionsbig">';
	//echo '<a href="group.php?'.api_get_cidreq().'&amp;action=add&amp;survey_id='.$_GET['survey_id'].'"><img src="../img/yesno.gif" /><br />'.get_lang('Add groups').'</a></div>';
	echo '<a style="padding-left:0px;" href="question.php?'.api_get_cidreq().'&amp;action=add&type=personality&amp;survey_id='.$_GET['survey_id'].'"><img src="../img/yesno.gif" />'.get_lang('PersonalityQuestion').'</a></div>';
	echo '</div>';	 
}

// Displaying the table header with all the questions
echo '<table class="data_table">';
echo '	<tr class="row_odd">';
echo '		<th width="15">'.get_lang('QuestionNumber').'</th>';
echo '		<th>'.get_lang('Title').'</th>';
echo '		<th>'.get_lang('Type').'</th>';
echo '		<th>'.get_lang('NumberOfOptions').'</th>';
echo '		<th width="100">'.get_lang('Modify').'</th>';
if($is_survey_type_1) { 
	echo '<th width="100">'.get_lang('Condition').'</th>';
    echo '<th width="40">'.get_lang('Group').'</th>';
}
echo '	</tr>';
// Displaying the table contents with all the questions
$question_counter = 1;
$sql = "SELECT * FROM $table_survey_question_group WHERE survey_id = '".Database::escape_string($_GET['survey_id'])."' ORDER BY id";
$result = api_sql_query($sql, __FILE__, __LINE__);
$groups = array();
while($row = Database::fetch_array($result)) {
    $groups[$row['id']] = $row['name'];
}
$sql = "SELECT survey_question.*, count(survey_question_option.question_option_id) as number_of_options
			FROM $table_survey_question survey_question
			LEFT JOIN $table_survey_question_option survey_question_option 
			ON survey_question.question_id = survey_question_option.question_id
			WHERE survey_question.survey_id = '".Database::escape_string($_GET['survey_id'])."'
			GROUP BY survey_question.question_id
			ORDER BY survey_question.sort ASC";
$result = api_sql_query($sql, __FILE__, __LINE__);
$question_counter_max = Database::num_rows($result);
while ($row = Database::fetch_array($result,'ASSOC')) {
	echo '<tr>';
	echo '	<td>'.$question_counter.'</td>';
	echo '	<td>';
	if (strlen($row['survey_question']) > 100) {
		echo substr(strip_tags($row['survey_question']),0, 100).' ... ';
	} else {
		echo $row['survey_question'];
	}
	echo '</td>';
	echo '	<td>'.get_lang(ucfirst($row['type'])).'</td>';
	echo '	<td>'.$row['number_of_options'].'</td>';
	echo '	<td>';
	echo '		<a href="question.php?'.api_get_cidreq().'&amp;action=edit&amp;type='.$row['type'].'&amp;survey_id='.$_GET['survey_id'].'&amp;question_id='.$row['question_id'].'">'.Display::return_icon('edit.gif', get_lang('Edit')).'</a>';
	echo '		<a href="survey.php?'.api_get_cidreq().'&amp;action=delete&amp;survey_id='.$_GET['survey_id'].'&amp;question_id='.$row['question_id'].'" onclick="javascript:if(!confirm(\''.addslashes(htmlentities(get_lang("DeleteSurveyQuestion").'?',ENT_QUOTES,$charset)).'\')) return false;">'.Display::return_icon('delete.gif', get_lang('Delete')).'</a>';
	if ($question_counter > 1)
	{
		echo '		<a href="survey.php?'.api_get_cidreq().'&amp;action=moveup&amp;survey_id='.$_GET['survey_id'].'&amp;question_id='.$row['question_id'].'">'.Display::return_icon('up.gif', get_lang('MoveUp')).'</a>';
	} else {
		echo ' <img src="../img/up_na.gif"> ';	
	}
	if ($question_counter < $question_counter_max)
	{
		echo '		<a href="survey.php?'.api_get_cidreq().'&amp;action=movedown&amp;survey_id='.$_GET['survey_id'].'&amp;question_id='.$row['question_id'].'">'.Display::return_icon('down.gif', get_lang('MoveDown')).'</a>';
	} else {
		echo ' <img src="../img/down_na.gif"> ';		
	}
	echo '	</td>';
	$question_counter++;
	
	if($is_survey_type_1)
    {
    	echo '<td>'.(($row['survey_group_pri']==0)?get_lang('Secondary'):get_lang('Primary')).'</td>';
        echo '<td>'.(($row['survey_group_pri']==0)?$groups[$row['survey_group_sec1']].'-'.$groups[$row['survey_group_sec2']]:$groups[$row['survey_group_pri']]).'</td>';
    }
	echo '</tr>'; 
}
echo '</table>';

if($is_survey_type_1)
{
	echo '<br /><br /><b>'.get_lang('ManageGroups').'</b><br /><br />';
	
	if (in_array($_GET['sendmsg'], array('GroupUpdatedSuccessfully','GroupDeletedSuccessfully','GroupCreatedSuccessfully'))){
		echo Display::display_confirmation_message(get_lang($_GET['sendmsg']), false);
	}
	
	if (in_array($_GET['sendmsg'], array('GroupNeedName'))){
		echo Display::display_warning_message(get_lang($_GET['sendmsg']), false);
	}
	
	echo '<table border="0"><tr><td width="100">'.get_lang('Name').'</td><td>'.get_lang('Description').'</td></tr></table>';
	
	echo '<form action="survey.php?action=addgroup&survey_id='.(int)$_GET['survey_id'].'" method="post">';
	if($_GET['action']=='editgroup') {	
		$sql = 'SELECT name,description FROM '.$table_survey_question_group.' WHERE id = '.Database::escape_string($_GET['gid']).' AND survey_id = '.Database::escape_string($_GET['survey_id']).' limit 1';
		$rs = api_sql_query($sql,__FILE__,__LINE__);
		$editedrow = Database::fetch_array($rs,'ASSOC');
	
		echo	'<input type="text" maxlength="20" name="name" value="'.$editedrow['name'].'" size="10" disabled>';
		echo	'<input type="text" maxlength="150" name="description" value="'.$editedrow['description'].'" size="40">';	
		echo	'<input type="hidden" name="group_id" value="'.Security::remove_XSS($_GET['gid']).'">';		
		echo	'<input type="submit" value="'.get_lang('Save').'"'.'<input type="button" value="'.get_lang('Cancel').'" onclick="window.location.href = \'survey.php?survey_id='.Security::remove_XSS($_GET['survey_id']).'\';" />';
	} else {
		echo	'<input type="text" maxlength="20" name="name" value="" size="10">';
		echo	'<input type="text" maxlength="250" name="description" value="" size="80">';
		echo	'<input type="submit" value="'.get_lang('Create').'"';	
	}
	echo	'</form><br />';

	echo '<table class="data_table">';
	echo '	<tr class="row_odd">';
	echo '		<th width="200">'.get_lang('Name').'</th>';
	echo '		<th>'.get_lang('Description').'</th>';
	echo '		<th width="100">'.get_lang('Modify').'</th>';
	echo '	</tr>';	
	
	$sql = 'SELECT id,name,description FROM '.$table_survey_question_group.' WHERE survey_id = '.Database::escape_string($_GET['survey_id']).' ORDER BY name';
	
	$rs = api_sql_query($sql,__FILE__,__LINE__);
	while($row = Database::fetch_array($rs,ASSOC)){
		$grouplist .= '<tr><td>'.$row['name'].'</td><td>'.$row['description'].'</td><td>'.
		'<a href="survey.php?survey_id='.(int)$_GET['survey_id'].'&gid='.$row['id'].'&action=editgroup">'.
		Display::return_icon('edit.gif', get_lang('Edit')).'</a> '.
		'<a href="survey.php?survey_id='.(int)$_GET['survey_id'].'&gid='.$row['id'].'&action=deletegroup" onclick="javascript:if(!confirm(\''.addslashes(htmlentities(sprintf(get_lang('DeleteSurveyGroup'),$row['name']).'?',ENT_QUOTES,$charset)).'\')) return false;">'.
		Display::return_icon('delete.gif', get_lang('Delete')).'</a>'.
		'</td></tr>';
	}				
	echo $grouplist.'</table>';
}

// Footer
Display :: display_footer();
?>