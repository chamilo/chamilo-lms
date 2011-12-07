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
if(!class_exists('FreeAnswer')):
/**
 * @package chamilo.exercise
 */
class FreeAnswer extends Question {

	static $typePicture = 'open_answer.gif';
	static $explanationLangVar = 'FreeAnswer';

	/**
	 * Constructor
	 */
	function FreeAnswer(){
		parent::question();
		$this -> type = FREE_ANSWER;
		$this -> isContent = $this-> getIsContent();
	}

	/**
	 * function which redifines Question::createAnswersForm
	 * @param the formvalidator instance
	 */
	function createAnswersForm ($form)
	{
		$form -> addElement('text','weighting',get_lang('Weighting'),'size="5"');
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
	function processAnswersCreation($form)
	{
		$this -> weighting = $form -> getSubmitValue('weighting');
		$this->save();
	}
	
	function return_header($feedback_type, $counter = null) {
	    parent::return_header($feedback_type, $counter);
	    $header = '<table width="100%" class="data_table_exercise_result_left" >	
	        <tr>		
			<td><i>'.get_lang("Answer").'</i></td>
			</tr>';				
        return $header;	  
	}
	
}
endif;
?>
