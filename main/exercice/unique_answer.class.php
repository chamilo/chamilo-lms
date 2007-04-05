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
*	File containing the UNIQUE_ANSWER class.
*	@package dokeos.exercise
* 	@author Eric Marguin
* 	@version $Id: admin.php 10680 2007-01-11 21:26:23Z pcool $
*/


if(!class_exists('UniqueAnswer')):

/**
	CLASS UNIQUE_ANSWER
 *
 *	This class allows to instantiate an object of type UNIQUE_ANSWER (MULTIPLE CHOICE, UNIQUE ANSWER),
 *	extending the class question
 *
 *	@author Eric Marguin
 *	@package dokeos.exercise
 **/

class UniqueAnswer extends Question {

	static $typePicture = 'mcua.gif';
	static $explanationLangVar = 'UniqueSelect';

	/**
	 * Constructor
	 */
	function UniqueAnswer(){
		$this -> type = UNIQUE_ANSWER;
	}

	/**
	 * function which redifines Question::createAnswersForm
	 * @param the formvalidator instance
	 * @param the answers number to display
	 */
	function createAnswersForm ($form) {

		global $fck_attribute;

		$fck_attribute = array();
		$fck_attribute['Width'] = '320px';
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

		for($i = 1 ; $i <= $nb_answers ; ++$i)
		{
			$form -> addElement ('html', '<tr>');
			if(is_object($answer))
			{
				if($answer -> correct[$i])
				{
					$correct = $i;
				}
				$defaults['answer['.$i.']'] = $answer -> answer[$i];
				$defaults['comment['.$i.']'] = $answer -> comment[$i];
				$defaults['weighting['.$i.']'] = $answer -> weighting[$i];
			}
			
			$renderer = & $form->defaultRenderer();
			$renderer->setElementTemplate('<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>');
			
			$answer_number=$form->addElement('text', null,null,'value="'.$i.'"');
			$answer_number->freeze();
			
			$form->addElement('radio', 'correct', null, null, $i);
			$form->addElement('html_editor', 'answer['.$i.']',null, 'style="vertical-align:middle"');
			$form->addRule('answer['.$i.']', get_lang('ThisFieldIsRequired'), 'required');
			$form->addElement('html_editor', 'comment['.$i.']',null, 'style="vertical-align:middle"');
			$form->addElement('text', 'weighting['.$i.']',null, 'style="vertical-align:middle" size="5" value="0"');
			$form -> addElement ('html', '</tr>');
		}
		$form -> addElement ('html', '</table>');

		$form->addElement('submit', 'lessAnswers', get_lang('LessAnswer'));
		$form->addElement('submit', 'moreAnswers', get_lang('PlusAnswer'));
		$renderer->setElementTemplate('{element}&nbsp;','lessAnswers');
		$renderer->setElementTemplate('{element}','moreAnswers');
		$form -> addElement ('html', '</div></div>');
		
		//We check the first radio button to be sure a radio button will be check 
		if($correct==0){
			$correct=1;
		}
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

		$correct = $form -> getSubmitValue('correct');
		$objAnswer = new Answer($this->id);
		$nb_answers = $form -> getSubmitValue('nb_answers');

		for($i=1 ; $i <= $nb_answers ; $i++)
        {
        	$answer = trim($form -> getSubmitValue('answer['.$i.']'));
            $comment = trim($form -> getSubmitValue('comment['.$i.']'));
            $weighting = trim($form -> getSubmitValue('weighting['.$i.']'));

        	$goodAnswer= ($correct == $i) ? true : false;

        	if($goodAnswer)
        	{
        		$nbrGoodAnswers++;
        		$weighting = abs($weighting);
        		if($weighting > 0)
                {
                    $questionWeighting += $weighting;
                }
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