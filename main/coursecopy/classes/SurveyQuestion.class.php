<?php // $Id:  $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2007 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Bart Mollet (bart.mollet@hogent.be)

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/

require_once 'Resource.class.php';

/**
 * An QuizQuestion
 * @author Yannick Warnier <yannick.warnier@dokeos.com>
 * @package dokeos.backup
 */
class SurveyQuestion extends Resource
{
	/**
	 * Survey ID
	 */
	var $survey_id;
	/**
	 * Question and question comment
	 */
	var $survey_question;
	var $survey_question_comment;
	/**
	 * Question type
	 */
	var $survey_question_type;
	/**
	 * Display ?
	 */
	var $display;
	/**
	 * Sorting order
	 */
	var $sort;
	/**
	 * Shared question ID
	 */
	var $shared_question_id;
	/**
	 * Maximum value for the vote
	 */
	var $max_value;
	/**
	 * Question's options
	 */
	var $options;
	/**
	 * Create a new SurveyQuestion
	 * @param int	 $id
	 * @param int 	 $survey_id
	 * @param string $survey_question
	 * @param string $survey_question_comment
	 * @param string $type
	 * @param string $display
	 * @param int	 $sort
	 * @param int	 $shared_question_id
	 * @param int	 $max_value
	 */
	function SurveyQuestion($id,$survey_id,$survey_question,$survey_question_comment,
							$type,$display,$sort,$shared_question_id,$max_value)
	{
		parent::Resource($id,RESOURCE_SURVEYQUESTION);
		$this->survey_id = $survey_id;
		$this->survey_question = $survey_question;
		$this->survey_question_comment = $survey_question_comment;
		$this->survey_question_type = $type;
		$this->display = $display;
		$this->sort = $sort;
		$this->shared_question_id = $shared_question_id;
		$this->max_value = $max_value;
		$this->answers = array();
	}
	/**
	 * Add an answer option to this SurveyQuestion
	 * @param string $option_text
	 * @param int	 $sort
	 */
	function add_answer($option_text,$sort)
	{
		$answer = array();
		$answer['option_text'] = $option_text;
		$answer['sort'] = $sort;
		$this->answers[] = $answer;
	}
	/**
	 * Show this question
	 */
	function show()
	{
		parent::show();
		echo $this->survey_question;
	}
}
?>
