<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
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
    public $explanationLangVar = 'Multiple answer true/false/don\'t know';
    public $options;

    public function __construct()
    {
        parent::__construct();
        $this->type = MULTIPLE_ANSWER_TRUE_FALSE;
        $this->isContent = $this->getIsContent();
        $this->options = [1 => 'True', 2 => 'False', 3 => "Don't know"];
    }

    public function createAnswersForm($form)
    {
        $nb_answers = (int) ($_POST['nb_answers'] ?? 4);
        // The previous default value was 2. See task #1759.
        $nb_answers += (isset($_POST['lessAnswers']) ? -1 : (isset($_POST['moreAnswers']) ? 1 : 0));

        $course_id = api_get_course_int_id();
        $obj_ex = Session::read('objExercise');
        $renderer = &$form->defaultRenderer();
        $defaults = [];

        $html = '';
        $html .= '<div class="overflow-x-auto">';
        $html .= '<table class="min-w-full text-sm border border-gray-200 rounded-lg overflow-hidden table table-striped table-hover">';
        $html .= '<thead class="bg-gray-20">';
        $html .= '<tr>';
        $html .= '<th class="px-3 py-2 text-left font-semibold text-gray-700">'.get_lang('N°').'</th>';
        $html .= '<th class="px-3 py-2 text-center font-semibold text-gray-700">'.get_lang('True').'</th>';
        $html .= '<th class="px-3 py-2 text-center font-semibold text-gray-700">'.get_lang('False').'</th>';
        $html .= '<th class="px-3 py-2 text-left font-semibold text-gray-700">'.get_lang('Answer').'</th>';

        // Show column comment when feedback is enabled
        if (EXERCISE_FEEDBACK_TYPE_EXAM != $obj_ex->getFeedbackType()) {
            $html .= '<th class="px-3 py-2 text-left font-semibold text-gray-700">'.get_lang('Comment').'</th>';
        }

        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody class="divide-y divide-gray-200 bg-white">';

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

        // Can be more options in the future
        $optionData = Question::readQuestionOption($this->id, $course_id);

        for ($i = 1; $i <= $nb_answers; $i++) {
            $form->addHtml('<tr>');

            $renderer->setElementTemplate(
                '<td class="px-3 py-2 align-top text-gray-700">'.
                '<!-- BEGIN error --><div class="text-xs text-red-600 mb-1">{error}</div><!-- END error -->'.
                '{element}</td>',
                'counter['.$i.']'
            );

            // This will generate 2 <td> cells for the 2 radios, matching the True/False columns.
            $renderer->setElementTemplate(
                '<td class="px-3 py-2 align-top text-center">'.
                '<!-- BEGIN error --><div class="text-xs text-red-600 mb-1">{error}</div><!-- END error -->'.
                '{element}</td>',
                'correct['.$i.']'
            );

            $renderer->setElementTemplate(
                '<td class="px-3 py-2 align-top">'.
                '<!-- BEGIN error --><div class="text-xs text-red-600 mb-1">{error}</div><!-- END error -->'.
                '{element}</td>',
                'answer['.$i.']'
            );
            $renderer->setElementTemplate(
                '<td class="px-3 py-2 align-top">'.
                '<!-- BEGIN error --><div class="text-xs text-red-600 mb-1">{error}</div><!-- END error -->'.
                '{element}</td>',
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
                $defaults['answer['.$i.']'] = $answer->answer[$i] ?? null;
                $defaults['comment['.$i.']'] = $answer->comment[$i] ?? null;
                $correct = $answer->correct[$i] ?? '';
                $defaults['correct['.$i.']'] = $correct;

                // Render True/False radios based on existing option IDs (iid)
                // Only take the first 2 options (True/False). The 3rd option is "Don't know" score, not a correct flag.
                if (!empty($optionData)) {
                    $count = 0;
                    foreach ($optionData as $data) {
                        $value = (int) ($data['iid'] ?? 0);
                        if ($value > 0) {
                            $form->addElement('radio', 'correct['.$i.']', null, null, $value);
                            $count++;
                        }
                        if ($count >= 2) {
                            break;
                        }
                    }
                } else {
                    // Fallback
                    $form->addElement('radio', 'correct['.$i.']', null, null, 1);
                    $form->addElement('radio', 'correct['.$i.']', null, null, 2);
                }
            } else {
                // New question: positions 1/2 are mapped to the real option iids on save
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

            if (isset($_POST['answer'][$i])) {
                $form->getElement("answer[$i]")->setValue(Security::remove_XSS($_POST['answer'][$i]));
            }

            // Show comment when feedback is enabled
            if (EXERCISE_FEEDBACK_TYPE_EXAM != $obj_ex->getFeedbackType()) {
                $form->addHtmlEditor(
                    'comment['.$i.']',
                    null,
                    true,
                    false,
                    [
                        'ToolbarSet' => 'TestProposedAnswer',
                        'Width' => '100%',
                        'Height' => '100',
                    ]
                );
                $form->applyFilter("comment[$i]", 'attr_on_filter');
                if (isset($_POST['comment'][$i])) {
                    $form->getElement("comment[$i]")->setValue(Security::remove_XSS($_POST['comment'][$i]));
                }
            }

            $form->addHtml('</tr>');
        }

        $form->addHtml('</tbody></table></div>');

        // Score block
        $scoreWrapperStart = '<div class="mt-6 border border-gray-200 rounded-lg bg-white p-4 mb-4">';
        $scoreWrapperStart .= '<div class="text-sm font-semibold text-gray-700 mb-3">';
        $scoreWrapperStart .= '<span class="text-red-600">*</span> '.get_lang('Score');
        $scoreWrapperStart .= '</div>';
        $scoreWrapperStart .= '<div class="flex flex-wrap gap-6 items-end">';

        $scoreWrapperEnd = '</div></div>';

        $scoreInputTemplate = function (string $label) {
            $tpl = '<div class="min-w-[160px]">';
            $tpl .= '<div class="text-xs text-gray-600 mb-1">'.$label.'</div>';
            $tpl .= '{element}';
            $tpl .= '<!-- BEGIN error --><div class="text-xs text-red-600 mt-1">{error}</div><!-- END error -->';
            $tpl .= '</div>';

            return $tpl;
        };

        $renderer->setElementTemplate($scoreWrapperStart.$scoreInputTemplate(get_lang('Correct')), 'option[1]');
        $renderer->setElementTemplate($scoreInputTemplate(get_lang('Wrong')), 'option[2]');
        $renderer->setElementTemplate($scoreInputTemplate(get_lang("Don't know")).$scoreWrapperEnd, 'option[3]');

        $scoreInputAttrs = [
            'class' => 'w-24 rounded-md border border-gray-300 px-2 py-1 text-sm',
        ];

        // 3 scores
        $txtOption1 = $form->addElement('text', 'option[1]', get_lang('Correct'), array_merge($scoreInputAttrs, ['value' => '1']));
        $txtOption2 = $form->addElement('text', 'option[2]', get_lang('Wrong'), array_merge($scoreInputAttrs, ['value' => '-0.5']));
        $txtOption3 = $form->addElement('text', 'option[3]', get_lang('Don\'t know'), array_merge($scoreInputAttrs, ['value' => '0']));

        $form->addRule('option[1]', get_lang('Required field'), 'required');
        $form->addRule('option[2]', get_lang('Required field'), 'required');
        $form->addRule('option[3]', get_lang('Required field'), 'required');

        $form->addElement('hidden', 'options_count', 3);

        // Extra values True, false, Dont know
        if (!empty($this->extra)) {
            $scores = explode(':', $this->extra);
            if (!empty($scores)) {
                $txtOption1->setValue($scores[0] ?? '1');
                $txtOption2->setValue($scores[1] ?? '-0.5');
                $txtOption3->setValue($scores[2] ?? '0');
            }
        }

        global $text;
        if (true == $obj_ex->edit_exercise_in_lp ||
            (empty($this->exerciseList) && empty($obj_ex->id))
        ) {
            // Setting the save button here and not in the question class.php
            $buttonGroup = [];
            $buttonGroup[] = $form->addButtonDelete(get_lang('Remove answer option'), 'lessAnswers', true);
            $buttonGroup[] = $form->addButtonCreate(get_lang('Add answer option'), 'moreAnswers', true);
            $buttonGroup[] = $form->addButtonSave($text, 'submitQuestion', true);

            $form->addGroup($buttonGroup);
        }

        if (!empty($this->id) && !$form->isSubmitted()) {
            $form->setDefaults($defaults);
        }
        $form->setConstants(['nb_answers' => $nb_answers]);
    }

    public function processAnswersCreation($form, $exercise)
    {
        $questionWeighting = 0;
        $objAnswer = new Answer($this->id);
        $nb_answers = (int) $form->getSubmitValue('nb_answers');
        $course_id = api_get_course_int_id();
        $repo = Container::getQuestionRepository();

        /** @var CQuizQuestion $question */
        $question = $repo->find($this->id);
        $optionsCollection = $question->getOptions();
        $isFirstCreation = $optionsCollection->isEmpty();

        // Ensure default options exist (True, False, DoubtScore)
        if ($isFirstCreation) {
            for ($i = 1; $i <= 3; $i++) {
                Question::saveQuestionOption($question, $this->options[$i], $i);
            }
        }

        /*
         * Getting quiz_question_options (true, false, doubt) because
         * it's possible that there are more options in the future.
         */
        $new_options = Question::readQuestionOption($this->id, $course_id);
        $sortedByPosition = [];
        foreach ($new_options as $item) {
            $sortedByPosition[(int) $item['position']] = $item;
        }

        /*
         * Saving quiz_question.extra values that has the correct scores of
         * the true, false, doubt options registered in this format:
         * XX:YY:ZZZ
         */
        $extra_values = [];
        for ($i = 1; $i <= 3; $i++) {
            $score = trim((string) $form->getSubmitValue('option['.$i.']'));
            $extra_values[] = $score;
        }
        $this->setExtra(implode(':', $extra_values));

        for ($i = 1; $i <= $nb_answers; $i++) {
            $answer = trim((string) $form->getSubmitValue('answer['.$i.']'));
            $comment = trim((string) $form->getSubmitValue('comment['.$i.']'));
            $goodAnswer = trim((string) $form->getSubmitValue('correct['.$i.']'));

            if ($isFirstCreation) {
                // First creation: map submitted position (1/2) to the real option iid
                $pos = (int) $goodAnswer;
                $goodAnswer = isset($sortedByPosition[$pos]) ? (string) $sortedByPosition[$pos]['iid'] : '';
            }

            // Total question weighting = nb_answers * "Correct" score (option[1])
            $questionWeighting += (float) ($extra_values[0] ?? 0);

            $objAnswer->createAnswer($answer, $goodAnswer, $comment, '', $i);
        }

        // Saves the answers into the database
        $objAnswer->save();

        // Sets the total weighting of the question
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
        if (EXERCISE_FEEDBACK_TYPE_EXAM != $exercise->getFeedbackType() ||
            in_array(
                $exercise->results_disabled,
                [
                    RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER,
                    RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING,
                ]
            )
        ) {
            if (false === $exercise->hideComment) {
                $header .= '<th>'.get_lang('Comment').'</th>';
            }
        }
        $header .= '</tr>';

        return $header;
    }
}
