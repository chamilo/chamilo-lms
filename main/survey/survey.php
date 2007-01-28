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
* 	@version $Id: survey.php 10941 2007-01-28 19:03:38Z pcool $
*
* 	@todo The ansTarget column is not done
* 	@todo try to understand the white, blue, ... template stuff.
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
	Display :: display_header();
	Display :: display_error_message(get_lang('NotAllowedHere'));
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
$tool_name = get_lang('Survey');

// Displaying the header
Display::display_header($tool_name);

// Action handling
if (isset($_GET['action']))
{
	if (($_GET['action'] == 'moveup' OR $_GET['action'] == 'movedown') AND isset($_GET['question_id']))
	{
		survey_manager::move_survey_question($_GET['action'], $_GET['question_id'], $_GET['survey_id']);
		Display::display_confirmation_message('SurveyQuestionMoved');
	}
}

// Displaying the survey information
$survey_data = survey_manager::get_survey($_GET['survey_id']);
echo '<strong>'.$survey_data['title'].'</strong><br />';
echo $survey_data['subtitle'].'<br />';

// Action links
$survey_actions = '<a href="create_new_survey.php?action=edit&amp;survey_id='.$_GET['survey_id'].'">'.Display::return_icon('edit.gif').'</a>';
$survey_actions .= '<a href="survey_list.php?action=delete&amp;survey_id='.$_GET['survey_id'].'">'.Display::return_icon('delete.gif').'</a>';
$survey_actions .= '<a href="create_survey_in_another_language.php?id_survey='.$_GET['survey_id'].'">'.Display::return_icon('copy.gif').'</a>';
$survey_actions .= '<a href="preview.php?survey_id='.$_GET['survey_id'].'">'.Display::return_icon('preview.gif').'</a>';
$survey_actions .= '<a href="survey_invite.php?survey_id='.$_GET['survey_id'].'">'.Display::return_icon('survey_publish.gif').'</a>';
$survey_actions .= '<a href="reporting.php?action=reporting&amp;surveyid='.$_GET['survey_id'].'">'.Display::return_icon('surveyreporting.gif').'</a>';
echo '<div style="float:right;">'.$survey_actions.'</div>';

echo '<a href="question.php?action=add&type=yesno&amp;survey_id='.$_GET['survey_id'].'">'.get_lang('YesNo').'</a> | ';
echo '<a href="question.php?action=add&type=multiplechoice&amp;survey_id='.$_GET['survey_id'].'">'.get_lang('MultipleChoice').'</a> | ';
echo '<a href="question.php?action=add&type=multipleresponse&amp;survey_id='.$_GET['survey_id'].'">'.get_lang('MultipleResponse').'</a> | ';
echo '<a href="question.php?action=add&type=open&amp;survey_id='.$_GET['survey_id'].'">'.get_lang('Open').'</a> | ';
echo '<a href="question.php?action=add&type=dropdown&amp;survey_id='.$_GET['survey_id'].'">'.get_lang('Dropdown').'</a> | ';
//echo '<a href="question.php?action=add&type=percentage&amp;survey_id='.$_GET['survey_id'].'">'.get_lang('Dropdown').'</a> | ';
//echo '<a href="question.php?action=add&type=rating&amp;survey_id='.$_GET['survey_id'].'">'.get_lang('Dropdown').'</a> | ';
echo '<a href="question.php?action=add&type=comment&amp;survey_id='.$_GET['survey_id'].'">'.get_lang('Comment').'</a> | ';
echo '<a href="question.php?action=add&type=pagebreak&amp;survey_id='.$_GET['survey_id'].'">'.get_lang('Pagebreak').'</a>';
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
			WHERE survey_question.survey_id = '".mysql_real_escape_string($_GET['survey_id'])."'
			GROUP BY survey_question.question_id
			ORDER BY survey_question.sort ASC";
$result = api_sql_query($sql, __FILE__, __LINE__);
$question_counter_max = mysql_num_rows($result);
while ($row = mysql_fetch_assoc($result))
{
	echo '<tr>';
	echo '	<td>'.$question_counter.'</td>';
	echo '	<td>'.$row['survey_question'].'</td>';
	echo '	<td>'.$row['type'].'</td>';
	echo '	<td>'.$row['number_of_options'].'</td>';
	echo '	<td>';
	echo '		<a href="question.php?action=edit&amp;type='.$row['type'].'&amp;survey_id='.$_GET['survey_id'].'&amp;question_id='.$row['question_id'].'">'.Display::return_icon('edit.gif').'</a>';
	echo '		<a href="survey.php?action=delete&amp;survey_id='.$_GET['survey_id'].'&ampquestion_id='.$row['question_id'].'">'.Display::return_icon('delete.gif').'</a>';
	if ($question_counter > 1)
	{
		echo '		<a href="survey.php?action=moveup&amp;survey_id='.$_GET['survey_id'].'&amp;question_id='.$row['question_id'].'">'.Display::return_icon('up.gif').'</a>';
	}
	if ($question_counter < $question_counter_max)
	{
		echo '		<a href="survey.php?action=movedown&amp;survey_id='.$_GET['survey_id'].'&amp;question_id='.$row['question_id'].'">'.Display::return_icon('down.gif').'</a>';
	}
	echo '	</td>';
	$question_counter++;
}
echo '</table>';


// Footer
Display :: display_footer();
?>