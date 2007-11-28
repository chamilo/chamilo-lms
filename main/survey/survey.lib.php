<?php
$config['survey']['debug'] = false;
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
* 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup, refactoring and rewriting large parts (if not all) of the code
* 	@version $Id: survey.lib.php 10680 2007-01-11 21:26:23Z pcool $
*
* 	@todo move this file to inc/lib
* 	@todo use consistent naming for the functions (save vs store for instance)
*/

class survey_manager
{

	/******************************************************************************************************
											SURVEY FUNCTIONS
	 *****************************************************************************************************/

	/**
	 * This function retrieves all the survey information
	 *
	 * @param integer $survey_id the id of the survey
	 * @param boolean $shared this parameter determines if we have to get the information of a survey from the central (shared) database or from the
	 * 		  course database
	 *
	 * @return array
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version February 2007
	 *
	 * @todo this is the same function as in create_new_survey.php
	 */
	function get_survey($survey_id,$shared=0)
	{
		global $_course;

		// table definition
		$table_survey = Database :: get_course_table(TABLE_SURVEY, $_course['db_name']);
		if ($shared<>0)
		{
			$table_survey	= Database :: get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION);
		}

		$sql = "SELECT * FROM $table_survey WHERE survey_id='".Database::escape_string($survey_id)."'";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		$return = mysql_fetch_assoc($result);

