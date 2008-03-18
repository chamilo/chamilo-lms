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
* 	@todo check if the user already filled the survey and if this is the case then the answers have to be updated and not stored again.
* 			alterantively we could not allow people from filling the survey twice.
* 	@todo performance could be improved if not the survey_id was stored with the invitation but the survey_code
*/

// name of the language file that needs to be included
$language_file = 'survey';

// unsetting the course id (because it is in the URL)
$cidReset = true;

// including the global dokeos file
require ('../inc/global.inc.php');

// including additional libraries
//require_once (api_get_path(LIBRARY_PATH)."/survey.lib.php");
require_once('survey.lib.php');
require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");

// getting all the course information
$_course = CourseManager::get_course_information($_GET['course']);

// Database table definitions
$table_survey 					= Database :: get_course_table(TABLE_SURVEY, $_course['db_name']);
$table_survey_question 			= Database :: get_course_table(TABLE_SURVEY_QUESTION, $_course['db_name']);
$table_survey_question_option 	= Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION, $_course['db_name']);
$table_course 					= Database :: get_main_table(TABLE_MAIN_COURSE);
$table_user 					= Database :: get_main_table(TABLE_MAIN_USER);
$table_survey_invitation 		= Database :: get_course_table(TABLE_SURVEY_INVITATION, $_course['db_name']);

// breadcrumbs
// $interbreadcrumb[] = array ("url" => 'survey_list.php', 'name' => get_lang('SurveyList'));

// Header
Display :: display_header(get_lang('Survey'));

// first we check if the needed parameters are present
if (!isset($_GET['course']) OR !isset($_GET['invitationcode']))
{
	Display :: display_error_message(get_lang('SurveyParametersMissingUseCopyPaste'), false);
	Display :: display_footer();
	exit;
}

// now we check if the invitationcode is valid
$sql = "SELECT * FROM $table_survey_invitation WHERE invitation_code = '".Database::escape_string($_GET['invitationcode'])."'";
$result = api_sql_query($sql, __FILE__, __LINE__);
if (mysql_num_rows($result) < 1)
{
	Display :: display_error_message(get_lang('WrongInvitationCode'), false);
	Display :: display_footer();
	exit;
}
$survey_invitation = mysql_fetch_assoc($result);

// now we check if the user already filled the survey
if ($survey_invitation['answered'] == 1)
{
	Display :: display_error_message(get_lang('YouAlreadyFilledThisSurvey'), false);
	Display :: display_footer();
	exit;
}

// checking if there is another survey with this code.
// If this is the case there will be a language choice
$sql = "SELECT * FROM $table_survey WHERE code='".Database::escape_string($survey_invitation['survey_code'])."'";
$result = api_sql_query($sql, __FILE__, __LINE__);
if (mysql_num_rows($result) > 1)
{
	if ($_POST['language'])
	{
		$survey_invitation['survey_id'] = $_POST['language'];
	}
	else
	{
		echo '<form id="language" name="language" method="POST" action="'.api_get_self().'?course='.$_GET['course'].'&invitationcode='.$_GET['invitationcode'].'">';
		echo '  <select name="language">';
		while ($row=mysql_fetch_assoc($result))
		{
			echo '<option value="'.$row['survey_id'].'">'.$row['lang'].'</option>';
		}
		echo '</select>';
		echo '  <input type="submit" name="Submit" value="'.get_lang('Ok').'" />';
		echo '</form>';
		display::display_footer();
		exit;
	}
}
else
{
	$row=mysql_fetch_assoc($result);
	$survey_invitation['survey_id'] = $row['survey_id'];
}

// getting the survey information
$survey_data = survey_manager::get_survey($survey_invitation['survey_id']);
$survey_data['survey_id'] = $survey_invitation['survey_id'];

// storing the answers
if ($_POST)
{
	// getting all the types of the question (because of the special treatment of the score question type
	$sql = "SELECT * FROM $table_survey_question WHERE survey_id = '".Database::escape_string($survey_invitation['survey_id'])."'";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	while ($row = mysql_fetch_assoc($result))
	{
		$types[$row['question_id']] = $row['type'];
	}


	// looping through all the post values
	foreach ($_POST as $key=>$value)
	{
		// if the post value key contains the string 'question' then it is an answer on a question
		if (strstr($key,'question'))
		{
			// finding the question id by removing 'question'
			$survey_question_id = str_replace('question', '',$key);

			// if the post value is an array then we have a multiple response question or a scoring question type
			// remark: when it is a multiple response then the value of the array is the option_id
			// 		   when it is a scoring question then the key of the array is the option_id and the value is the value
			if (is_array($value))
			{
				SurveyUtil::remove_answer($survey_invitation['user'], $survey_invitation['survey_id'], $survey_question_id);
				foreach ($value as $answer_key => $answer_value)
				{
					if ($types[$survey_question_id] == 'score')
					{
						$option_id = $answer_key;
						$option_value = $answer_value;
					}
					else
					{
						$option_id = $answer_value;
						$option_value = '';
					}
					SurveyUtil::store_answer($survey_invitation['user'], $survey_invitation['survey_id'], $survey_question_id, $option_id, $option_value, $survey_data);
				}
			}
			// all the other question types (open question, multiple choice, percentage, ...)
			else
			{
				if ($types[$survey_question_id] == 'percentage')
				{
					$sql = "SELECT * FROM $table_survey_question_option WHERE question_option_id='".Database::escape_string($value)."'";
					$result = api_sql_query($sql, __FILE__, __LINE__);
					$row = mysql_fetch_assoc($result);
					$option_value = $row['option_text'];
				}
				else
				{
					$option_value = 0;
					if($types[$survey_question_id] == 'open')
					{
						$option_value = $value;
						//$value = 0;
					}
				}


				$survey_question_answer = $value;
				SurveyUtil::remove_answer($survey_invitation['user'], $survey_invitation['survey_id'], $survey_question_id);
				SurveyUtil::store_answer($survey_invitation['user'], $survey_invitation['survey_id'], $survey_question_id, $value, $option_value, $survey_data);
				//SurveyUtil::store_answer($user,$survey_id,$question_id, $option_id, $option_value, $survey_data);
			}
		}
	}
}

