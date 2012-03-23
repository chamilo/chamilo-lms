<?php
/* For licensing terms, see /license.txt */

/**
 *	File containing the UNIQUE_ANSWER class.
 *	@package chamilo.exercise
 * 	@author Eric Marguin
 */
/**
 * Code
 */

if(!class_exists('UniqueAnswer')):

/**
	CLASS UNIQUE_ANSWER
 *
 *	This class allows to instantiate an object of type UNIQUE_ANSWER (MULTIPLE CHOICE, UNIQUE ANSWER),
 *	extending the class question
 *
 *	@author Eric Marguin
 *  @author Julio Montoya
 *	@package chamilo.exercise
 **/

class UniqueAnswer extends Question {

	static $typePicture = 'mcua.gif';
	static $explanationLangVar = 'UniqueSelect';

	/**
	 * Constructor
	 */
	function UniqueAnswer(){
		//this is highly important
		parent::question();
		$this -> type = UNIQUE_ANSWER;
		$this -> isContent = $this-> getIsContent();
	}

	/**
	 * function which redifines Question::createAnswersForm
	 * @param the formvalidator instance
	 * @param the answers number to display
	 */
	function createAnswersForm ($form) {
		// getting the exercise list
		$obj_ex =$_SESSION['objExercise'];

		$editor_config = array('ToolbarSet' => 'TestProposedAnswer', 'Width' => '100%', 'Height' => '125');

		//this line define how many question by default appear when creating a choice question
		$nb_answers = isset($_POST['nb_answers']) ? (int) $_POST['nb_answers'] : 4;  // The previous default value was 2. See task #1759.
		$nb_answers += (isset($_POST['lessAnswers']) ? -1 : (isset($_POST['moreAnswers']) ? 1 : 0));

		/*
	 		Types of Feedback
	 		$feedback_option[0]=get_lang('Feedback');
			$feedback_option[1]=get_lang('DirectFeedback');
			$feedback_option[2]=get_lang('NoFeedback');
	 	*/

		$feedback_title='';
		$comment_title='';

		if ($obj_ex->selectFeedbackType()==0) {
			$comment_title = '<th>'.get_lang('Comment').'</th>';
		} elseif ($obj_ex->selectFeedbackType()==1) {
			$editor_config['Width'] = '250';
			$editor_config['Height'] = '110';
			$comment_title = '<th width="500px" >'.get_lang('Comment').'</th>';
			$feedback_title = '<th width="350px" >'.get_lang('Scenario').'</th>';
		}

		$html='
		<div class="row">
			<div class="label">
			'.get_lang('Answers').' <br /> <img src="../img/fill_field.png">
			</div>
			<div class="formw">
				<table class="data_table">
					<tr style="text-align: center;">
						<th width="10px">
							'.get_lang('Number').'
						</th>
						<th width="10px" >
							'.get_lang('True').'
						</th>
						<th width="50%">
							'.get_lang('Answer').'
						</th>
							'.$comment_title.'
							'.$feedback_title.'
						<th width="50px">
							'.get_lang('Weighting').'
						</th>        
					</tr>';

		$form -> addElement ('html', $html);

		$defaults = array();
		$correct = 0;
		if(!empty($this -> id)) {
			$answer = new Answer($this -> id);
			$answer -> read();
			if(count($answer->nbrAnswers)>0 && !$form->isSubmitted()) {
				$nb_answers = $answer->nbrAnswers;
			}
		}
		$form -> addElement('hidden', 'nb_answers');

		//Feedback SELECT
		$question_list=$obj_ex->selectQuestionList();
		$select_question=array();
		$select_question[0]=get_lang('SelectTargetQuestion');

		require_once '../newscorm/learnpathList.class.php';        
		if (is_array($question_list)) {
			foreach ($question_list as $key=>$questionid) {
				//To avoid warning messages
				if (!is_numeric($questionid)) {
					continue;
				}
				$question = Question::read($questionid);
				$select_question[$questionid]='Q'.$key.' :'.cut($question->selectTitle(),20);
			}
		}
		$select_question[-1]=get_lang('ExitTest');

		$list = new LearnpathList(api_get_user_id());
		$flat_list = $list->get_flat_list();
		$select_lp_id=array();
		$select_lp_id[0]=get_lang('SelectTargetLP');

		foreach ($flat_list as $id => $details) {
			$select_lp_id[$id] = cut($details['lp_name'],20);
		}

		$temp_scenario = array();
        
        if ($nb_answers < 1) {
            $nb_answers = 1;
            Display::display_normal_message(get_lang('YouHaveToCreateAtLeastOneAnswer'));
        }
                        

        for ($i = 1 ; $i <= $nb_answers ; ++$i) {
            $form -> addElement ('html', '<tr>');                
            if (isset($answer) && is_object($answer)) {
                if ($answer -> correct[$i]) {
                    $correct = $i;
                }
                $defaults['answer['.$i.']']    = $answer -> answer[$i];
                $defaults['comment['.$i.']']   = $answer -> comment[$i];
                $defaults['weighting['.$i.']'] = float_format($answer -> weighting[$i], 1);

                $item_list=explode('@@',$answer -> destination[$i]);

                $try       = $item_list[0];
                $lp        = $item_list[1];
                $list_dest = $item_list[2];
                $url       = $item_list[3];

                if ($try==0)
                    $try_result=0;
                else
                    $try_result=1;

                if ($url==0)
                    $url_result='';
                else
                    $url_result=$url;

                $temp_scenario['url'.$i]        = $url_result;
                $temp_scenario['try'.$i]        = $try_result;
                $temp_scenario['lp'.$i]         = $lp;
                $temp_scenario['destination'.$i]= $list_dest;
            } else {
                $defaults['answer[1]']     = get_lang('langDefaultUniqueAnswer1');
                $defaults['weighting[1]']  = 10;
                $defaults['answer[2]']     = get_lang('langDefaultUniqueAnswer2');
                $defaults['weighting[2]']  = 0;

                $temp_scenario['destination'.$i] = array('0');
                $temp_scenario['lp'.$i] = array('0');
            }
            $defaults['scenario'] = $temp_scenario;

            $renderer =& $form->defaultRenderer();
            
            $renderer->setElementTemplate('<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>', 'correct');  
            $renderer->setElementTemplate('<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>', 'counter['.$i.']');  
            $renderer->setElementTemplate('<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>', 'answer['.$i.']');  
            $renderer->setElementTemplate('<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>', 'comment['.$i.']');  
            $renderer->setElementTemplate('<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>', 'weighting['.$i.']');        
            
            $answer_number = $form->addElement('text', 'counter['.$i.']',null,' value = "'.$i.'"');
            $answer_number->freeze();

            $form->addElement('radio', 'correct', null, null, $i, 'class="checkbox" style="margin-left: 0em;"');
            $form->addElement('html_editor', 'answer['.$i.']', null, 'style="vertical-align:middle"', $editor_config);
            
            $form->addRule('answer['.$i.']', get_lang('ThisFieldIsRequired'), 'required');

            if ($obj_ex->selectFeedbackType() == EXERCISE_FEEDBACK_TYPE_END) {
                // feedback
                $form->addElement('html_editor', 'comment['.$i.']', null, 'style="vertical-align:middle"', $editor_config);
            } elseif ($obj_ex->selectFeedbackType() == EXERCISE_FEEDBACK_TYPE_DIRECT) {                    
                // direct feedback
                $form->addElement('html_editor', 'comment['.$i.']', null, 'style="vertical-align:middle"', $editor_config);
                //Adding extra feedback fields
                $group = array();
                $group['try'.$i] =&$form->createElement('checkbox', 'try'.$i,get_lang('TryAgain').': ' );
                $group['lp'.$i] =&$form->createElement('select', 'lp'.$i,get_lang('SeeTheory').': ',$select_lp_id);
                $group['destination'.$i]=&$form->createElement('select', 'destination'.$i, get_lang('GoToQuestion').': ' ,$select_question);
                $group['url'.$i] =&$form->createElement('text', 'url'.$i,get_lang('Other').': ',array('size'=>'25px'));
                
                $renderer->setElementTemplate('<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>', 'url'.$i);    
                $renderer->setElementTemplate('<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>', 'lp'.$i);
                $renderer->setElementTemplate('<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>', 'try'.$i);
                

                $form -> addGroup($group, 'scenario', 'scenario');
                $renderer->setGroupElementTemplate('<div class="exercise_scenario_label">{label}</div><div class="exercise_scenario_element">{element}</div>','scenario');
            }
            $form->addElement('text', 'weighting['.$i.']', null, array('class' => "span1", 'value' => '0'));                    
            $form->addElement ('html', '</tr>');                
        }

		$form -> addElement ('html', '</table>');
		$form -> addElement ('html', '<br />');
		$navigator_info = api_get_navigator();

		global $text, $class, $show_quiz_edition;
		//ie6 fix
		if ($show_quiz_edition) {
			if ($navigator_info['name']=='Internet Explorer' &&  $navigator_info['version']=='6') {
                
                $form->addElement('submit', 'lessAnswers', get_lang('LessAnswer'),'class="minus"');
                $form->addElement('submit', 'moreAnswers', get_lang('PlusAnswer'),'class="plus"'); 
                $form->addElement('submit','submitQuestion',$text, 'class="'.$class.'"');				
				
			} else {
                //setting the save button here and not in the question class.php                
                $form->addElement('style_submit_button', 'lessAnswers', get_lang('LessAnswer'),'class="minus"');
                $form->addElement('style_submit_button', 'moreAnswers', get_lang('PlusAnswer'),'class="plus"');                
                $form->addElement('style_submit_button', 'submitQuestion',$text, 'class="'.$class.'"');				
			}
		}
		$renderer->setElementTemplate('{element}','submitQuestion');
		$renderer->setElementTemplate('{element}&nbsp;','lessAnswers');
		$renderer->setElementTemplate('{element}','moreAnswers');

		$form -> addElement ('html', '</div></div>');

		//We check the first radio button to be sure a radio button will be check
		if ($correct==0) {
			$correct=1;
		}
		$defaults['correct'] = $correct;

		if (!empty($this -> id)) {
			$form -> setDefaults($defaults);
		} else {
			if ($this -> isContent == 1) {
				$form -> setDefaults($defaults);
			}
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
		$correct = $form -> getSubmitValue('correct');
		$objAnswer = new Answer($this->id);
		$nb_answers = $form -> getSubmitValue('nb_answers');

		for ($i=1 ; $i <= $nb_answers ; $i++) {
        	$answer = trim($form -> getSubmitValue('answer['.$i.']'));
            $comment = trim($form -> getSubmitValue('comment['.$i.']'));
            $weighting = trim($form -> getSubmitValue('weighting['.$i.']'));

            $scenario= $form -> getSubmitValue('scenario');

            echo '<pre>';
           	//$list_destination = $form -> getSubmitValue('destination'.$i);
           	//$destination_str = $form -> getSubmitValue('destination'.$i);

 		    $try = $scenario['try'.$i];
            $lp= $scenario['lp'.$i];
 			$destination = $scenario['destination'.$i];
 			$url = trim($scenario['url'.$i]);

 			/*
 			How we are going to parse the destination value

			here we parse the destination value which is a string
		 	1@@3@@2;4;4;@@http://www.dokeos.com

		 	where: try_again@@lp_id@@selected_questions@@url

			try_again = is 1 || 0
			lp_id = id of a learning path (0 if dont select)
			selected_questions= ids of questions
			url= an url
			*/
			/*
 			$destination_str='';
 			foreach ($list_destination as $destination_id)
 			{
 				$destination_str.=$destination_id.';';
 			}*/

        	$goodAnswer= ($correct == $i) ? true : false;

        	if($goodAnswer) {
        		$nbrGoodAnswers++;
        		$weighting = abs($weighting);
        		if($weighting > 0)
                {
                    $questionWeighting += $weighting;
                }
        	}

 			if (empty($try))
 				$try=0;

 			if (empty($lp))
 			{
 				$lp=0;
 			}

 			if (empty($destination)) {
 				$destination=0;
 			}
            
 			if ($url=='') {
 				$url=0;
 			}

 			//1@@1;2;@@2;4;4;@@http://www.dokeos.com
			$dest= $try.'@@'.$lp.'@@'.$destination.'@@'.$url;
        	$objAnswer -> createAnswer($answer,$goodAnswer,$comment,$weighting,$i,NULL,NULL,$dest);
        }
        
    	// saves the answers into the data base
        $objAnswer -> save();

        // sets the total weighting of the question
        $this -> updateWeighting($questionWeighting);
        $this -> save();
	}
	
	function return_header($feedback_type, $counter = null) {
	    parent::return_header($feedback_type, $counter);
	    $header = '<table width="100%" class="data_table_exercise_result">			
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
    
  function create_answer($id, $question_id, $answer_title, $comment, $score = 0, $correct = 0) {
    $tbl_quiz_answer = Database::get_course_table(TABLE_QUIZ_ANSWER);
    $tbl_quiz_question = Database::get_course_table(TABLE_QUIZ_QUESTION);
    $course_id = api_get_course_int_id();
    $position = 1;
    $question_id = filter_var($question_id,FILTER_SANITIZE_NUMBER_INT);
    $score = filter_var($score,FILTER_SANITIZE_NUMBER_FLOAT);
    $correct = filter_var($correct,FILTER_SANITIZE_NUMBER_INT);
    // Get the max position
    $sql = "SELECT max(position) as max_position FROM $tbl_quiz_answer "
          ." WHERE c_id = $course_id AND question_id = $question_id";
    $rs_max  = Database::query($sql);
    $row_max = Database::fetch_object($rs_max);
    $position = $row_max->max_position + 1;
    // Insert a new answer
    $sql = "INSERT INTO $tbl_quiz_answer "
    ."(c_id, id, question_id,answer,correct,comment,ponderation,position,destination)"
    ."VALUES ($course_id, $id,$question_id,'".Database::escape_string($answer_title)."',"
    ."$correct,'".Database::escape_string($comment)."',$score,$position, "
    ." '0@@0@@0@@0')";
    $rs = Database::query($sql);
    if ($correct) {
        $sql = "UPDATE $tbl_quiz_question "
        ." SET ponderation = (ponderation + $score) WHERE c_id = $course_id AND id = ".$question_id;
        $rs = Database::query($sql);
    }
  }
}
endif;