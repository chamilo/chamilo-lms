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
* 	@author unknown, the initial survey that did not make it in 1.8 because of bad code
* 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup, refactoring and rewriting large parts of the code
* 	@version $Id: survey_list.php 10680 2007-01-11 21:26:23Z pcool $
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

// Database table definitions
$table_survey 					= Database :: get_course_table(TABLE_SURVEY);
$table_survey_question 			= Database :: get_course_table(TABLE_SURVEY_QUESTION);
$table_survey_question_option 	= Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
$table_course 					= Database :: get_main_table(TABLE_MAIN_COURSE);
$table_user 					= Database :: get_main_table(TABLE_MAIN_USER);

// getting the survey information
$survey_data = survey_manager::get_survey($_GET['survey_id']);
$urlname = substr(strip_tags($survey_data['title']), 0, 40);
if (strlen(strip_tags($survey_data['title'])) > 40)
{
	$urlname .= '...';
}

// breadcrumbs
$interbreadcrumb[] = array ("url" => 'survey_list.php', 'name' => get_lang('SurveyList'));
$interbreadcrumb[] = array ("url" => "survey.php?survey_id=".$_GET['survey_id'], "name" => $urlname);

// Header
Display :: display_header(get_lang('SurveyPreview'));

// only a course admin is allowed to preview a survey: you are NOT a course admin => error message
if (!api_is_allowed_to_edit())
{
	Display :: display_error_message(get_lang('NotAllowedHere'), false);
}
// only a course admin is allowed to preview a survey: you are a course admin
else
{
	if (!isset($_GET['survey_id']) OR !is_numeric($_GET['survey_id']))
	{
		Display :: display_error_message(get_lang('InvallidSurvey'), false);
	}

	// survey information
	echo '<div id="survey_title">'.$survey_data['survey_title'].'</div>';
	echo '<div id="survey_subtitle">'.$survey_data['survey_subtitle'].'</div>';

	// displaying the survey introduction
	if (!isset($_GET['show']))
	{
		echo '<div id="survey_content" class="survey_content">'.$survey_data['survey_introduction'].'</div>';
		$limit = 0;
	}

	// displaying the survey thanks message
	if ($_POST['finish_survey'])
	{
		echo '<div id="survey_content" class="survey_content"><strong>'.get_lang('SurveyFinished').'</strong>'.$survey_data['survey_thanks'].'</div>';
		Display :: display_footer();
		exit;
	}

	if (isset($_GET['show']))
	{
		// Getting all the questions for this page
		$sql = "SELECT 	survey_question.question_id, survey_question.survey_id, survey_question.survey_question, survey_question.display, survey_question.sort, survey_question.type,
						survey_question_option.question_option_id, survey_question_option.option_text, survey_question_option.sort as option_sort
				FROM $table_survey_question survey_question
				LEFT JOIN $table_survey_question_option survey_question_option
				ON survey_question.question_id = survey_question_option.question_id
				WHERE survey_question.survey_id = '".mysql_real_escape_string($_GET['survey_id'])."'
				ORDER BY survey_question.sort ASC";
		if ($_GET['show'])
		{
			$sql .= ' LIMIT '.($_GET['show']+1).',1000';
		}
		$result = api_sql_query($sql, __FILE__, __LINE__);
		$question_counter_max = mysql_num_rows($result);
		$counter = 0;
		$limit=0;
		while ($row = mysql_fetch_assoc($result))
		{
			// if the type is not a pagebreak we store it in the $questions array
			if($row['type'] <> 'pagebreak')
			{
				$questions[$row['sort']]['question_id'] = $row['question_id'];
				$questions[$row['sort']]['survey_id'] = $row['survey_id'];
				$questions[$row['sort']]['survey_question'] = $row['survey_question'];
				$questions[$row['sort']]['display'] = $row['display'];
				$questions[$row['sort']]['type'] = $row['type'];
				$questions[$row['sort']]['options'][$row['option_sort']] = $row['option_text'];
			}
			// if the type is a pagebreak we are finished loading the questions for this page
			else
			{
				$limit = $counter;
				break;
			}
			$counter++;
		}
	}

	// Displaying the form with the questions
	echo '<form id="question" name="question" method="post" action="'.$_SERVER['PHP_SELF'].'?survey_id='.$_GET['survey_id'].'&show='.$limit.'">';
	foreach ($questions as $key=>$question)
	{
		$display = new $question['type'];
		$display->render_question($question);
	}
	if (($limit AND $limit <> $question_counter_max) OR !$_GET['show'])
	{
		//echo '<a href="'.$_SERVER['PHP_SELF'].'?survey_id='.$_GET['survey_id'].'&amp;show='.$limit.'">NEXT</a>';
		echo '<br /><input type="submit" name="next_survey_page" value="'.get_lang('Next').' >> " />';
	}
	if ($limit==0 AND $_GET['show'])
	{
		echo '<input type="submit" name="finish_survey" value="'.get_lang('FinishSurvey').' >> " />';
	}
	echo '</form>';
}

// Footer
Display :: display_footer();
?>