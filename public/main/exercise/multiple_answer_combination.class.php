<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Class MultipleAnswerCombination.
 *
 *	This class allows to instantiate an object of type
 *  MULTIPLE_ANSWER (MULTIPLE CHOICE, MULTIPLE ANSWER),
 *	extending the class question
 *
 *	@author Eric Marguin
 */
class MultipleAnswerCombination extends Question
{
    public $typePicture = 'mcmac.png';
    public $explanationLangVar = 'Exact Selection';

    public function __construct()
    {
        parent::__construct();
        $this->type = MULTIPLE_ANSWER_COMBINATION;
        $this->isContent = $this->getIsContent();
    }

    public function createAnswersForm($form)
    {
        $nb_answers = $_POST['nb_answers'] ?? 2;
        $nb_answers += (isset($_POST['lessAnswers']) ? -1 : (isset($_POST['moreAnswers']) ? 1 : 0));
        $obj_ex = Session::read('objExercise');

        $html = '<table class="table table-striped table-hover">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th width="10">'.get_lang('N°').'</th>';
        $html .= '<th width="10">'.get_lang('True').'</th>';
        $html .= '<th width="50%">'.get_lang('Answer').'</th>';
        $html .= '<th width="50%">'.get_lang('Comment').'</th>';
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

        $form->addElement('hidden', 'nb_answers');
        $boxes_names = [];

        if ($nb_answers < 1) {
            $nb_answers = 1;
            echo Display::return_message(get_lang('You have to create at least one answer'));
        }

        for ($i = 1; $i <= $nb_answers; $i++) {
            $form->addHtml('<tr>');

            if (is_object($answer)) {
                $defaults['answer['.$i.']'] = $answer->answer[$i];
                $defaults['comment['.$i.']'] = $answer->comment[$i];
                $defaults['weighting['.$i.']'] = float_format($answer->weighting[$i], 1);
                $defaults['correct['.$i.']'] = $answer->correct[$i];
            } else {
                $defaults['answer[1]'] = get_lang('Lack of Vitamin A');
                $defaults['comment[1]'] = get_lang('The Vitamin A is responsible for...');
                $defaults['correct[1]'] = true;
                $defaults['weighting[1]'] = 10;

                $defaults['answer[2]'] = get_lang('Lack of Calcium');
                $defaults['comment[2]'] = get_lang('The calcium acts as a ...');
                $defaults['correct[2]'] = false;
            }

            $renderer = &$form->defaultRenderer();

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

            $answer_number = $form->addElement('text', 'counter['.$i.']', null, 'value="'.$i.'"');
            $answer_number->freeze();

            $form->addCheckBox('correct['.$i.']', null);
            $boxes_names[] = 'correct['.$i.']';

            $form->addHtmlEditor(
                'answer['.$i.']',
                null,
                true,
                false,
                ['ToolbarSet' => 'TestProposedAnswer', 'Width' => '100%', 'Height' => '100']
            );
            $form->addRule('answer['.$i.']', get_lang('Required field'), 'required');

            $form->addHtmlEditor(
                'comment['.$i.']',
                null,
                true,
                false,
                ['ToolbarSet' => 'TestProposedAnswer', 'Width' => '100%', 'Height' => '100']
            );

            $form->addHtml('</tr>');
        }

        $form->addHtml('</tbody></table>');
        $form->add_multiple_required_rule(
            $boxes_names,
            get_lang('Choose at least one good answer'),
            'multiple_required'
        );

        // only 1 answer the all deal ...
        $form->addText('weighting[1]', get_lang('Score'), false, ['value' => 10]);

        global $text;
        if (true == $obj_ex->edit_exercise_in_lp ||
            (empty($this->exerciseList) && empty($obj_ex->id))
        ) {
            // setting the save button here and not in the question class.php
            $buttonGroup = [
                $form->addButtonDelete(get_lang('Remove answer option'), 'lessAnswers', true),
                $form->addButtonCreate(get_lang('Add answer option'), 'moreAnswers', true),
                $form->addButtonSave($text, 'submitQuestion', true),
            ];
            $form->addGroup($buttonGroup);
        }

        $defaults['correct'] = $correct;

        if (!empty($this->id)) {
            $form->setDefaults($defaults);
        } else {
            if (1 == $this->isContent) {
                $form->setDefaults($defaults);
            }
        }

        $form->setConstants(['nb_answers' => $nb_answers]);
    }

    public function processAnswersCreation($form, $exercise)
    {
        $questionWeighting = 0;
        $objAnswer = new Answer($this->id);
        $nb_answers = $form->getSubmitValue('nb_answers');

        for ($i = 1; $i <= $nb_answers; $i++) {
            $answer = trim($form->getSubmitValue('answer['.$i.']'));
            $comment = trim($form->getSubmitValue('comment['.$i.']'));
            if (1 == $i) {
                $weighting = trim($form->getSubmitValue('weighting['.$i.']'));
            } else {
                $weighting = 0;
            }
            $goodAnswer = trim($form->getSubmitValue('correct['.$i.']'));

            if ($goodAnswer) {
                $weighting = abs($weighting);
            } else {
                // $weighting = -$weighting;
                $weighting = abs($weighting);
            }
            if ($weighting > 0) {
                $questionWeighting += $weighting;
            }
            $objAnswer->createAnswer(
                $answer,
                $goodAnswer,
                $comment,
                $weighting,
                $i
            );
        }

        // saves the answers into the data base
        $objAnswer->save();

        // sets the total weighting of the question
        $this->updateWeighting($questionWeighting);
        $this->save($exercise);
    }

    public function return_header(Exercise $exercise, $counter = null, $score = [])
    {
        $header = parent::return_header($exercise, $counter, $score);
        $header .= '<table class="'.$this->questionTableClass.'"><tr>';

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
