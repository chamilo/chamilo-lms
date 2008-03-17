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
* 	@author unknown
* 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup, refactoring and rewriting large parts of the code
* 	@version $Id: survey.php 14636 2008-03-17 22:24:08Z yannoo $
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
if (!api_is_allowed_to_edit())
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
$table_course 					= Database :: get_main_table(TABLE_MAIN_COURSE);
$table_user 					= Database :: get_main_table(TABLE_MAIN_USER);
$user_info 						= Database :: get_main_table(TABLE_MAIN_SURVEY_REMINDER);

// breadcrumbs
$interbreadcrumb[] = array ("url" => "survey_list.php", "name" => get_lang('SurveyList'));

// getting the survey information
$survey_data = survey_manager::get_survey($_GET['survey_id']);
$tool_name = substr(strip_tags($survey_data['title']), 0, 40);
if (strlen(strip_tags($survey_data['title'])) > 40)
{
	$tool_name .= '...';
}

// Displaying the header
Display::display_header($tool_name);

// Action handling
if (isset($_GET['action']))
{
	if (($_GET['action'] == 'moveup' OR $_GET['action'] == 'movedown') AND isset($_GET['question_id']))
	{
		survey_manager::move_survey_question($_GET['action'], $_GET['question_id'], $_GET['survey_id']);
		Display::display_confirmation_message('SurveyQuestionMoved', false);
	}
	if ($_GET['action'] == 'delete' AND is_numeric($_GET['question_id']))
	{
		survey_manager::delete_survey_question($_GET['survey_id'], $_GET['question_id'], $survey_data['is_shared']);
	}
}

if (isset($_GET['message']))
{
	// we have created the survey or updated the survey
	if (in_array($_GET['message'], array('SurveyUpdatedSuccesfully','SurveyCreatedSuccesfully')))
	{
		Display::display_confirmation_message(get_lang($_GET['message']).'<br />'.get_lang('YouCanNowAddQuestionToYourSurvey'), false);
	}
	// we have added a question
	if (in_array($_GET['message'], array('QuestionAdded','QuestionUpdated')))
	{
		Display::display_confirmation_message(get_lang($_GET['message']), false);
	}
}

// We exit here is the first or last question is a pagebreak (which causes errors)
check_first_last_question($_GET['survey_id']);

// Action links
$survey_actions = get_lang('Survey').': ';
$survey_actions .= '<a href="create_new_survey.php?'.api_get_cidreq().'&amp;action=edit&amp;survey_id='.$_GET['survey_id'].'">'.Display::return_icon('edit.gif', get_lang('Edit')).'</a>';
$survey_actions .= '<a href="survey_list.php?'.api_get_cidreq().'&amp;action=delete&amp;survey_id='.$_GET['survey_id'].'" onclick="javascript:if(!confirm(\''.addslashes(htmlentities(get_lang("DeleteSurvey").'?',ENT_QUOTES,$charset)).'\')) return false;">'.Display::return_icon('delete.gif', get_lang('Delete')).'</a>';
//$survey_actions .= '<a href="create_survey_in_another_language.php?id_survey='.$_GET['survey_id'].'">'.Display::return_icon('copy.gif', get_lang('Copy')).'</a>';
$survey_actions .= '<a href="preview.php?'.api_get_cidreq().'&amp;survey_id='.$_GET['survey_id'].'">'.Display::return_icon('preview.gif', get_lang('Preview')).'</a>';
$survey_actions .= '<a href="survey_invite.php?'.api_get_cidreq().'&amp;survey_id='.$_GET['survey_id'].'">'.Display::return_icon('survey_publish.gif', get_lang('Publish')).'</a>';
$survey_actions .= '<a href="reporting.php?'.api_get_cidreq().'&amp;survey_id='.$_GET['survey_id'].'">'.Display::return_icon('statistics.gif', get_lang('Reporting')).'</a>';
echo '<div style="float:right;">'.$survey_actions.'</div>';

