<?php // $Id: QuizQuestion.class.php 18549 2009-02-17 18:08:58Z cfasanando $
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004 Dokeos S.A.
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
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package dokeos.backup
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
	/**
	 * Create a new QuizQuestion
	 * @param string $question
	 * @param string $description
	 * @param int $ponderation
	 * @param int $type
	 * @param int $position
	 */
	function QuizQuestion($id,$question,$description,$ponderation,$type,$position,$picture,$level)
	{
		parent::Resource($id,RESOURCE_QUIZQUESTION);
		$this->question = $question;
		$this->description = $description;
		$this->ponderation = $ponderation;
		$this->quiz_type = $type;
		$this->position = $position;
		$this->picture = $picture;
		$this->level = $level;
		$this->answers = array();
	}
	/**
	 * Add an answer to this QuizQuestion
	 */
	function add_answer($answer_text,$correct,$comment,$ponderation,$position,$hotspot_coordinates,$hotspot_type)
	{
		$answer = array();
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
	function show()
	{
		parent::show();
		echo $this->question;	
	}
}
