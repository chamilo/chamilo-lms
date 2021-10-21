<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Class UniqueAnswerNoOption
 * Allows to instantiate an object of type UNIQUE_ANSWER (MULTIPLE CHOICE, UNIQUE ANSWER),
 * extending the class question.
 *
 * @author Eric Marguin
 * @author Julio Montoya
 */
class UniqueAnswerNoOption extends Question
{
    public $typePicture = 'mcuao.png';
    public $explanationLangVar = 'Unique answer with unknown';

    public function __construct()
    {
        parent::__construct();
        $this->type = UNIQUE_ANSWER_NO_OPTION;
        $this->isContent = $this->getIsContent();
    }

    public function createAnswersForm($form)
    {
        // getting the exercise list
        $obj_ex = Session::read('objExercise');

        $editor_config = [
            'ToolbarSet' => 'TestProposedAnswer',
            'Width' => '100%',
            'Height' => '125',
        ];
        // This line define how many question by default appear when creating a choice question
        // The previous default value was 2. See task #1759.
        $nb_answers = isset($_POST['nb_answers']) ? (int) $_POST['nb_answers'] : 3;
        $nb_answers += (isset($_POST['lessAnswers']) ? -1 : (isset($_POST['moreAnswers']) ? 1 : 0));

        /*
          Types of Feedback
          $feedback_option[0]=get_lang('Feedback');
          $feedback_option[1]=get_lang('Adaptative test with immediate feedback');
          $feedback_option[2]=get_lang('Exam (no feedback)');
         */

        $feedback_title = '';
        if (1 == $obj_ex->getFeedbackType()) {
            $editor_config['Width'] = '250';
            $editor_config['Height'] = '110';
            $comment_title = '<th width="50%" >'.get_lang('Comment').'</th>';
            $feedback_title = '<th width="50%" >'.get_lang('Scenario').'</th>';
        } else {
            $comment_title = '<th width="50%">'.get_lang('Comment').'</th>';
        }

        $html = '<table class="table table-striped table-hover">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>'.get_lang('N°').'</th>';
        $html .= '<th>'.get_lang('True').'</th>';
        $html .= '<th width="50%">'.get_lang('Answer').'</th>';
        $html .= $comment_title.$feedback_title;
        $html .= '<th>'.get_lang('Score').'</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        $form->addHeader(get_lang('Answers'));
        $form->addHtml($html);

        $defaults = [];
        $correct = 0;
        $answer = false;
        if (!empty($this->id)) {
            $answer = new Answer($this->id);
            $answer->read();
            if ($answer->nbrAnswers > 0 && !$form->isSubmitted()) {
                $nb_answers = $answer->nbrAnswers;
            }
        }

        $temp_scenario = [];
        if ($nb_answers < 1) {
            $nb_answers = 1;
            echo Display::return_message(get_lang('You have to create at least one answer'));
        }
        $editQuestion = isset($_GET['editQuestion']) ? $_GET['editQuestion'] : false;
        if ($editQuestion) {
            //fixing $nb_answers
            $new_list = [];
            $count = 1;
            if (isset($_POST['lessAnswers'])) {
                if (!isset($_SESSION['less_answer'])) {
                    $_SESSION['less_answer'] = $this->id;
                    $nb_answers--;
                }
            }
            for ($k = 1; $k <= $nb_answers; $k++) {
                if ('666' != $answer->position[$k]) {
                    $new_list[$count] = $count;
                    $count++;
                }
            }
        } else {
            for ($k = 1; $k <= $nb_answers; $k++) {
                $new_list[$k] = $k;
            }
        }

        foreach ($new_list as $key) {
            $i = $key;
            $form->addHtml('<tr>');
            if (is_object($answer)) {
                if (666 == $answer->position[$i]) {
                    //we set nothing
                } else {
                    if ($answer->correct[$i]) {
                        $correct = $i;
                    }
                    $answer_result = $answer->answer[$i];
                    $weight_result = float_format($answer->weighting[$i], 1);
                    if ($nb_answers == $i) {
                        $weight_result = '0';
                    }

                    $defaults['answer['.$i.']'] = $answer_result;
                    $defaults['comment['.$i.']'] = $answer->comment[$i];
                    $defaults['weighting['.$i.']'] = $weight_result;

                    $item_list = explode('@@', $answer->destination[$i]);

                    $try = $item_list[0];
                    $lp = $item_list[1];
                    $list_dest = $item_list[2];
                    $url = $item_list[3];

                    if (0 == $try) {
                        $try_result = 0;
                    } else {
                        $try_result = 1;
                    }

                    if (0 == $url) {
                        $url_result = '';
                    } else {
                        $url_result = $url;
                    }

                    $temp_scenario['url'.$i] = $url_result;
                    $temp_scenario['try'.$i] = $try_result;
                    $temp_scenario['lp'.$i] = $lp;
                    $temp_scenario['destination'.$i] = $list_dest;
                }
            }

            $defaults['scenario'] = $temp_scenario;
            $renderer = &$form->defaultRenderer();

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

            $answerNumber = $form->addText('counter['.$i.']', null, false, ['value' => $i]);
            $answerNumber->freeze();

            $form->addElement(
                'radio',
                'correct',
                null,
                null,
                $i,
                ['class' => 'checkbox', 'style' => 'margin-left: 0em;']
            );
            $form->addHtmlEditor('answer['.$i.']', null, [], $editor_config);
            $form->addHtmlEditor('comment['.$i.']', null, [], $editor_config);
            $form->addText('weighting['.$i.']', null, false, ['style' => 'width: 60px;', 'value' => '0']);
            $form->addHtml('</tr>');
            $i++;
        }

        if (empty($this->id)) {
            $form->addHidden('new_question', 1);
        }

        //Adding the "I don't know" question answer
        //if (empty($this -> id)) {
        $i = 666;
        $form->addHtml('<tr>');

        $defaults["counter[$i]"] = '-';
        $defaults['answer['.$i.']'] = get_lang('Don\'t know');
        $defaults['weighting['.$i.']'] = '0';
        $defaults['scenario'] = $temp_scenario;
        $renderer = &$form->defaultRenderer();

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

        $form
            ->addElement('text', 'counter['.$i.']', null)
            ->freeze();

        $form->addHidden('position['.$i.']', '666');
        $form->addElement('radio', 'correct', null, null, $i, ['class' => 'checkbox', 'disabled' => true]);
        $form->addHtmlEditor('answer['.$i.']', null, true, [], $editor_config);

        $form->addRule('answer['.$i.']', get_lang('Required field'), 'required');
        $form->addHtmlEditor('comment['.$i.']', null, true, [], $editor_config);
        $form->addElement('text', "weighting[$i]", null)->freeze();

        $form->addHTml('</tr>');
        $form->addHtml('</tbody></table>');

        $buttonGroup = [];

        global $text;
        //ie6 fix
        if (true == $obj_ex->edit_exercise_in_lp ||
            (empty($this->exerciseList) && empty($obj_ex->id))
        ) {
            //setting the save button here and not in the question class.php
            $buttonGroup[] = $form->addButtonDelete(get_lang('Remove answer option'), 'lessAnswers', true);
            $buttonGroup[] = $form->addButtonCreate(get_lang('Add answer option'), 'moreAnswers', true);
            $buttonGroup[] = $form->addButtonSave($text, 'submitQuestion', true);

            $form->addGroup($buttonGroup);
        }

        //We check the first radio button to be sure a radio button will be check
        if (0 == $correct) {
            $correct = 1;
        }
        $defaults['correct'] = $correct;

        if (!empty($this->id)) {
            $form->setDefaults($defaults);
        } else {
            $form->setDefaults($defaults);
        }

        $form->addElement('hidden', 'nb_answers');
        $form->setConstants(['nb_answers' => $nb_answers]);
    }

