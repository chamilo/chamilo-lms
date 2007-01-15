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
*	File containing the FillBlanks class.
*
*	@author Eric Marguin
*	@package dokeos.exercise
============================================================================== 
*/

if(!class_exists('FillBlanks')):

/**
	CLASS FillBlanks
 *	
 *	This class allows to instantiate an object of type MULTIPLE_ANSWER (MULTIPLE CHOICE, MULTIPLE ANSWER), 
 *	extending the class question
 *
 *	@author Eric Marguin
 *	@package dokeos.exercise
 **/
 
class FillBlanks extends Question {
	
	static $typePicture = 'fill_in_blanks.png';
	static $explanationLangVar = 'FillBlanks';
	
	/**
	 * Constructor
	 */
	function FillBlanks(){
		parent::question();
		$this -> type = FILL_IN_BLANKS;
	}
	
	/**
	 * function which redifines Question::createAnswersForm
	 * @param the formvalidator instance
	 */
	function createAnswersForm ($form) {
		
		
		
		$defaults = array();
		
		if(!empty($this->id))
		{
			$objAnswer = new answer($this->id);
			$a_answer = explode('::', $objAnswer->selectAnswer(1));
			$defaults['answer'] = $a_answer[0];
			$a_weightings = explode(',',$a_answer[1]);			
		}
		else
		{
			$defaults['answer'] = get_lang('DefaultTextInBlanks');
		}
		
		// javascript
		echo '
		<script type="text/javascript">
		var firstTime = true;
		function updateBlanks() {
			field = document.getElementById("answer");
			var answer = field.value;
			var blanks = answer.match(/\[[^\]]*\]/g);
			
			var fields = "<div class=\"row\"><div class=\"label\">'.get_lang('Weighting').'</div><div class=\"formw\"><table>";
			if(blanks!=null){
				for(i=0 ; i<blanks.length ; i++){
					if(document.getElementById("weighting["+i+"]"))
						value = document.getElementById("weighting["+i+"]").value;
					else
						value = "1";
					fields += "<tr><td>"+blanks[i]+"</td><td><input size=\"5\" value=\""+value+"\" type=\"text\" id=\"weighting["+i+"]\" name=\"weighting["+i+"]\" /></td></tr>";
				
				}
			}
			document.getElementById("blanks_weighting").innerHTML = fields + "</table></div></div>";
			if(firstTime){
				firstTime = false;
			';
		
		if(count($a_weightings)>0)
		{	
			foreach($a_weightings as $i=>$weighting)
			{
				echo 'document.getElementById("weighting['.$i.']").value = "'.$weighting.'";';
				
			}
		}
		echo '}
		}
		window.onload = updateBlanks;
		</script>
		';
		
		
		// answer
		$form -> addElement ('html', '<br /><br /><div class="row"><div class="label"></div><div class="formw">'.get_lang('TypeTextBelow').', '.get_lang('And').' '.get_lang('UseTagForBlank').'</div></div>');
		$form -> addElement ('textarea', 'answer',get_lang('Answer'),'id="answer" cols="65" rows="6" onkeyup="updateBlanks(this)"');
		$form -> addRule ('answer',get_lang('GiveText'),'required');
		$form -> addRule ('answer',get_lang('DefineBlanks'),'regex','/\[.*\]/');
		
		
		$form -> addElement('html','<div id="blanks_weighting"></div>');
		
		$form -> setDefaults($defaults);
		
	}
	
	
	/**
	 * abstract function which creates the form to create / edit the answers of the question
	 * @param the formvalidator instance
	 */
	function processAnswersCreation($form) {
		
		$answer = $form -> getSubmitValue('answer');
		
		//remove the :: eventually written by the user
		$answer = str_replace('::','',$answer);
		
		// get the blanks weightings
		$nb = preg_match_all('/\[[^\]]*\]/', $answer, $blanks);
		if($nb>0)
		{
			$answer .= '::';
			for($i=0 ; $i<$nb ; ++$i)
			{
				$answer .= $form -> getSubmitValue('weighting['.$i.']').',';
				$this -> weighting += $form -> getSubmitValue('weighting['.$i.']');
			}
			$answer = substr($answer,0,-1);
		}
		
		$this -> save();
		
        $objAnswer = new answer($this->id);
        $objAnswer->createAnswer($answer,0,'',0,'');
        $objAnswer->save();
        
	}
	
}

endif;
?>