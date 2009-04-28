<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos SPRL

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/


/**
*	File containing the FillBlanks class.
*	@package dokeos.exercise
* 	@author Eric Marguin
* 	@author Julio Montoya Armas switchable fill in blank option added
* 	@version $Id: admin.php 10680 2007-01-11 21:26:23Z pcool $
*/


if(!class_exists('FillBlanks')):

/**
	CLASS FillBlanks
 *
 *	This class allows to instantiate an object of type MULTIPLE_ANSWER (MULTIPLE CHOICE, MULTIPLE ANSWER),
 *	extending the class question
 *
 *	@author Eric Marguin
 * 	@author Julio Montoya multiple fill in blank option added
 *	@package dokeos.exercise
 **/

class FillBlanks extends Question 
{
	static $typePicture = 'fill_in_blanks.gif';
	static $explanationLangVar = 'FillBlanks';

	/**
	 * Constructor
	 */
	function FillBlanks()
	{
		parent::question();
		$this -> type = FILL_IN_BLANKS;
	}

	/**
	 * function which redifines Question::createAnswersForm
	 * @param the formvalidator instance
	 */
	function createAnswersForm ($form) 
	{
		$defaults = array();		
		global $fck_attribute;
		$fck_attribute = array();
		//$fck_attribute['Width'] = '348px';
		$fck_attribute['Width'] = '100%';
		$fck_attribute['Height'] = '350px';
		$fck_attribute['ToolbarSet'] = 'Full';

	
		if(!empty($this->id)) {
			$objAnswer = new answer($this->id);
			
			// the question is encoded like this
		    // [A] B [C] D [E] F::10,10,10@1
		    // number 1 before the "@" means that is a switchable fill in blank question
		    // [A] B [C] D [E] F::10,10,10@ or  [A] B [C] D [E] F::10,10,10
		    // means that is a normal fill blank question		

			$pre_array = explode('::', $objAnswer->selectAnswer(1));
	
			//make sure we only take the last bit to find special marks
			$sz = count($pre_array);
			$is_set_switchable = explode('@', $pre_array[$sz-1]);			
			if ($is_set_switchable[1]) {
				$defaults['multiple_answer']=1;	
			} else {
				$defaults['multiple_answer']=0;	
			}
			
			//take the complete string except after the last '::'
			$defaults['answer'] = '';
			for($i=0;$i<($sz-1);$i++) {
				$defaults['answer'] .= $pre_array[$i];
			}
			$a_weightings = explode(',',$is_set_switchable[0]);
		} else {						
			$defaults['answer'] = get_lang('DefaultTextInBlanks');
		}

		// javascript
		
		echo '
		<script type="text/javascript">
			function FCKeditor_OnComplete( editorInstance )
			{				
				editorInstance.EditorDocument.addEventListener( \'keyup\', updateBlanks, true ) ;
			}
		
		var firstTime = true;
		function updateBlanks() 
		{
			if (firstTime) {
				field = document.getElementById("answer");
				var answer = field.value; } 
			else {
				var oEditor = FCKeditorAPI.GetInstance(\'answer\'); 
				answer =  oEditor.GetXHTML( true ) ;
			}   
			
			var blanks = answer.match(/\[[^\]]*\]/g);

			var fields = "<div class=\"row\"><div class=\"label\">'.get_lang('Weighting').'</div><div class=\"formw\"><table>";
			if(blanks!=null){
				for(i=0 ; i<blanks.length ; i++){
					if(document.getElementById("weighting["+i+"]"))
						value = document.getElementById("weighting["+i+"]").value;
					else
						value = "10";
					fields += "<tr><td>"+blanks[i]+"</td><td><input style=\"margin-left: 0em;\" size=\"5\" value=\""+value+"\" type=\"text\" id=\"weighting["+i+"]\" name=\"weighting["+i+"]\" /></td></tr>";

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
		
		//$form->addElement('html_editor', 'answer',null, '');
						
		$form -> addElement ('html_editor', 'answer','<img src="../img/fill_field.png">','id="answer" cols="122" rows="6" onkeyup="updateBlanks(this)"');
		$form -> addRule ('answer',get_lang('GiveText'),'required');
		$form -> addRule ('answer',get_lang('DefineBlanks'),'regex','/\[.*\]/');
 
		//added multiple answers
		$form -> addElement ('checkbox','multiple_answer','', get_lang('FillInBlankSwitchable'));
		
		$form -> addElement('html','<div id="blanks_weighting"></div>');

		
		$form -> setDefaults($defaults);

	}


	/**
	 * abstract function which creates the form to create / edit the answers of the question
	 * @param the formvalidator instance
	 */
	function processAnswersCreation($form) 
	{
		$answer = $form -> getSubmitValue('answer');

		//remove the :: eventually written by the user
		$answer = str_replace('::','',$answer);

		// get the blanks weightings
		$nb = preg_match_all('/\[[^\]]*\]/', $answer, $blanks);
		if(isset($_GET['editQuestion']))
		{
			$this -> weighting = 0;
		}
		
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
		$is_multiple = $form -> getSubmitValue('multiple_answer');
		$answer.='@'.$is_multiple;
		
		$this -> save();
        $objAnswer = new answer($this->id);
        $objAnswer->createAnswer($answer,0,'',0,'');
        $objAnswer->save();
	}
}

endif;
?>
