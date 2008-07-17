<?php // $Id: Quiz.class.php 15802 2008-07-17 04:52:13Z yannoo $
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
require_once('Resource.class.php');
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
	
	var $attempts;
	/**
	 * Create a new Quiz
	 * @param string $title
	 * @param string $description
	 * @param int $random
	 * @param int $type
	 * @param int $active
	 */
	function Quiz($id,$title,$description,$random,$type,$active,$media,$attempts=0)
	{
		parent::Resource($id,RESOURCE_QUIZ);
		$this->title = $title;
		$this->description = $description;
		$this->random = $random;
		$this->quiz_type = $type;
		$this->active = $active;
		$this->media = $media;
		$this->attempts = $attempts;
		$this->question_ids = array();
	}
	/**
	 * Add a question to this Quiz
	 */
	function add_question($id)
	{
		$this->question_ids[] = $id;
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
?>