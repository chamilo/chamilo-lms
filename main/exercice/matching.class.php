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
*	File containing the Matching class.
*
*	@author Eric Marguin
*	@package dokeos.exercise
============================================================================== 
*/

if(!class_exists('Matching')):

/**
	CLASS Matching
 *	
 *	This class allows to instantiate an object of type MULTIPLE_ANSWER (MULTIPLE CHOICE, MULTIPLE ANSWER), 
 *	extending the class question
 *
 *	@author Eric Marguin
 *	@package dokeos.exercise
 **/
 
class Matching extends Question {
	
	
	static $typePicture = 'matching.png';
	static $explanationLangVar = 'Matching';
	
	/**
	 * Constructor
	 */
	function Matching(){
		parent::question();
		$this -> type = MATCHING;
	}
	
	/**
	 * function which redifines Question::createAnswersForm
	 * @param the formvalidator instance
	 */
	function createAnswersForm ($form) {		
		
		$defaults = array();
		
		$nb_matches = $nb_options = 2;
		if($form -> isSubmitted())
		{
			$nb_matches = $form -> getSubmitValue('nb_matches');
			$nb_options = $form -> getSubmitValue('nb_options');		
			if(isset($_POST['lessMatches']))
				$nb_matches--;
			if(isset($_POST['moreMatches']))
				$nb_matches++;
			if(isset($_POST['lessOptions']))
				$nb_options--;
			if(isset($_POST['moreOptions']))
				$nb_options++;
			
		}
		else if(!empty($this -> id)) 
		{
			$answer = new Answer($this -> id);
			$answer -> read();
			if(count($answer->nbrAnswers)>0)
			{
				$a_matches = $a_options = array();
				$nb_matches = $nb_options = 0;
				for($i=1 ; $i<=$answer->nbrAnswers ; $i++){
					if($answer -> isCorrect($i))
					{						
						$nb_matches++;
						$defaults['answer['.$nb_matches.']'] = $answer -> selectAnswer($i);
						$defaults['weighting['.$nb_matches.']'] = $answer -> selectWeighting($i);
						$defaults['matches['.$nb_matches.']'] = $answer -> correct[$i];
					}
					else
					{
						$nb_options++;
						$defaults['option['.$nb_options.']'] = $answer -> selectAnswer($i);
					}
				}
				
			}
		}
		else {
			$defaults['answer[1]'] = get_lang('DefaultMakeCorrespond1');
			$defaults['answer[2]'] = get_lang('DefaultMakeCorrespond2');	
			$defaults['matches[2]'] = '2';
			$defaults['option[1]'] = get_lang('DefaultMatchingOptA');
			$defaults['option[2]'] = get_lang('DefaultMatchingOptB');
		}
		$a_matches = array();
		for($i=1 ; $i<=$nb_options ; ++$i)
		{
			$a_matches[$i] = chr(64+$i);  // fill the array with A, B, C.....
		}
		
		
		
		
		
		$form -> addElement('hidden', 'nb_matches', $nb_matches);
		$form -> addElement('hidden', 'nb_options', $nb_options);
		
		
		////////////////////////
		// DISPLAY MATCHES ////
		//////////////////////	
		
		$html='
		<div class="row">
			<div class="label">
			'.get_lang('Answers').'
			</div>
			<div class="formw">
				'.get_lang('MakeCorrespond').'
				<table cellpadding="0" cellspacing="5">
					<tr bgcolor="#e6e6e6">
						<td>
							N�
						</td>
						<td>
							'.get_lang('Answer').'
						</td>
						<td>
							'.get_lang('MatchesTo').'
						</td>
						<td>
							Weighting
						</td>
						<td width="0"></td>
					</tr>';
		$form -> addElement ('html', $html);
		
		
		for($i = 1 ; $i <= $nb_matches ; ++$i)
		{
			
			$form -> addElement ('html', '<tr><td>');

			$group = array();
			$puce = FormValidator :: createElement ('text', null,null,'value="'.$i.'"');
			$puce->freeze();			
			$group[] = $puce;
			$group[] = FormValidator :: createElement ('text', 'answer['.$i.']',null, 'size="30"');
			$group[] = FormValidator :: createElement ('select', 'matches['.$i.']',null,$a_matches);
			$group[] = FormValidator :: createElement ('text', 'weighting['.$i.']',null, 'style="vertical-align:middle" size="2" value="1"');
			$form -> addGroup($group, null, null, '</td><td width="0">');
						
			$form -> addElement ('html', '</td></tr>');
			
		}
	
		$form -> addElement ('html', '</table></div></div>');	
		$group = array();
		$group[] = FormValidator :: createElement ('submit', 'lessMatches', '-elem');
		$group[] = FormValidator :: createElement ('submit', 'moreMatches', '+elem');
		$form -> addGroup($group);
		
		
		
		////////////////////////
		// DISPLAY OPTIONS ////
		//////////////////////
		$html='
		<div class="row">
			<div class="label">
			</div>
			<div class="formw"><br /><br />
				<table cellpadding="0" cellspacing="5">
					<tr bgcolor="#e6e6e6">
						<td>
							N�
						</td>
						<td>
							'.get_lang('Answer').'
						</td>
						<td width="0"></td>
					</tr>';
		$form -> addElement ('html', $html);
		
		
		
		for($i = 1 ; $i <= $nb_options ; ++$i)
		{
			$form -> addElement ('html', '<tr><td>');

			$group = array();
			$puce = FormValidator :: createElement ('text', null,null,'value="'.chr(64+$i).'"');
			$puce->freeze();			
			$group[] = $puce;
			$group[] = FormValidator :: createElement ('text', 'option['.$i.']',null, 'size="30"');
			$form -> addGroup($group, null, null, '</td><td width="0">');
						
			$form -> addElement ('html', '</td></tr>');
			
		}
	
		$form -> addElement ('html', '</table></div></div>');		
		$group = array();
		$group[] = FormValidator :: createElement ('submit', 'lessOptions', '-elem');
		$group[] = FormValidator :: createElement ('submit', 'moreOptions', '+elem');
		$form -> addGroup($group);
		
		$form -> setDefaults($defaults);
		
		$form->setConstants(array('nb_matches' => $nb_matches,'nb_options' => $nb_options));
		
	}
	
	
	/**
	 * abstract function which creates the form to create / edit the answers of the question
	 * @param the formvalidator instance
	 */
	function processAnswersCreation($form) {
		
		$nb_matches = $form -> getSubmitValue('nb_matches');
		$nb_options = $form -> getSubmitValue('nb_options');
		$this -> weighting = 0;
		$objAnswer = new Answer($this->id);
		
		$position = 0;
		
		// insert the options
		for($i=1 ; $i<=$nb_options ; ++$i)
		{
			$position++;
			$option = $form -> getSubmitValue('option['.$i.']');
			$objAnswer->createAnswer($option, 0, '', 0, $position);
		}
		
		// insert the answers
		for($i=1 ; $i<=$nb_matches ; ++$i)
		{
			$position++;
			$answer = $form -> getSubmitValue('answer['.$i.']');
			$matches = $form -> getSubmitValue('matches['.$i.']');
			$weighting = $form -> getSubmitValue('weighting['.$i.']');
			$this -> weighting += $weighting;
			$objAnswer->createAnswer($answer,$matches,'',$weighting,$position);
		}
		
		$objAnswer->save();
		$this->save();
        
	}
	
}

endif;
?>