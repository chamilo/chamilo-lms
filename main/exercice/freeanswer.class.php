<?php // $Id: uniquer_answer.class.php 10234 2006-12-26
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert
	
	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".
	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	See the GNU General Public License for more details.
	
	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
============================================================================== 
*/
/**
============================================================================== 
*	File containing the FreeAnswer class.
*
*	@author Eric Marguin
*	@package dokeos.exercise
============================================================================== 
*/

if(!class_exists('FreeAnswer')):

/**
	CLASS FreeAnswer
 *	
 *	This class allows to instantiate an object of type FREE_ANSWER, 
 *	extending the class question
 *
 *	@author Eric Marguin
 *	@package dokeos.exercise
 **/
 
class FreeAnswer extends Question {
	
	/**
	 * Constructor
	 */
	function FreeAnswer(){
		parent::question();
		$this -> type = FREE_ANSWER;
	}
	
	/**
	 * function which redifines Question::createAnswersForm
	 * @param the formvalidator instance
	 */
	function createAnswersForm ($form) {		
		
		$form -> addElement('text','weighting',get_lang('Weighting'),'size="5"');
		if(!empty($this->id))
		{
			$form -> setDefaults(array('weighting' => $this->weighting));
		}
		else {
			$form -> setDefaults(array('weighting' => '1'));
		}
		
	}
	
	
	/**
	 * abstract function which creates the form to create / edit the answers of the question
	 * @param the formvalidator instance
	 */
	function processAnswersCreation($form) {
		
		$this -> weighting = $form -> getSubmitValue('weighting');
		
		$this->save();
        
	}
	
}

endif;
?>