		// we do this (temporarily) to have the array match the quickform elements immediately
		// idealiter the fields in the db match the quickform fields
		$return['survey_code'] 			= $return['code'];
		$return['survey_title'] 		= $return['title'];
		$return['survey_subtitle'] 		= $return['subtitle'];
		$return['survey_language'] 		= $return['lang'];
		$return['start_date'] 			= $return['avail_from'];
		$return['end_date'] 			= $return['avail_till'];
		$return['survey_share'] 		= $return['is_shared'];
		$return['survey_introduction'] 	= $return['intro'];
		$return['survey_thanks'] 		= $return['surveythanks'];
		return $return;
	}

	/**
	 * This function stores a survey in the database.
	 *
	 * @param array $values
	 * @return array $return the type of return message that has to be displayed and the message in it
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version February 2007
	 */
	function store_survey($values)
	{
		global $_user;

		// table defnitions
		$table_survey 	= Database :: get_course_table(TABLE_SURVEY);

		if ($values['survey_share']['survey_share'] !== '0')
		{
			$shared_survey_id = survey_manager::store_shared_survey($values);
		}

		if (!$values['survey_id'] OR !is_numeric($values['survey_id']))
		{
			if ($values['anonymous']=='')
			{
				$values['anonymous']=0;
			}
			$sql = "INSERT INTO $table_survey (code, title, subtitle, author, lang, avail_from, avail_till, is_shared, template, intro, surveythanks, creation_date, anonymous) VALUES (
						'".Database::escape_string($values['survey_code'])."',
						'".Database::escape_string($values['survey_title'])."',
						'".Database::escape_string($values['survey_subtitle'])."',
						'".Database::escape_string($_user['user_id'])."',
						'".Database::escape_string($values['survey_language'])."',
						'".Database::escape_string($values['start_date'])."',
						'".Database::escape_string($values['end_date'])."',
						'".Database::escape_string($shared_survey_id)."',
						'".Database::escape_string('template')."',
						'".Database::escape_string($values['survey_introduction'])."',
						'".Database::escape_string($values['survey_thanks'])."',
						'".date('Y-m-d H:i:s')."',
						'".Database::escape_string($values['anonymous'])."'
						)";
			$result = api_sql_query($sql, __FILE__, __LINE__);
			$survey_id = mysql_insert_id();

			//$return['message'] = get_lang('SurveyCreatedSuccesfully').'<br />'.get_lang('YouCanNowAddQuestionToYourSurvey').': ';
			//$return['message'] .= '<a href="survey.php?survey_id='.$survey_id.'">'.get_lang('ClickHere').'</a>';
			$return['message'] = 'SurveyCreatedSuccesfully';
			$return['type'] = 'confirmation';
			$return['id']	= $survey_id;
		}
		else
		{
			if ($values['anonymous']=='')
			{
				$values['anonymous']=0;
			}
			$sql = "UPDATE $table_survey SET
							code 			= '".Database::escape_string($values['survey_code'])."',
							title 			= '".Database::escape_string($values['survey_title'])."',
							subtitle 		= '".Database::escape_string($values['survey_subtitle'])."',
							author 			= '".Database::escape_string($_user['user_id'])."',
							lang 			= '".Database::escape_string($values['survey_language'])."',
							avail_from 		= '".Database::escape_string($values['start_date'])."',
							avail_till		= '".Database::escape_string($values['end_date'])."',
							is_shared		= '".Database::escape_string($shared_survey_id)."',
							template 		= '".Database::escape_string('template')."',
							intro			= '".Database::escape_string($values['survey_introduction'])."',
							surveythanks	= '".Database::escape_string($values['survey_thanks'])."',
							anonymous	= '".Database::escape_string($values['anonymous'])."'
					WHERE survey_id = '".Database::escape_string($values['survey_id'])."'";
			$result = api_sql_query($sql, __FILE__, __LINE__);

			//$return['message'] = get_lang('SurveyUpdatedSuccesfully').'<br />'.get_lang('YouCanNowAddQuestionToYourSurvey').': ';
			//$return['message'] .= '<a href="survey.php?survey_id='.$values['survey_id'].'">'.get_lang('Here').'</a>';
			//$return['message'] .= get_lang('OrReturnToSurveyOverview').'<a href="survey_list.php">'.get_lang('Here').'</a>';
			$return['message'] = 'SurveyUpdatedSuccesfully';
			$return['type'] = 'confirmation';
			$return['id']	= $values['survey_id'];
		}

	return $return;
	}

	/**
	 * This function stores a shared survey in the central database.
	 *
	 * @param array $values
	 * @return array $return the type of return message that has to be displayed and the message in it
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version February 2007
	 */
	function store_shared_survey($values)
	{
		global $_user;
		global $_course;

		// table defnitions
		$table_survey	= Database :: get_main_table(TABLE_MAIN_SHARED_SURVEY);

		if (!$values['survey_id'] OR !is_numeric($values['survey_id']) OR $values['survey_share']['survey_share'] == 'true')
		{
			$sql = "INSERT INTO $table_survey (code, title, subtitle, author, lang, template, intro, surveythanks, creation_date, course_code) VALUES (
						'".Database::escape_string($values['survey_code'])."',
						'".Database::escape_string($values['survey_title'])."',
						'".Database::escape_string($values['survey_subtitle'])."',
						'".Database::escape_string($_user['user_id'])."',
						'".Database::escape_string($values['survey_language'])."',
						'".Database::escape_string('template')."',
						'".Database::escape_string($values['survey_introduction'])."',
						'".Database::escape_string($values['survey_thanks'])."',
						'".date()."',
						'".$_course['id']."')";
			$result = api_sql_query($sql, __FILE__, __LINE__);
			$return	= mysql_insert_id();
		}
		else
		{
			$sql = "UPDATE $table_survey SET
							code 			= '".Database::escape_string($values['survey_code'])."',
							title 			= '".Database::escape_string($values['survey_title'])."',
							subtitle 		= '".Database::escape_string($values['survey_subtitle'])."',
							author 			= '".Database::escape_string($_user['user_id'])."',
							lang 			= '".Database::escape_string($values['survey_language'])."',
							template 		= '".Database::escape_string('template')."',
							intro			= '".Database::escape_string($values['survey_introduction'])."',
							surveythanks	= '".Database::escape_string($values['survey_thanks'])."'
					WHERE survey_id = '".Database::escape_string($values['survey_share']['survey_share'])."'";
			$result = api_sql_query($sql, __FILE__, __LINE__);
			$return	= $values['survey_share']['survey_share'];
		}
		return $return;
	}

	/**
	 * This function deletes a survey (and also all the question in that survey
	 *
	 * @param $survey_id the id of the survey that has to be deleted
	 * @return true
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 */
	function delete_survey($survey_id, $shared=false)
	{
		// Database table definitions
		$table_survey 		= Database :: get_course_table(TABLE_SURVEY);
		if ($shared)
		{
			$table_survey 	= Database :: get_main_table(TABLE_MAIN_SHARED_SURVEY);
		}

		// deleting the survey
		$sql = "DELETE from $table_survey WHERE survey_id='".Database::escape_string($survey_id)."'";
		$res = api_sql_query($sql, __FILE__, __LINE__);

		// deleting the questions of the survey
		survey_manager::delete_all_survey_questions($survey_id, $shared);

		return true;
	}
	
	/**
	 * This function duplicates a survey (and also all the question in that survey
	 *
	 * @param $survey_id the id of the survey that has to be duplicated
	 * @return true
	 *
	 * @author Eric Marguin <e.marguin@elixir-interactive.com>, Elixir Interactive
	 * @version October 2007
	 */
	function empty_survey($survey_id)
	{
		// Database table definitions
		$table_survey_invitation = Database :: get_course_table(TABLE_SURVEY_INVITATION);
		$table_survey_answer = Database :: get_course_table(TABLE_SURVEY_ANSWER);
		$table_survey = Database :: get_course_table(TABLE_SURVEY);
		
		$datas = survey_manager::get_survey($survey_id);

		$sql = 'DELETE FROM '.$table_survey_invitation.' WHERE survey_code = "'.Database::escape_string($datas['code']).'"';
		api_sql_query($sql, __FILE__, __LINE__);
		
		$sql = 'DELETE FROM '.$table_survey_answer.' WHERE survey_id='.intval($survey_id);
		api_sql_query($sql, __FILE__, __LINE__);
		
		$sql = 'UPDATE '.$table_survey.' SET invited=0, answered=0 WHERE survey_id='.intval($survey_id);
		api_sql_query($sql, __FILE__, __LINE__);

		return true;
	}

	/**
	 * This function recalculates the number of people who have taken the survey (=filled at least one question)
	 *
	 * @param $survey_id the id of the survey somebody
	 * @return true
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version February 2007
	 */
	function update_survey_answered($survey_id, $user, $survey_code)
	{
		global $_course;

		// Database table definitions
		$table_survey 				= Database :: get_course_table(TABLE_SURVEY, $_course['db_name']);
		$table_survey_invitation 	= Database :: get_course_table(TABLE_SURVEY_INVITATION, $_course['db_name']);

		// getting a list with all the people who have filled the survey
		$people_filled = survey_manager::get_people_who_filled_survey($survey_id);
		$number = count($people_filled);

		// storing this value in the survey table
		$sql = "UPDATE $table_survey SET answered = '".Database::escape_string($number)."' WHERE survey_id = '".Database::escape_string($survey_id)."'";
		$res = api_sql_query($sql, __FILE__, __LINE__);

		// storing that the user has finished the survey.
		$sql = "UPDATE $table_survey_invitation SET answered='1' WHERE user='".Database::escape_string($user)."' AND survey_code='".Database::escape_string($survey_code)."'";
		$res = api_sql_query($sql, __FILE__, __LINE__);
	}

	/**
	 * This function gets a complete structure of a survey (all survey information, all question information
	 * of all the questions and all the options of all the questions.
	 *
	 * @param integer $survey_id the id of the survey
	 * @param boolean $shared this parameter determines if we have to get the information of a survey from the central (shared) database or from the
	 * 		  course database
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version February 2007
	 */
	function get_complete_survey_structure($survey_id, $shared=0)
	{
		// Database table definitions
		$table_survey 					= Database :: get_course_table(TABLE_SURVEY);
		$table_survey_question 			= Database :: get_course_table(TABLE_SURVEY_QUESTION);
		$table_survey_question_option 	= Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);

		if ($shared<>0)
		{
			$table_survey 					= Database :: get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION);
			$table_survey_question 			= Database :: get_course_table(TABLE_SHARED_SURVEY_QUESTION);
			$table_survey_question_option 	= Database :: get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION_OPTION);
		}

		$structure = survey_manager::get_survey($survey_id, $shared);

		$structure['questions'] = survey_manager::get_questions($survey_id);
	}

	/******************************************************************************************************
										SURVEY QUESTION FUNCTIONS
	 *****************************************************************************************************/

	/**
	 * This function return the "icon" of the question type
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version February 2007
	 */
	function icon_question($type)
	{
		// the possible question types
		$possible_types = array('yesno', 'multiplechoice', 'multipleresponse', 'open', 'dropdown', 'comment', 'pagebreak', 'percentage', 'score');

		// the images array
		$icon_question = array(
				'yesno' 			=> 'yesno.gif',
				'multiplechoice' 	=> 'mcua.gif',
				'multipleresponse' 	=> 'mcma.gif',
				'open' 				=> 'open_answer.gif',
				'dropdown' 			=> 'dropdown.gif',
				'percentage' 		=> 'percentagequestion.gif',
				'score' 			=> 'scorequestion.gif',
				'comment' 			=> 'commentquestion.gif',
				'pagebreak' 		=> 'page_end.gif',
				);

		if (in_array($type, $possible_types))
		{
			return $icon_question[$type];
		}
		else
		{
			return false;
		}
	}

	/**
	 * This function retrieves all the information of a question
	 *
	 * @param integer $question_id the id of the question
	 * @return array
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 *
	 * @todo one sql call should do the trick
	 */
	function get_question($question_id, $shared=false)
	{
		// table definitions
		$tbl_survey_question 			= Database :: get_course_table(TABLE_SURVEY_QUESTION);
		$table_survey_question_option 	= Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
		if ($shared)
		{
			$tbl_survey_question 			= Database :: get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION);
			$table_survey_question_option	= Database :: get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION_OPTION);
		}

		// getting the information of the question
		$sql = "SELECT * FROM $tbl_survey_question WHERE question_id='".Database::escape_string($question_id)."' ORDER BY `sort`";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		$row = mysql_fetch_assoc($result);
		$return['survey_id'] 			= $row['survey_id'];
	    $return['question_id'] 			= $row['question_id'];
	    $return['type'] 				= $row['type'];
	    $return['question'] 			= $row['survey_question'];
	    $return['horizontalvertical'] 	= $row['display'];
	    $return['shared_question_id']	= $row['shared_question_id'];
	    $return['maximum_score']		= $row['max_value'];

	    // getting the information of the question options
		$sql = "SELECT * FROM $table_survey_question_option WHERE question_id='".Database::escape_string($question_id)."' ORDER BY `sort` ";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($row = mysql_fetch_assoc($result))
		{
			/** @todo this should be renamed to options instead of answers */
			$return['answers'][] = $row['option_text'];
			/** @todo this can be done more elegantly (used in reporting) */
			$return['answersid'][] = $row['question_option_id'];
		}
		return $return;
	}


	/**
	 * This function gets all the question of any given survey
	 *
	 * @param integer $survey_id the id of the survey
	 * @return array containing all the questions of the survey
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version February 2007
	 *
	 * @todo one sql call should do the trick
	 */
	function get_questions($survey_id)
	{
		// table definitions
		$tbl_survey_question 			= Database :: get_course_table(TABLE_SURVEY_QUESTION);
		$table_survey_question_option 	= Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);

		// getting the information of the question
		$sql = "SELECT * FROM $tbl_survey_question WHERE survey_id='".Database::escape_string($survey_id)."'";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($row = mysql_fetch_assoc($result))
		{
			$return[$row['question_id']]['survey_id'] 			= $row['survey_id'];
		    $return[$row['question_id']]['question_id'] 		= $row['question_id'];
		    $return[$row['question_id']]['type'] 				= $row['type'];
	    	$return[$row['question_id']]['question'] 			= $row['survey_question'];
		    $return[$row['question_id']]['horizontalvertical'] 	= $row['display'];
		    $return[$row['question_id']]['maximum_score'] 		= $row['max_value'];
		    $return[$row['question_id']]['sort'] 				= $row['sort'];

		}

	    // getting the information of the question options
		$sql = "SELECT * FROM $table_survey_question_option WHERE survey_id='".Database::escape_string($survey_id)."'";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($row = mysql_fetch_assoc($result))
		{
			$return[$row['question_id']]['answers'][] = $row['option_text'];
		}
		return $return;
	}

	/**
	 * This function saves a question in the database.
	 * This can be either an update of an existing survey or storing a new survey
	 *
	 * @param array $form_content all the information of the form
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 */
	function save_question($form_content)
	{
		global $_course;

		// table definitions
		$table_survey 			= Database :: get_course_table(TABLE_SURVEY, $_course['db_name']);
		$tbl_survey_question 	= Database :: get_course_table(TABLE_SURVEY_QUESTION, $_course['db_name']);

		// getting all the information of the survey
		$survey_data = survey_manager::get_survey($form_content['survey_id']);

		// storing the question in the shared database
		if (is_numeric($survey_data['survey_share']) AND $survey_data['survey_share'] <> 0)
		{
			$shared_question_id = survey_manager::save_shared_question($form_content, $survey_data);
			$form_content['shared_question_id'] = $shared_question_id;
		}

		// storing a new question
		if ($form_content['question_id'] == '' OR !is_numeric($form_content['question_id']))
		{
			// finding the max sort order of the questions in the given survey
			$sql = "SELECT max(sort) AS max_sort FROM $tbl_survey_question WHERE survey_id='".Database::escape_string($form_content['survey_id'])."'";
			$result = api_sql_query($sql, __FILE__, __LINE__);
			$row = mysql_fetch_assoc($result);
			$max_sort = $row['max_sort'];

			// adding the question to the survey_question table
			$sql = "INSERT INTO $tbl_survey_question (survey_id,survey_question,survey_question_comment,type,display, sort, shared_question_id, max_value) VALUES (
						'".Database::escape_string($form_content['survey_id'])."',
						'".Database::escape_string($form_content['question'])."',
						'".Database::escape_string($form_content['question_comment'])."',
						'".Database::escape_string($form_content['type'])."',
						'".Database::escape_string($form_content['horizontalvertical'])."',
						'".Database::escape_string($max_sort+1)."',
						'".Database::escape_string($form_content['shared_question_id'])."',
						'".Database::escape_string($form_content['maximum_score'])."'
						)";
			$result = api_sql_query($sql, __FILE__, __LINE__);
			$question_id = mysql_insert_id();
			$form_content['question_id'] = $question_id;
			$return_message = 'QuestionAdded';
		}
		// updating an existing question
		else
		{
			// adding the question to the survey_question table
			$sql = "UPDATE $tbl_survey_question SET
						survey_question 		= '".Database::escape_string($form_content['question'])."',
						survey_question_comment = '".Database::escape_string($form_content['question_comment'])."',
						display 				= '".Database::escape_string($form_content['horizontalvertical'])."',
						max_value 				= '".Database::escape_string($form_content['maximum_score'])."'
						WHERE question_id 		= '".Database::escape_string($form_content['question_id'])."'";
			$result = api_sql_query($sql, __FILE__, __LINE__);
			$return_message = 'QuestionUpdated';
		}
		// storing the options of the question
		survey_manager::save_question_options($form_content, $survey_data);
		return $return_message;
	}

	/**
	 * This function saves the question in the shared database
	 *
	 * @param array $form_content all the information of the form
	 * @param array $survey_data all the information of the survey
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version February 2007
	 *
	 * @todo editing of a shared question
	 */
	function save_shared_question($form_content, $survey_data)
	{
		global $_course;

		// table definitions
		$tbl_survey_question 	= Database :: get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION);

		// storing a new question
		if ($form_content['shared_question_id'] == '' OR !is_numeric($form_content['shared_question_id']))
		{
			// finding the max sort order of the questions in the given survey
			$sql = "SELECT max(sort) AS max_sort FROM $tbl_survey_question
					WHERE survey_id='".Database::escape_string($survey_data['survey_share'])."'
					AND code='".Database::escape_string($_course['id'])."'";
			$result = api_sql_query($sql, __FILE__, __LINE__);
			$row = mysql_fetch_assoc($result);
			$max_sort = $row['max_sort'];

			// adding the question to the survey_question table
			$sql = "INSERT INTO $tbl_survey_question (survey_id, survey_question, survey_question_comment, type, display, sort, code) VALUES (
						'".Database::escape_string($survey_data['survey_share'])."',
						'".Database::escape_string($form_content['question'])."',
						'".Database::escape_string($form_content['question_comment'])."',
						'".Database::escape_string($form_content['type'])."',
						'".Database::escape_string($form_content['horizontalvertical'])."',
						'".Database::escape_string($max_sort+1)."',
						'".Database::escape_string($_course['id'])."')";
			$result = api_sql_query($sql, __FILE__, __LINE__);
			$shared_question_id = mysql_insert_id();
		}
		// updating an existing question
		else
		{
			// adding the question to the survey_question table
			$sql = "UPDATE $tbl_survey_question SET
						survey_question = '".Database::escape_string($form_content['question'])."',
						survey_question_comment = '".Database::escape_string($form_content['question_comment'])."',
						display = '".Database::escape_string($form_content['horizontalvertical'])."'
						WHERE question_id = '".Database::escape_string($form_content['shared_question_id'])."'
						AND code='".Database::escape_string($_course['id'])."'";
			$result = api_sql_query($sql, __FILE__, __LINE__);
			$shared_question_id = $form_content['shared_question_id'];
		}

		return $shared_question_id;
	}

	/**
	 * This functions moves a question of a survey up or down
	 *
	 * @param string $direction
	 * @param integer $survey_question_id
	 * @param integer $survey_id
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 */
	function move_survey_question($direction, $survey_question_id, $survey_id)
	{
		// table definition
		$table_survey_question 	= Database :: get_course_table(TABLE_SURVEY_QUESTION);

		if ($direction == 'moveup')
		{
			$sort = 'DESC';
		}
		if ($direction == 'movedown')
		{
			$sort = 'ASC';
		}

		// finding the two questions that needs to be swapped
		$sql = "SELECT * FROM $table_survey_question WHERE survey_id='".Database::escape_string($survey_id)."' ORDER BY sort $sort";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($row = mysql_fetch_assoc($result))
		{
			if ($found == true)
			{
				$question_id_two = $row['question_id'];
				$question_sort_two = $row['sort'];
				$found = false;
			}
			if ($row['question_id'] == $survey_question_id)
			{
				$found = true;
				$question_id_one = $row['question_id'];
				$question_sort_one = $row['sort'];
			}
		}

		$sql1 = "UPDATE $table_survey_question SET sort = '".Database::escape_string($question_sort_two)."' WHERE question_id='".Database::escape_string($question_id_one)."'";
		$result = api_sql_query($sql1, __FILE__, __LINE__);
		$sql2 = "UPDATE $table_survey_question SET sort = '".Database::escape_string($question_sort_one)."' WHERE question_id='".Database::escape_string($question_id_two)."'";
		$result = api_sql_query($sql2, __FILE__, __LINE__);
	}


	/**
	 * This function deletes all the questions of a given survey
	 * This function is normally only called when a survey is deleted
	 *
	 * @param $survey_id the id of the survey that has to be deleted
	 * @return true
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 */
	function delete_all_survey_questions($survey_id, $shared=false)
	{
		// table definitions
		$table_survey_question 	= Database :: get_course_table(TABLE_SURVEY_QUESTION);
		if ($shared)
		{
			$table_survey_question 	= Database :: get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION);
		}

		// deleting the survey questions
		$sql = "DELETE from $table_survey_question WHERE survey_id='".Database::escape_string($survey_id)."'";
		$res = api_sql_query($sql, __FILE__, __LINE__);

		// deleting all the options of the questions of the survey
		survey_manager::delete_all_survey_questions_options($survey_id, $shared);

		// deleting all the answers on this survey
		survey_manager::delete_all_survey_answers($survey_id);
	}


	/**
	 * This function deletes a survey question and all its options
	 *
	 * @param integer $survey_id the id of the survey
	 * @param integer $question_id the id of the question
	 * @param integer $shared
	 *
	 * @todo also delete the answers to this question
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version March 2007
	 */
	function delete_survey_question($survey_id, $question_id, $shared=false)
	{
		// table definitions
		$table_survey_question 	= Database :: get_course_table(TABLE_SURVEY_QUESTION);
		if ($shared)
		{
			survey_manager::delete_shared_survey_question($survey_id, $question_id);
		}

		// deleting the survey questions
		$sql = "DELETE from $table_survey_question WHERE survey_id='".Database::escape_string($survey_id)."' AND question_id='".Database::escape_string($question_id)."'";
		$res = api_sql_query($sql, __FILE__, __LINE__);

		// deleting the options of the question of the survey
		survey_manager::delete_survey_question_option($survey_id, $question_id, $shared);
	}

	/**
	 * This function deletes a shared survey question from the main database and all its options
	 *
	 * @param integer $question_id the id of the question
	 * @param integer $shared
	 *
	 * @todo delete all the options of this question
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version March 2007
	 */
	function delete_shared_survey_question($survey_id, $question_id)
	{
		// table definitions
		$table_survey_question 	= Database :: get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION);
		$table_survey_question_option 	= Database :: get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION_OPTION);

		// first we have to get the shared_question_id
		$question_data = survey_manager::get_question($question_id);

		// deleting the survey questions
		$sql = "DELETE FROM $table_survey_question WHERE question_id='".Database::escape_string($question_data['shared_question_id'])."'";
		$res = api_sql_query($sql, __FILE__, __LINE__);

		// deleting the options of the question of the survey question
		$sql = "DELETE FROM $table_survey_question_option WHERE question_id='".Database::escape_string($question_data['shared_question_id'])."'";
		$res = api_sql_query($sql, __FILE__, __LINE__);
	}

	/******************************************************************************************************
									SURVEY QUESTION OPTIONS FUNCTIONS
	 *****************************************************************************************************/

	/**
	 * This function stores the options of the questions in the table
	 *
	 * @param array $form_content
	 * @return
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 *
	 * @todo writing the update statement when editing a question
	 */
	function save_question_options($form_content, $survey_data)
	{
		// a percentage question type has options 1 -> 100
		if ($form_content['type'] == 'percentage')
		{
			for($i=1;$i<101;$i++)
			{
				$form_content['answers'][] = $i;
			}
		}

		if (is_numeric($survey_data['survey_share']) AND $survey_data['survey_share'] <> 0)
		{
			survey_manager::save_shared_question_options($form_content, $survey_data);
		}

		// table defintion
		$table_survey_question_option 	= Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);

		// we are editing a question so we first have to remove all the existing options from the database
		if (is_numeric($form_content['question_id']))
		{
			$sql = "DELETE FROM $table_survey_question_option WHERE question_id = '".Database::escape_string($form_content['question_id'])."'";
			$result = api_sql_query($sql, __FILE__, __LINE__);
		}

		$counter = 1;
		foreach ($form_content['answers'] as $key=>$answer)
		{
			$sql = "INSERT INTO $table_survey_question_option (question_id, survey_id, option_text, sort) VALUES (
							'".Database::escape_string($form_content['question_id'])."',
							'".Database::escape_string($form_content['survey_id'])."',
							'".Database::escape_string($answer)."',
							'".Database::escape_string($counter)."')";
			$result = api_sql_query($sql, __FILE__, __LINE__);
			$counter++;
		}
	}

	/**
	 * This function stores the options of the questions in the shared table
	 *
	 * @param array $form_content
	 * @return
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version February 2007
	 *
	 * @todo writing the update statement when editing a question
	 */
	function save_shared_question_options($form_content, $survey_data)
	{
		// table defintion
		$table_survey_question_option 	= Database :: get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION_OPTION);

		// we are editing a question so we first have to remove all the existing options from the database
		$sql = "DELETE FROM $table_survey_question_option WHERE question_id = '".Database::escape_string($form_content['shared_question_id'])."'";
		$result = api_sql_query($sql, __FILE__, __LINE__);

		$counter = 1;
		foreach ($form_content['answers'] as $key=>$answer)
		{
			$sql = "INSERT INTO $table_survey_question_option (question_id, survey_id, option_text, sort) VALUES (
							'".Database::escape_string($form_content['shared_question_id'])."',
							'".Database::escape_string($survey_data['is_shared'])."',
							'".Database::escape_string($answer)."',
							'".Database::escape_string($counter)."')";
			$result = api_sql_query($sql, __FILE__, __LINE__);
			$counter++;
		}
	}

	/*
		if (is_numeric($survey_data['survey_share']) AND $survey_data['survey_share'] <> 0)
		{
			$form_content = survey_manager::save_shared_question($form_content, $survey_data);
		}
	*/


	/**
	 * This function deletes all the options of the questions of a given survey
	 * This function is normally only called when a survey is deleted
	 *
	 * @param $survey_id the id of the survey that has to be deleted
	 * @return true
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 */
	function delete_all_survey_questions_options($survey_id, $shared=false)
	{
		// table definitions
		$table_survey_question_option 	= Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
		if ($shared)
		{
			$table_survey_question 	= Database :: get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION_OPTION);
		}

		// deleting the options of the survey questions
		$sql = "DELETE from $table_survey_question_option WHERE survey_id='".Database::escape_string($survey_id)."'";
		$res = api_sql_query($sql, __FILE__, __LINE__);
		return true;
	}


	/**
	 * This function deletes the options of a given question
	 *
	 * @param unknown_type $survey_id
	 * @param unknown_type $question_id
	 * @param unknown_type $shared
	 * @return unknown
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version March 2007
	 */
	function delete_survey_question_option($survey_id, $question_id, $shared=false)
	{
		// table definitions
		$table_survey_question_option 	= Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
		if ($shared)
		{
			$table_survey_question 	= Database :: get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION_OPTION);
		}

		// deleting the options of the survey questions
		$sql = "DELETE from $table_survey_question_option WHERE survey_id='".Database::escape_string($survey_id)."' AND question_id='".Database::escape_string($question_id)."'";
		$res = api_sql_query($sql, __FILE__, __LINE__);
		return true;
	}



	/******************************************************************************************************
									SURVEY ANSWERS FUNCTIONS
	 *****************************************************************************************************/

	/**
	 * This function deletes all the answers anyone has given on this survey
	 * This function is normally only called when a survey is deleted
	 *
	 * @param $survey_id the id of the survey that has to be deleted
	 * @return true
	 *
	 * @todo write the function
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 */
	function delete_all_survey_answers($survey_id)
	{
		return true;
	}

	/**
	 * This function gets all the persons who have filled the survey
	 *
	 * @param integer $survey_id
	 * @return array
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version February 2007
	 */
	function get_people_who_filled_survey($survey_id, $all_user_info = false)
	{
		global $_course;

		// Database table definition
		$table_survey_answer 		= Database :: get_course_table(TABLE_SURVEY_ANSWER, $_course['db_name']);
		$table_user					= Database :: get_main_table('user');

		// variable initialisation
		$return = array();
		
		// getting the survey information
		$survey_data = survey_manager::get_survey($survey_id);

		if ($all_user_info)
		{
			$sql = "SELECT DISTINCT answered_user.user as invited_user, user.firstname, user.lastname, user.user_id 
						FROM $table_survey_answer answered_user, $table_user as user
						WHERE answered_user.user = user.user_id
						AND survey_id= '".Database::escape_string($survey_data['survey_id'])."'";
		}
		else 
		{
			$sql = "SELECT DISTINCT user FROM $table_survey_answer WHERE survey_id= '".Database::escape_string($survey_data['survey_id'])."'";
		}
		$res = api_sql_query($sql, __FILE__, __LINE__);
		while ($row = mysql_fetch_assoc($res))
		{
			if ($all_user_info)
			{
				$return[] = $row;
			}
			else 
			{
				$return[] = $row['user'];
			}
		}

		return $return;
	}
}












