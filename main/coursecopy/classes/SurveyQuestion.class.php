<?php
/* For licensing terms, see /license.txt */
/**
 * Survey questions backup script
 * @package chamilo.backup
 */
/**
 * Code
 */
require_once 'Resource.class.php';
/**
 * A SurveyQuestion
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 * @package chamilo.backup
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
