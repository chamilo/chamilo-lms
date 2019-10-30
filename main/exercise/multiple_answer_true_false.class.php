<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Class MultipleAnswerTrueFalse
 * This class allows to instantiate an object of type MULTIPLE_ANSWER
 * (MULTIPLE CHOICE, MULTIPLE ANSWER), extending the class question.
 *
 * @author Julio Montoya
 */
class MultipleAnswerTrueFalse extends Question
{
    public $typePicture = 'mcmao.png';
    public $explanationLangVar = 'MultipleAnswerTrueFalse';
    public $options;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = MULTIPLE_ANSWER_TRUE_FALSE;
        $this->isContent = $this->getIsContent();
        $this->options = [1 => 'True', 2 => 'False', 3 => 'DoubtScore'];
    }

    /**
     * {@inheritdoc}
     */
    public function createAnswersForm($form)
    {
        $nb_answers = isset($_POST['nb_answers']) ? $_POST['nb_answers'] : 4;
        // The previous default value was 2. See task #1759.
        $nb_answers += (isset($_POST['lessAnswers']) ? -1 : (isset($_POST['moreAnswers']) ? 1 : 0));

        $course_id = api_get_course_int_id();
        $obj_ex = Session::read('objExercise');
        $renderer = &$form->defaultRenderer();
        $defaults = [];

        $html = '<table class="table table-striped table-hover">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>'.get_lang('N°').'</th>';
        $html .= '<th>'.get_lang('True').'</th>';
        $html .= '<th>'.get_lang('False').'</th>';
        $html .= '<th>'.get_lang('Answer').'</th>';

        // show column comment when feedback is enable
        if ($obj_ex->getFeedbackType() != EXERCISE_FEEDBACK_TYPE_EXAM) {
            $html .= '<th>'.get_lang('Comment').'</th>';
        }

        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        $form->addHeader(get_lang('Answers'));
        $form->addHtml($html);

        $answer = null;

        if (!empty($this->id)) {
            $answer = new Answer($this->id);
            $answer->read();

            if ($answer->nbrAnswers > 0 && !$form->isSubmitted()) {
                $nb_answers = $answer->nbrAnswers;
            }
        }

        $form->addElement('hidden', 'nb_answers');
        if ($nb_answers < 1) {
            $nb_answers = 1;
            echo Display::return_message(get_lang('You have to create at least one answer'));
        }

        // Can be more options
        $optionData = Question::readQuestionOption($this->id, $course_id);

        for ($i = 1; $i <= $nb_answers; $i++) {
            $form->addHtml('<tr>');

            $renderer->setElementTemplate(
                '<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>',
                'correct['.$i.']'
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

            $answer_number = $form->addElement(
                'text',
                'counter['.$i.']',
                null,
                'value="'.$i.'"'
            );

            $answer_number->freeze();

            if (is_object($answer)) {
                $defaults['answer['.$i.']'] = $answer->answer[$i];
                $defaults['comment['.$i.']'] = $answer->comment[$i];
                $correct = $answer->correct[$i];
                $defaults['correct['.$i.']'] = $correct;

                $j = 1;
                if (!empty($optionData)) {
                    foreach ($optionData as $id => $data) {
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

                $defaults['answer['.$i.']'] = '';
                $defaults['comment['.$i.']'] = '';
                $defaults['correct['.$i.']'] = '';
            }

            $form->addHtmlEditor(
                "answer[$i]",
                get_lang('Required field'),
                true,
                false,
                ['ToolbarSet' => 'TestProposedAnswer', 'Width' => '100%', 'Height' => '100']
            );

            // show comment when feedback is enable
            if ($obj_ex->getFeedbackType() != EXERCISE_FEEDBACK_TYPE_EXAM) {
                $form->addElement(
                    'html_editor',
                    'comment['.$i.']',
                    null,
                    [],
                    [
                        'ToolbarSet' => 'TestProposedAnswer',
                        'Width' => '100%',
                        'Height' => '100',
                    ]
                );
            }

            $form->addHtml('</tr>');
        }

        $form->addHtml('</tbody></table>');

        $correctInputTemplate = '<div class="form-group">';
        $correctInputTemplate .= '<label class="col-sm-2 control-label">';
        $correctInputTemplate .= '<span class="form_required">*</span>'.get_lang('Score');
        $correctInputTemplate .= '</label>';
        $correctInputTemplate .= '<div class="col-sm-8">';
        $correctInputTemplate .= '<table>';
        $correctInputTemplate .= '<tr>';
        $correctInputTemplate .= '<td>';
        $correctInputTemplate .= get_lang('Correct').'{element}';
        $correctInputTemplate .= '<!-- BEGIN error --><span class="form_error">{error}</span><!-- END error -->';
        $correctInputTemplate .= '</td>';

        $wrongInputTemplate = '<td>';
        $wrongInputTemplate .= get_lang('Wrong').'{element}';
        $wrongInputTemplate .= '<!-- BEGIN error --><span class="form_error">{error}</span><!-- END error -->';
        $wrongInputTemplate .= '</td>';

        $doubtScoreInputTemplate = '<td>'.get_lang('Don\'t know').'<br>{element}';
        $doubtScoreInputTemplate .= '<!-- BEGIN error --><span class="form_error">{error}</span><!-- END error -->';
        $doubtScoreInputTemplate .= '</td>';
        $doubtScoreInputTemplate .= '</tr>';
        $doubtScoreInputTemplate .= '</table>';
        $doubtScoreInputTemplate .= '</div>';
        $doubtScoreInputTemplate .= '</div>';

        $renderer->setElementTemplate($correctInputTemplate, 'option[1]');
        $renderer->setElementTemplate($wrongInputTemplate, 'option[2]');
        $renderer->setElementTemplate($doubtScoreInputTemplate, 'option[3]');

        // 3 scores
        $form->addElement('text', 'option[1]', get_lang('Correct'), ['class' => 'span1', 'value' => '1']);
        $form->addElement('text', 'option[2]', get_lang('Wrong'), ['class' => 'span1', 'value' => '-0.5']);
        $form->addElement('text', 'option[3]', get_lang('Don\'t know'), ['class' => 'span1', 'value' => '0']);

        $form->addRule('option[1]', get_lang('Required field'), 'required');
        $form->addRule('option[2]', get_lang('Required field'), 'required');
        $form->addRule('option[3]', get_lang('Required field'), 'required');

        $form->addElement('hidden', 'options_count', 3);

        // Extra values True, false,  Dont known
        if (!empty($this->extra)) {
            $scores = explode(':', $this->extra);

            if (!empty($scores)) {
                for ($i = 1; $i <= 3; $i++) {
                    $defaults['option['.$i.']'] = $scores[$i - 1];
                }
            }
        }

        global $text;
        if ($obj_ex->edit_exercise_in_lp == true ||
            (empty($this->exerciseList) && empty($obj_ex->id))
        ) {
            // setting the save button here and not in the question class.php
            $buttonGroup[] = $form->addButtonDelete(get_lang('Remove answer option'), 'lessAnswers', true);
            $buttonGroup[] = $form->addButtonCreate(get_lang('Add answer option'), 'moreAnswers', true);
            $buttonGroup[] = $form->addButtonSave($text, 'submitQuestion', true);

            $form->addGroup($buttonGroup);
        }

        if (!empty($this->id)) {
            $form->setDefaults($defaults);
        } else {
            $form->setDefaults($defaults);
        }
        $form->setConstants(['nb_answers' => $nb_answers]);
    }

    /**
     * {@inheritdoc}
     */
    public function processAnswersCreation($form, $exercise)
    {
        $questionWeighting = 0;
        $objAnswer = new Answer($this->id);
        $nb_answers = $form->getSubmitValue('nb_answers');
        $course_id = api_get_course_int_id();

        $correct = [];
        $options = Question::readQuestionOption($this->id, $course_id);

        if (!empty($options)) {
            foreach ($options as $optionData) {
                $id = $optionData['id'];
                unset($optionData['id']);
                Question::updateQuestionOption($id, $optionData, $course_id);
            }
        } else {
            for ($i = 1; $i <= 3; $i++) {
                $last_id = Question::saveQuestionOption(
                    $this->id,
                    $this->options[$i],
                    $course_id,
                    $i
                );
                $correct[$i] = $last_id;
            }
        }

        /* Getting quiz_question_options (true, false, doubt) because
        it's possible that there are more options in the future */
        $new_options = Question::readQuestionOption($this->id, $course_id);
        $sortedByPosition = [];
        foreach ($new_options as $item) {
            $sortedByPosition[$item['position']] = $item;
        }

        /* Saving quiz_question.extra values that has the correct scores of
        the true, false, doubt options registered in this format
        XX:YY:ZZZ where XX is a float score value.*/
        $extra_values = [];
        for ($i = 1; $i <= 3; $i++) {
            $score = trim($form->getSubmitValue('option['.$i.']'));
            $extra_values[] = $score;
        }
        $this->setExtra(implode(':', $extra_values));

        for ($i = 1; $i <= $nb_answers; $i++) {
            $answer = trim($form->getSubmitValue('answer['.$i.']'));
            $comment = trim($form->getSubmitValue('comment['.$i.']'));
            $goodAnswer = trim($form->getSubmitValue('correct['.$i.']'));
            if (empty($options)) {
                //If this is the first time that the question is created when
                // change the default values from the form 1 and 2 by the correct "option id" registered
                $goodAnswer = isset($sortedByPosition[$goodAnswer]) ? $sortedByPosition[$goodAnswer]['id'] : '';
            }
            $questionWeighting += $extra_values[0]; //By default 0 has the correct answers
            $objAnswer->createAnswer($answer, $goodAnswer, $comment, '', $i);
        }

        // saves the answers into the database
        $objAnswer->save();
        // sets the total weighting of the question
        $this->updateWeighting($questionWeighting);
        $this->save($exercise);
    }

    /**
     * {@inheritdoc}
     */
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
                $header .= '<th>'.get_lang('ExpectedYour choice').'</th>';
            }
        }

        $header .= '<th>'.get_lang('Answer').'</th>';

        if ($exercise->showExpectedChoice()) {
            $header .= '<th>'.get_lang('Status').'</th>';
        }
        if ($exercise->getFeedbackType() != EXERCISE_FEEDBACK_TYPE_EXAM ||
            in_array(
                $exercise->results_disabled,
                [
                    RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER,
                    RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING,
                ]
            )
        ) {
            $header .= '<th>'.get_lang('Comment').'</th>';
        }
        $header .= '</tr>';

        return $header;
    }
}