class question
{
	// the html code of the form
	var $html;

	/**
	 * This function does the generic part of any survey question: the question field
	 *
	 * @return unknown
	 */
	/**
	 * This function does the generic part of any survey question: the question field
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 *
	 * @todo the form_text has to become a wysiwyg editor or adding a question_comment field
	 * @todo consider adding a question_comment form element
	 */
	function create_form($form_content)
	{
		global $fck_attribute;
		$this->html = '<form id="question_form" name="question_form" method="post" action="'.api_get_self().'?action='.$_GET['action'].'&type='.$_GET['type'].'&survey_id='.$_GET['survey_id'].'&question_id='.$_GET['question_id'].'">';
		$this->html .= '		<input type="hidden" name="survey_id" id="survey_id" value="'.$_GET['survey_id'].'"/>';
		$this->html .= '		<input type="hidden" name="question_id" id="question_id" value="'.$_GET['question_id'].'"/>';
		$this->html .= '		<input type="hidden" name="shared_question_id" id="shared_question_id" value="'.$form_content['shared_question_id'].'"/>';
		$this->html .= '		<input type="hidden" name="type" id="type" value="'.$_GET['type'].'"/>';
		$this->html .= '<table>';
		$this->html .= '	<tr>';
		$this->html .= '		<td colspan="3"><strong>'.get_lang('Question').'</strong></td>';
		$this->html .= '	</tr>';
		$this->html .= '	<tr>';
		//$this->html .= '		<td><label for="question">'.get_lang('Question').'</label></td>';
		$fck_attribute['Width'] = '100%';
		$fck_attribute['Height'] = '100';
		$fck_attribute['ToolbarSet'] = 'Survey';
		//$this->html .= '		<td><input type="text" name="question" id="question" value="'.$form_content['question'].'"/></td>';
		$this->html .= '		<td colspan="3" width="500">'.api_return_html_area('question', html_entity_decode(stripslashes($form_content['question']))).'</td>';
		$this->html .= '	</tr>';
		/*
		$this->html .= '	<tr>';
		$this->html .= '		<td><label for="question_comment">'.get_lang('QuestionComment').'</label></td>';
		$this->html .= '		<td><input type="text" name="question_comment" id="question_comment" value="'.$form_content['question_comment'].'"/></td>';
		$this->html .= '		<td>&nbsp;</td>';
		$this->html .= '	</tr>';
		*/

		$this->html .='		<tr>
								<td colspan="">&nbsp</td>
							</tr>';
		return $this->html;
	}

