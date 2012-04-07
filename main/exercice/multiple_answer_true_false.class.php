<?php
/* For licensing terms, see /license.txt */
/**
 * CLASS MultipleAnswer
 *
 * This class allows to instantiate an object of type MULTIPLE_ANSWER
 * (MULTIPLE CHOICE, MULTIPLE ANSWER), extending the class question
 * @author Julio Montoya
 * @package chamilo.exercise
 **/
/**
 * Code
 */
if(!class_exists('MultipleAnswerTrueFalse')):
/**
 * Class
 * @package chamilo.exercise
 */
class MultipleAnswerTrueFalse extends Question {

	static $typePicture = 'mcmao.gif';
	static $explanationLangVar = 'MultipleAnswerTrueFalse';
    var    $options;
	/**
	 * Constructor
	 */
	function MultipleAnswerTrueFalse(){
		parent::question();
		$this->type = MULTIPLE_ANSWER_TRUE_FALSE;
		$this->isContent = $this-> getIsContent();
        $this->options = array(1=>get_lang('True'),2 =>get_lang('False'), 3 =>get_lang('DoubtScore'));
	}

	/**
	 * function which redifines Question::createAnswersForm
	 * @param the formvalidator instance
	 * @param the answers number to display
	 */
	function createAnswersForm ($form) {

		$nb_answers  = isset($_POST['nb_answers']) ? $_POST['nb_answers'] : 4;  // The previous default value was 2. See task #1759.
		$nb_answers += (isset($_POST['lessAnswers']) ? -1 : (isset($_POST['moreAnswers']) ? 1 : 0));

		$obj_ex = $_SESSION['objExercise'];
        
		$html.='<div class="row">
			     <div class="label">
			         '.get_lang('Answers').'<br /><img src="../img/fill_field.png">
			     </div>
			     <div class="formw">';        
        
        $html2 ='<div class="row">
                 <div class="label">               
                 </div>
                 <div class="formw">';
        
        $form -> addElement ('html', $html2);        
        $form -> addElement ('html', '<table><tr>');  
        $renderer = & $form->defaultRenderer();
        $defaults = array();
        //Extra values True, false,  Dont known
        if (!empty($this->extra)) {
            $scores = explode(':',$this->extra);
        
            if (!empty($scores)) {
                for ($i = 1; $i <=3; $i++) {
                    $defaults['option['.$i.']']	= $scores[$i-1];                
                }        
            }        
        }
        
        // 3 scores
        $form->addElement('text', 'option[1]',get_lang('True'),      array('size'=>'5','value'=>'1'));
        $form->addElement('text', 'option[2]',get_lang('False'),     array('size'=>'5','value'=>'-0.5'));        
        $form->addElement('text', 'option[3]',get_lang('DoubtScore'),array('size'=>'5','value'=>'0'));  
                
        $form -> addElement('hidden', 'options_count', 3);
                    
        $form -> addElement ('html', '</tr></table>');
        $form -> addElement ('html', '</div></div>');
       
		$html.='<table class="data_table">
					<tr style="text-align: center;">
						<th>
							'.get_lang('Number').'
						</th>
						<th>
							'.get_lang('True').'
						</th>
                        <th>
                            '.get_lang('False').'
                        </th>     
						<th>
							'.get_lang('Answer').'
						</th>';
        				// show column comment when feedback is enable
        				if ($obj_ex->selectFeedbackType() != EXERCISE_FEEDBACK_TYPE_EXAM ) {
        				    $html .='<th>'.get_lang('Comment').'</th>';
        				}
				$html .= '</tr>';
		$form -> addElement ('html', $html);

		
		$correct = 0;
		if (!empty($this -> id))	{
			$answer = new Answer($this -> id);
			$answer->read();			
			if (count($answer->nbrAnswers) > 0 && !$form->isSubmitted()) {
				$nb_answers = $answer->nbrAnswers;
			}
		}
		
		$form -> addElement('hidden', 'nb_answers');
		$boxes_names = array();

		if ($nb_answers < 1) {
			$nb_answers = 1;
			Display::display_normal_message(get_lang('YouHaveToCreateAtLeastOneAnswer'));
		}
		
        $course_id = api_get_course_int_id();
        
        // Can be more options        
        $option_data = Question::readQuestionOption($this->id, $course_id);
          
		for ($i = 1 ; $i <= $nb_answers ; ++$i) {
            
            $renderer->setElementTemplate('<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>', 'correct['.$i.']');  
            $renderer->setElementTemplate('<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>', 'counter['.$i.']');  
            $renderer->setElementTemplate('<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>', 'answer['.$i.']');  
            $renderer->setElementTemplate('<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>', 'comment['.$i.']'); 
            
            $answer_number=$form->addElement('text', 'counter['.$i.']',null,'value="'.$i.'"');
            $answer_number->freeze();            
            
			if (is_object($answer)) {               
				$defaults['answer['.$i.']']     = $answer -> answer[$i];
				$defaults['comment['.$i.']']    = $answer -> comment[$i];
				//$defaults['weighting['.$i.']']  = float_format($answer -> weighting[$i], 1);
      
                $correct = $answer->correct[$i];
                                
                $defaults['correct['.$i.']']    = $correct;          
			
                $j = 1;             
                if (!empty($option_data)) {
                    foreach ($option_data as $id=>$data) {                 
                        $form->addElement('radio', 'correct['.$i.']', null, null, $id);
                        $j++;
                        if ($j == 3) {
                        	break;
                        }
                       
                    }            
                }
			} else {                
                $form->addElement('radio', 'correct['.$i.']', null, null, 1);            
                $form->addElement('radio', 'correct['.$i.']', null, null, 2);
            
                $defaults['answer['.$i.']']     = '';
                $defaults['comment['.$i.']']    = '';
                $defaults['correct['.$i.']']    = '';                
			}          
            
            //$form->addElement('select', 'correct['.$i.']',null, $this->options, array('id'=>$i,'onchange'=>'multiple_answer_true_false_onchange(this)'));
            
			$boxes_names[] = 'correct['.$i.']';

			$form->addElement('html_editor', 'answer['.$i.']',null, 'style="vertical-align:middle"', array('ToolbarSet' => 'TestProposedAnswer', 'Width' => '100%', 'Height' => '100'));
			$form->addRule('answer['.$i.']', get_lang('ThisFieldIsRequired'), 'required');

			// show comment when feedback is enable
			if ($obj_ex->selectFeedbackType() != EXERCISE_FEEDBACK_TYPE_EXAM) {
				$form->addElement('html_editor', 'comment['.$i.']',null, 'style="vertical-align:middle"', array('ToolbarSet' => 'TestProposedAnswer', 'Width' => '100%', 'Height' => '100'));
			}
			$form->addElement ('html', '</tr>');
		}
		$form -> addElement ('html', '</table>');
		$form -> addElement ('html', '<br />');

		//$form -> add_multiple_required_rule ($boxes_names , get_lang('ChooseAtLeastOneCheckbox') , 'multiple_required');


		$navigator_info = api_get_navigator();

		global $text, $class, $show_quiz_edition;
		if ($show_quiz_edition) {
			//ie6 fix
			if ($navigator_info['name']=='Internet Explorer' &&  $navigator_info['version']=='6') {
                
                $form->addElement('submit', 'lessAnswers', get_lang('LessAnswer'),'class="btn minus"');
                $form->addElement('submit', 'moreAnswers', get_lang('PlusAnswer'),'class="btn plus"');				
                $form->addElement('submit', 'submitQuestion',$text, 'class="'.$class.'"');
			} else {
                // setting the save button here and not in the question class.php
                
                $form->addElement('style_submit_button', 'lessAnswers', get_lang('LessAnswer'),'class="btn minus"');
                $form->addElement('style_submit_button', 'moreAnswers', get_lang('PlusAnswer'),'class="btn plus"');
                $form->addElement('style_submit_button', 'submitQuestion',$text, 'class="'.$class.'"');	
			}
		}
		$renderer->setElementTemplate('{element}&nbsp;','lessAnswers');
		$renderer->setElementTemplate('{element}&nbsp;','submitQuestion');
		$renderer->setElementTemplate('{element}&nbsp;','moreAnswers');
		$form -> addElement ('html', '</div></div>');
		$defaults['correct'] = $correct;

		if (!empty($this -> id)) {
			$form -> setDefaults($defaults);
		} else {
			//if ($this -> isContent == 1) {
				$form -> setDefaults($defaults);
			//}
		}

		$form->setConstants(array('nb_answers' => $nb_answers));
	}


