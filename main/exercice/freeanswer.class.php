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
*	File containing the FreeAnswer class.
*	This class allows to instantiate an object of type FREE_ANSWER,
*	extending the class question
*	@package dokeos.exercise
* 	@author Eric Marguin
* 	@version $Id: admin.php 10680 2007-01-11 21:26:23Z pcool $
*/


if(!class_exists('FreeAnswer')):

class FreeAnswer extends Question {

	static $typePicture = 'open_answer.gif';
	static $explanationLangVar = 'freeAnswer';

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
			$form -> setDefaults(array('weighting' => '10'));
		}

	}


	/**
	 * abstract function which creates the form to create / edit the answers of the question
	 * @param the formvalidator instance
	 */
	function processAnswersCreation($form) 
	{
		$this -> weighting = $form -> getSubmitValue('weighting');
		$this->save();
	}
}
endif;
?>