	/**
	 * This functions displays the form after the html variable has correctly been finished
	 * (adding a submit button, closing the table and closing the form)
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 *
	 */
	function render_form()
	{
		$this->html .= '	<tr>';
		$this->html .= '		<td>&nbsp;</td>';
		$this->html .= '		<td>  <input type="submit" name="save_question" value="'.get_lang('SaveQuestion').'" /></td>';
		$this->html .= '		<td>&nbsp;</td>';
		$this->html .= '	</tr>';
		$this->html .= '</table>';
		$this->html .= '</form>';
		echo $this->html;
	}

	/**
	 * This function handles the actions on a question and its answers
	 *
	 * @todo consider using $form_content instead of $_POST
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 */
	function handle_action($form_content)
	{
		global $config;

		// moving an answer up
		if ($_POST['move_up'])
		{
			foreach ($_POST['move_up'] as $key=>$value)
			{
				$id1		= $key;
				$content1 	= $form_content['answers'][$id1];
				$id2		= $key-1;
				$content2	= $form_content['answers'][$id2];
				$form_content['answers'][$id1] = $content2;
				$form_content['answers'][$id2] = $content1;
			}
		}

		// moving an answer down
		if ($_POST['move_down'])
		{
			foreach ($_POST['move_down'] as $key=>$value)
			{
				$id1		= $key;
				$content1 	= $form_content['answers'][$id1];
				$id2		= $key+1;
				$content2	= $form_content['answers'][$id2];
				$form_content['answers'][$id1] = $content2;
				$form_content['answers'][$id2] = $content1;
			}
		}

		// adding an answer
		if ($_POST['add_answer'])
		{
			$form_content['answers'][]='';
		}

		// removing an answer
		if ($_POST['remove_answer'])
		{
			$max_answer = count($form_content['answers']);
			unset($form_content['answers'][$max_answer-1]);
		}

		// saving a question
		if ($_POST['save_question'])
		{
			$message = survey_manager::save_question($form_content);
			if ($config['survey']['debug'])
			{
				Display :: display_header();
				Display :: display_confirmation_message($message.'<br />'.get_lang('ReturnTo').' <a href="survey.php?survey_id='.$_GET['survey_id'].'">'.get_lang('Survey').'</a>', false);
			}
			else
			{
				header('location:survey.php?survey_id='.$_GET['survey_id'].'&message='.$message);
			}
		}

		/**
		 * This solution is a little bit strange but I could not find a different solution.
		 */
		if ($_POST['delete_answer'])
		{
			foreach ($_POST['delete_answer'] as $key=>$value)
			{
				unset($form_content['answers'][$key]);
				$deleted = $key;
			}
			foreach ($form_content['answers'] as $key=>$value)
			{
				if ($key>$deleted)
				{
					$form_content['answers'][$key-1] = $form_content['answers'][$key];
					unset($form_content['answers'][$key]);
				}
			}
		}
		return $form_content;
	}

