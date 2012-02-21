<?php
/* For licensing terms, see /license.txt */
/**
*	File containing the FreeAnswer class.
*	This class allows to instantiate an object of type FREE_ANSWER,
*	extending the class question
*	@package chamilo.exercise
* 	@author Eric Marguin
* 	@version $Id: admin.php 10680 2007-01-11 21:26:23Z pcool $
*/
/**
 * Code
 */
if(!class_exists('OralExpression')):
/**
 * @package chamilo.exercise
 */
class OralExpression extends Question {

	static $typePicture = 'audio_question.png';
	static $explanationLangVar = 'OralExpression';

	/**
	 * Constructor
	 */
	function OralExpression(){
		parent::question();
		$this -> type = ORAL_EXPRESSION;
		$this -> isContent = $this-> getIsContent();
	}

	/**
	 * function which redifines Question::createAnswersForm
	 * @param the formvalidator instance
	 */
	function createAnswersForm ($form) {
		$form -> addElement('text','weighting',get_lang('Weighting'), array('class' => 'span1'));
		global $text, $class;
		// setting the save button here and not in the question class.php
		$form->addElement('style_submit_button','submitQuestion',$text, 'class="'.$class.'"');
		if (!empty($this->id)) {
			$form -> setDefaults(array('weighting' => float_format($this->weighting, 1)));
		} else {
			if ($this -> isContent == 1) {
				$form -> setDefaults(array('weighting' => '10'));
			}
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
	
	function return_header($feedback_type, $counter = null) {
	    parent::return_header($feedback_type, $counter);
	    $header = '<table width="100%" border="0" cellspacing="3" cellpadding="3">
			<tr>
			<td>&nbsp;</td>
			</tr>
			<tr>
			<td><i>'.get_lang("Answer").'</i> </td>
			</tr>
			<tr>
			<td>&nbsp;</td>
			</tr>';				
        return $header;	  
	}
	
}
endif;