echo '<div>';
echo '<div style="float:left; text-align:center; margin:5px;"><a href="question.php?'.api_get_cidreq().'&amp;action=add&type=yesno&amp;survey_id='.$_GET['survey_id'].'"><img src="../img/yesno.gif" /><br />'.get_lang('YesNo').'</a></div>';
echo '<div style="float:left; text-align:center; margin:5px;"><a href="question.php?'.api_get_cidreq().'&amp;action=add&type=multiplechoice&amp;survey_id='.$_GET['survey_id'].'"><img src="../img/mcua.gif" /><br />'.get_lang('MultipleChoice').'</a></div>';
echo '<div style="float:left; text-align:center; margin:5px;"><a href="question.php?'.api_get_cidreq().'&amp;action=add&type=multipleresponse&amp;survey_id='.$_GET['survey_id'].'"><img src="../img/mcma.gif" /><br />'.get_lang('MultipleResponse').'</a></div>';
echo '<div style="float:left; text-align:center; margin:5px;"><a href="question.php?'.api_get_cidreq().'&amp;action=add&type=open&amp;survey_id='.$_GET['survey_id'].'"><img src="../img/open_answer.gif" /><br />'.get_lang('Open').'</a></div>';
echo '<div style="float:left; text-align:center; margin:5px;"><a href="question.php?'.api_get_cidreq().'&amp;action=add&type=dropdown&amp;survey_id='.$_GET['survey_id'].'"><img src="../img/dropdown.gif" /><br />'.get_lang('Dropdown').'</a></div>';
echo '<div style="float:left; text-align:center; margin:5px;"><a href="question.php?'.api_get_cidreq().'&amp;action=add&type=percentage&amp;survey_id='.$_GET['survey_id'].'"><img src="../img/percentagequestion.gif" /><br />'.get_lang('Percentage').'</a></div>';
echo '<div style="float:left; text-align:center; margin:5px;"><a href="question.php?'.api_get_cidreq().'&amp;action=add&type=score&amp;survey_id='.$_GET['survey_id'].'"><img src="../img/scorequestion.gif" /><br />'.get_lang('Score').'</a></div>';
echo '<div style="float:left; text-align:center; margin:5px;"><a href="question.php?'.api_get_cidreq().'&amp;action=add&type=comment&amp;survey_id='.$_GET['survey_id'].'"><img src="../img/commentquestion.gif" /><br />'.get_lang('Comment').'</a></div>';
echo '<div style="float:left; text-align:center; margin:5px;"><a href="question.php?'.api_get_cidreq().'&amp;action=add&type=pagebreak&amp;survey_id='.$_GET['survey_id'].'"><img src="../img/page_end.gif" /><br />'.get_lang('Pagebreak').'</a></div>';
echo '</div>';
echo '<div style="clear:both;"></div>';

// Displaying the table header with all the questions
echo '<table class="data_table">';
echo '	<tr class="row_odd">';
echo '		<th width="15">'.get_lang('QuestionNumber').'</th>';
echo '		<th>'.get_lang('Title').'</th>';
echo '		<th>'.get_lang('Type').'</th>';
echo '		<th>'.get_lang('NumberOfOptions').'</th>';
echo '		<th width="100">'.get_lang('Modify').'</th>';
echo '	</tr>';
// Displaying the table contents with all the questions
$question_counter = 1;
$sql = "SELECT survey_question.*, count(survey_question_option.question_option_id) as number_of_options
			FROM $table_survey_question survey_question
			LEFT JOIN $table_survey_question_option survey_question_option
			ON survey_question.question_id = survey_question_option.question_id
			WHERE survey_question.survey_id = '".Database::escape_string($_GET['survey_id'])."'
			GROUP BY survey_question.question_id
			ORDER BY survey_question.sort ASC";
$result = api_sql_query($sql, __FILE__, __LINE__);
$question_counter_max = mysql_num_rows($result);
while ($row = mysql_fetch_assoc($result))
{
	echo '<tr>';
	echo '	<td>'.$question_counter.'</td>';
	echo '	<td>';
	if (strlen($row['survey_question']) > 100)
	{
		echo substr(strip_tags($row['survey_question']),0, 100).' ... ';
	}
	else
	{
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
	}
	if ($question_counter < $question_counter_max)
	{
		echo '		<a href="survey.php?'.api_get_cidreq().'&amp;action=movedown&amp;survey_id='.$_GET['survey_id'].'&amp;question_id='.$row['question_id'].'">'.Display::return_icon('down.gif', get_lang('MoveDown')).'</a>';
	}
	echo '	</td>';
	$question_counter++;
}
echo '</table>';

// Footer
Display :: display_footer();
?>