	/**
	 * This functions adds two buttons. One to add an option, one to remove an option
	 *
	 * @param unknown_type $form_content
	 * @return html code
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 */
	function add_remove_buttons($form_content)
	{
		if (count($form_content['answers'])<=2)
		{
			$remove_answer_attribute = 'disabled="disabled"';
		}

		$return .= '	<tr>';
		$return .= '		<td align="right">&nbsp;</td>';
		$return .= '		<td colspan="2">';
		$return .= '			<input type="submit" name="remove_answer" value="'.get_lang('RemoveAnswer').'" '.$remove_answer_attribute.' />';
		$return .= '			<input type="submit" name="add_answer" value="'.get_lang('AddAnswer').'" />';
		$return .= '		</td>';
		$return .= '	</tr>';
		return $return;
	}


	/**
	 * render the question. In this case this starts with the form tag
	 *
	 * @param unknown_type $form_content
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 */
	function render_question($form_content)
	{
		$this->html = '<form id="question" name="question" method="post" action="'.api_get_self().'?survey_id='.$_GET['survey_id'].'">';
		echo $this->html;
	}
}

class yesno extends question
{
	/**
	 * This function creates the form elements for the yesno questions
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 */
	function create_form($form_content)
	{
		$this->html = parent::create_form($form_content);
		// Horizontal or vertical
		$this->html .= '	<tr>';
		$this->html .= '		<td colspan="2"><strong>'.get_lang('DisplayAnswersHorVert').'</strong></td>';
		$this->html .= '	</tr>';
		$this->html .= '	<tr>';
		$this->html .= '		<td align="right" valign="top">&nbsp;</td>';
		$this->html .= '		<td>';
		$this->html .= '		  <input name="horizontalvertical" type="radio" value="horizontal" ';
						if ($form_content['horizontalvertical'] == 'horizontal')
						{
							$this->html .= 'checked="checked"';
						}
		$this->html .= '/>'.get_lang('Horizontal').'</label><br />';
    	$this->html .= '		  <input name="horizontalvertical" type="radio" value="vertical" ';
						if ($form_content['horizontalvertical'] == 'vertical')
						{
							$this->html .= 'checked="checked"';
						}
    	$this->html .= ' />'.get_lang('Vertical').'</label>';
		$this->html .= '		</td>';
		$this->html .= '		<td>&nbsp;</td>';
		$this->html .= '	</tr>';
		$this->html .='		<tr>
								<td colspan="">&nbsp</td>
							</tr>';

		// The options
		$this->html .= '	<tr>';
		$this->html .= '		<td colspan="3"><strong>'.get_lang('AnswerOptions').'</strong></td>';
		$this->html .= '	</tr>';
		$this->html .= '	<tr>';
		$this->html .= '		<td align="right"><label for="answers[0]">1</label></td>';
		//$this->html .= '		<td><input type="text" name="answers[0]" id="answers[0]" value="'.$form_content['answers'][0].'" /></td>';
		$this->html .= '		<td>'.api_return_html_area('answers[0]', stripslashes($form_content['answers'][0])).'</td>';
		$this->html .= '		<td><input type="image" src="../img/down.gif"  value="move_down[0]" name="move_down[0]"/></td>';
		$this->html .= '	</tr>';
		$this->html .= '	<tr>';
		$this->html .= '		<td align="right"><label for="answers[1]">2</label></td>';
		//$this->html .= '		<td><input type="text" name="answers[1]" id="answers[1]" value="'.$form_content['answers'][1].'" /></td>';
		$this->html .= '		<td>'.api_return_html_area('answers[1]', stripslashes($form_content['answers'][1])).'</td>';
		$this->html .= '		<td><input type="image" src="../img/up.gif" value="move_up[1]" name="move_up[1]" /></td>';
		$this->html .= '	</tr>';
	}

