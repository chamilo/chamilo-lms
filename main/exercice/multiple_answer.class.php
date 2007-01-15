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
*	File containing the MultipleAnswer class.
*
*	@author Eric Marguin
*	@package dokeos.exercise
============================================================================== 
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
	
	static $typePicture = 'mcma.png';
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
		$fck_attribute['Width'] = '300px';
		$fck_attribute['Height'] = '100px';
		$fck_attribute['ToolbarSet'] = 'Small';
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
				<table cellpadding="0" cellspacing="5">
					<tr bgcolor="#e6e6e6">
						<td>
							'.get_lang('Number').'
						</td>
						<td>
							'.get_lang('True').'
						</td>
						<td>
							'.get_lang('Answer').'
						</td>
						<td>
							'.get_lang('Comment').'
						</td>
						<td>
							'.get_lang('Weighting').'
						</td>
						<td width="0"></td>
					</tr>';
		$form -> addElement ('html', $html);
		
		$defaults = array();
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
			
			if(is_object($answer))
			{
				$defaults['answer['.$i.']'] = $answer -> answer[$i];
				$defaults['comment['.$i.']'] = $answer -> comment[$i];
				$defaults['weighting['.$i.']'] = $answer -> weighting[$i];
				$defaults['correct['.$i.']'] = $answer -> correct[$i];
			}
			
			$form -> addElement ('html', '<tr><td>');

			$group = array();
			$puce = FormValidator :: createElement ('text', null,null,'value="1"');
			$puce->freeze();			
			$group[] = $puce;
			$group[] = FormValidator :: createElement ('checkbox', 'correct['.$i.']', null, null, $i);			
			$group[] = FormValidator :: createElement ('html_editor', 'answer['.$i.']',null, 'style="vertical-align:middle" cols="30"');
			$group[] = FormValidator :: createElement ('html_editor', 'comment['.$i.']',null, 'style="vertical-align:middle" cols="30"');
			$group[] = FormValidator :: createElement ('text', 'weighting['.$i.']',null, 'style="vertical-align:middle" size="5" value="0"');
			$form -> addGroup($group, null, null, '</td><td width="0">');
						
			$form -> addElement ('html', '</td></tr>');
			
		}
	
		$form -> addElement ('html', '</table></div></div>');		
		$group = array();
		$group[] = FormValidator :: createElement ('submit', 'lessAnswers', '-answ');
		$group[] = FormValidator :: createElement ('submit', 'moreAnswers', '+answ');
		$form -> addGroup($group);
		
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