	/**
	 * abstract function which creates the form to create / edit the answers of the question
	 * @param the formvalidator instance
	 * @param the answers number to display
	 */
	function processAnswersCreation($form) {
		$questionWeighting = $nbrGoodAnswers = 0;
		$objAnswer        = new Answer($this->id);
		$nb_answers       = $form->getSubmitValue('nb_answers');
        $options_count    = $form->getSubmitValue('options_count');
        $course_id        = api_get_course_int_id();
       
        $correct = array();
        $options = Question::readQuestionOption($this->id, $course_id);
        	
        
        if (!empty($options)) {
            foreach ($options as $option_data) {
                $id = $option_data['id'];
                unset($option_data['id']);
                Question::updateQuestionOption($id, $option_data, $course_id);
            }
        } else {            
            for ($i=1 ; $i <= 3 ; $i++) {                
        	   $last_id = Question::saveQuestionOption($this->id, $this->options[$i], $course_id, $i);
               $correct[$i] = $last_id;
            }            
        }
  
        //Getting quiz_question_options (true, false, doubt) because it's possible that there are more options in the future
        $new_options = Question::readQuestionOption($this->id, $course_id);
        
        $sorted_by_position = array();
        foreach($new_options as $item) {
        	$sorted_by_position[$item['position']] = $item;
        }        
        
        //Saving quiz_question.extra values that has the correct scores of the true, false, doubt options registered in this format XX:YY:ZZZ where XX is a float score value 
        $extra_values = array();
        for ($i=1 ; $i <= 3 ; $i++) {
            $score = trim($form -> getSubmitValue('option['.$i.']'));
            $extra_values[]= $score;
        }        
        $this->setExtra(implode(':',$extra_values));       
          
		for ($i=1 ; $i <= $nb_answers ; $i++) {
        	$answer     = trim($form -> getSubmitValue('answer['.$i.']'));
            $comment    = trim($form -> getSubmitValue('comment['.$i.']'));
            $goodAnswer = trim($form -> getSubmitValue('correct['.$i.']'));  
            if (empty($options)) {
                //If this is the first time that the question is created when change the default values from the form 1 and 2 by the correct "option id" registered 
                $goodAnswer = $sorted_by_position[$goodAnswer]['id'];
            }         
    	    $questionWeighting += $extra_values[0]; //By default 0 has the correct answers
        	$objAnswer->createAnswer($answer, $goodAnswer, $comment,'',$i);            
        }        
    

    	// saves the answers into the data base
        $objAnswer -> save();
    
        // sets the total weighting of the question
        $this -> updateWeighting($questionWeighting);
        $this -> save();
	}
	
	function return_header($feedback_type, $counter = null) {
	    $header = "";
	    if ($in_echo == 1) {
	        parent::return_header($feedback_type, $counter, $in_echo);
	    } else {
	        $header = parent::return_header($feedback_type, $counter, $in_echo) . $header;
	    }    	
  	    $header .= '<table width="100%" class="data_table_exercise_result">		
		<tr>
			<td><i>'.get_lang("Choice").'</i> </td>
			<td><i>'. get_lang("ExpectedChoice").'</i></td>
			<td><i>'. get_lang("Answer").'</i></td>';
			if ($feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) { 
				$header .= '<td><i>'.get_lang("Comment").'</i></td>';
			} else { 
				$header .= '<td>&nbsp;</td>';
			}
        $header .= '</tr>';
        return $header;	  
	}
}
endif;
