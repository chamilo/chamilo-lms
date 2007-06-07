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
*	File containing the MultipleAnswer class.
*	@package dokeos.exercise
* 	@author Eric Marguin
* 	@version $Id: admin.php 10680 2007-01-11 21:26:23Z pcool $
*/

if(!class_exists('MultipleAnswer')):

/**
	CLASS MultipleAnswer
 *
 *	This class allows to instantiate an object of type MULTIPLE_ANSWER (MULTIPLE CHOICE, MULTIPLE ANSWER),
 *	extending the class question
 *
 *	@author Eric Marguin
 *	@package dokeos.exercise
 **/

class MultipleAnswer extends Question {

	static $typePicture = 'mcma.gif';
	static $explanationLangVar = 'MultipleSelect';

	/**
	 * Constructor
	 */
	function MultipleAnswer(){
		parent::question();
		$this -> type = MULTIPLE_ANSWER;
	}

	/**
	 * function which redifines Question::createAnswersForm
	 * @param the formvalidator instance
	 * @param the answers number to display
	 */
	function createAnswersForm ($form) {

		global $fck_attribute;

		$fck_attribute = array();
		$fck_attribute['Width'] = '348px';
		$fck_attribute['Height'] = '100px';
		$fck_attribute['ToolbarSet'] = 'Test';
		$fck_attribute['Config']['IMUploadPath'] = 'upload/test/';
		$fck_attribute['Config']['FlashUploadPath'] = 'upload/test/';

		$nb_answers = isset($_POST['nb_answers']) ? $_POST['nb_answers'] : 2;
		$nb_answers += (isset($_POST['lessAnswers']) ? -1 : (isset($_POST['moreAnswers']) ? 1 : 0));

		$html='
		<div class="row">
			<div class="label">
			'.get_lang('Answers').'
			</div>
			<div class="formw">
				<table class="data_table">
					<tr style="text-align: center;">
						<th>
							'.get_lang('Number').'
						</th>
						<th>
							'.get_lang('True').'
						</th>
						<th>
							'.get_lang('Answer').'
						</th>
						<th>
							'.get_lang('Comment').'
						</th>
						<th>
							'.get_lang('Weighting').'
						</th>
						
					</tr>';
		$form -> addElement ('html', $html);

		$defaults = array();
		$correct = 0;
		if(!empty($this -> id))
		{
			$answer = new Answer($this -> id);
			$answer -> read();
			if(count($answer->nbrAnswers)>0 && !$form->isSubmitted())
			{
				$nb_answers = $answer->nbrAnswers;
			}
		}

		$form -> addElement('hidden', 'nb_answers');
		
		$boxes_names = array();

		for($i = 1 ; $i <= $nb_answers ; ++$i)
		{
			
			if(is_object($answer))
			{
				$defaults['answer['.$i.']'] = $answer -> answer[$i];
				$defaults['comment['.$i.']'] = $answer -> comment[$i];
				$defaults['weighting['.$i.']'] = $answer -> weighting[$i];
				$defaults['correct['.$i.']'] = $answer -> correct[$i];
			}
			
			$renderer = & $form->defaultRenderer();
			$renderer->setElementTemplate('<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>');
			
			$answer_number=$form->addElement('text', null,null,'value="'.$i.'"');
			$answer_number->freeze();
			
			$form->addElement('checkbox', 'correct['.$i.']', null, null, 'style="margin-left: 0em;"');
			$boxes_names[] = 'correct['.$i.']';
			
			$form->addElement('html_editor', 'answer['.$i.']',null, 'style="vertical-align:middle"');
			$form->addRule('answer['.$i.']', get_lang('ThisFieldIsRequired'), 'required');
			$form->addElement('html_editor', 'comment['.$i.']',null, 'style="vertical-align:middle"');
			$form->addElement('text', 'weighting['.$i.']',null, 'style="vertical-align:middle;margin-left: 0em;" size="5" value="0"');
			$form -> addElement ('html', '</tr>');
		}
		$form -> addElement ('html', '</table>');
		
		$form -> add_multiple_required_rule ($boxes_names , get_lang('ChooseAtLeastOneCheckbox') , 'multiple_required');

		$form->addElement('submit', 'lessAnswers', get_lang('LessAnswer'));
		$form->addElement('submit', 'moreAnswers', get_lang('PlusAnswer'));
		$renderer->setElementTemplate('{element}&nbsp;','lessAnswers');
		$renderer->setElementTemplate('{element}','moreAnswers');
		$form -> addElement ('html', '</div></div>');

		$defaults['correct'] = $correct;
		$form -> setDefaults($defaults);

		$form->setConstants(array('nb_answers' => $nb_answers));

	}


	/**
	 * abstract function which creates the form to create / edit the answers of the question
	 * @param the formvalidator instance
	 * @param the answers number to display
	 */
	function processAnswersCreation($form) {

		$questionWeighting = $nbrGoodAnswers = 0;

		$objAnswer = new Answer($this->id);

		$nb_answers = $form -> getSubmitValue('nb_answers');

		for($i=1 ; $i <= $nb_answers ; $i++)
        {
        	$answer = trim($form -> getSubmitValue('answer['.$i.']'));
            $comment = trim($form -> getSubmitValue('comment['.$i.']'));
            $weighting = trim($form -> getSubmitValue('weighting['.$i.']'));
            $goodAnswer = trim($form -> getSubmitValue('correct['.$i.']'));

			if($goodAnswer){
    			$weighting = abs($weighting);
			}
			else{
				$weighting = abs($weighting);
				$weighting = -$weighting;
			}
    		if($weighting > 0)
            {
                $questionWeighting += $weighting;
            }

        	$objAnswer -> createAnswer($answer,$goodAnswer,$comment,$weighting,$i);

        }

    	// saves the answers into the data base
        $objAnswer -> save();

        // sets the total weighting of the question
        $this -> updateWeighting($questionWeighting);
        $this -> save();

	}

}

endif;
?>