	/**
	 * Render the yes not question type
	 *
	 * @param unknown_type $form_content
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 */
	function render_question($form_content, $answers=array())
	{
		foreach ($form_content['options'] as $key=>$value)
		{
			$this->html .= '<label><input name="question'.$form_content['question_id'].'" type="radio" value="'.$key.'"';
			if (in_array($key,$answers))
			{
				$this->html .= 'checked="checked"';
			}
			$this->html .= '/>'.$value.'</label>';
			if ($form_content['display'] == 'vertical')
			{
				$this->html .= '<br />';
			}
		}
		echo '<div class="survey_question_wrapper">';
		echo '<div class="survey_question">'.$form_content['survey_question'].'</div>';
		echo '<div class="survey_question_options">';
		echo $this->html;
		echo '</div>';
		echo '</div>';
	}

}

class multiplechoice extends question
{
	/**
	 * This function creates the form elements for the multiple choice questions
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 */
	function create_form($form_content)
	{
		$this->html = parent::create_form($form_content);
		$this->html .= '	<tr>';
		$this->html .= '		<td colspan="2"><strong>'.get_lang('DisplayAnswersHorVert').'</strong></td>';
		$this->html .= '	</tr>';
		// Horizontal or vertical
		$this->html .= '	<tr>';
		$this->html .= '		<td align="right" valign="top">&nbsp;</td>';
		$this->html .= '		<td>';
		$this->html .= '		  <input name="horizontalvertical" type="radio" value="horizontal" ';
						if ($form_content['horizontalvertical'] == 'horizontal')
						{
							$this->html .= 'checked="checked"';
						}
		$this->html .= '/>'.get_lang('Horizontal').'</label><br />';
    	$this->html .= '		  <input name="horizontalvertical" type="radio" value="vertical" ';
						if ($form_content['horizontalvertical'] == 'vertical')
						{
							$this->html .= 'checked="checked"';
						}
    	$this->html .= ' />'.get_lang('Vertical').'</label>';		$this->html .= '		</td>';
		$this->html .= '		<td>&nbsp;</td>';
		$this->html .= '	</tr>';
		$this->html .='		<tr>
								<td colspan="">&nbsp</td>
							</tr>';

		// The Options
		$this->html .= '	<tr>';
		$this->html .= '		<td colspan="3"><strong>'.get_lang('AnswerOptions').'</strong></td>';
		$this->html .= '	</tr>';
		$total_number_of_answers = count($form_content['answers']);
		foreach ($form_content['answers'] as $key=>$value)
		{
			$this->html .= '	<tr>';
			$this->html .= '		<td align="right"><label for="answers['.$key.']">'.($key+1).'</label></td>';
			//$this->html .= '		<td><input type="text" name="answers['.$key.']" id="answers['.$key.']" value="'.$form_content['answers'][$key].'" /></td>';
			$this->html .= '		<td width="500">'.api_return_html_area('answers['.$key.']', html_entity_decode(stripslashes($form_content['answers'][$key]))).'</td>';
			$this->html .= '		<td>';
			if ($key<$total_number_of_answers-1)
			{
				$this->html .= '			<input type="image" src="../img/down.gif"  value="move_down['.$key.']" name="move_down['.$key.']"/>';
			}
			else
			{
				$this->html .= '			<img src="../img/spacer.gif" alt="'.get_lang('Empty').'" title="'.get_lang('Empty').'" />';
			}
			if ($key>0)
			{
				$this->html .= '			<input type="image" src="../img/up.gif"  value="move_up['.$key.']" name="move_up['.$key.']"/>';
			}
			else
			{
				$this->html .= '			<img src="../img/spacer.gif" alt="'.get_lang('Empty').'" title="'.get_lang('Empty').'" />';
			}
			if ($total_number_of_answers> 2)
			{
				$this->html .= '			<input type="image" src="../img/delete.gif"  value="delete_answer['.$key.']" name="delete_answer['.$key.']"/>';
			}
			$this->html .= ' 		</td>';
			$this->html .= '	</tr>';
		}
		// The buttons for adding or removing
		$this->html .= parent :: add_remove_buttons($form_content);
	}

