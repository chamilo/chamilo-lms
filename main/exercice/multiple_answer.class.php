<?php
/* For licensing terms, see /license.txt */
/**
 *    File containing the MultipleAnswer class.
 * @package chamilo.exercise
 * @author Eric Marguin
 */
/**
 * Code
 */
/**
CLASS MultipleAnswer
 *
 *    This class allows to instantiate an object of type MULTIPLE_ANSWER (MULTIPLE CHOICE, MULTIPLE ANSWER),
 *    extending the class question
 *
 * @author Eric Marguin
 * @package chamilo.exercise
 **/

class MultipleAnswer extends Question
{
    static $typePicture = 'mcma.gif';
    static $explanationLangVar = 'MultipleSelect';

    /**
     * Constructor
     */
    public function MultipleAnswer()
    {
        parent::question();
        $this->type      = MULTIPLE_ANSWER;
        $this->isContent = $this->getIsContent();
    }

    /**
     * function which redefines Question::createAnswersForm
     * @param FormValidator instance
     */
    public function createAnswersForm($form)
    {
        $nb_answers = isset($_POST['nb_answers']) ? $_POST['nb_answers'] : 4; // The previous default value was 2. See task #1759.
        $nb_answers += (isset($_POST['lessAnswers']) ? -1 : (isset($_POST['moreAnswers']) ? 1 : 0));

        $obj_ex = $this->exercise;

        $html = '<table class="data_table">
					<tr>
						<th width="10px">
							'.get_lang('Number').'
						</th>
						<th width="10px">
							'.get_lang('True').'
						</th>
						<th width="50%">
							'.get_lang('Answer').'
						</th>';
        // show column comment when feedback is enable
        if (!empty($obj_ex) && $obj_ex->selectFeedbackType() != EXERCISE_FEEDBACK_TYPE_EXAM) {
            $html .= '<th>
                                '.get_lang('Comment').'
                            </th>';
        }
        $html .= '<th width="50px">
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
        $boxes_names = array();

        if ($nb_answers < 1) {
            $nb_answers = 1;
            Display::display_normal_message(get_lang('YouHaveToCreateAtLeastOneAnswer'));
        }

        for ($i = 1; $i <= $nb_answers; ++$i) {
            if (isset($answer) && is_object($answer)) {
                $answer_id                     = $answer->getRealAnswerIdFromList($i);
                $defaults['answer['.$i.']']    = isset($answer->answer[$answer_id]) ? $answer->answer[$answer_id] : null;
                $defaults['comment['.$i.']']   = isset($answer->comment[$answer_id]) ? $answer->comment[$answer_id] : null;
                $defaults['weighting['.$i.']'] = isset($answer->weighting[$answer_id]) ? Text::float_format(
                    $answer->weighting[$answer_id],
                    1
                ) : null;
                $defaults['correct['.$i.']']   = isset($answer->correct[$answer_id]) ? $answer->correct[$answer_id] : null;
            } else {
                $defaults['answer[1]']    = get_lang('DefaultMultipleAnswer2');
                $defaults['comment[1]']   = get_lang('DefaultMultipleComment2');
                $defaults['correct[1]']   = true;
                $defaults['weighting[1]'] = 10;

                $defaults['answer[2]']    = get_lang('DefaultMultipleAnswer1');
                $defaults['comment[2]']   = get_lang('DefaultMultipleComment1');
                $defaults['correct[2]']   = false;
                $defaults['weighting[2]'] = -5;
            }
            $renderer = & $form->defaultRenderer();

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
            $renderer->setElementTemplate(
                '<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>',
                'weighting['.$i.']'
            );

            $answer_number = $form->addElement('text', 'counter['.$i.']', null, 'value="'.$i.'"');
            $answer_number->freeze();

            $form->addElement('checkbox', 'correct['.$i.']', null, null, 'class="checkbox" style="margin-left: 0em;"');
            $boxes_names[] = 'correct['.$i.']';

            if ($obj_ex->fastEdition) {
                $form->addElement(
                    'textarea',
                    'answer['.$i.']',
                    null,
                    $this->textareaSettings
                );
            } else {
                $form->addElement(
                    'html_editor',
                    'answer['.$i.']',
                    null,
                    'style="vertical-align:middle"',
                    array('ToolbarSet' => 'TestProposedAnswer', 'Width' => '100%', 'Height' => '100')
                );
            }
            $form->addRule('answer['.$i.']', get_lang('ThisFieldIsRequired'), 'required');

            // show comment when feedback is enable
            if (!empty($obj_ex) && $obj_ex->selectFeedbackType() != EXERCISE_FEEDBACK_TYPE_EXAM) {
                if ($obj_ex->fastEdition) {
                    $form->addElement(
                        'textarea',
                        'comment['.$i.']',
                        null,
                        $this->textareaSettings
                    );
                } else {
                    $form->addElement(
                        'html_editor',
                        'comment['.$i.']',
                        null,
                        'style="vertical-align:middle"',
                        array('ToolbarSet' => 'TestProposedAnswer', 'Width' => '100%', 'Height' => '100')
                    );
                }
            }

            $form->addElement('text', 'weighting['.$i.']', null, array('class' => "span1", 'value' => '0'));
            $form->addElement('html', '</tr>');
        }
        $form->addElement('html', '</table>');
        $form->addElement('html', '<br />');

        $form->add_multiple_required_rule($boxes_names, get_lang('ChooseAtLeastOneCheckbox'), 'multiple_required');
        $navigator_info = api_get_navigator();

        if ($form->isFrozen() == false) {
            if ($obj_ex->edit_exercise_in_lp == true) {
                //ie6 fix
                if ($navigator_info['name'] == 'Internet Explorer' && $navigator_info['version'] == '6') {
                    $form->addElement('submit', 'lessAnswers', get_lang('LessAnswer'), 'class="btn minus"');
                    $form->addElement('submit', 'moreAnswers', get_lang('PlusAnswer'), 'class="btn plus"');
                    $form->addElement('submit', 'submitQuestion', $this->submitText, 'class="'.$this->submitClass.'"');
                } else {
                    // setting the save button here and not in the question class.php
                    $form->addElement('style_submit_button', 'lessAnswers', get_lang('LessAnswer'), 'class="btn minus"');
                    $form->addElement('style_submit_button', 'moreAnswers', get_lang('PlusAnswer'), 'class="btn plus"');
                    $form->addElement(
                        'style_submit_button',
                        'submitQuestion',
                        $this->submitText,
                        'class="'.$this->submitClass.'"'
                    );
                }
            }
            $renderer->setElementTemplate('{element}&nbsp;', 'lessAnswers');
            $renderer->setElementTemplate('{element}&nbsp;', 'submitQuestion');
            $renderer->setElementTemplate('{element}&nbsp;', 'moreAnswers');
        }
        $form->addElement('html', '</div></div>');

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
     * @param FormValidator instance
     */
    public function processAnswersCreation($form)
    {
        $questionWeighting = $nbrGoodAnswers = 0;
        $objAnswer         = new Answer($this->id);
        $nb_answers        = $form->getSubmitValue('nb_answers');

        for ($i = 1; $i <= $nb_answers; $i++) {
            $answer     = trim($form->getSubmitValue('answer['.$i.']'));
            $comment    = trim($form->getSubmitValue('comment['.$i.']'));
            $weighting  = trim($form->getSubmitValue('weighting['.$i.']'));
            $goodAnswer = trim($form->getSubmitValue('correct['.$i.']'));

            if ($goodAnswer) {
                $weighting = abs($weighting);
            } else {
                $weighting = abs($weighting);
                $weighting = -$weighting;
            }
            if ($weighting > 0) {
                $questionWeighting += $weighting;
            }
            $objAnswer->createAnswer($answer, $goodAnswer, $comment, $weighting, $i);
        }

        // Saves the answers into the data base.
        $objAnswer->save();

        // Sets the total weighting of the question.
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
}
