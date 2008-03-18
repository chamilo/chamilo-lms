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
		$return = Database::fetch_array($result,'ASSOC');

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
			$survey_id = Database::insert_id();

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
						'".date('Y-m-d H:i:s')."',
						'".$_course['id']."')";
			$result = api_sql_query($sql, __FILE__, __LINE__);
			$return	= Database::insert_id();
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
		$row = Database::fetch_array($result,'ASSOC');
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
		while ($row = Database::fetch_array($result,'ASSOC'))
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
		while ($row = Database::fetch_array($result,'ASSOC'))
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
		while ($row = Database::fetch_array($result,'ASSOC'))
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
		if (strlen($form_content['question'])>1)
		{		//checks lenght of the question	
			$empty_answer=false;	
					
			if ($form_content['type'] != 'percentage')
			{
				for($i=0;$i<count($form_content['answers']);$i++)
				{									
					if (strlen($form_content['answers'][$i])<1) 
					{
						$empty_answer=true;					
						break;									
					}							
				}
			}
			
			if ($form_content['type'] == 'score' )
			{
				if (strlen($form_content['maximum_score'])<1)
				{
					$empty_answer=true;					
				}
			}
									
			if (!$empty_answer)  
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
					$row = Database::fetch_array($result,'ASSOC');
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
					$question_id = Database::insert_id();
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
				$message_options=survey_manager::save_question_options($form_content, $survey_data);
			}
			else
			{			
				$return_message='PleasFillAllAnswer'; 
			}		
		}
		else
		{
			$return_message='PleaseEnterAQuestion';	 
		}
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
				$row = Database::fetch_array($result,'ASSOC');
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
				$shared_question_id = Database::insert_id();
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
		$found = false;
		while ($row = Database::fetch_array($result,'ASSOC'))
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

		$counter=1;		
		if(is_array($form_content['answers'])){
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
						FROM $table_survey_answer answered_user
						LEFT JOIN $table_user as user
							ON answered_user.user = user.user_id
						WHERE survey_id= '".Database::escape_string($survey_data['survey_id'])."'";
		}
		else 
		{
			$sql = "SELECT DISTINCT user FROM $table_survey_answer WHERE survey_id= '".Database::escape_string($survey_data['survey_id'])."'";
		}
		$res = api_sql_query($sql, __FILE__, __LINE__);
		while ($row = Database::fetch_array($res,'ASSOC'))
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
	public $html;

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
									
			if ($message == 'QuestionAdded' || $message == 'QuestionUpdated' ) {				
				if ($config['survey']['debug'])
				{								
					Display :: display_header();
					Display :: display_confirmation_message($message.'<br />'.get_lang('ReturnTo').' <a href="survey.php?survey_id='.$_GET['survey_id'].'">'.get_lang('Survey').'</a>', false);					
				}
				else			
				{					
					header('location:survey.php?survey_id='.Security::remove_XSS($_GET['survey_id']).'&message='.$message);
				}
			}
			else 
			{			
				if ($message == 'PleaseEnterAQuestion' || $message=='PleasFillAllAnswer'){							
					$_SESSION['temp_user_message']=$form_content['question'];
					$_SESSION['temp_sys_message']=$message;
					$_SESSION['temp_answers']=$form_content['answers'];																						
					header('location:question.php?'.api_get_cidreq().'&survey_id='.Security::remove_XSS($_GET['survey_id']).'&action='.Security::remove_XSS($_GET['action']).'&type='.Security::remove_XSS($_GET['type']).'');																  										
				}
				
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

/**
 * This class offers a series of general utility functions for survey querying and display
 * @package dokeos.survey
 */
class SurveyUtil {
	/**
	 * Checks whether the given survey has a pagebreak question as the first or the last question.
	 * If so, break the current process, displaying an error message
	 * @param	integer	Survey ID (database ID)
	 * @param	boolean	Optional. Whether to continue the current process or exit when breaking condition found. Defaults to true (do not break).
	 * @return	void 
	 */
	function check_first_last_question($survey_id, $continue=true)
	{
		// table definitions
		$tbl_survey_question 			= Database :: get_course_table(TABLE_SURVEY_QUESTION);
		$table_survey_question_option 	= Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
	
		// getting the information of the question
		$sql = "SELECT * FROM $tbl_survey_question WHERE survey_id='".Database::escape_string($survey_id)."' ORDER BY sort ASC";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		$total = Database::num_rows($result);
		$counter=1;
		$error = false;
		while ($row = Database::fetch_array($result,'ASSOC'))
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
	/**
	 * This function removes an (or multiple) answer(s) of a user on a question of a survey
	 *
	 * @param mixed   The user id or email of the person who fills the survey
	 * @param integer The survey id
	 * @param integer The question id
	 * @param integer The option id
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 */
	function remove_answer($user, $survey_id, $question_id)
	{
		global $_course;
		// table definition
		$table_survey_answer 		= Database :: get_course_table(TABLE_SURVEY_ANSWER, $_course['db_name']);
		$sql = "DELETE FROM $table_survey_answer
				WHERE user = '".Database::escape_string($user)."'
				AND survey_id = '".Database::escape_string($survey_id)."'
				AND question_id = '".Database::escape_string($question_id)."'";
		$result = api_sql_query($sql, __FILE__, __LINE__);
	}
	/**
	 * This function stores an answer of a user on a question of a survey
	 *
	 * @param mixed   The user id or email of the person who fills the survey
	 * @param integer Survey id
	 * @param integer Question id
	 * @param integer Option id
	 * @param string  Option value
	 * @param array	  Survey data settings
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 */
	function store_answer($user, $survey_id, $question_id, $option_id, $option_value, $survey_data)
	{
		global $_course;
		global $types;
		// table definition
		$table_survey_answer 		= Database :: get_course_table(TABLE_SURVEY_ANSWER, $_course['db_name']);
	
		// make the survey anonymous
		if ($survey_data['anonymous'] == 1)
		{
			if (!$_SESSION['surveyuser'])
			{
				$user = md5($user.time());
				$_SESSION['surveyuser'] = $user;
			}
			else
			{
				$user = $_SESSION['surveyuser'];
			}
		}
		$sql = "INSERT INTO $table_survey_answer (user, survey_id, question_id, option_id, value) VALUES (
				'".Database::escape_string($user)."',
				'".Database::escape_string($survey_id)."',
				'".Database::escape_string($question_id)."',
				'".Database::escape_string($option_id)."',
				'".Database::escape_string($option_value)."'
				)";
		$result = api_sql_query($sql, __FILE__, __LINE__);
	}
	/**
	 * This function checks the parameters that are used in this page
	 *
	 * @return 	string 	The header, an error and the footer if any parameter fails, else it returns true
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
					$people_filled_userids[]=$value['invited_user'];
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
			$tool_name = get_lang('Reporting');
			Display::display_header($tool_name);
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
	 * @return	void
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
			SurveyUtil::display_question_report($survey_data);
		}
		if ($_GET['action'] == 'userreport')
		{
			SurveyUtil::display_user_report();
		}
		if ($_GET['action'] == 'comparativereport')
		{
			SurveyUtil::display_comparative_report();
		}
		if ($_GET['action'] == 'completereport')
		{
			SurveyUtil::display_complete_report();
		}
	}
	
	/**
	 * This function displays the user report which is basically nothing more than a one-page display of all the questions
	 * of the survey that is filled with the answers of the person who filled the survey.
	 *
	 * @return 	string	html code of the one-page survey with the answers of the selected user
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version February 2007 - Updated March 2008
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
		echo '<option value="reporting.php?action='.Security::remove_XSS($_GET['action']).'&amp;survey_id='.Security::remove_XSS($_GET['survey_id']).'">'.get_lang('SelectUser').'</option>';
		
		foreach ($people_filled as $key=>$person)
		{
			if ($survey_data['anonymous'] == 0)
			{
				$name = $person['firstname'].' '.$person['lastname'];
				$id = $person['user_id'];
			}
			else 
			{
				$name  = $key+1;
				$id = $person;
			}
			echo '<option value="reporting.php?action='.Security::remove_XSS($_GET['action']).'&amp;survey_id='.Security::remove_XSS($_GET['survey_id']).'&amp;user='.$id.'" ';
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
			}
		}
	}
	
	/**
	 * This function displays the report by question.
	 *
	 * It displays a table with all the options of the question and the number of users who have answered positively on the option.
	 * The number of users who answered positive on a given option is expressed in an absolute number, in a percentage of the total
	 * and graphically using bars
	 * By clicking on the absolute number you get a list with the persons who have answered this.
	 * You can then click on the name of the person and you will then go to the report by user where you see all the
	 * answers of that user.
	 *
	 * @param 	array 	All the survey data
	 * @return 	string	html code that displays the report by question
	 * @todo allow switching between horizontal and vertical.
	 * @todo multiple response: percentage are probably not OK
	 * @todo the question and option text have to be shortened and should expand when the user clicks on it.
	 * @todo the pagebreak and comment question types should not be shown => removed from $survey_data before
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version February 2007 - Updated March 2008
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
			$offset = Database::escape_string($_GET['question']);
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
		$question = Database::fetch_array($result);
		
		// navigate through the questions (next and previous)
		if ($_GET['question'] <> 0)
		{
			echo '<a href="reporting.php?action='.Security::remove_XSS($_GET['action']).'&amp;survey_id='.$_GET['survey_id'].'&amp;question='.Security::remove_XSS($offset-1).'"> &lt;&lt; '.get_lang('PreviousQuestion').'</a>  ';
		}
		else
		{
			echo '&lt;&lt;'.get_lang('PreviousQuestion').' ';
		}
		echo ' | ';
		if ($_GET['question'] < ($survey_data['number_of_questions']-1))
		{
			echo '<a href="reporting.php?action='.$_GET['action'].'&amp;survey_id='.Security::remove_XSS($_GET['survey_id']).'&amp;question='.Security::remove_XSS($offset+1).'">'.get_lang('NextQuestion').'&gt;&gt; </a>';
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
			$options = SurveyUtil::display_question_report_score($survey_data, $question, $offset);
		}
		elseif ($question['type'] == 'open')
		{
			/** @todo also get the user who has answered this */
			$sql = "SELECT * FROM $table_survey_answer WHERE survey_id='".Database::escape_string($_GET['survey_id'])."'
						AND question_id = '".Database::escape_string($question['question_id'])."'";
			$result = api_sql_query($sql, __FILE__, __LINE__);
			while ($row = Database::fetch_array($result))
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
			while ($row = Database::fetch_array($result))
			{
				$options[$row['question_option_id']] = $row;
			}	
			// getting the answers
			$sql = "SELECT *, count(answer_id) as total FROM $table_survey_answer
						WHERE survey_id='".Database::escape_string($_GET['survey_id'])."'
						AND question_id = '".Database::escape_string($question['question_id'])."'
						GROUP BY option_id, value";
			$result = api_sql_query($sql, __FILE__, __LINE__);
			while ($row = Database::fetch_array($result))
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
				if($number_of_answers == 0)
				{
					$answers_number = 0;
				}
				else
				{
					$answers_number = $absolute_number/$number_of_answers*100;
				}
				echo '	<tr>';
				echo '		<td>'.$value['option_text'].'</td>';
				echo '		<td align="right"><a href="reporting.php?action='.Security::remove_XSS($_GET['action']).'&amp;survey_id='.Security::remove_XSS($_GET['survey_id']).'&amp;question='.Security::remove_XSS($offset).'&amp;viewoption='.$value['question_option_id'].'">'.$absolute_number.'</a></td>';
				echo '		<td align="right">'.round($answers_number, 2).' %</td>';
				echo '		<td align="right">';
				$size = $answers_number*2;
				if ($size > 0)
				{
					echo '<div style="border:1px solid #264269; background-color:#aecaf4; height:10px; width:'.$size.'px">&nbsp;</div>';
				}
				echo '		</td>';
				echo '	</tr>';
			}
	
			// displaying the table: footer (totals)
			echo '	<tr>';
			echo '		<td style="border-top:1px solid black;"><b>'.get_lang('Total').'</b></td>';
			echo '		<td style="border-top:1px solid black;" align="right"><b>'.($number_of_answers==0?'0':$number_of_answers).'</b></td>';
			echo '		<td style="border-top:1px solid black;">&nbsp;</td>';
			echo '		<td style="border-top:1px solid black;">&nbsp;</td>';
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
			while ($row = Database::fetch_array($result))
			{
				echo '<a href="reporting.php?action=userreport&survey_id='.Security::remove_XSS($_GET['survey_id']).'&user='.$row['user'].'">'.$row['user'].'</a><br />';
			}
		}
	}
	/**
	 * Display score data about a survey question
	 * @param	array	Question info
	 * @param	integer	The offset of results shown
	 * @return	void 	(direct output)
	 */
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
		while ($row = Database::fetch_array($result))
		{
			$options[$row['question_option_id']] = $row;
		}
	
		// getting the answers
		$sql = "SELECT *, count(answer_id) as total FROM $table_survey_answer
					WHERE survey_id='".Database::escape_string($_GET['survey_id'])."'
					AND question_id = '".Database::escape_string($question['question_id'])."'
					GROUP BY option_id, value";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($row = Database::fetch_array($result))
		{
			$number_of_answers += $row['total'];
			$data[$row['option_id']][$row['value']] = $row;
		}
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
				echo '		<td><a href="reporting.php?action='.Security::remove_XSS($_GET['action']).'&amp;survey_id='.Security::remove_XSS($_GET['survey_id']).'&amp;question='.Security::remove_XSS($offset).'&amp;viewoption='.$value['question_option_id'].'&amp;value='.$i.'">'.$absolute_number.'</a></td>';
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
	 * @return	string	HTML code
	 * @todo open questions are not in the complete report yet.
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
		echo '<form id="form1a" name="form1a" method="post" action="'.api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&survey_id='.Security::remove_XSS($_GET['survey_id']).'">';
		echo '<input type="hidden" name="export_report" value="export_report">';
		echo '<input type="hidden" name="export_format" value="csv">';
		echo '</form>';
		echo '<form id="form1b" name="form1b" method="post" action="'.api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&survey_id='.Security::remove_XSS($_GET['survey_id']).'">';
		echo '<input type="hidden" name="export_report" value="export_report">';
		echo '<input type="hidden" name="export_format" value="xls">';
		echo '</form>';
		echo '<form id="form2" name="form2" method="post" action="'.api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&survey_id='.Security::remove_XSS($_GET['survey_id']).'">';
		echo '<a class="survey_export_link" href="#" onclick="document.form1a.submit();"><img align="absbottom" src="'.api_get_path(WEB_IMG_PATH).'excel.gif">&nbsp;'.get_lang('ExportAsCSV').'</a>';
		echo '<a class="survey_export_link" href="#" onclick="document.form1b.submit();"><img align="absbottom" src="'.api_get_path(WEB_IMG_PATH).'excel.gif">&nbsp;'.get_lang('ExportAsXLS').'</a>';
		// the table
		echo '<br /><table class="data_table" border="1">';
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
				WHERE questions.question_id = options.question_id
				AND questions.survey_id = '".Database::escape_string($_GET['survey_id'])."'
				GROUP BY questions.question_id";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($row = Database::fetch_array($result))
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
		while ($row = Database::fetch_array($result))
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
		while ($row = Database::fetch_array($result))
		{
			if ($old_user <> $row['user'] AND $old_user<>'')
			{
				SurveyUtil::display_complete_report_row($possible_answers, $answers_of_user, $old_user, $questions);
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
		SurveyUtil::display_complete_report_row($possible_answers, $answers_of_user, $old_user, $questions); // this is to display the last user
	
		echo '</table>';
	
		echo '</form>';
	}
	
	
	/**
	 * This function displays a row (= a user and his/her answers) in the table of the complete report.
	 *
	 * @param 	array	Possible options
	 * @param 	array 	User answers
	 * @param	mixed	User ID or user details string
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version February 2007 - Updated March 2008
	 */
	function display_complete_report_row($possible_options, $answers_of_user, $user, $questions)
	{
		global $survey_data;
		
		$table_survey_question = Database :: get_course_table(TABLE_SURVEY_QUESTION);
		echo '<tr>';
		if ($survey_data['anonymous'] == 0)
		{
			if(intval($user)!==0)
			{
				$sql = 'SELECT firstname, lastname FROM '.Database::get_main_table(TABLE_MAIN_USER).' WHERE user_id='.intval($user);
				$rs = api_sql_query($sql, __FILE__, __LINE__);
				if($row = Database::fetch_array($rs))
				{
					$user_displayed = $row['lastname'].' '.$row['firstname'];
				}
				else
				{
					$user_displayed = '-';
				}
				echo '		<th><a href="'.api_get_self().'?action=userreport&survey_id='.Security::remove_XSS($_GET['survey_id']).'&user='.$user.'">'.$user_displayed.'</a></th>'; // the user column
			}
			else
			{
				echo '		<th>'.$user.'</th>'; // the user column
			}
		}
		else
		{ 
			echo '<th>-</th>';
		}
		
	
		foreach ($possible_options as $question_id=>$possible_option)
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
	 * Quite similar to display_complete_report(), returns an HTML string 
	 * that can be used in a csv file
	 * @todo consider merging this function with display_complete_report
	 * @return	string	The contents of a csv file
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
				ON questions.question_id = options.question_id "
				." AND questions.survey_id = '".Database::escape_string($_GET['survey_id'])."'
				GROUP BY questions.question_id";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($row = Database::fetch_array($result))
		{
			// we show the questions if
			// 1. there is no question filter and the export button has not been clicked
			// 2. there is a quesiton filter but the question is selected for display
			if (!($_POST['submit_question_filter']) OR (is_array($_POST['questions_filter']) && in_array($row['question_id'], $_POST['questions_filter'])))
			{
				// we do not show comment and pagebreak question types
				if ($row['type'] <> 'comment' AND $row['type'] <> 'pagebreak')
				{
					if($row['number_of_options'] == 0 && $row['type']=='open')
					{
							$return .= str_replace("\r\n",'  ',html_entity_decode(strip_tags($row['survey_question']))).';';					
					}
					else
					{
						for ($ii = 0; $ii < $row['number_of_options']; $ii ++)
						{
							$return .= str_replace("\r\n",'  ',html_entity_decode(strip_tags($row['survey_question']))).';';
						}
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
		$possible_answers = array();
		$possible_answers_type = array();
		while ($row = Database::fetch_array($result))
		{
			// we show the options if
			// 1. there is no question filter and the export button has not been clicked
			// 2. there is a quesiton filter but the question is selected for display
			if (!($_POST['submit_question_filter']) OR (is_array($_POST['questions_filter']) && in_array($row['question_id'], $_POST['questions_filter'])))
			{
				// we do not show comment and pagebreak question types
				if ($row['type'] <> 'comment' AND $row['type'] <> 'pagebreak')
				{
					$return .= html_entity_decode(strip_tags($row['option_text'])).';';
					$possible_answers[$row['question_id']][$row['question_option_id']] =$row['question_option_id'];
					$possible_answers_type[$row['question_id']] = $row['type'];
				}
			}
		}
		$return .= "\n";
	
		// getting all the answers of the users
		$old_user='';
		$answers_of_user = array();
		$sql = "SELECT * FROM $table_survey_answer WHERE survey_id='".Database::escape_string($_GET['survey_id'])."' ORDER BY user ASC";
		
		$open_question_iterator = 1;
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($row = Database::fetch_array($result))
		{
			if ($old_user <> $row['user'] AND $old_user <> '')
			{
				$return .= SurveyUtil::export_complete_report_row($possible_answers, $answers_of_user, $old_user);
				$answers_of_user=array();
			}
			if($possible_answers_type[$row['question_id']] == 'open')
			{
				$temp_id = 'open'.$open_question_iterator;
				$answers_of_user[$row['question_id']][$temp_id] = $row;
				$open_question_iterator++;
			}
			else
			{
				$answers_of_user[$row['question_id']][$row['option_id']] = $row;
			}
			$old_user = $row['user'];
		}
		$return .= SurveyUtil::export_complete_report_row($possible_answers, $answers_of_user, $old_user); // this is to display the last user
		return $return;
	}
	
	
	/**
	 * Add a line to the csv file
	 *
	 * @param	array	Possible answers
	 * @param	array	User's answers
	 * @param 	mixed	User ID or user details as string - Used as a string in the result string
	 * @return	string	One line of the csv file
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version February 2007
	 */
	function export_complete_report_row($possible_options, $answers_of_user, $user)
	{
		$return = $user.';'; // the user column
	
		if(is_array($possible_options))
		{
			foreach ($possible_options as $question_id=>$possible_option)
			{
				if(is_array($possible_option) && count($possible_option)>0)
				{
					foreach ($possible_option as $option_id=>$value)
					{
						$key = array_keys($answers_of_user[$question_id]);
						if(substr($key[0],0,4)=='open')
						{
							$return .= '"'.str_replace('"','""',html_entity_decode(strip_tags($answers_of_user[$question_id][$key[0]]['option_id']))).'"';
						}
						elseif (!empty($answers_of_user[$question_id][$option_id]))
						{
							$return .= 'v';
						}
						$return .= ';';
					}
				}
			}
		}
		$return .= "\n";
		return $return;
	}
	/**
	 * Quite similar to display_complete_report(), returns an HTML string 
	 * that can be used in a csv file
	 * @todo consider merging this function with display_complete_report
	 * @return	string	The contents of a csv file
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version February 2007
	 */
	function export_complete_report_xls($filename)
	{
		require_once(api_get_path(LIBRARY_PATH).'pear/Spreadsheet_Excel_Writer/Writer.php');
		$workbook = new Spreadsheet_Excel_Writer();
		$workbook->send($filename);
		$worksheet =& $workbook->addWorksheet('Report 1');
		$line = 0;
		$column = 1; //skip the first column (row titles)
		
		// Database table definitions
		$table_survey_question 			= Database :: get_course_table(TABLE_SURVEY_QUESTION);
		$table_survey_question_option 	= Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
		$table_survey_answer 			= Database :: get_course_table(TABLE_SURVEY_ANSWER);
	
		// First line (questions)
		$sql = "SELECT questions.question_id, questions.type, questions.survey_question, count(options.question_option_id) as number_of_options
				FROM $table_survey_question questions LEFT JOIN $table_survey_question_option options
				ON questions.question_id = options.question_id "
				." AND questions.survey_id = '".Database::escape_string($_GET['survey_id'])."'
				GROUP BY questions.question_id";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($row = Database::fetch_array($result))
		{
			// we show the questions if
			// 1. there is no question filter and the export button has not been clicked
			// 2. there is a quesiton filter but the question is selected for display
			if (!($_POST['submit_question_filter']) OR (is_array($_POST['questions_filter']) && in_array($row['question_id'], $_POST['questions_filter'])))
			{
				// we do not show comment and pagebreak question types
				if ($row['type'] <> 'comment' AND $row['type'] <> 'pagebreak')
				{
					if($row['number_of_options'] == 0 && $row['type']=='open')
					{
							$worksheet->write($line,$column,html_entity_decode(strip_tags($row['survey_question'])));
							$column ++;					
					}
					else
					{
						for ($ii = 0; $ii < $row['number_of_options']; $ii ++)
						{
							$worksheet->write($line,$column,html_entity_decode(strip_tags($row['survey_question'])));
							$column ++;
						}
					}
				}
			}
		}
		$line++;
		$column = 1;
	
		// getting all the questions and options (second line)
		$sql = "SELECT 	survey_question.question_id, survey_question.survey_id, survey_question.survey_question, survey_question.display, survey_question.sort, survey_question.type,
						survey_question_option.question_option_id, survey_question_option.option_text, survey_question_option.sort as option_sort
				FROM $table_survey_question survey_question
				LEFT JOIN $table_survey_question_option survey_question_option
				ON survey_question.question_id = survey_question_option.question_id
				WHERE survey_question.survey_id = '".Database::escape_string($_GET['survey_id'])."'
				ORDER BY survey_question.sort ASC";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		$possible_answers = array();
		$possible_answers_type = array();
		while ($row = Database::fetch_array($result))
		{
			// we show the options if
			// 1. there is no question filter and the export button has not been clicked
			// 2. there is a quesiton filter but the question is selected for display
			if (!($_POST['submit_question_filter']) OR (is_array($_POST['questions_filter']) && in_array($row['question_id'], $_POST['questions_filter'])))
			{
				// we do not show comment and pagebreak question types
				if ($row['type'] <> 'comment' AND $row['type'] <> 'pagebreak')
				{
					$worksheet->write($line,$column,html_entity_decode(strip_tags($row['option_text'])));
					$possible_answers[$row['question_id']][$row['question_option_id']] =$row['question_option_id'];
					$possible_answers_type[$row['question_id']] = $row['type'];
					$column++;
				}
			}
		}

		// getting all the answers of the users
		$line ++;
		$column = 0;
		$old_user='';
		$answers_of_user = array();
		$sql = "SELECT * FROM $table_survey_answer " .
				"WHERE survey_id='".Database::escape_string($_GET['survey_id'])."' " .
						"ORDER BY user ASC";
		$open_question_iterator = 1;
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($row = Database::fetch_array($result))
		{
			if ($old_user <> $row['user'] AND $old_user <> '')
			{
				$return = SurveyUtil::export_complete_report_row_xls($possible_answers, $answers_of_user, $old_user);
				foreach($return as $elem)
				{
					$worsheet->write($line,$column,$elem);
					$column++;
				}
				$answers_of_user=array();
				$line++;
				$column=0;
			}
			if($possible_answers_type[$row['question_id']] == 'open')
			{
				$temp_id = 'open'.$open_question_iterator;
				$answers_of_user[$row['question_id']][$temp_id] = $row;
				$open_question_iterator++;
			}
			else
			{
				$answers_of_user[$row['question_id']][$row['option_id']] = $row;
			}
			$old_user = $row['user'];
		}
		$return = SurveyUtil::export_complete_report_row_xls($possible_answers, $answers_of_user, $old_user); // this is to display the last user
		foreach($return as $elem)
		{
			$worksheet->write($line,$column,$elem);
			$column++;
		}
		$workbook->close();
		return null;
	}
	
	
	/**
	 * Add a line to the csv file
	 *
	 * @param	array	Possible answers
	 * @param	array	User's answers
	 * @param 	mixed	User ID or user details as string - Used as a string in the result string
	 * @return	string	One line of the csv file
	 */
	function export_complete_report_row_xls($possible_options, $answers_of_user, $user)
	{
		$return = array();
		$return[] = $user; // the user column
	
		if(is_array($possible_options))
		{
			foreach ($possible_options as $question_id=>$possible_option)
			{
				if(is_array($possible_option) && count($possible_option)>0)
				{
					foreach ($possible_option as $option_id=>$value)
					{
						$key = array_keys($answers_of_user[$question_id]);
						if(substr($key[0],0,4)=='open')
						{
							$return[] = html_entity_decode(strip_tags($answers_of_user[$question_id][$key[0]]['option_id']));
						}
						elseif (!empty($answers_of_user[$question_id][$option_id]))
						{
							$return[] = 'v';
						}
						else
						{
							$return[] = '';
						}
					}
				}
			}
		}
		return $return;
	}
	
	/**
	 * This function displays the comparative report which allows you to compare two questions
	 * A comparative report creates a table where one question is on the x axis and a second question is on the y axis.
	 * In the intersection is the number of people who have answerd positive on both options.
	 *
	 * @return	string	HTML code
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
		echo '<form id="form1" name="form1" method="get" action="'.api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&survey_id='.Security::remove_XSS($_GET['survey_id']).'&xaxis='.Security::remove_XSS($_GET['xaxis']).'&y='.Security::remove_XSS($_GET['yaxis']).'">';
		// survey_id
		echo '<input type="hidden" name="action" value="'.Security::remove_XSS($_GET['action']).'"/>';
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
			$answers_x = SurveyUtil::get_answers_of_question_by_user($_GET['survey_id'], $_GET['xaxis']);
			$answers_y = SurveyUtil::get_answers_of_question_by_user($_GET['survey_id'], $_GET['yaxis']);
	
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
										echo SurveyUtil::comparative_check($answers_x, $answers_y, $question_x['answersid'][($ii-1)], $question_y['answersid'][($ij)], $x, $y);
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
									echo SurveyUtil::comparative_check($answers_x, $answers_y, $question_x['answersid'][($ii-1)], $question_y['answersid'][($ij)], 0, $y);
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
										echo SurveyUtil::comparative_check($answers_x, $answers_y, $question_x['answersid'][($ii-1)], $question_y['answersid'][($ij)], $x, 0);
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
									echo SurveyUtil::comparative_check($answers_x, $answers_y, $question_x['answersid'][($ii-1)], $question_y['answersid'][($ij)]);
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
	 * Get all the answers of a question grouped by user
	 *
	 * @param	integer	Survey ID
	 * @param	integer	Question ID
	 * @return 	Array	Array containing all answers of all users, grouped by user
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version February 2007 - Updated March 2008
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
		while ($row = Database::fetch_array($result))
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
	 * Count the number of users who answer positively on both options
	 *
	 * @param	array	All answers of the x axis
	 * @param	array	All answers of the y axis
	 * @param	integer x axis value (= the option_id of the first question)
	 * @param	integer y axis value (= the option_id of the second question)
	 * @return	integer Number of users who have answered positively to both options
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
	/**
	 * Get all the information about the invitations of a certain survey
	 *
	 * @return	array	Lines of invitation [user, code, date, empty element]
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 *
	 * @todo use survey_id parameter instead of $_GET
	 */
	function get_survey_invitations_data()
	{
		// Database table definition
		$table_survey_invitation 		= Database :: get_course_table(TABLE_SURVEY_INVITATION);
		$table_user 					= Database :: get_main_table(TABLE_MAIN_USER);
	
		$sql = "SELECT
					survey_invitation.user as col1,
					survey_invitation.invitation_code as col2,
					survey_invitation.invitation_date as col3,
					'' as col4
					FROM $table_survey_invitation survey_invitation
			LEFT JOIN $table_user user ON  survey_invitation.user = user.user_id
			WHERE survey_invitation.survey_id = '".Database::escape_string($_GET['survey_id'])."'";
		$res = api_sql_query($sql, __FILE__, __LINE__);
		while ($row = Database::fetch_array($res))
		{
			$survey_invitation_data[] = $row;
		}
		return $survey_invitation_data;
	}
	
	/**
	 * Get the total number of survey invitations for a given survey (through $_GET['survey_id'])
	 *
	 * @return	integer	Total number of survey invitations
	 *
	 * @todo use survey_id parameter instead of $_GET
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 */
	function get_number_of_survey_invitations()
	{
		// Database table definition
		$table_survey_invitation 		= Database :: get_course_table(TABLE_SURVEY_INVITATION);
	
		$sql = "SELECT count(user) AS total FROM $table_survey_invitation WHERE survey_id='".Database::escape_string($_GET['survey_id'])."'";
		$res = api_sql_query($sql, __FILE__, __LINE__);
		$row = mysql_fetch_assoc($res);
		return $row['total'];
	}
	/**
	 * Save the invitation mail
	 *
	 * @param string 	Text of the e-mail
	 * @param integer	Whether the mail contents are for invite mail (0, default) or reminder mail (1)
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 */
	function save_invite_mail($mailtext, $reminder=0)
	{
		// Database table definition
		$table_survey 					= Database :: get_course_table(TABLE_SURVEY);
	
		// reminder or not
		if ($reminder == 0)
		{
			$mail_field = 'invite_mail';
		}
		else
		{
			$mail_field = 'reminder_mail';
		}
	
		$sql = "UPDATE $table_survey SET $mail_field = '".Database::escape_string($mailtext)."' WHERE survey_id = '".Database::escape_string($_GET['survey_id'])."'";
		$result = api_sql_query($sql, __FILE__, __LINE__);
	}
	
	/**
	 * This function saves all the invitations of course users and additional users in the
	 *
	 * @param	array	Users array
	 * @param 	string	Title of the invitation, used as the title of the mail
	 * @param 	string	Text of the invitation, used as the text of the mail.
	 * 				 The text has to contain a **link** string or this will automatically be added to the end
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 *
	 * @todo create the survey link
	 */
	function save_invitations($users_array, $invitation_title, $invitation_text, $reminder=0)
	{
		global $_user;
		global $_course;
		global $_configuration;
	
		// getting the survey information
		$survey_data = survey_manager::get_survey($_GET['survey_id']);
	
		// Database table definition
		$table_survey_invitation 	= Database :: get_course_table(TABLE_SURVEY_INVITATION);
		$table_user 				= Database :: get_main_table(TABLE_MAIN_USER);
	
		$already_invited = array();
		$survey_invitations = array();
		// get the people who are already invited
		if ($reminder == 1)
		{
			$survey_invitations = SurveyUtil::get_invitations($survey_data['survey_code']);
		}
		else
		{
			$already_invited = SurveyUtil::get_invited_users($survey_data['code']);
		}
	
		$counter = 0;
		if (is_array($users_array))
		{
			foreach ($users_array as $key=>$value)
			{
				if(isset($value))
				{
					// generating the unique code
					if(is_array($survey_invitations))
					{
						if ($reminder == 1 AND array_key_exists($value,$survey_invitations))
						{
							$invitation_code = $survey_invitations[$value]['invitation_code'];
						}
						else 
						{
							$invitation_code = md5($value.microtime());
						}
					}
					$survey_link = '';
					$full_invitation_text= '';
								
					// storing the invitation (only if the user_id is not in $already_invited['course_users'] OR email is not in $already_invited['additional_users']
					$add_users_array = explode(';',$already_invited['additional_users']);
					if ((is_numeric($value) AND !in_array($value,$already_invited['course_users'])) OR (!is_numeric($value) AND !in_array($value,$add_users_array)))
					{
						if(is_array($survey_invitations))
						{
							if (!array_key_exists($value,$survey_invitations))
							{
								$sql = "INSERT INTO $table_survey_invitation (user, survey_code, invitation_code, invitation_date) VALUES
											('".Database::escape_string($value)."','".Database::escape_string($survey_data['code'])."','".Database::escape_string($invitation_code)."','".Database::escape_string(date('Y-m-d H:i:s'))."')";
								$result = api_sql_query($sql, __FILE__, __LINE__);
							}
						}
						// replacing the **link** part with a valid link for the user
						$survey_link = $_configuration['root_web'].$_configuration['code_append'].'survey/'.'fillsurvey.php?course='.$_course['sysCode'].'&invitationcode='.$invitation_code;
						$text_link = '<a href="'.$survey_link.'">'.get_lang('ClickHereToAnswerTheSurvey')."</a><br />\r\n<br />\r\n".get_lang('OrCopyPasteTheFollowingUrl')." <br />\r\n ".$survey_link;
						
						$replace_count = 0;
						$full_invitation_text = str_ireplace('**link**', $text_link ,$invitation_text, $replace_count);
						if ($replace_count < 1)
						{
							$full_invitation_text = $full_invitation_text . "<br />\r\n<br />\r\n".$text_link;
						}
						
		
						// optionally: finding the e-mail of the course user
						if (is_numeric($value))
						{
							$sql = "SELECT firstname, lastname, email FROM $table_user WHERE user_id='".Database::escape_string($value)."'";
							$result = api_sql_query($sql, __FILE__, __LINE__);
							$row = Database::fetch_array($result);
							$recipient_email = $row['email'];
							$recipient_name = $row['firstname'].' '.$row['lastname'];
						}
						else
						{
							/** @todo check if the $value is a valid email	 */
							$recipient_email = $value;
						}
		
						// sending the mail
						$sender_name  = $_user['firstName'].' '.$_user['lastName'];
						$sender_email = $_user['mail'];
						
						$replyto = array();
						if(api_get_setting('survey_email_sender_noreply')=='noreply')
						{
							$noreply = api_get_setting('noreply_email_address');
							if(!empty($noreply))
							{
								$replyto['Reply-to'] = $noreply;
								$sender_name = $noreply;
								$sender_email = $noreply;
							}
						}
						api_mail_html($recipient_name, $recipient_email, $invitation_title, $full_invitation_text, $sender_name, $sender_email, $replyto);
						//mail($recipient_email, strip_tags($invitation_title), strip_tags($invitation_text));
						$counter++;
					}
				}
			}
		}
	
		return $counter;
	}
	
	/**
	 * This function recalculates the number of users who have been invited and updates the survey table with this value.
	 *
	 * @param	string	Survey code
	 * @return	void
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 */
	function update_count_invited($survey_code)
	{
		// Database table definition
		$table_survey_invitation 	= Database :: get_course_table(TABLE_SURVEY_INVITATION);
		$table_survey 				= Database :: get_course_table(TABLE_SURVEY);
	
		// counting the number of people that are invited
		$sql = "SELECT count(user) as total FROM $table_survey_invitation WHERE survey_code = '".Database::escape_string($survey_code)."'";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		$row = Database::fetch_array($result);
		$total_invited = $row['total'];
	
		// updating the field in the survey table
		$sql = "UPDATE $table_survey SET invited = '".Database::escape_string($total_invited)."' WHERE code = '".Database::escape_string($survey_code)."'";
		$result = api_sql_query($sql, __FILE__, __LINE__);
	}
	
	/**
	 * This function gets all the invited users for a given survey code.
	 *
	 * @param	string	Survey code
	 * @return 	array	Array containing the course users and additional users (non course users)
	 *
	 * @todo consider making $defaults['additional_users'] also an array
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 */
	function get_invited_users($survey_code)
	{
		// Database table definition
		$table_survey_invitation 	= Database :: get_course_table(TABLE_SURVEY_INVITATION);
	
		// Selecting all the invitations of this survey
		$sql = "SELECT user FROM $table_survey_invitation WHERE survey_code='".Database::escape_string($survey_code)."'";
	
		$defaults = array();
		$defaults['course_users'] = array();
		$defaults['additional_users'] = '';
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($row = Database::fetch_array($result))
		{
			if (is_numeric($row['user']))
			{
				$defaults['course_users'][] = $row['user'];
			}
			else
			{
				if (empty($defaults['additional_users']))
				{
					$defaults['additional_users'] = $row['user'];
				}
				else
				{
					$defaults['additional_users'] .= ';'.$row['user'];
				}
			}
		}
		return $defaults;
	}
	/**
	 * Get all the invitations
	 *
	 * @param	string	Survey code
	 * @return	array	Database rows matching the survey code
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version September 2007
	 */
	function get_invitations($survey_code)
	{
		// Database table definition
		$table_survey_invitation 	= Database :: get_course_table(TABLE_SURVEY_INVITATION);
	
		$sql = "SELECT * FROM $table_survey_invitation WHERE survey_code = '".Database::escape_string($survey_code)."'";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		$return = array();
		while ($row = Database::fetch_array($result))
		{
			$return[$row['user']] = $row;
		}
		return $return;
	}
	/**
	 * This function displays the form for searching a survey
	 *
	 * @return	void	(direct output)
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 *
	 * @todo use quickforms
	 * @todo consider moving this to surveymanager.inc.lib.php
	 */
	function display_survey_search_form()
	{
		echo '<form method="get" action="survey_list.php?search=advanced">';
		echo '<table>
				<tr>
					<td>'.get_lang('Title').'</td>
					<td><input type="text" name="keyword_title"/></td>
				</tr>
				<tr>
					<td>'.get_lang('Code').'</td>
					<td><input type="text" name="keyword_code"/></td>
				</tr>
				<tr>
					<td>'.get_lang('Language').'</td>
					<td>
						<select name="keyword_language"><option value="%">'.get_lang('All').'</option>';
		$languages = api_get_languages();
		foreach ($languages['name'] as $index => $name)
		{
			echo '<option value="'.$languages['folder'][$index].'">'.$name.'</option>';
		}
		echo '			</select>
					</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td><input type="submit" name="do_search" value="'.get_lang('Ok').'"/></td>
				</tr>
			</table>
		</form>';
	}
	
	/**
	 * This function displays the sortable table with all the surveys
	 *
	 * @return	void	(direct output)
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 */
	function display_survey_list()
	{
		$parameters = array();
		if ($_GET['do_search'])
		{
			$message = get_lang('DisplaySearchResults').'<br />';
			$message .= '<a href="'.api_get_self().'">'.get_lang('DisplayAll').'</a>';
			Display::display_normal_message($message, false);
		}
	
		// Create a sortable table with survey-data
		$table = new SortableTable('surveys', 'get_number_of_surveys', 'get_survey_data',2);
		$table->set_additional_parameters($parameters);
		$table->set_header(0, '', false);
		$table->set_header(1, get_lang('SurveyName'));
		$table->set_header(2, get_lang('SurveyCode'));
		$table->set_header(3, get_lang('NumberOfQuestions'));
		$table->set_header(4, get_lang('Author'));
		$table->set_header(5, get_lang('Language'));
		$table->set_header(6, get_lang('Shared'));
		$table->set_header(7, get_lang('AvailableFrom'));
		$table->set_header(8, get_lang('AvailableUntill'));
		$table->set_header(9, get_lang('Invite'));
		$table->set_header(10, get_lang('Anonymous'));
		$table->set_header(11, get_lang('Modify'), false);
		$table->set_column_filter(10, 'anonymous_filter');
		$table->set_column_filter(11, 'modify_filter');
		$table->set_form_actions(array ('delete' => get_lang('DeleteSurvey')));
		$table->display();
	
	}
	
	/**
	 * This function changes the modify column of the sortable table
	 *
	 * @param integer $survey_id the id of the survey
	 * @return html code that are the actions that can be performed on any survey
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 */
	function modify_filter($survey_id)
	{
		global $charset;
		$survey_id = Security::remove_XSS($survey_id);
		$return = '<a href="create_new_survey.php?'.api_get_cidreq().'&amp;action=edit&amp;survey_id='.$survey_id.'">'.Display::return_icon('edit.gif', get_lang('Edit')).'</a>';
		$return .= '<a href="survey_list.php?'.api_get_cidreq().'&amp;action=delete&amp;survey_id='.$survey_id.'" onclick="javascript:if(!confirm(\''.addslashes(htmlentities(get_lang("DeleteSurvey").'?',ENT_QUOTES,$charset)).'\')) return false;">'.Display::return_icon('delete.gif', get_lang('Delete')).'</a>';
		//$return .= '<a href="create_survey_in_another_language.php?id_survey='.$survey_id.'">'.Display::return_icon('copy.gif', get_lang('Copy')).'</a>';
		//$return .= '<a href="survey.php?survey_id='.$survey_id.'">'.Display::return_icon('add.gif', get_lang('Add')).'</a>';
		$return .= '<a href="preview.php?'.api_get_cidreq().'&amp;survey_id='.$survey_id.'">'.Display::return_icon('preview.gif', get_lang('Preview')).'</a>';
		$return .= '<a href="survey_invite.php?'.api_get_cidreq().'&amp;survey_id='.$survey_id.'">'.Display::return_icon('survey_publish.gif', get_lang('Publish')).'</a>';
		$return .= '<a href="survey_list.php?'.api_get_cidreq().'&amp;action=empty&amp;survey_id='.$survey_id.'" onclick="javascript:if(!confirm(\''.addslashes(htmlentities(get_lang("EmptySurvey").'?')).'\')) return false;">'.Display::return_icon('empty.gif', get_lang('EmptySurvey')).'</a>';
		$return .= '<a href="reporting.php?'.api_get_cidreq().'&amp;survey_id='.$survey_id.'">'.Display::return_icon('statistics.gif', get_lang('Reporting')).'</a>';
		return $return;
	}
	/**
	 * Returns "yes" when given parameter is one, "no" for any other value
	 * @param	integer	Whether anonymous or not
	 * @return	string	"Yes" or "No" in the current language
	 */
	function anonymous_filter($anonymous)
	{
		if ($anonymous == 1)
		{
			return get_lang('Yes');
		}
		else
		{
			return get_lang('No');
		}
	}
	
	
	/**
	 * This function handles the search restriction for the SQL statements
	 *
	 * @return	string	Part of a SQL statement or false on error
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 */
	function survey_search_restriction()
	{
		if ($_GET['do_search'])
		{
			if ($_GET['keyword_title']<>'')
			{
				$search_term[] = 'title =\''.Database::escape_string($_GET['keyword_title']).'\'';
			}
			if ($_GET['keyword_code']<>'')
			{
				$search_term[] = 'code =\''.Database::escape_string($_GET['keyword_code']).'\'';
			}
			if ($_GET['keyword_language']<>'%')
			{
				$search_term[] = 'lang =\''.Database::escape_string($_GET['keyword_language']).'\'';
			}
	
			$search_restriction = implode(' AND ', $search_term);
			return $search_restriction;
		}
		else
		{
			return false;
		}
	}
	/**
	 * This function calculates the total number of surveys
	 *
	 * @return	integer	Total number of surveys
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 */
	function get_number_of_surveys()
	{
		global $table_survey;
	
		$search_restriction = SurveyUtil::survey_search_restriction();
		if ($search_restriction)
		{
			$search_restriction = 'WHERE '.$search_restriction;
		}
	
		$sql = "SELECT count(survey_id) AS total_number_of_items FROM ".$table_survey.' '.$search_restriction;
		$res = api_sql_query($sql, __FILE__, __LINE__);
		$obj = Database::fetch_object($res);
		return $obj->total_number_of_items;
	
	}
	
	/**
	 * This function gets all the survey data that is to be displayed in the sortable table
	 *
	 * @param unknown_type $from
	 * @param unknown_type $number_of_items
	 * @param unknown_type $column
	 * @param unknown_type $direction
	 * @return unknown
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version January 2007
	 */
	function get_survey_data($from, $number_of_items, $column, $direction)
	{
		global $table_survey;
		global $table_user;
		global $table_survey_question;
	
		// searching
		$search_restriction = SurveyUtil::survey_search_restriction();
		if ($search_restriction)
		{
			$search_restriction = ' AND '.$search_restriction;
		}
		$sql = "SELECT
					survey.survey_id							AS col0,
	                CONCAT('<a href=\"survey.php?survey_id=',survey.survey_id,'\">',survey.title,'</a>')		AS col1,
					survey.code									AS col2,
					count(survey_question.question_id)			AS col3,
	                CONCAT(user.firstname, ' ', user.lastname)	AS col4,
	                survey.lang									AS col5,
	                IF(is_shared<>0,'V','-')	 					AS col6,
					survey.avail_from							AS col7,
	                survey.avail_till							AS col8,
	                CONCAT('<a href=\"survey_invitation.php?view=answered&amp;survey_id=',survey.survey_id,'\">',survey.answered,'</a> / <a href=\"survey_invitation.php?view=invited&amp;survey_id=',survey.survey_id,'\">',survey.invited, '</a>')	AS col9,
	                survey.anonymous							AS col10,
	                survey.survey_id							AS col11
	             FROM $table_survey survey
				 LEFT JOIN $table_survey_question survey_question ON survey.survey_id = survey_question.survey_id
	             , $table_user user
	             WHERE survey.author = user.user_id
	             $search_restriction
	             ";
		$sql .= " GROUP BY survey.survey_id";
		$sql .= " ORDER BY col$column $direction ";
		$sql .= " LIMIT $from,$number_of_items";
		$res = api_sql_query($sql, __FILE__, __LINE__);
		$surveys = array ();
		while ($survey = Database::fetch_array($res))
		{
			$surveys[] = $survey;
		}
		return $surveys;
	}
}
?>