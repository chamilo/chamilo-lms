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
* 	@version $Id: reporting.php 13587 2007-10-29 16:17:50Z elixir_inter $
*
* 	@todo The question has to be more clearly indicated (same style as when filling the survey)
*/

// name of the language file that needs to be included
$language_file = 'survey';

// including the global dokeos file
require ('../inc/global.inc.php');

// export
/**
 * @todo use export_table_csv($data, $filename = 'export')
 */
if ($_POST['export_report'])
{
			$data = export_complete_report();
			$filename = 'fileexport.csv';

			header('Content-type: application/octet-stream');
			header('Content-Type: application/force-download');
			header('Content-length: '.$len);
			if (preg_match("/MSIE 5.5/", $_SERVER['HTTP_USER_AGENT']))
			{
				header('Content-Disposition: filename= '.$filename);
			}
			else
			{
				header('Content-Disposition: attachment; filename= '.$filename);
			}
			if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE'))
			{
				header('Pragma: ');
				header('Cache-Control: ');
				header('Cache-Control: public'); // IE cannot download from sessions without a cache
			}
			header('Content-Description: '.$filename);
			header('Content-transfer-encoding: binary');

			echo $data;
			exit;

}

// including additional libraries
//require_once (api_get_path(LIBRARY_PATH)."/survey.lib.php");
require_once('survey.lib.php');
require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");

// Checking the parameters
check_parameters();

