<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Class UniqueAnswer.
 *
 * This class allows to instantiate an object of type UNIQUE_ANSWER
 * (MULTIPLE CHOICE, UNIQUE ANSWER),
 * extending the class question
 *
 * @author Eric Marguin
 * @author Julio Montoya
 */
class UniqueAnswer extends Question
{
    public $typePicture = 'mcua.png';
    public $explanationLangVar = 'Multiple choice';

    public function __construct()
    {
        parent::__construct();
        $this->type = UNIQUE_ANSWER;
        $this->isContent = $this->getIsContent();
    }

    public function createAnswersForm($form)
    {
        // Getting the exercise list
        /** @var Exercise $obj_ex */
        $obj_ex = Session::read('objExercise');

        $editor_config = [
            'ToolbarSet' => 'TestProposedAnswer',
            'Width' => '100%',
            'Height' => '125',
        ];

        //this line defines how many questions by default appear when creating a choice question
        // The previous default value was 2. See task #1759.
        $nb_answers = isset($_POST['nb_answers']) ? (int) $_POST['nb_answers'] : 4;
        $nb_answers += (isset($_POST['lessAnswers']) ? -1 : (isset($_POST['moreAnswers']) ? 1 : 0));

        $feedback_title = '';
        switch ($obj_ex->getFeedbackType()) {
            case EXERCISE_FEEDBACK_TYPE_DIRECT:
                // Scenario
                $comment_title = '<th width="20%">'.get_lang('Comment').'</th>';
                break;
            case EXERCISE_FEEDBACK_TYPE_POPUP:
                $comment_title = '<th width="20%">'.get_lang('Comment').'</th>';

                break;
            default:
                $comment_title = '<th width="40%">'.get_lang('Comment').'</th>';

                break;
        }

        $html = '<table class="table table-striped table-hover">
            <thead>
                <tr style="text-align: center;">
                    <th width="5%">'.get_lang('N°').'</th>
                    <th width="5%"> '.get_lang('True').'</th>
                    <th width="40%">'.get_lang('Answer').'</th>
                        '.$comment_title.'
                    <th width="10%">'.get_lang('Score').'</th>
                </tr>
            </thead>
            <tbody>';

        $form->addHeader(get_lang('Answers'));
        $form->addHtml($html);

        $defaults = [];
        $correct = 0;
        if (!empty($this->id)) {
            $answer = new Answer($this->id);
            $answer->read();
            if ($answer->nbrAnswers > 0 && !$form->isSubmitted()) {
                $nb_answers = $answer->nbrAnswers;
            }
        }
        $form->addHidden('nb_answers', $nb_answers);

        $obj_ex->setQuestionList(true);
        $question_list = $obj_ex->getQuestionList();
        $select_question = [];
        $select_question[0] = get_lang('Select target question');
        if (is_array($question_list)) {
            foreach ($question_list as $key => $questionid) {
                //To avoid warning messages
                if (!is_numeric($questionid)) {
                    continue;
                }
                $question = Question::read($questionid);
                $questionTitle = strip_tags($question->selectTitle());
                $select_question[$questionid] = "Q$key: $questionTitle";
            }
        }
        $select_question[-1] = get_lang('Exit test');

        $list = new LearnpathList(api_get_user_id());
        $flat_list = $list->get_flat_list();
        $select_lp_id = [];
        $select_lp_id[0] = get_lang('Select target course');

        foreach ($flat_list as $id => $details) {
            $select_lp_id[$id] = cut($details['lp_name'], 20);
        }

        $temp_scenario = [];
        if ($nb_answers < 1) {
            $nb_answers = 1;
            echo Display::return_message(get_lang('You have to create at least one answer'));
        }

        for ($i = 1; $i <= $nb_answers; $i++) {
            $form->addHtml('<tr>');
            if (isset($answer) && is_object($answer)) {
                if (isset($answer->correct[$i]) && $answer->correct[$i]) {
                    $correct = $i;
                }
                $defaults['answer['.$i.']'] = isset($answer->answer[$i]) ? $answer->answer[$i] : '';
                $defaults['comment['.$i.']'] = isset($answer->comment[$i]) ? $answer->comment[$i] : '';
                $defaults['weighting['.$i.']'] = isset($answer->weighting[$i]) ? float_format($answer->weighting[$i], 1) : 0;
            } else {
                $defaults['answer[1]'] = get_lang('A then B then C');
                $defaults['weighting[1]'] = 10;
                $defaults['answer[2]'] = get_lang('A then C then B');
                $defaults['weighting[2]'] = 0;
                $temp_scenario['destination'.$i] = ['0'];
                $temp_scenario['lp'.$i] = ['0'];
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

            $answerNumber = $form->addText(
                'counter['.$i.']',
                null,
                false,
                ['value' => $i]
            );
            $answerNumber->freeze();

            $form->addElement(
                'radio',
                'correct',
                null,
                null,
                $i,
            );

            $form->addHtmlEditor('answer['.$i.']', null, null, false, $editor_config);

            $form->addRule(
                'answer['.$i.']',
                get_lang('Required field'),
                'required'
            );

            switch ($obj_ex->getFeedbackType()) {
                case EXERCISE_FEEDBACK_TYPE_DIRECT:
                    $this->setDirectOptions($i, $form, $renderer, $select_lp_id, $select_question);

                    break;
                case EXERCISE_FEEDBACK_TYPE_POPUP:
                default:
                    $form->addHtmlEditor('comment['.$i.']', null, null, false, $editor_config);

                    break;
            }
            $form->addText('weighting['.$i.']', null, null, ['value' => '0']);
            $form->addHtml('</tr>');
        }

        $form->addHtml('</tbody>');
        $form->addHtml('</table>');

        global $text;
        $buttonGroup = [];

        if (true == $obj_ex->edit_exercise_in_lp ||
            (empty($this->exerciseList) && empty($obj_ex->id))
        ) {

            //setting the save button here and not in the question class.php
            $buttonGroup[] = $form->addButtonDelete(get_lang('Remove answer option'), 'lessAnswers', true);
            $buttonGroup[] = $form->addButtonCreate(get_lang('Add answer option'), 'moreAnswers', true);
            $buttonGroup[] = $form->addButton(
                'submitQuestion',
                $text,
                'check',
                'primary',
                'default',
                null,
                ['id' => 'submit-question'],
                true
            );
            $form->addGroup($buttonGroup);
        }

        // We check the first radio button to be sure a radio button will be check
        if (0 == $correct) {
            $correct = 1;
        }

        if (isset($_POST) && isset($_POST['correct'])) {
            $correct = (int) $_POST['correct'];
        }

        $defaults['correct'] = $correct;

        if (!empty($this->id)) {

            if (!empty($this->id)) {
                $table = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
                $res = Database::select(
                    'destination',
                    $table,
                    ['where' => ['question_id = ? AND quiz_id = ?' => [$this->id, $obj_ex->id]], 'limit' => 1],
                    'first'
                );

                if (!empty($res['destination'])) {
                    $json = json_decode($res['destination'], true);
                    $defaults['scenario_success_selector'] = $json['success'] ?? '';
                    $defaults['scenario_failure_selector'] = $json['failure'] ?? '';

                    if (str_starts_with($json['success'] ?? '', '/')) {
                        $defaults['scenario_success_selector'] = 'url';
                        $defaults['scenario_success_url'] = $json['success'];
                    }
                    if (str_starts_with($json['failure'] ?? '', '/')) {
                        $defaults['scenario_failure_selector'] = 'url';
                        $defaults['scenario_failure_url'] = $json['failure'];
                    }
                }
            }
            $form->setDefaults($defaults);
        } else {
            if (1 == $this->isContent) {
                // Default sample content.
                $form->setDefaults($defaults);
            } else {
                $correct = 1;
                if (isset($_POST) && isset($_POST['correct'])) {
                    $correct = (int) $_POST['correct'];
                }

                $form->setDefaults(['correct' => $correct]);
            }
        }
        $form->setConstants(['nb_answers' => $nb_answers]);
    }

    public function setDirectOptions($i, FormValidator $form, $renderer, $select_lp_id, $select_question)
    {
        $editor_config = [
            'ToolbarSet' => 'TestProposedAnswer',
            'Width' => '100%',
            'Height' => '125',
        ];

        $form->addHtmlEditor(
            'comment['.$i.']',
            null,
            null,
            false,
            $editor_config
        );
    }

    public function processAnswersCreation($form, $exercise)
    {
        $questionWeighting = 0;
        $nbrGoodAnswers    = 0;
        $correct           = $form->getSubmitValue('correct');
        $objAnswer         = new Answer($this->id);
        $nb_answers        = (int) $form->getSubmitValue('nb_answers');

        for ($i = 1; $i <= $nb_answers; $i++) {
            $answer    = trim($form->getSubmitValue('answer['.$i.']'));
            $comment   = trim($form->getSubmitValue('comment['.$i.']'));
            $weighting = trim($form->getSubmitValue('weighting['.$i.']'));
            $goodAnswer = ($correct == $i);

            if ($goodAnswer) {
                $nbrGoodAnswers++;
                $weighting = abs($weighting);
                if ($weighting > 0) {
                    $questionWeighting += $weighting;
                }
            }

            $objAnswer->createAnswer(
                $answer,
                $goodAnswer,
                $comment,
                $weighting,
                $i
            );
        }

        $objAnswer->save();

        // Update question total weighting and persist changes.
        $this->updateWeighting($questionWeighting);
        $this->save($exercise);
    }

    public function return_header(Exercise $exercise, $counter = null, $score = [])
    {
        $header = parent::return_header($exercise, $counter, $score);
        $header .= '<table class="'.$this->questionTableClass.'"><tr>';

        $header .= '<th>'.get_lang('Your choice').'</th>';
        if ($exercise->showExpectedChoiceColumn()) {
            $header .= '<th>'.get_lang('Expected choice').'</th>';
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
