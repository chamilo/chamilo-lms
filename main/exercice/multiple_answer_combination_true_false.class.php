<?php
/* For licensing terms, see /license.txt */
/**
*	File containing the MultipleAnswer class.
*	@package chamilo.exercise
* 	@author Eric Marguin
* 	@version $Id: admin.php 10680 2007-01-11 21:26:23Z pcool $
*/
/**
 * Code
 */
if(class_exists('MultipleAnswerCombinationTrueFalse')) { return true; }
require 'multiple_answer_combination.class.php';
/**
	CLASS MultipleAnswer
 *
 *	This class allows to instantiate an object of type MULTIPLE_ANSWER (MULTIPLE CHOICE, MULTIPLE ANSWER),
 *	extending the class question
 *
 *	@author Eric Marguin
 *	@package chamilo.exercise
 **/
class MultipleAnswerCombinationTrueFalse extends MultipleAnswerCombination {

	static $typePicture = 'mcmaco.gif';
	static $explanationLangVar = 'MultipleAnswerCombinationTrueFalse';
    var    $options; 

	/**
	 * Constructor
	 */
	function MultipleAnswerCombinationTrueFalse(){
		parent::question();
		$this -> type = MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE;
		$this -> isContent = $this-> getIsContent();
        $this->options = array('1'=>get_lang('True'),'0' =>get_lang('False'), '2' =>get_lang('DontKnow'));
	}
}
