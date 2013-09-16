<?php
/* For licensing terms, see /license.txt */

/**
 * File containing the UNIQUE_ANSWER class.
 * @package chamilo.exercise
 * @author Eric Marguin
 */
/**
 * Code
 */
/**
CLASS UNIQUE_ANSWER
 *
 *    This class allows to instantiate an object of type UNIQUE_ANSWER (MULTIPLE CHOICE, UNIQUE ANSWER),
 *    extending the class question
 *
 * @author Eric Marguin
 * @author Julio Montoya
 * @package chamilo.exercise
 **/

class UniqueAnswer extends Question
{

    static $typePicture = 'mcua.gif';
    static $explanationLangVar = 'UniqueSelect';

    /**
     * Constructor
     */
    public function UniqueAnswer()
    {
        //this is highly important
        parent::question();
        $this->type      = UNIQUE_ANSWER;
        $this->isContent = $this->getIsContent();
    }

    /**
     * function which redifines Question::createAnswersForm
     * @param FormValidator instance
     */
    public function createAnswersForm($form)
    {
        // Getting the exercise list
        /** @var Exercise $obj_ex */
        $obj_ex = $this->exercise;
        $editor_config = array('ToolbarSet' => 'TestProposedAnswer', 'Width' => '100%', 'Height' => '125');

        //this line define how many question by default appear when creating a choice question
        // The previous default value was 2. See task #1759.
        $nb_answers = isset($_POST['nb_answers']) ? (int)$_POST['nb_answers'] : 4;
        $nb_answers += (isset($_POST['lessAnswers']) ? -1 : (isset($_POST['moreAnswers']) ? 1 : 0));
        $feedback_title = '';
        $comment_title  = '';

        if ($obj_ex->selectFeedbackType() == EXERCISE_FEEDBACK_TYPE_END) {
            $comment_title = '<th>'.get_lang('Comment').'</th>';
        } elseif ($obj_ex->selectFeedbackType() == EXERCISE_FEEDBACK_TYPE_DIRECT) {
            //Scenario
            $editor_config['Width']  = '250';
            $editor_config['Height'] = '110';
            $comment_title           = '<th width="500px" >'.get_lang('Comment').'</th>';
            $feedback_title          = '<th width="350px" >'.get_lang('Scenario').'</th>';
        }

        $html = '<table class="data_table">
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

        $form->addElement('label', get_lang('Answers').'<br />'.Display::return_icon('fill_field.png'), $html);

        $defaults = array();
        $correct  = 0;
        if (!empty($this->id)) {
            $answer = new Answer($this->id);
            $answer->read();
            if (count($answer->nbrAnswers) > 0 && !$form->isSubmitted()) {
                $nb_answers = $answer->nbrAnswers;
            }
        }

        $form->addElement('hidden', 'nb_answers');

        $list            = new LearnpathList(api_get_user_id());
        $flat_list       = $list->get_flat_list();
        $select_lp_id    = array();
        $select_lp_id[0] = get_lang('SelectTargetLP');

        foreach ($flat_list as $id => $details) {
            $select_lp_id[$id] = Text::cut($details['lp_name'], 20);
        }

        $temp_scenario = array();

        if ($nb_answers < 1) {
            $nb_answers = 1;
            Display::display_normal_message(get_lang('YouHaveToCreateAtLeastOneAnswer'));
        }

        for ($i = 1; $i <= $nb_answers; ++$i) {
            $form->addElement('html', '<tr>');
            if (isset($answer) && is_object($answer)) {

                $answer_id = $answer->getRealAnswerIdFromList($i);

                if ($answer->correct[$answer_id]) {
                    $correct = $i;
                }

                $defaults['answer['.$i.']']    = $answer->answer[$answer_id];
                $defaults['comment['.$i.']']   = $answer->comment[$answer_id];
                $defaults['weighting['.$i.']'] = Text::float_format($answer->weighting[$answer_id], 1);

                if (!empty($answer->destination[$answer_id])) {
                    $item_list = explode('@@', $answer->destination[$answer_id]);
                    $try       = $item_list[0];
                    $lp        = $item_list[1];
                    $list_dest = $item_list[2];
                    $url       = $item_list[3];

                    if ($try == 0) {
                        $try_result = 0;
                    } else {
                        $try_result = 1;
                    }

                    if ($url == 0) {
                        $url_result = '';
                    } else {
                        $url_result = $url;
                    }

                    $temp_scenario['url'.$i]         = $url_result;
                    $temp_scenario['try'.$i]         = $try_result;
                    $temp_scenario['lp'.$i]          = $lp;
                    $temp_scenario['destination'.$i] = $list_dest;
                }


            } else {
                $defaults['answer[1]']    = get_lang('DefaultUniqueAnswer1');
                $defaults['weighting[1]'] = 10;
                $defaults['answer[2]']    = get_lang('DefaultUniqueAnswer2');
                $defaults['weighting[2]'] = 0;

                $temp_scenario['destination'.$i] = array('0');
                $temp_scenario['lp'.$i]          = array('0');
            }
            $defaults['scenario'] = $temp_scenario;

            $renderer = $form->defaultRenderer();

            $renderer->setElementTemplate(
                '<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>',
                'correct'
            );
            $renderer->setElementTemplate(
                '<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>',
                'counter['.$i.']'
            );
            $renderer->setElementTemplate(
                '<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>',
                'answer['.$i.']'
            );
            $renderer->setElementTemplate(
                '<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>',
                'comment['.$i.']'
            );
            $renderer->setElementTemplate(
                '<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>',
                'weighting['.$i.']'
            );

            $answer_number = $form->addElement('text', 'counter['.$i.']', null, ' value = "'.$i.'"');
            $answer_number->freeze();

            $form->addElement('radio', 'correct', null, null, $i, 'class="checkbox" style="margin-left: 0em;"');

            if ($obj_ex->fastEdition) {
                $form->addElement('textarea', 'answer['.$i.']', null, $this->textareaSettings);
            } else {
                $form->addElement('html_editor', 'answer['.$i.']', null, 'style="vertical-align:middle"', $editor_config);
            }

            $form->addRule('answer['.$i.']', get_lang('ThisFieldIsRequired'), 'required');

            if ($obj_ex->selectFeedbackType() == EXERCISE_FEEDBACK_TYPE_END) {

                if ($obj_ex->fastEdition) {
                    // feedback
                    $form->addElement(
                        'textarea',
                        'comment['.$i.']',
                        null,
                        $this->textareaSettings
                    );
                } else {
                    // feedback
                    $form->addElement(
                        'html_editor',
                        'comment['.$i.']',
                        null,
                        'style="vertical-align:middle"',
                        $editor_config
                    );
                }
            } elseif ($obj_ex->selectFeedbackType() == EXERCISE_FEEDBACK_TYPE_DIRECT) {

                // Feedback SELECT

                $question_list      = $obj_ex->selectQuestionList();
                $select_question    = array();
                $select_question[0] = get_lang('SelectTargetQuestion');
                // @todo improve this loop if you have 5000 questions it will blow!
                if (is_array($question_list)) {
                    foreach ($question_list as $key => $questionid) {
                        //To avoid warning messages
                        if (!is_numeric($questionid)) {
                            continue;
                        }
                        $question = Question::read($questionid);

                        if ($question) {
                            $select_question[$questionid] = 'Q'.$key.' :'.Text::cut($question->selectTitle(), 20);
                        }
                    }
                }

                $select_question[-1] = get_lang('ExitTest');

                $form->addElement(
                    'html_editor',
                    'comment['.$i.']',
                    null,
                    'style="vertical-align:middle"',
                    $editor_config
                );

                // Direct feedback

                //Adding extra feedback fields
                $group                   = array();
                $group['try'.$i]         = $form->createElement('checkbox', 'try'.$i, null, get_lang('TryAgain'));
                $group['lp'.$i]          = $form->createElement(
                    'select',
                    'lp'.$i,
                    get_lang('SeeTheory').': ',
                    $select_lp_id
                );
                $group['destination'.$i] = $form->createElement(
                    'select',
                    'destination'.$i,
                    get_lang('GoToQuestion').': ',
                    $select_question
                );
                $group['url'.$i]         = $form->createElement(
                    'text',
                    'url'.$i,
                    get_lang('Other').': ',
                    array('class' => 'span2', 'placeholder' => get_lang('Other'))
                );
                $form->addGroup($group, 'scenario');

                $renderer->setElementTemplate(
                    '<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}',
                    'scenario'
                );

            }
            $form->addElement('text', 'weighting['.$i.']', null, array('class' => "span1", 'value' => '0'));
            $form->addElement('html', '</tr>');
        }

        $form->addElement('html', '</table>');
        $form->addElement('html', '<br />');

        $navigator_info = api_get_navigator();

        // ie6 fix.

        if ($form->isFrozen() == false) {
            if ($obj_ex->edit_exercise_in_lp == true) {
                if ($navigator_info['name'] == 'Internet Explorer' && $navigator_info['version'] == '6') {
                    $form->addElement('submit', 'lessAnswers', get_lang('LessAnswer'), 'class="btn minus"');
                    $form->addElement('submit', 'moreAnswers', get_lang('PlusAnswer'), 'class="btn plus"');
                    $form->addElement('submit', 'submitQuestion', $this->submitText, 'class="'.$this->submitClass.'"');
                } else {
                    //setting the save button here and not in the question class.php
                    $form->addElement('style_submit_button', 'lessAnswers', get_lang('LessAnswer'), 'class="btn minus"');
                    $form->addElement('style_submit_button', 'moreAnswers', get_lang('PlusAnswer'), 'class="btn plus"');
                    $form->addElement('style_submit_button', 'submitQuestion', $this->submitText, 'class="'.$this->submitClass.'"');
                }
            }
            $renderer->setElementTemplate('{element}&nbsp;', 'submitQuestion');
            $renderer->setElementTemplate('{element}&nbsp;', 'lessAnswers');
            $renderer->setElementTemplate('{element}&nbsp;', 'moreAnswers');
        }

        $form->addElement('html', '</div></div>');

        //We check the first radio button to be sure a radio button will be check
        if ($correct == 0) {
            $correct = 1;
        }
        $defaults['correct'] = $correct;

        if (!empty($this->id)) {
            $form->setDefaults($defaults);
        } else {
            if ($this->isContent == 1) {
                $form->setDefaults($defaults);
            }
        }
        $form->setConstants(array('nb_answers' => $nb_answers));
    }