    public function processAnswersCreation($form, $exercise)
    {
        $questionWeighting = $nbrGoodAnswers = 0;
        $correct = $form->getSubmitValue('correct');
        $objAnswer = new Answer($this->id);
        $nb_answers = $form->getSubmitValue('nb_answers');
        $minus = 1;
        if ($form->getSubmitValue('new_question')) {
            $minus = 0;
        }

        for ($i = 1; $i <= $nb_answers - $minus; $i++) {
            $position = trim($form->getSubmitValue('position['.$i.']'));
            $answer = trim($form->getSubmitValue('answer['.$i.']'));
            $comment = trim($form->getSubmitValue('comment['.$i.']'));
            $weighting = trim($form->getSubmitValue('weighting['.$i.']'));
            $scenario = $form->getSubmitValue('scenario');
            $try = $scenario['try'.$i] ?? null;
            $lp = $scenario['lp'.$i] ?? null;
            $destination = $scenario['destination'.$i] ?? null;
            $url = trim($scenario['url'.$i] ?? null);

            /*
            How we are going to parse the destination value

            here we parse the destination value which is a string
            1@@3@@2;4;4;@@http://www.chamilo.org

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
            $goodAnswer = $correct == $i ? true : false;

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

            if ('' == $url) {
                $url = 0;
            }

            //1@@1;2;@@2;4;4;@@http://www.chamilo.org
            $dest = $try.'@@'.$lp.'@@'.$destination.'@@'.$url;
            $objAnswer->createAnswer(
                $answer,
                $goodAnswer,
                $comment,
                $weighting,
                $i,
                null,
                null,
                $dest
            );
        }

        //Create 666 answer
        $i = 666;
        $answer = trim($form->getSubmitValue('answer['.$i.']'));
        $comment = trim($form->getSubmitValue('comment['.$i.']'));
        $weighting = trim($form->getSubmitValue('weighting['.$i.']'));
        $goodAnswer = $correct == $i ? true : false;
        $dest = '';

        $objAnswer->createAnswer(
            $answer,
            $goodAnswer,
            $comment,
            $weighting,
            $i,
            null,
            null,
            $dest
        );

        // saves the answers into the data base
        $objAnswer->save();

        // sets the total weighting of the question
        $this->updateWeighting($questionWeighting);
        $this->save($exercise);
    }

    public function return_header(Exercise $exercise, $counter = null, $score = [])
    {
        $header = parent::return_header($exercise, $counter, $score);
        $header .= '<table class="'.$this->question_table_class.'"><tr>';

        if (!in_array($exercise->results_disabled, [
            RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER,
        ])
        ) {
            $header .= '<th>'.get_lang('Your choice').'</th>';
            if ($exercise->showExpectedChoiceColumn()) {
                $header .= '<th>'.get_lang('Expected choice').'</th>';
            }
        }
        $header .= '<th>'.get_lang('Answer').'</th>';
        if ($exercise->showExpectedChoice()) {
            $header .= '<th class="text-center">'.get_lang('Status').'</th>';
        }
        if (false === $exercise->hideComment) {
            $header .= '<th>'.get_lang('Comment').'</th>';
        }
        $header .= '</tr>';

        return $header;
    }
}