// displaying the survey title and subtitle (appears on every page)
echo '<div id="survey_title">'.$survey_data['survey_title'].'</div>';
echo '<div id="survey_subtitle">'.$survey_data['survey_subtitle'].'</div>';

// checking time availability
$start_date = mktime(0,0,0,substr($survey_data['start_date'],5,2),substr($survey_data['start_date'],8,2),substr($survey_data['start_date'],0,4));
$end_date = mktime(0,0,0,substr($survey_data['end_date'],5,2),substr($survey_data['end_date'],8,2),substr($survey_data['end_date'],0,4));
$cur_date = time();
if($cur_date < $start_date)
{
	Display :: display_warning_message(get_lang('SurveyNotAvailableYet'), false);
	Display :: display_footer();
	exit;
}
if($cur_date > $end_date)
{
	Display :: display_warning_message(get_lang('SurveyNotAvailableAnymore'), false);
	Display :: display_footer();
	exit;
}

// displaying the survey introduction
if (!isset($_GET['show']))
{
	echo '<div id="survey_content" class="survey_content">'.$survey_data['survey_introduction'].'</div>';
	$limit = 0;
}

// displaying the survey thanks message
if ($_POST['finish_survey'])
{
	echo '<div id="survey_content" class="survey_content"><strong>'.get_lang('SurveyFinished').'</strong> <br />'.$survey_data['survey_thanks'].'</div>';
	survey_manager::update_survey_answered($survey_data['survey_id'], $survey_invitation['user'], $survey_invitation['survey_code']);
	Display :: display_footer();
	exit;
}

if (isset($_GET['show']))
{
	// Getting all the questions for this page and add them to a multidimensional array where the first index is the page.
	// as long as there is no pagebreak fount we keep adding questions to the page
	$questions_displayed = array();
	$counter = 0;
	$sql = "SELECT * FROM $table_survey_question
			WHERE survey_id = '".Database::escape_string($survey_invitation['survey_id'])."'
			ORDER BY sort ASC";
	$result = api_sql_query($sql, __FILE__, __LINE__);

	while ($row = mysql_fetch_assoc($result))
	{
		if($row['type'] == 'pagebreak')
		{
			$counter++;
		}
		else
		{
			$paged_questions[$counter][] = $row['question_id'];
		}
	}

	if (key_exists($_GET['show'],$paged_questions))
	{
		$sql = "SELECT 	survey_question.question_id, survey_question.survey_id, survey_question.survey_question, survey_question.display, survey_question.sort, survey_question.type, survey_question.max_value,
						survey_question_option.question_option_id, survey_question_option.option_text, survey_question_option.sort as option_sort
				FROM $table_survey_question survey_question
				LEFT JOIN $table_survey_question_option survey_question_option
				ON survey_question.question_id = survey_question_option.question_id
				WHERE survey_question.survey_id = '".Database::escape_string($survey_invitation['survey_id'])."'
				AND survey_question.question_id IN (".implode(',',$paged_questions[$_GET['show']]).")
				ORDER BY survey_question.sort, survey_question_option.sort ASC";

		$result = api_sql_query($sql, __FILE__, __LINE__);
		$question_counter_max = mysql_num_rows($result);
		$counter = 0;
		$limit=0;
		$questions = array();
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
				$questions[$row['sort']]['options'][$row['question_option_id']] = $row['option_text'];
				$questions[$row['sort']]['maximum_score'] = $row['max_value'];
			}
			// if the type is a pagebreak we are finished loading the questions for this page
			else
			{
				break;
			}
			$counter++;
		}
	}
}

// selecting the maximum number of pages
$sql = "SELECT * FROM $table_survey_question WHERE type='".Database::escape_string('pagebreak')."' AND survey_id='".Database::escape_string($survey_invitation['survey_id'])."'";
$result = api_sql_query($sql, __FILE__, __LINE__);
$numberofpages = mysql_num_rows($result) + 1;
	// Displaying the form with the questions
if (isset($_GET['show']))
{
	$show = (int)$_GET['show'] + 1;
}
else
{
	$show = 0;
}


// Displaying the form with the questions
echo '<form id="question" name="question" method="post" action="'.api_get_self().'?course='.$_GET['course'].'&invitationcode='.$_GET['invitationcode'].'&show='.$show.'">';
echo '<input type="hidden" name="language" value="'.$_POST['language'].'" />';
if(is_array($questions)){
	foreach ($questions as $key=>$question)
	{
		$display = new $question['type'];
		$display->render_question($question);
	}
}
if (($show < $numberofpages) OR !$_GET['show'])
{
	//echo '<a href="'.api_get_self().'?survey_id='.$survey_invitation['survey_id'].'&amp;show='.$limit.'">NEXT</a>';
	echo '<input type="submit" name="next_survey_page" value="'.get_lang('Next').' >> " />';
}
if ($show >= $numberofpages AND $_GET['show'])
{
	echo '<input type="submit" name="finish_survey" value="'.get_lang('FinishSurvey').' >> " />';
}
echo '</form>';

// Footer
Display :: display_footer();
?>
