<?php
/* For licensing terms, see /license.txt */
/**
 * Exercises questions backup script
 * @package chamilo.backup
 */
/**
 * Code
 */
require_once 'Resource.class.php';
/**
 * An QuizQuestion
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package chamilo.backup
 */
class QuizQuestion extends Resource
{
	/**
	 * The question
	 */
	var $question;
	/**
	 * The description
	 */
	var $description;
	/**
	 * Ponderation
	 */
	var $ponderation;
	/**
	 * Type
	 */
	var $quiz_type;
	/**
	 * Position
	 */
	var $position;
	/**
	 * Level
	 */
	var $level;
	/**
	 * Answers
	 */
	var $answers;
	/**
	 * Picture
	 */
	var $picture;
    
    var $extra;
	/**
	 * Create a new QuizQuestion
	 * @param string $question
	 * @param string $description
	 * @param int $ponderation
	 * @param int $type
	 * @param int $position
	 */
	function QuizQuestion($id,$question,$description,$ponderation,$type,$position,$picture,$level, $extra) {
		parent::Resource($id, RESOURCE_QUIZQUESTION);
		$this->question = $question;
		$this->description = $description;
		$this->ponderation = $ponderation;
		$this->quiz_type = $type;
		$this->position = $position;
		$this->picture = $picture;
		$this->level = $level;
		$this->answers = array();
        $this->extra = $extra;
	}
    
	/**
	 * Add an answer to this QuizQuestion
	 */
	function add_answer($answer_id, $answer_text,$correct,$comment,$ponderation,$position,$hotspot_coordinates,$hotspot_type) {
		$answer = array();		
		$answer['id'] = $answer_id;
		$answer['answer'] = $answer_text;
		$answer['correct'] = $correct;
		$answer['comment'] = $comment;
		$answer['ponderation'] = $ponderation;
		$answer['position'] = $position;
		$answer['hotspot_coordinates'] = $hotspot_coordinates;
		$answer['hotspot_type'] = $hotspot_type;
		$this->answers[] = $answer;
	}
    
	/**
	 * Show this question
	 */
	function show() {
		parent::show();
		echo $this->question;
	}
}
