<?php
/* For licensing terms, see /license.txt */

require_once 'Resource.class.php';

/**
 * An Quiz
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package dokeos.backup
 */
class Quiz extends Resource
{
	/**
	 * The title
	 */
	var $title;
	/**
	 * The description
	 */
	var $description;
	/**
	 * random
	 */
	var $random;
	/**
	 * Type
	 */
	var $quiz_type;
	/**
	 * Active
	 */
	var $active;
	/**
	 * Sound or video file
	 * This should be the id of the file and not the file-name like in the
	 * database!
	 */
	var $media;
	/**
	 * Questions
	 */
	var $question_ids;
	/**
	 * Questions orders
	 */
	var $question_orders;
	/**
	 * Max attempts
	 */
	var $attempts;
	/**
	 * Results disabled
	 */
	var $results_disabled;
	/**
	 * Access condition
	 */
	var $access_condition;
	/**
	 * Start time
	 */
	var $start_time;
	/**
	 * End time
	 */
	var $end_time;
	/**
	 * Feedback type
	 */
	var $feedback_type;
	/**
	 * Random answers
	 */
	var $random_answers;
	/**
	 * Expired time
	 */
	var $expired_time;
	
	/**
	 * Create a new Quiz
	 * @param string $title
	 * @param string $description
	 * @param int $random
	 * @param int $type
	 * @param int $active
	 */
	function Quiz($id, $title, $description, $random, $type, $active, $media, $attempts = 0, $results_disabled = 0, $access_condition = null, 
	             $start_time = '0000-00-00 00:00:00', $end_time = '0000-00-00 00:00:00', $feedback_type = 0, $random_answers = 0, $expired_time = 0, $session_id = 0)
	{
		parent::Resource($id, RESOURCE_QUIZ);
		$this->title = $title;
		$this->description = $description;
		$this->random = $random;
		$this->quiz_type = $type;
		$this->active = $active;
		$this->media = $media;
		$this->attempts = $attempts;
		$this->question_ids = array();
		$this->question_orders= array();
		$this->results_disabled = $results_disabled;
		$this->access_condition = $access_condition;
		$this->start_time = $start_time;
		$this->end_time = $end_time;
		$this->feedback_type = $feedback_type;
		$this->random_answers = $random_answers; 
		$this->expired_time = $expired_time;
		$this->session_id = $session_id;
	}
	/**
	 * Add a question to this Quiz
	 */
	function add_question($id,$question_order)
	{
		$this->question_ids[] = $id;
		$this->question_orders[] = $question_order;
	}
	/**
	 * Show this question
	 */
	function show()
	{
		parent::show();
		echo $this->title;
	}
}