	/**
	 * render the multiple choice question type
	 *
	 * @param unknown_type $form_content
	 *
	 * @todo it would make more sense to consider yesno as a special case of multiplechoice and not the other way around
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 */
	function render_question($form_content, $answers=array())
	{
		$question = new yesno();
		$question->render_question($form_content, $answers);
	}
}



class multipleresponse extends question
{
	/**
	 * This function creates the form elements for the multiple response questions
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 */
	function create_form($form_content)
	{
		$this->html = parent::create_form($form_content);
		$this->html .= '	<tr>';
		$this->html .= '		<td colspan="2"><strong>'.get_lang('DisplayAnswersHorVert').'</strong></td>';
		$this->html .= '	</tr>';
		// Horizontal or vertical
		$this->html .= '	<tr>';
		$this->html .= '		<td align="right" valign="top">&nbsp;</td>';
		$this->html .= '		<td>';
		$this->html .= '		  <input name="horizontalvertical" type="radio" value="horizontal" ';
						if ($form_content['horizontalvertical'] == 'horizontal')
						{
							$this->html .= 'checked="checked"';
						}
		$this->html .= '/>'.get_lang('Horizontal').'</label><br />';
    	$this->html .= '		  <input name="horizontalvertical" type="radio" value="vertical" ';
						if ($form_content['horizontalvertical'] == 'vertical')
						{
							$this->html .= 'checked="checked"';
						}
    	$this->html .= ' />'.get_lang('Vertical').'</label>';		$this->html .= '		</td>';
		$this->html .= '		<td>&nbsp;</td>';
		$this->html .= '	</tr>';
		$this->html .='		<tr>
								<td colspan="">&nbsp</td>
							</tr>';


		// The options
		$this->html .= '	<tr>';
		$this->html .= '		<td colspan="3"><strong>'.get_lang('AnswerOptions').'</strong></td>';
		$this->html .= '	</tr>';
		$total_number_of_answers = count($form_content['answers']);
		foreach ($form_content['answers'] as $key=>$value)
		{
			$this->html .= '	<tr>';
			$this->html .= '		<td align="right"><label for="answers['.$key.']">'.($key+1).'</label></td>';
			//$this->html .= '		<td><input type="text" name="answers['.$key.']" id="answers['.$key.']" value="'.$form_content['answers'][$key].'" /></td>';
			$this->html .= '		<td width="500">'.api_return_html_area('answers['.$key.']', html_entity_decode(stripslashes($form_content['answers'][$key]))).'</td>';
			$this->html .= '		<td>';
			if ($key<$total_number_of_answers-1)
			{
				$this->html .= '			<input type="image" src="../img/down.gif"  value="move_down['.$key.']" name="move_down['.$key.']"/>';
			}
			else
			{
				$this->html .= '			<img src="../img/spacer.gif" alt="'.get_lang('Empty').'" title="'.get_lang('Empty').'" />';
			}
			if ($key>0)
			{
				$this->html .= '			<input type="image" src="../img/up.gif"  value="move_up['.$key.']" name="move_up['.$key.']"/>';
			}
			else
			{
				$this->html .= '			<img src="../img/spacer.gif" alt="'.get_lang('Empty').'" title="'.get_lang('Empty').'" />';
			}
			if ($total_number_of_answers> 2)
			{
				$this->html .= '			<input type="image" src="../img/delete.gif"  value="delete_answer['.$key.']" name="delete_answer['.$key.']"/>';
			}
			$this->html .= ' 		</td>';
			$this->html .= '	</tr>';
		}
		// The buttons for adding or removing
		$this->html .= parent :: add_remove_buttons($form_content);
	}

	/**
	 * Render the multiple response question type
	 *
	 * @param unknown_type $form_content
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 */
	function render_question($form_content, $answers=array())
	{
		foreach ($form_content['options'] as $key=>$value)
		{
			$this->html .= '<label><input name="question'.$form_content['question_id'].'[]" type="checkbox" value="'.$key.'"';
			if (in_array($key,$answers))
			{
				$this->html .= 'checked="checked"';
			}
			$this->html .= ' />'.$value.'</label>';
			if ($form_content['display'] == 'vertical')
			{
				$this->html .= '<br />';
			}
		}
		echo '<div class="survey_question_wrapper">';
		echo '<div class="survey_question">'.$form_content['survey_question'].'</div>';
		echo '<div class="survey_question_options">';
		echo $this->html;
		echo '</div>';
	}
}

class dropdown extends question
{
	/**
	 * This function creates the form elements for the dropdown questions
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 */
	function create_form($form_content)
	{
		$this->html = parent::create_form($form_content);
		// The answers
		$this->html .= '	<tr>';
		$this->html .= '		<td colspan="3"><strong>'.get_lang('AnswerOptions').'</strong></td>';
		$this->html .= '	</tr>';
		$total_number_of_answers = count($form_content['answers']);
		foreach ($form_content['answers'] as $key=>$value)
		{
			$this->html .= '	<tr>';
			$this->html .= '		<td align="right"><label for="answers['.$key.']">'.($key+1).'</label></td>';
			$this->html .= '		<td><input type="text" name="answers['.$key.']" id="answers['.$key.']" value="'.stripslashes(htmlentities($form_content['answers'][$key])).'" /></td>';
			$this->html .= '		<td>';
			if ($key<$total_number_of_answers-1)
			{
				$this->html .= '			<input type="image" src="../img/down.gif"  value="move_down['.$key.']" name="move_down['.$key.']"/>';
			}
			if ($key>0)
			{
				$this->html .= '			<input type="image" src="../img/up.gif"  value="move_up['.$key.']" name="move_up['.$key.']"/>';
			}
			if ($total_number_of_answers> 2)
			{
				$this->html .= '			<input type="image" src="../img/delete.gif"  value="delete_answer['.$key.']" name="delete_answer['.$key.']"/>';
			}
			$this->html .= ' 		</td>';
			$this->html .= '	</tr>';
		}
		// The buttons for adding or removing
		$this->html .= parent :: add_remove_buttons($form_content);
	}

