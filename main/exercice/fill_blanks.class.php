<?php
/* For licensing terms, see /license.txt */
/**
 *
 *	Class FillBlanks
 *
 *	@author Eric Marguin
 * 	@author Julio Montoya multiple fill in blank option added
 *	@package chamilo.exercise
 **/
class FillBlanks extends Question
{
	static $typePicture = 'fill_in_blanks.gif';
	static $explanationLangVar = 'FillBlanks';

    /**
     * Constructor
     */
    public function FillBlanks()
    {
        parent::question();
        $this -> type = FILL_IN_BLANKS;
        $this -> isContent = $this-> getIsContent();
    }

    /**
     * function which redefines Question::createAnswersForm
     * @param the formvalidator instance
     */
    function createAnswersForm($form)
    {
        $defaults = array();

        if (!empty($this->id)) {
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
                $defaults['multiple_answer'] = 1;
            } else {
                $defaults['multiple_answer'] = 0;
            }

            //Take the complete string except after the last '::'

            $defaults['answer'] = '';
            for ($i=0; $i<($sz-1); $i++) {
                $defaults['answer'] .= $pre_array[$i];
            }
            $a_weightings = explode(',', $is_set_switchable[0]);
        } else {
            $defaults['answer'] = get_lang('DefaultTextInBlanks');
        }

        $setValues = null;

        if (isset($a_weightings) && count($a_weightings) > 0) {
            foreach ($a_weightings as $i => $weighting) {
                $setValues .= 'document.getElementById("weighting['.$i.']").value = "'.$weighting.'";';
            }
        }

		// javascript
		echo '<script>

			function FCKeditor_OnComplete(editorInstance) {
				if (window.attachEvent) {
					editorInstance.EditorDocument.attachEvent("onkeyup", updateBlanks) ;
				} else {
					editorInstance.EditorDocument.addEventListener("keyup", updateBlanks, true);
				}
			}

            var firstTime = true;

            function updateBlanks() {
                if (firstTime) {
                    field = document.getElementById("answer");
                    var answer = field.value;

                } else {
                    var oEditor = FCKeditorAPI.GetInstance("answer");
                    //var answer =  oEditor.GetXHTML(true);
                    var answer = oEditor.EditorDocument.body.innerHTML;
                }

                var blanks = answer.match(/\[[^\]]*\]/g);
                var fields = "<div class=\"control-group\"><label class=\"control-label\">'.get_lang('Weighting').'</label><div class=\"controls\"><table>";
                if (blanks!=null) {
                    for (i=0 ; i<blanks.length ; i++){
                        if (document.getElementById("weighting["+i+"]"))
                            value = document.getElementById("weighting["+i+"]").value;
                        else
                            value = "10";
                        fields += "<tr><td><label>"+blanks[i]+"</label></td><td><input style=\"margin-left: 0em;\" size=\"5\" value=\""+value+"\" type=\"text\" id=\"weighting["+i+"]\" name=\"weighting["+i+"]\" /></td></tr>";
                    }
                }
                document.getElementById("blanks_weighting").innerHTML = fields + "</table></div></div>";
                if (firstTime) {
                    firstTime = false;
                    '.$setValues.'
			    }
            }

            window.onload = updateBlanks;
		</script>';

		// answer
		$form->addElement('label', null, '<br /><br />'.get_lang('TypeTextBelow').', '.get_lang('And').' '.get_lang('UseTagForBlank'));
		$form->addElement('html_editor', 'answer', '<img src="../img/fill_field.png">','id="answer" cols="122" rows="6" onkeyup="javascript: updateBlanks(this);"', array('ToolbarSet' => 'TestQuestionDescription', 'Width' => '100%', 'Height' => '350'));

		$form->addRule('answer', get_lang('GiveText'),'required');
		$form->addRule('answer', get_lang('DefineBlanks'),'regex','/\[.*\]/');

		//added multiple answers
		$form->addElement('checkbox', 'multiple_answer','', get_lang('FillInBlankSwitchable'));

		$form->addElement('html', '<div id="blanks_weighting"></div>');

		global $text, $class;
		// setting the save button here and not in the question class.php
		$form->addElement('style_submit_button', 'submitQuestion', $text, 'class="'.$class.'"');

		if (!empty($this->id)) {
			$form -> setDefaults($defaults);
		} else {
			if ($this->isContent == 1) {
				$form->setDefaults($defaults);
			}
		}
	}

	/**
	 * abstract function which creates the form to create / edit the answers of the question
	 * @param FormValidator $form
	 */
	function processAnswersCreation($form)
	{
		global $charset;
		$answer = $form->getSubmitValue('answer');

		//remove the :: eventually written by the user
        $answer = str_replace('::', '', $answer);

        // get the blanks weightings
        $nb = preg_match_all('/\[[^\]]*\]/', $answer, $blanks);
        if (isset($_GET['editQuestion'])) {
            $this ->weighting = 0;
        }

		if ($nb > 0) {
		  	$answer .= '::';
			for ($i=0 ; $i < $nb; ++$i) {
                $blankItem = $blanks[0][$i];
                $replace = array("[", "]");
                $newBlankItem = str_replace($replace, "", $blankItem);
                $newBlankItem = "[".trim($newBlankItem)."]";
                $answer = str_replace($blankItem, $newBlankItem, $answer);
                $answer .= $form->getSubmitValue('weighting['.$i.']').',';
                $this -> weighting += $form->getSubmitValue('weighting['.$i.']');
            }
            $answer = api_substr($answer, 0, -1);
		}

		$is_multiple = $form->getSubmitValue('multiple_answer');
		$answer.= '@'.$is_multiple;

		$this->save();
        $objAnswer = new answer($this->id);
        $objAnswer->createAnswer($answer, 0, '', 0, '1');
        $objAnswer->save();
	}

    /**
     * @param null $feedback_type
     * @param null $counter
     * @param null $score
     * @return null|string
     */
    function return_header($feedback_type = null, $counter = null, $score = null)
    {
	    $header = parent::return_header($feedback_type, $counter, $score);
	    $header .= '<table class="'.$this->question_table_class .'">
			<tr>
                <th>'.get_lang("Answer").'</th>
			</tr>';
        return $header;
	}
}