/** @todo this has to be moved to a more appropriate place (after the display_header of the code)*/
if (!api_is_allowed_to_edit())
{
	Display :: display_header();
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

// getting the survey information
$survey_data = survey_manager::get_survey($_GET['survey_id']);
$urlname = substr(strip_tags($survey_data['title']), 0, 40);
if (strlen(strip_tags($survey_data['title'])) > 40)
{
	$urlname .= '...';
}

// breadcrumbs
$interbreadcrumb[] = array ("url" => "survey_list.php", "name" => get_lang('SurveyList'));
$interbreadcrumb[] = array ('url' => 'survey.php?survey_id='.$_GET['survey_id'], 'name' => $urlname);
if (!$_GET['action'] OR $_GET['action'] == 'overview')
{
	$tool_name = get_lang('Reporting');
}
else
{
	$interbreadcrumb[] = array ("url" => "reporting.php?survey_id=".$_GET['survey_id'], "name" => get_lang('Reporting'));
	switch ($_GET['action'])
	{
		case 'questionreport':
			$tool_name = get_lang('DetailedReportByQuestion');
			break;
		case 'userreport':
			$tool_name = get_lang('DetailedReportByUser');
			break;
		case 'comparativereport':
			$tool_name = get_lang('ComparativeReport');
			break;
		case 'completereport':
			$tool_name = get_lang('CompleteReport');
			break;
	}
}

// Displaying the header
Display::display_header($tool_name);

// Action handling
handle_reporting_actions();

if (!$_GET['action'] OR $_GET['action'] == 'overview')
{
	echo '<b><a href="reporting.php?action=questionreport&amp;survey_id='.$_GET['survey_id'].'">'.get_lang('DetailedReportByQuestion').'</a></b> <br />'.get_lang('DetailedReportByQuestionDetail').' <br /><br />';
	echo '<b><a href="reporting.php?action=userreport&amp;survey_id='.$_GET['survey_id'].'">'.get_lang('DetailedReportByUser').'</a></b><br />'.get_lang('DetailedReportByUserDetail').'.<br /><br />';
	echo '<b><a href="reporting.php?action=comparativereport&amp;survey_id='.$_GET['survey_id'].'">'.get_lang('ComparativeReport').'</a></b><br />'.get_lang('ComparativeReportDetail').'.<br /><br />';
	echo '<b><a href="reporting.php?action=completereport&amp;survey_id='.$_GET['survey_id'].'">'.get_lang('CompleteReport').'</a></b><br />'.get_lang('CompleteReportDetail').'<br /><br />';
}

// Footer
Display :: display_footer();





/**
 * This function checks the parameters that are used in this page
 *
 *  @return the header, an error and the footer if any parameter fails, else it returns true
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version February 2007
 */
function check_parameters()
{
	$error = false;
	
	// getting the survey data
	$survey_data = survey_manager::get_survey($_GET['survey_id']);

	// $_GET['survey_id'] has to be numeric
	if (!is_numeric($_GET['survey_id']))
	{
		$error = get_lang('IllegalSurveyId');
	}

	// $_GET['action']
	$allowed_actions = array('overview', 'questionreport', 'userreport', 'comparativereport', 'completereport');
	if (isset($_GET['action']) AND !in_array($_GET['action'], $allowed_actions))
	{
		$error = get_lang('ActionNotAllowed');
	}

	// user report
	if ($_GET['action'] == 'userreport')
	{
		global $people_filled;
		if ($survey_data['anonymous'] == 0)
		{
			$people_filled_full_data = true;
		}
		else 
		{
			$people_filled_full_data = false;
		}
		$people_filled = survey_manager::get_people_who_filled_survey($_GET['survey_id'], $people_filled_full_data);
		if ($survey_data['anonymous'] == 0)
		{
			foreach ($people_filled as $key=>$value)
			{
				$people_filled_userids[]=$value['user_id'];
			}
		}
		else 
		{
			$people_filled_userids = $people_filled;
		}		
		
		if (isset($_GET['user']) AND !in_array($_GET['user'], $people_filled_userids))
		{
			$error = get_lang('UnknowUser');
		}
	}

	// question report
	if ($_GET['action'] == 'questionreport')
	{
		if (isset($_GET['question'])AND !is_numeric($_GET['question']))
		{
			$error = get_lang('UnknowQuestion');
		}
	}

	if ($error)
	{
		Display::display_header();
		Display::display_error_message(get_lang('Error').': '.$error, false);
		Display::display_footer();
		exit;
	}
	else
	{
		return true;
	}
}

/**
 * This function deals with the action handling
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version February 2007
 */
function handle_reporting_actions()
{
	// getting the number of question
	$temp_questions_data = survey_manager::get_questions($_GET['survey_id']);

	// sorting like they should be displayed and removing the non-answer question types (comment and pagebreak)
	foreach ($temp_questions_data as $key=>$value)
	{
		if ($value['type'] <> 'comment' AND $value['type']<>'pagebreak')
		{
			$questions_data[$value['sort']]=$value;
		}
	}

	// counting the number of questions that are relevant for the reporting
	$survey_data['number_of_questions'] = count($questions_data);

	if ($_GET['action'] == 'questionreport')
	{
		display_question_report($survey_data);
	}
	if ($_GET['action'] == 'userreport')
	{
		display_user_report();
	}
	if ($_GET['action'] == 'comparativereport')
	{
		display_comparative_report();
	}
	if ($_GET['action'] == 'completereport')
	{
		display_complete_report();
	}
}

/**
 * This function displays the user report which is basically nothing more than a one-page display of all the questions
 * of the survey that is filled with the answers of the person who filled the survey.
 *
 * @return html code of the one-page survey with the answers of the selected user
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version February 2007
 */
function display_user_report()
{
	global $people_filled, $survey_data;
	
	// Database table definitions
	$table_survey_question 			= Database :: get_course_table(TABLE_SURVEY_QUESTION);
	$table_survey_question_option 	= Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
	$table_survey_answer 			= Database :: get_course_table(TABLE_SURVEY_ANSWER);

	// step 1: selection of the user
	echo "<script language=\"JavaScript\" type=\"text/JavaScript\">
	<!--
	function jumpMenu(targ,selObj,restore)
	{
	  eval(targ+\".location='\"+selObj.options[selObj.selectedIndex].value+\"'\");
	  if (restore) selObj.selectedIndex=0;
	}
	//-->
	</script>
	";
	echo get_lang('SelectUserWhoFilledSurvey').'<br />';
	echo '<select name="user" onchange="jumpMenu(\'parent\',this,0)">';
	echo '<option value="reporting.php?action='.$_GET['action'].'&amp;survey_id='.$_GET['survey_id'].'">'.get_lang('SelectUser').'</option>';
	
	foreach ($people_filled as $key=>$person)
	{
		if ($survey_data['anonymous'] == 0)
		{
			$name = $person['firstname'].' '.$person['lastname'];
			$id = $person['user_id'];
		}
		else 
		{
			$name  = $person;
			$id = $person;
		}
		echo '<option value="reporting.php?action='.$_GET['action'].'&amp;survey_id='.$_GET['survey_id'].'&amp;user='.$id.'" ';
		if ($_GET['user'] == $person)
		{
			echo 'selected="selected"';
		}
		echo '>'.$name.'</option>';
	}
	echo '</select>';

	// step 2: displaying the survey and the answer of the selected users
	if (isset($_GET['user']))
	{
		Display::display_normal_message(get_lang('AllQuestionsOnOnePage'), false);

		// getting all the questions and options
		$sql = "SELECT 	survey_question.question_id, survey_question.survey_id, survey_question.survey_question, survey_question.display, survey_question.max_value, survey_question.sort, survey_question.type,
						survey_question_option.question_option_id, survey_question_option.option_text, survey_question_option.sort as option_sort
				FROM $table_survey_question survey_question
				LEFT JOIN $table_survey_question_option survey_question_option
				ON survey_question.question_id = survey_question_option.question_id
				WHERE survey_question.survey_id = '".Database::escape_string($_GET['survey_id'])."'
				ORDER BY survey_question.sort ASC";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($row = mysql_fetch_assoc($result))
		{
			if($row['type'] <> 'pagebreak')
			{
				$questions[$row['sort']]['question_id'] 						= $row['question_id'];
				$questions[$row['sort']]['survey_id'] 							= $row['survey_id'];
				$questions[$row['sort']]['survey_question'] 					= $row['survey_question'];
				$questions[$row['sort']]['display'] 							= $row['display'];
				$questions[$row['sort']]['type'] 								= $row['type'];
				$questions[$row['sort']]['maximum_score'] 						= $row['max_value'];
				$questions[$row['sort']]['options'][$row['question_option_id']] = $row['option_text'];
			}
		}

		// getting all the answers of the user
		$sql = "SELECT * FROM $table_survey_answer WHERE survey_id = '".Database::escape_string($_GET['survey_id'])."' AND user = '".Database::escape_string($_GET['user'])."'";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($row = mysql_fetch_assoc($result))
		{
			$answers[$row['question_id']][] = $row['option_id'];
			$all_answers[$row['question_id']][] = $row;
		}
		/*
		echo '<pre>';
		print_r($all_answers);
		echo '</pre>';
		*/
		
		// displaying all the questions
		foreach ($questions as $key=>$question)
		{
			// if the question type is a scoring then we have to format the answers differently
			if ($question['type'] == 'score')
			{
				foreach($all_answers[$question['question_id']] as $key=>$answer_array)
				{
					$second_parameter[$answer_array['option_id']] = $answer_array['value'];
				}
			}
			else
			{
				$second_parameter = $answers[$question['question_id']];
				if ($question['type'] == 'open')
				{
					$second_parameter = array();
					$second_parameter[] = $all_answers[$question['question_id']][0]['option_id'];
				}
			}
			$display = new $question['type'];
			$display->render_question($question, $second_parameter);
//			echo '<pre>';
	//		print_r($answers[$question['question_id']]);
		//	echo '</pre>';
		}
	}
}

/**
 * This function displays the report by question.
 * It displays a table with all the options of the question and the number of users who have answered positively on the option.
 * The number of users who answered positive on a given option is expressed in an absolute number, in a percentage of the total
 * and graphically using bars
 * By clicking on the absolute number you get a list with the persons who have answered this.
 * You can then click on the name of the person and you will then go to the report by user where you see all the
 * answers of that user.
 *
 * @param array $survey_data all the data of the survey
 *
 * @return html code that displays the report by question
 *
 * @todo allow switching between horizontal and vertical.
 * @todo multiple response: percentage are probably not OK
 * @todo the question and option text have to be shortened and should expand when the user clicks on it.
 * @todo the pagebreak and comment question types should not be shown => removed from $survey_data before
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version February 2007
 */
function display_question_report($survey_data)
{
	// Database table definitions
	$table_survey_question 			= Database :: get_course_table(TABLE_SURVEY_QUESTION);
	$table_survey_question_option 	= Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
	$table_survey_answer 			= Database :: get_course_table(TABLE_SURVEY_ANSWER);

	// determining the offset of the sql statement (the n-th question of the survey)
	if (!isset($_GET['question']))
	{
		$offset = 0;
	}
	else
	{
		$offset = $_GET['question'];
	}

	echo '<div id="question_report_questionnumbers">';
	for($i=1; $i<=($survey_data['number_of_questions']); $i++ )
	{
		if ($offset <> $i-1)
		{
			echo '<a href="reporting.php?action=questionreport&amp;survey_id='.(int)$_GET['survey_id'].'&amp;question='.($i-1).'">'.$i.'</a>';
		}
		else
		{
			echo $i;
		}
		if ($i < $survey_data['number_of_questions'])
		{
			echo ' | ';
		}
	}
	echo '</div>';

	// getting the question information
	$sql = "SELECT * FROM $table_survey_question WHERE survey_id='".Database::escape_string($_GET['survey_id'])."' AND type<>'pagebreak' AND type<>'comment' ORDER BY sort ASC LIMIT ".$offset.",1";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	$question = mysql_fetch_assoc($result);

	// navigate through the questions (next and previous)
	if ($_GET['question'] <> 0)
	{
		echo '<a href="reporting.php?action='.$_GET['action'].'&amp;survey_id='.$_GET['survey_id'].'&amp;question='.($offset-1).'"> &lt;&lt; '.get_lang('PreviousQuestion').'</a>  ';
	}
	else
	{
		echo '&lt;&lt;'.get_lang('PreviousQuestion').' ';
	}
	echo ' | ';
	if ($_GET['question'] < ($survey_data['number_of_questions']-1))
	{
		echo '<a href="reporting.php?action='.$_GET['action'].'&amp;survey_id='.$_GET['survey_id'].'&amp;question='.($offset+1).'">'.get_lang('NextQuestion').'&gt;&gt; </a>';
	}
	else
	{
		echo get_lang('NextQuestion'). '&gt;&gt;';
	}
	echo '<br />';

	echo $question['survey_question'];

	echo '<br />';

	if ($question['type'] == 'score')
	{
		/** @todo this function should return the options as this is needed further in the code */
		$options = display_question_report_score($survey_data, $question, $offset);
	}
	elseif ($question['type'] == 'open')
	{
		/** @todo also get the user who has answered this */
		$sql = "SELECT * FROM $table_survey_answer WHERE survey_id='".Database::escape_string($_GET['survey_id'])."'
					AND question_id = '".Database::escape_string($question['question_id'])."'";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($row = mysql_fetch_assoc($result))
		{
			echo $row['option_id'].'<hr noshade="noshade" size="1" />';
		}

	}
	else
	{
		// getting the options
		$sql = "SELECT * FROM $table_survey_question_option
					WHERE survey_id='".Database::escape_string($_GET['survey_id'])."'
					AND question_id = '".Database::escape_string($question['question_id'])."'
					ORDER BY sort ASC";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($row = mysql_fetch_assoc($result))
		{
			$options[$row['question_option_id']] = $row;
		}
		//echo '<pre>';
		//print_r($options);
		//echo '<pre>';

		// getting the answers
		$sql = "SELECT *, count(answer_id) as total FROM $table_survey_answer
					WHERE survey_id='".Database::escape_string($_GET['survey_id'])."'
					AND question_id = '".Database::escape_string($question['question_id'])."'
					GROUP BY option_id, value";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($row = mysql_fetch_assoc($result))
		{
			$number_of_answers += $row['total'];
			$data[$row['option_id']] = $row;
		}
		//echo '<pre>';
		//print_r($data);
		//echo '<pre>';

		// displaying the table: headers
		echo '<table>';
		echo '	<tr>';
		echo '		<th>&nbsp;</th>';
		echo '		<th>'.get_lang('AbsoluteTotal').'</th>';
		echo '		<th>'.get_lang('Percentage').'</th>';
		echo '		<th>'.get_lang('VisualRepresentation').'</th>';
		echo '	<tr>';


		// displaying the table: the content
		foreach ($options as $key=>$value)
		{
			$absolute_number = $data[$value['question_option_id']]['total'];

			echo '	<tr>';
			echo '		<td>'.$value['option_text'].'</td>';
			echo '		<td><a href="reporting.php?action='.$_GET['action'].'&amp;survey_id='.$_GET['survey_id'].'&amp;question='.$offset.'&amp;viewoption='.$value['question_option_id'].'">'.$absolute_number.'</a></td>';
			echo '		<td>'.round($absolute_number/$number_of_answers*100, 2).' %</td>';
			echo '		<td>';
			$size = $absolute_number/$number_of_answers*100*2;
			if ($size > 0)
			{
				echo '<div style="border:1px solid #264269; background-color:#aecaf4; height:10px; width:'.$size.'px">&nbsp;</div>';
			}
			echo '		</td>';
			echo '	</tr>';
		}

		// displaying the table: footer (totals)
		echo '	<tr>';
		echo '		<td style="border-top:1px solid black"><b>'.get_lang('Total').'</b></td>';
		echo '		<td style="border-top:1px solid black"><b>'.$number_of_answers.'</b></td>';
		echo '		<td style="border-top:1px solid black">&nbsp;</td>';
		echo '		<td style="border-top:1px solid black">&nbsp;</td>';
		echo '	</tr>';

		echo '</table>';
	}

	if (isset($_GET['viewoption']))
	{
		echo get_lang('PeopleWhoAnswered').': '.$options[$_GET['viewoption']]['option_text'].'<br />';

		if (is_numeric($_GET['value']))
		{
			$sql_restriction = "AND value='".Database::escape_string($_GET['value'])."'";
		}

		$sql = "SELECT user FROM $table_survey_answer WHERE option_id = '".Database::escape_string($_GET['viewoption'])."' $sql_restriction";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($row = mysql_fetch_assoc($result))
		{
			echo '<a href="reporting.php?action=userreport&survey_id='.$_GET['survey_id'].'&user='.$row['user'].'">'.$row['user'].'</a><br />';
		}
	}
}

function display_question_report_score($survey_data, $question, $offset)
{
	// Database table definitions
	$table_survey_question 			= Database :: get_course_table(TABLE_SURVEY_QUESTION);
	$table_survey_question_option 	= Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
	$table_survey_answer 			= Database :: get_course_table(TABLE_SURVEY_ANSWER);

	// getting the options
	$sql = "SELECT * FROM $table_survey_question_option
				WHERE survey_id='".Database::escape_string($_GET['survey_id'])."'
				AND question_id = '".Database::escape_string($question['question_id'])."'
				ORDER BY sort ASC";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	while ($row = mysql_fetch_assoc($result))
	{
		$options[$row['question_option_id']] = $row;
	}

	// getting the answers
	$sql = "SELECT *, count(answer_id) as total FROM $table_survey_answer
				WHERE survey_id='".Database::escape_string($_GET['survey_id'])."'
				AND question_id = '".Database::escape_string($question['question_id'])."'
				GROUP BY option_id, value";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	while ($row = mysql_fetch_assoc($result))
	{
		$number_of_answers += $row['total'];
		$data[$row['option_id']][$row['value']] = $row;
	}

	/*
	echo '<pre>';
	print_r($data);
	echo '</pre>';
	*/

	// displaying the table: headers
	echo '<table>';
	echo '	<tr>';
	echo '		<th>&nbsp;</th>';
	echo '		<th>'.get_lang('Score').'</th>';
	echo '		<th>'.get_lang('AbsoluteTotal').'</th>';
	echo '		<th>'.get_lang('Percentage').'</th>';
	echo '		<th>'.get_lang('VisualRepresentation').'</th>';
	echo '	<tr>';


	// displaying the table: the content
	foreach ($options as $key=>$value)
	{
		for ($i=1; $i<=$question['max_value']; $i++)
		{
			$absolute_number = $data[$value['question_option_id']][$i]['total'];

			echo '	<tr>';
			echo '		<td>'.$value['option_text'].'</td>';
			echo '		<td>'.$i.'</td>';
			echo '		<td><a href="reporting.php?action='.$_GET['action'].'&amp;survey_id='.$_GET['survey_id'].'&amp;question='.$offset.'&amp;viewoption='.$value['question_option_id'].'&amp;value='.$i.'">'.$absolute_number.'</a></td>';
			echo '		<td>'.round($absolute_number/$number_of_answers*100, 2).' %</td>';
			echo '		<td>';
			$size = ($absolute_number/$number_of_answers*100*2);
			if ($size > 0)
			{
				echo '			<div style="border:1px solid #264269; background-color:#aecaf4; height:10px; width:'.$size.'px">&nbsp;</div>';
			}
			echo '		</td>';
			echo '	</tr>';
		}
	}
		// displaying the table: footer (totals)
		echo '	<tr>';
		echo '		<td style="border-top:1px solid black"><b>'.get_lang('Total').'</b></td>';
		echo '		<td style="border-top:1px solid black">&nbsp;</td>';
		echo '		<td style="border-top:1px solid black"><b>'.$number_of_answers.'</b></td>';
		echo '		<td style="border-top:1px solid black">&nbsp;</td>';
		echo '		<td style="border-top:1px solid black">&nbsp;</td>';
		echo '	</tr>';

		echo '</table>';
}

/**
 * This functions displays the complete reporting
 *
 * @return html code
 *
 * @todo open questions are not in the complete report yet.
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version February 2007
 */
function display_complete_report()
{
	// Database table definitions
	$table_survey_question 			= Database :: get_course_table(TABLE_SURVEY_QUESTION);
	$table_survey_question_option 	= Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
	$table_survey_answer 			= Database :: get_course_table(TABLE_SURVEY_ANSWER);

	// the form
	echo '<form id="form1" name="form1" method="post" action="'.api_get_self().'?action='.$_GET['action'].'&survey_id='.$_GET['survey_id'].'">';

	/*// the export button
	echo '<input type="submit" name="export_report" value="'.get_lang('ExportCurrentReport').'" />';*/
	
	echo '<input type="hidden" name="export_report" value="export_report">';
	
	echo '</form>';
	
	echo '<form id="form2" name="form2" method="post" action="'.api_get_self().'?action='.$_GET['action'].'&survey_id='.$_GET['survey_id'].'">';
	
	echo '<a href="#" onclick="document.form1.submit();"><img align="absbottom" src="'.api_get_path(WEB_IMG_PATH).'excel.gif">&nbsp;'.get_lang('ExportCurrentReport').'</a>';
	
	// the table
	echo '<table class="data_table" border="1">';

	// getting the number of options per question
	echo '	<tr>';
	echo '		<th>';
	if ($_POST['submit_question_filter'] OR $_POST['export_report'])
	{
		echo '			<input type="submit" name="reset_question_filter" value="'.get_lang('ResetQuestionFilter').'" />';
	}
	echo '			<input type="submit" name="submit_question_filter" value="'.get_lang('SubmitQuestionFilter').'" />';
	echo '</th>';
	$sql = "SELECT questions.question_id, questions.type, questions.survey_question, count(options.question_option_id) as number_of_options
			FROM $table_survey_question questions LEFT JOIN $table_survey_question_option options
			ON questions.question_id = options.question_id
			/*WHERE questions.question_id = options.question_id*/
			AND questions.survey_id = '".Database::escape_string($_GET['survey_id'])."'
			GROUP BY questions.question_id";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	while ($row = mysql_fetch_assoc($result))
	{
		// we show the questions if
		// 1. there is no question filter and the export button has not been clicked
		// 2. there is a quesiton filter but the question is selected for display
		if (!($_POST['submit_question_filter']  OR $_POST['export_report']) OR in_array($row['question_id'], $_POST['questions_filter']))
		{
			// we do not show comment and pagebreak question types
			if ($row['type'] <> 'comment' AND $row['type'] <> 'pagebreak')
			{
				echo '		<th';
				if ($row['number_of_options'] >0)
				{
					echo ' colspan="'.$row['number_of_options'].'"';
				}
				echo '>';

				echo '<label><input type="checkbox" name="questions_filter[]" value="'.$row['question_id'].'" checked="checked"/> ';
				echo $row['survey_question'];
				echo '</label>';
				echo '</th>';
			}
		}
		$questions[$row['question_id']] = $row;
	}
	echo '	</tr>';

	// getting all the questions and options
	echo '	<tr>';
	echo '		<th>&nbsp;</th>'; // the user column
	$sql = "SELECT 	survey_question.question_id, survey_question.survey_id, survey_question.survey_question, survey_question.display, survey_question.sort, survey_question.type,
					survey_question_option.question_option_id, survey_question_option.option_text, survey_question_option.sort as option_sort
			FROM $table_survey_question survey_question
			LEFT JOIN $table_survey_question_option survey_question_option
			ON survey_question.question_id = survey_question_option.question_id
			WHERE survey_question.survey_id = '".Database::escape_string($_GET['survey_id'])."'
			ORDER BY survey_question.sort ASC";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	while ($row = mysql_fetch_assoc($result))
	{
		// we show the options if
		// 1. there is no question filter and the export button has not been clicked
		// 2. there is a quesiton filter but the question is selected for display
		if (!($_POST['submit_question_filter'] OR $_POST['export_report']) OR in_array($row['question_id'], $_POST['questions_filter']))
		{
			// we do not show comment and pagebreak question types
			if ($row['type'] <> 'comment' AND $row['type'] <> 'pagebreak')
			{
				echo '			<th>';
				echo $row['option_text'];
				echo '</th>';
				$possible_answers[$row['question_id']][$row['question_option_id']] =$row['question_option_id'];
			}
		}
	}
	echo '	</tr>';

	// getting all the answers of the users
	$old_user='';
	$answers_of_user = array();
	$sql = "SELECT * FROM $table_survey_answer WHERE survey_id='".Database::escape_string($_GET['survey_id'])."' ORDER BY user ASC";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	while ($row = mysql_fetch_assoc($result))
	{
		if ($old_user <> $row['user'] AND $old_user<>'')
		{
			display_complete_report_row($possible_answers, $answers_of_user, $old_user, $questions);
			$answers_of_user=array();
		}
		if ($questions[$row['question_id']]['type']<> 'open')
		{
			$answers_of_user[$row['question_id']][$row['option_id']] = $row;
		}
		else 
		{
			$answers_of_user[$row['question_id']][0] = $row;
		}
		$old_user = $row['user'];
	}
	display_complete_report_row($possible_answers, $answers_of_user, $old_user, $questions); // this is to display the last user

	echo '</table>';

	echo '</form>';
}


/**
 * This function displays a row (= a user and his/her answers) in the table of the complete report.
 *
 * @param array $possible_answers all the possible options
 * @param array $answers_of_user the answers of the user
 * @param string $user the user
 *
 * @todo rename $possible_answers to $possible_options ?
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version February 2007
 */
function display_complete_report_row($possible_answers, $answers_of_user, $user, $questions)
{
	$table_survey_question = Database :: get_course_table(TABLE_SURVEY_QUESTION);
	echo '<tr>';
	
	if(intval($user)!==0)
	{
		$sql = 'SELECT firstname, lastname FROM '.Database::get_main_table(TABLE_MAIN_USER).' WHERE user_id='.intval($user);
		$rs = api_sql_query($sql, __FILE__, __LINE__);
		if($row = mysql_fetch_array($rs, MYSQL_ASSOC))
		{
			$user_displayed = $row['lastname'].' '.$row['firstname'];
		}
		else
		{
			$user_displayed = '-';
		}
		echo '		<th><a href="'.api_get_self().'?action=userreport&survey_id='.$_GET['survey_id'].'&user='.$user.'">'.$user_displayed.'</a></th>'; // the user column
	}
	else
	{
		echo '		<th>'.$user.'</th>'; // the user column
	}
	

	foreach ($possible_answers as $question_id=>$possible_option)
	{
		if ($questions[$question_id]['type'] == 'open')
		{
			echo '<td align="center">';
			echo $answers_of_user[$question_id]['0']['option_id'];
			echo '</td>';
		}
		else 
		{		
			foreach ($possible_option as $option_id=>$value)
			{
				echo '<td align="center">';
				if (!empty($answers_of_user[$question_id][$option_id]))
				{
					if ($answers_of_user[$question_id][$option_id]['value']<>0)
					{
						echo $answers_of_user[$question_id][$option_id]['value'];
					}
					else
					{
						echo 'v';
					}
				}
			}
		}
		
	}
	echo '</tr>';
}


/**
 * the function is quite similar to display_complete_report and return a html string that can be used in a csv file
 *
 * @todo consider merging this function with display_complete_report
 *
 * @return string $return the content of a csv file
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version February 2007
 */
function export_complete_report()
{
	// Database table definitions
	$table_survey_question 			= Database :: get_course_table(TABLE_SURVEY_QUESTION);
	$table_survey_question_option 	= Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
	$table_survey_answer 			= Database :: get_course_table(TABLE_SURVEY_ANSWER);

	// the first column
	$return = ';';

	$sql = "SELECT questions.question_id, questions.type, questions.survey_question, count(options.question_option_id) as number_of_options
			FROM $table_survey_question questions LEFT JOIN $table_survey_question_option options
			ON questions.question_id = options.question_id
			/*WHERE questions.question_id = options.question_id*/
			AND questions.survey_id = '".Database::escape_string($_GET['survey_id'])."'
			GROUP BY questions.question_id";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	while ($row = mysql_fetch_assoc($result))
	{
		// we show the questions if
		// 1. there is no question filter and the export button has not been clicked
		// 2. there is a quesiton filter but the question is selected for display
		if (!($_POST['submit_question_filter']  OR $_POST['export_report']) OR in_array($row['question_id'], $_POST['questions_filter']))
		{
			// we do not show comment and pagebreak question types
			if ($row['type'] <> 'comment' AND $row['type'] <> 'pagebreak')
			{
				for ($ii = 0; $ii < $row['number_of_options']; $ii ++)
				{
					$return .= str_replace("\r\n",'  ',html_entity_decode(strip_tags($row['survey_question']))).';';
				}
			}
		}
	}
	$return .= "\n";

	// getting all the questions and options
	$return .= ';';
	$sql = "SELECT 	survey_question.question_id, survey_question.survey_id, survey_question.survey_question, survey_question.display, survey_question.sort, survey_question.type,
					survey_question_option.question_option_id, survey_question_option.option_text, survey_question_option.sort as option_sort
			FROM $table_survey_question survey_question
			LEFT JOIN $table_survey_question_option survey_question_option
			ON survey_question.question_id = survey_question_option.question_id
			WHERE survey_question.survey_id = '".Database::escape_string($_GET['survey_id'])."'
			ORDER BY survey_question.sort ASC";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	while ($row = mysql_fetch_assoc($result))
	{
		// we show the options if
		// 1. there is no question filter and the export button has not been clicked
		// 2. there is a quesiton filter but the question is selected for display
		if (!($_POST['submit_question_filter'] OR $_POST['export_report']) OR in_array($row['question_id'], $_POST['questions_filter']))
		{
			// we do not show comment and pagebreak question types
			if ($row['type'] <> 'comment' AND $row['type'] <> 'pagebreak')
			{
				$return .= html_entity_decode(strip_tags($row['option_text'])).';';
				$possible_answers[$row['question_id']][$row['question_option_id']] =$row['question_option_id'];
			}
		}
	}
	$return .= "\n";

	// getting all the answers of the users
	$old_user='';
	$answers_of_user = array();
	$sql = "SELECT * FROM $table_survey_answer WHERE survey_id='".Database::escape_string($_GET['survey_id'])."' ORDER BY user ASC";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	while ($row = mysql_fetch_assoc($result))
	{
		if ($old_user <> $row['user'] AND $old_user<>'')
		{
			$return .= export_complete_report_row($possible_answers, $answers_of_user, $old_user);
			$answers_of_user=array();
		}
		$answers_of_user[$row['question_id']][$row['option_id']] = $row;
		$old_user = $row['user'];
	}
	$return .= export_complete_report_row($possible_answers, $answers_of_user, $old_user); // this is to display the last user

	return $return;
}


/**
 * add a line to the csv file
 *
 * @param array $possible_answers all the possible answers
 * @param array $answers_of_user the answers of the user
 * @param string $user the user
 *
 * @return string $return line of the csv file
 *
 * @todo rename $possible_answers to $possible_options ?
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version February 2007
 */
function export_complete_report_row($possible_answers, $answers_of_user, $user)
{
	$return = $user.';'; // the user column

	foreach ($possible_answers as $question_id=>$possible_option)
	{
		foreach ($possible_option as $option_id=>$value)
		{
			if (!empty($answers_of_user[$question_id][$option_id]))
			{
				$return .= 'v';
			}
			$return .= ';';
		}
	}
	$return .= "\n";
	return $return;
}

/**
 * This function displays the comparative report which allows you to compare two questions
 * A comparative report creates a table where one question is on the x axis and a second question is on the y axis.
 * In the intersection is the number of people who have answerd positive on both options.
 *
 * @return html code
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version February 2007
 */
function display_comparative_report()
{
	// allowed question types for comparative report
	$allowed_question_types = array('yesno', 'multiplechoice', 'multipleresponse', 'dropdown', 'percentage', 'score');

	// getting all the questions
	$questions = survey_manager::get_questions($_GET['survey_id']);

	// displaying an information message that only the questions with predefined answers can be used in a comparative report
	Display::display_normal_message(get_lang('OnlyQuestionsWithPredefinedAnswers'), false);

	// The form for selecting the axis of the table
	echo '<form id="form1" name="form1" method="get" action="'.api_get_self().'?action='.$_GET['action'].'&survey_id='.$_GET['survey_id'].'&xaxis='.$_GET['xaxis'].'&y='.$_GET['yaxis'].'">';
	// survey_id
	echo '<input type="hidden" name="action" value="'.$_GET['action'].'"/>';
	echo '<input type="hidden" name="survey_id" value="'.(int)$_GET['survey_id'].'"/>';
	// X axis
	echo get_lang('SelectXAxis').': ';
	echo '<select name="xaxis">';
	echo '<option value="">---</option>';
	foreach ($questions as $key=>$question)
	{
		if (in_array($question['type'], $allowed_question_types))
		{
			echo '<option value="'.$question['question_id'].'"';
			if ($_GET['xaxis'] == $question['question_id'])
			{
				echo ' selected="selected"';
			}
			echo '">'.substr(strip_tags($question['question']), 0, 50).'</option>';
		}
	}
	echo '</select><br /><br />';
	// Y axis
	echo get_lang('SelectYAxis').': ';
	echo '<select name="yaxis">';
	echo '<option value="">---</option>';
	foreach ($questions as $key=>$question)
	{
		if (in_array($question['type'], $allowed_question_types))
		{
			echo '<option value="'.$question['question_id'].'"';
			if ($_GET['yaxis'] == $question['question_id'])
			{
				echo ' selected="selected"';
			}
			echo '">'.substr(strip_tags($question['question']), 0, 50).'</option>';
		}
	}
	echo '</select><br /><br />';
	echo '<input type="submit" name="Submit" value="Submit" />';
	echo '</form>';

	// getting all the information of the x axis
	if (isset($_GET['xaxis']) AND is_numeric($_GET['xaxis']))
	{
		$question_x = survey_manager::get_question($_GET['xaxis']);
	}

	// getting all the information of the y axis
	if (isset($_GET['yaxis']) AND is_numeric($_GET['yaxis']))
	{
		$question_y = survey_manager::get_question($_GET['yaxis']);
	}

	if (isset($_GET['xaxis']) AND is_numeric($_GET['xaxis']) AND isset($_GET['yaxis']) AND is_numeric($_GET['yaxis']))
	{
		// getting the answers of the two questions
		$answers_x = get_answers_of_question_by_user($_GET['survey_id'], $_GET['xaxis']);
		$answers_y = get_answers_of_question_by_user($_GET['survey_id'], $_GET['yaxis']);

		// displaying the table
		echo '<table border="1" class="data_table">';

		// the header
		echo '	<tr>';
		for ($ii=0; $ii<=count($question_x['answers']); $ii++)
		{
			if ($ii == 0)
			{
				echo '		<th>&nbsp;</th>';
			}
			else
			{
				if ($question_x['type']=='score')
				{
					for($x=1; $x<=$question_x['maximum_score']; $x++)
					{
						echo '		<th>'.$question_x['answers'][($ii-1)].'<br />'.$x.'</th>';
					}
					$x='';
				}
				else
				{
					echo '		<th>'.$question_x['answers'][($ii-1)].'</th>';
				}
			}
		}
		echo '	</tr>';

		// the main part
		for ($ij=0; $ij<count($question_y['answers']); $ij++)
		{
			// The Y axis is a scoring question type so we have more rows than the options (actually options * maximum score)
			if ($question_y['type'] == 'score')
			{
				for($y=1; $y<=$question_y['maximum_score']; $y++)
				{
					echo '	<tr>';
					for ($ii=0; $ii<=count($question_x['answers']); $ii++)
					{
						if ($question_x['type']=='score')
						{
							for($x=1; $x<=$question_x['maximum_score']; $x++)
							{
								if ($ii == 0)
								{
									echo '		<th>'.$question_y['answers'][($ij)].' '.$y.'</th>';
									break;
								}
								else
								{
									echo '		<td align="center">';
									echo comparative_check($answers_x, $answers_y, $question_x['answersid'][($ii-1)], $question_y['answersid'][($ij)], $x, $y);
									echo '</td>';
								}
							}
						}
						else
						{
							if ($ii == 0)
							{
								echo '		<th>'.$question_y['answers'][($ij)].' '.$y.'</th>';
							}
							else
							{
								echo '		<td align="center">';
								echo comparative_check($answers_x, $answers_y, $question_x['answersid'][($ii-1)], $question_y['answersid'][($ij)], 0, $y);
								echo '</td>';
							}
						}
					}
					echo '	</tr>';
				}
			}
			// The Y axis is NOT a score question type so the number of rows = the number of options
			else
			{
				echo '	<tr>';
				for ($ii=0; $ii<=count($question_x['answers']); $ii++)
				{
						if ($question_x['type']=='score')
						{
							for($x=1; $x<=$question_x['maximum_score']; $x++)
							{
								if ($ii == 0)
								{
									echo '		<th>'.$question_y['answers'][($ij)].'</th>';
									break;
								}
								else
								{
									echo '		<td align="center">';
									echo comparative_check($answers_x, $answers_y, $question_x['answersid'][($ii-1)], $question_y['answersid'][($ij)], $x, 0);
									echo '</td>';
								}
							}
						}
						else
						{
							if ($ii == 0)
							{
								echo '		<th>'.$question_y['answers'][($ij)].'</th>';
							}
							else
							{
								echo '		<td align="center">';
								echo comparative_check($answers_x, $answers_y, $question_x['answersid'][($ii-1)], $question_y['answersid'][($ij)]);
								echo '</td>';
							}
						}
				}
				echo '	</tr>';
			}
		}
		echo '</table>';
	}
}

/**
 * get all the answers of a question grouped by user
 *
 * @param integer $survey_id the id of the survey
 * @param integer $question_id the id of the question
 * @return array $return an array countaining all the answers of all the users grouped by user
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version February 2007
 */
function get_answers_of_question_by_user($survey_id, $question_id)
{
	// Database table definitions
	$table_survey_question 			= Database :: get_course_table(TABLE_SURVEY_QUESTION);
	$table_survey_question_option 	= Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
	$table_survey_answer 			= Database :: get_course_table(TABLE_SURVEY_ANSWER);

	$sql = "SELECT * FROM $table_survey_answer
				WHERE survey_id='".Database::escape_string($survey_id)."'
				AND question_id='".Database::escape_string($question_id)."'
				ORDER BY USER ASC";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	while ($row = mysql_fetch_assoc($result))
	{
		if ($row['value'] == 0)
		{
			$return[$row['user']][] = $row['option_id'];
		}
		else
		{
			$return[$row['user']][] = $row['option_id'].'*'.$row['value'];
		}

	}
	return $return;
}


/**
 * count the number of users who answer positively on both options
 *
 * @param array $answers_x all the answers of the x axis
 * @param array $answers_y all the answers of the y axis
 * @param integer $option_x the x axis value (= the option_id of the first question)
 * @param integer $option_y the y axis value (= the option_id of the second question)
 * @return integer the number of users who have answered positively on both options
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version February 2007
 */
function comparative_check($answers_x, $answers_y, $option_x, $option_y, $value_x=0, $value_y=0)
{
	if ($value_x==0)
	{
		$check_x = $option_x;
	}
	else
	{
		$check_x = $option_x.'*'.$value_x;
	}
	if ($value_y==0)
	{
		$check_y = $option_y;
	}
	else
	{
		$check_y = $option_y.'*'.$value_y;
	}

	$counter = 0;
	foreach ($answers_x as $user => $answers)
	{
		// check if the user has given $option_x as answer
		if (in_array($check_x, $answers))
		{
			// check if the user has given $option_y as an answer
			if (in_array($check_y, $answers_y[$user]))
			{
				$counter++;
			}
		}
	}
	return $counter;
}
?>