	/**
	 * Render the dropdown question type
	 *
	 * @param unknown_type $form_content
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 */
	function render_question($form_content, $answers=array())
	{

		foreach ($form_content['options'] as $key=>$value)
		{
			$this->html .= '<option value="'.$key.'" ';
			if (in_array($key,$answers))
			{
				$this->html .= 'selected="selected"';
			}
			$this->html .= '>'.$value.'</option>';
		}
		echo '<div class="survey_question_wrapper">';
		echo '<div class="survey_question">'.$form_content['survey_question'].'</div>';
		echo '<div class="survey_question_options">';
		echo '<select name="question'.$form_content['question_id'].'" id="select">';
		echo $this->html;
		echo '</select>';
		echo '</div>';
		/*


    <option value="test">test</option>

		*/
	}
}


class open extends question
{
	/**
	 * This function creates the form elements for the open questions
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 *
	 * @todo add a limit for the number of characters that can be type
	 * @todo add a checkbox weither the answer is a textarea or a wysiwyg editor
	 */
	function create_form($form_content)
	{
		$this->html = parent::create_form($form_content);
	}

	/**
	 * render the open question type
	 *
	 * @param unknown_type $form_content
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 */
	function render_question($form_content, $answers=array())
	{
		echo '<div class="survey_question_wrapper">';
		echo '<div class="survey_question">'.$form_content['survey_question'].'</div>';
		echo '<div class="survey_question_options">';
		if (is_array($answers))
		{
			$content = implode('',$answers);
		}
		else 
		{
			$content = $answers;
		}
		echo '<label for="question'.$form_content['question_id'].'"></label><textarea name="question'.$form_content['question_id'].'" id="textarea" style="width: 400px; height: 130px;">'.$content.'</textarea>';
		echo '</div>';
	}
}

class comment extends question
{
	/**
	 * This function creates the form elements for a comment.
	 * A comment is nothing more than a block of text that the user can read
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 *
	 * @param array $form_content
	 */
	function create_form($form_content)
	{
		$this->html = parent::create_form($form_content);
	}


	/**
	 * Render the comment "question" type
	 *
	 * @param unknown_type $form_content
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 */
	function render_question($form_content)
	{
		echo '<div class="survey_question_wrapper">';
		echo '<div class="survey_question">'.$form_content['survey_question'].'</div>';
		echo '</div>';
		echo "\n";
	}
}

class pagebreak extends question
{
	/**
	 * This function creates the form elements for a comment.
	 * A comment is nothing more than a block of text that the user can read
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 *
	 * @param array $form_content
	 */
	function create_form($form_content)
	{
		$this->html = parent::create_form($form_content);
	}
}

class percentage extends question
{
	function create_form($form_content)
	{
		$this->html = parent::create_form($form_content);
	}

	function render_question($form_content, $answers=array())
	{
		$this->html .= '<option value="--">--</option>';
		foreach ($form_content['options'] as $key=>$value)
		{
			$this->html .= '<option value="'.$key.'" ';
			if (in_array($key,$answers))
			{
				$this->html .= 'selected="selected"';
			}
			$this->html .= '>'.$value.'</option>';
		}
		echo '<div class="survey_question_wrapper">';
		echo '<div class="survey_question">'.$form_content['survey_question'].'</div>';
		echo '<div class="survey_question_options">';
		echo '<select name="question'.$form_content['question_id'].'" id="select">';
		echo $this->html;
		echo '</select>';
		echo '</div>';
	}
}


class score extends question
{
	function create_form($form_content)
	{
		$this->html = parent::create_form($form_content);
		// the maximum score that can be given
		$this->html .= '	<tr>';
		$this->html .= '		<td colspan="3"><strong>'.get_lang('MaximumScore').'</strong></td>';
		$this->html .= '	</tr>';
		$this->html .= '	<tr>
								<td colspan="3"><input type="text" name="maximum_score" value="'.$form_content['maximum_score'].'"></td>
							</tr>';
		// The answers
		$this->html .= '	<tr>';
		$this->html .= '		<td colspan="3"><strong>'.get_lang('AnswerOptions').'</strong></td>';
		$this->html .= '	</tr>';
		$total_number_of_answers = count($form_content['answers']);
		foreach ($form_content['answers'] as $key=>$value)
		{
			$this->html .= '	<tr>';
			$this->html .= '		<td align="right"><label for="answers['.$key.']">'.($key+1).'</label></td>';
			//$this->html .= '		<td><input type="text" name="answers['.$key.']" id="answers['.$key.']" value="'.$form_content['answers'][$key].'" /></td>';
			$this->html .= '		<td width="500">'.api_return_html_area('answers['.$key.']', stripslashes($form_content['answers'][$key])).'</td>';
			$this->html .= '		<td>';
			if ($key<$total_number_of_answers-1)
			{
				$this->html .= '			<input type="image" src="../img/down.gif"  value="move_down['.$key.']" name="move_down['.$key.']"/>';
			}
			if ($key>0)
			{
				$this->html .= '			<input type="image" src="../img/up.gif"  value="move_up['.$key.']" name="move_up['.$key.']"/>';
			}
			if ($total_number_of_answers> 2)
			{
				$this->html .= '			<input type="image" src="../img/delete.gif"  value="delete_answer['.$key.']" name="delete_answer['.$key.']"/>';
			}
			$this->html .= ' 		</td>';
			$this->html .= '	</tr>';
		}
		// The buttons for adding or removing
		$this->html .= parent :: add_remove_buttons($form_content);
	}

	function render_question($form_content, $answers=array())
	{
		/*
		echo '<div style="border: 1px solid red;">';
		echo '<pre>';
		print_r($answers);
		echo '</pre></div>';
		*/
		$this->html = '<table>';
		foreach ($form_content['options'] as $key=>$value)
		{
			$this->html .= '<tr>
								<td>'.$value.'</td>';
			$this->html .= '	<td>';
			$this->html .= '<select name="question'.$form_content['question_id'].'['.$key.']">';
			$this->html .= '<option value="--">--</option>';
			for($i=1; $i<=$form_content['maximum_score']; $i++)
			{
				$this->html .= '<option value="'.$i.'"';
				if ($answers[$key] == $i)
				{
					$this->html .= 'selected="selected" ';
				}
				$this->html .= '>'.$i.'</option>';
			}
			$this->html .= '</select>';
			$this->html .= '	</td>';
			$this->html .= '</tr>';
		}
		$this->html .= '</table>';
		echo '<div class="survey_question_wrapper">';
		echo '<div class="survey_question">'.$form_content['survey_question'].'</div>';
		echo '<div class="survey_question_options">';
		//echo '<select name="question'.$form_content['question_id'].'" id="select">';
		echo $this->html;
		//echo '</select>';
		echo '</div>';
	}
}

function db_escape_string($value)
{

}

function check_first_last_question($survey_id, $continue=true)
{
	// table definitions
	$tbl_survey_question 			= Database :: get_course_table(TABLE_SURVEY_QUESTION);
	$table_survey_question_option 	= Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);

	// getting the information of the question
	$sql = "SELECT * FROM $tbl_survey_question WHERE survey_id='".Database::escape_string($survey_id)."' ORDER BY sort ASC";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	$total = mysql_num_rows($result);
	$counter=1;
	$error = false;
	while ($row = mysql_fetch_assoc($result))
	{
		if ($counter == 1 AND $row['type'] == 'pagebreak')
		{
			Display::display_error_message(get_lang('PagebreakNotFirst'), false);
			$error = true;
		}
		if ($counter == $total AND $row['type'] == 'pagebreak')
		{
			Display::display_error_message(get_lang('PagebreakNotLast'), false);
			$error = true;
		}		
		$counter++;
	}
	
	if (!$continue AND $error)
	{
		Display::display_footer();
		exit;
	}
}
?>