    /**
     * abstract function which creates the form to create / edit the answers of the question
     * @param the formvalidator instance
     * @param the answers number to display
     */
    public function processAnswersCreation($form)
    {
        $questionWeighting = $nbrGoodAnswers = 0;

        $correct           = $form->getSubmitValue('correct');
        $objAnswer         = new Answer($this->id);
        $nb_answers        = $form->getSubmitValue('nb_answers');

        for ($i = 1; $i <= $nb_answers; $i++) {
            $answer    = trim($form->getSubmitValue('answer['.$i.']'));
            $comment   = trim($form->getSubmitValue('comment['.$i.']'));
            $weighting = trim($form->getSubmitValue('weighting['.$i.']'));

            $scenario = $form->getSubmitValue('scenario');

            //$list_destination = $form -> getSubmitValue('destination'.$i);
            //$destination_str = $form -> getSubmitValue('destination'.$i);

            $try         = $scenario['try'.$i];
            $lp          = $scenario['lp'.$i];
            $destination = $scenario['destination'.$i];
            $url         = trim($scenario['url'.$i]);

            /*
            How we are going to parse the destination value

           here we parse the destination value which is a string
            1@@3@@2;4;4;@@http://www.chamilo.org

            where: try_again@@lp_id@@selected_questions@@url

           try_again = is 1 || 0
           lp_id = id of a learning path (0 if dont select)
           selected_questions= ids of questions
           url= an url

            $destination_str='';
             foreach ($list_destination as $destination_id) {
                $destination_str.=$destination_id.';';
            }*/

            $goodAnswer = ($correct == $i) ? true : false;

            if ($goodAnswer) {
                $nbrGoodAnswers++;
                $weighting = abs($weighting);
                if ($weighting > 0) {
                    $questionWeighting += $weighting;
                }
            }

            if (empty($try)) {
                $try = 0;
            }

            if (empty($lp)) {
                $lp = 0;
            }

            if (empty($destination)) {
                $destination = 0;
            }

            if ($url == '') {
                $url = 0;
            }

            //1@@1;2;@@2;4;4;@@http://www.chamilo.org
            $dest = $try.'@@'.$lp.'@@'.$destination.'@@'.$url;
            $objAnswer->createAnswer($answer, $goodAnswer, $comment, $weighting, $i, null, null, $dest);
        }

        // saves the answers into the data base
        $objAnswer->save();

        // sets the total weighting of the question
        $this->updateWeighting($questionWeighting);
        $this->save();
    }

    /**
     * {@inheritdoc}
     */
    public function return_header($feedback_type = null, $counter = null, $score = null, $show_media = false, $hideTitle = 0)
    {
        $header = parent::return_header($feedback_type, $counter, $score, $show_media, $hideTitle);
        $header .= '<table class="'.$this->question_table_class.'">
			<tr>
				<th>'.get_lang("Choice").'</th>
				<th>'.get_lang("ExpectedChoice").'</th>
				<th>'.get_lang("Answer").'</th>';
        if ($feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) {
            $header .= '<th>'.get_lang("Comment").'</th>';
        } else {
            $header .= '<th>&nbsp;</th>';
        }
        $header .= '</tr>';

        return $header;
    }

    /**
     * Create database record for the given answer
     * @param int The answer ID (technically 1 is OK if you don't know)
     * @param int The question this answer is attached to
     * @param string The answer itself
     * @param string The answer comment (shown as feedback if enabled)
     * @param float  Score given by this answer if selected (can be negative)
     * @param int Whether this answer is considered correct (1) or not (0)
     * @param int The course ID - if not provided, will be guessed from the context
     * @return void
     * @assert (1,null,'a','',1,1,null) === false
     * @assert (1,1,'','',1,1,null) === false
     */
    public function create_answer(
        $id = 1,
        $question_id,
        $answer_title,
        $comment = '',
        $score = 0,
        $correct = 0,
        $course_id = null
    ) {
        if (empty($question_id) or empty($answer_title)) {
            return false;
        }
        $tbl_quiz_answer   = Database::get_course_table(TABLE_QUIZ_ANSWER);
        $tbl_quiz_question = Database::get_course_table(TABLE_QUIZ_QUESTION);
        if (empty($course_id)) {
            $course_id = api_get_course_int_id();
        }
        $question_id = filter_var($question_id, FILTER_SANITIZE_NUMBER_INT);
        $score       = filter_var($score, FILTER_SANITIZE_NUMBER_FLOAT);
        $correct     = filter_var($correct, FILTER_SANITIZE_NUMBER_INT);
        if (empty($question_id) or empty($score) or empty($correct)) {
            return false;
        }
        // Get the max position
        $sql      = "SELECT max(position) as max_position FROM $tbl_quiz_answer WHERE question_id = $question_id";
        $rs_max   = Database::query($sql);
        $row_max  = Database::fetch_object($rs_max);
        $position = $row_max->max_position + 1;
        // Insert a new answer
        $sql = "INSERT INTO $tbl_quiz_answer (question_id,answer,correct,comment,ponderation,position,destination)"
            ."VALUES ($question_id, '".Database::escape_string($answer_title)."',"
            ."$correct,'".Database::escape_string($comment)."',$score,$position, "
            ." '0@@0@@0@@0')";
        Database::query($sql);
        if ($correct) {
            $sql = "UPDATE $tbl_quiz_question SET ponderation = (ponderation + $score) WHERE iid = ".$question_id;
            $rs  = Database::query($sql);

            return $rs;
        }
    }
}
