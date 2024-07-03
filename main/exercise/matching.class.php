<?php

/* For licensing terms, see /license.txt */

/**
 * Class Matching
 * Matching questions type class.
 *
 * This class allows to instantiate an object of
 * type MULTIPLE_ANSWER (MULTIPLE CHOICE, MULTIPLE ANSWER)
 * extending the class question
 *
 * @author Eric Marguin
 */
class Matching extends Question
{
    public $typePicture = 'matching.png';
    public $explanationLangVar = 'Matching';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = MATCHING;
        $this->isContent = $this->getIsContent();
    }

    /**
     * {@inheritdoc}
     */
    public function createAnswersForm($form)
    {
        $defaults = [];
        $nb_matches = $nb_options = 2;
        $matches = [];
        $answer = null;
        $counter = 1;

        if (!empty($this->iid)) {
            $answer = new Answer($this->iid);
            $answer->read();
            if ($answer->nbrAnswers > 0) {
                for ($i = 1; $i <= $answer->nbrAnswers; $i++) {
                    $correct = $answer->isCorrect($i);
                    if (empty($correct)) {
                        $matches[$answer->selectAutoId($i)] = chr(64 + $counter);
                        $counter++;
                    }
                }
            }
        }

        if ($form->isSubmitted()) {
            $nb_matches = $form->getSubmitValue('nb_matches');
            $nb_options = $form->getSubmitValue('nb_options');
            if (isset($_POST['lessOptions'])) {
                $nb_matches--;
                $nb_options--;
            }
            if (isset($_POST['moreOptions'])) {
                $nb_matches++;
                $nb_options++;
            }
        } elseif (!empty($this->iid)) {
            if ($answer->nbrAnswers > 0) {
                $nb_matches = $nb_options = 0;
                for ($i = 1; $i <= $answer->nbrAnswers; $i++) {
                    if ($answer->isCorrect($i)) {
                        $nb_matches++;
                        $defaults['answer['.$nb_matches.']'] = $answer->selectAnswer($i);
                        $defaults['weighting['.$nb_matches.']'] = float_format($answer->selectWeighting($i));
                        $defaults['matches['.$nb_matches.']'] = $answer->correct[$i];
                    } else {
                        $nb_options++;
                        $defaults['option['.$nb_options.']'] = $answer->selectAnswer($i);
                    }
                }
            }
        } else {
            $defaults['answer[1]'] = get_lang('DefaultMakeCorrespond1');
            $defaults['answer[2]'] = get_lang('DefaultMakeCorrespond2');
            $defaults['matches[2]'] = '2';
            $defaults['option[1]'] = get_lang('DefaultMatchingOptA');
            $defaults['option[2]'] = get_lang('DefaultMatchingOptB');
        }

        if (empty($matches)) {
            for ($i = 1; $i <= $nb_options; $i++) {
                // fill the array with A, B, C.....
                $matches[$i] = chr(64 + $i);
            }
        } else {
            for ($i = $counter; $i <= $nb_options; $i++) {
                // fill the array with A, B, C.....
                $matches[$i] = chr(64 + $i);
            }
        }

        $form->addElement('hidden', 'nb_matches', $nb_matches);
        $form->addElement('hidden', 'nb_options', $nb_options);

        $thWeighting = '';
        if (MATCHING === $this->type) {
            $thWeighting = '<th width="10%">'.get_lang('Weighting').'</th>';
        }
        // DISPLAY MATCHES
        $html = '<table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th width="5%">'.get_lang('Number').'</th>
                    <th width="70%">'.get_lang('Answer').'</th>
                    <th width="15%">'.get_lang('MatchesTo').'</th>
                    '.$thWeighting.'
                </tr>
            </thead>
            <tbody>';

        $form->addHeader(get_lang('MakeCorrespond'));
        $form->addHtml($html);

        if ($nb_matches < 1) {
            $nb_matches = 1;
            Display::addFlash(
                Display::return_message(get_lang('YouHaveToCreateAtLeastOneAnswer'))
            );
        }

        $editorConfig = [
            'ToolbarSet' => 'TestMatching',
            'Width' => '100%',
            'Height' => '125',
        ];

        for ($i = 1; $i <= $nb_matches; $i++) {
            $renderer = &$form->defaultRenderer();
            $renderer->setElementTemplate(
                '<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error -->{element}</td>',
                "answer[$i]"
            );

            $renderer->setElementTemplate(
                '<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error -->{element}</td>',
                "matches[$i]"
            );

            $renderer->setElementTemplate(
                '<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error -->{element}</td>',
                "weighting[$i]"
            );

            $form->addHtml('<tr>');
            $form->addHtml("<td>$i</td>");
            $form->addHtmlEditor(
                "answer[$i]",
                null,
                null,
                false,
                $editorConfig
            );
            $form->addSelect(
                "matches[$i]",
                null,
                $matches,
                ['id' => 'matches_'.$i]
            );
            if (MATCHING === $this->type) {
                $form->addText(
                    "weighting[$i]",
                    null,
                    true,
                    ['id' => 'weighting_'.$i, 'value' => 10]
                );
            } else {
                $form->addHidden("weighting[$i]", "0");
            }
            $form->addHtml('</tr>');
        }

        $form->addHtml('</tbody></table>');

        // DISPLAY OPTIONS
        $html = '<table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th width="15%">'.get_lang('Number').'</th>
                    <th width="85%">'.get_lang('Answer').'</th>
                </tr>
            </thead>
            <tbody>';

        $form->addHtml($html);

        if ($nb_options < 1) {
            $nb_options = 1;
            Display::addFlash(
                Display::return_message(get_lang('YouHaveToCreateAtLeastOneAnswer'))
            );
        }

        for ($i = 1; $i <= $nb_options; $i++) {
            $renderer = &$form->defaultRenderer();
            $renderer->setElementTemplate(
                '<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error -->{element}</td>',
                "option[$i]"
            );

            $form->addHtml('<tr>');
            $form->addHtml('<td>'.chr(64 + $i).'</td>');
            $form->addHtmlEditor(
                "option[$i]",
                null,
                null,
                false,
                $editorConfig
            );

            $form->addHtml('</tr>');
        }

        $form->addHtml('</table>');

        if (MATCHING_COMBINATION === $this->type) {
            //only 1 answer the all deal ...
            $form->addText('questionWeighting', get_lang('Score'), true, ['value' => 10]);
            if (!empty($this->iid)) {
                $defaults['questionWeighting'] = $this->weighting;
            }
        }

        global $text;
        $group = [];
        // setting the save button here and not in the question class.php
        $group[] = $form->addButtonDelete(get_lang('DelElem'), 'lessOptions', true);
        $group[] = $form->addButtonCreate(get_lang('AddElem'), 'moreOptions', true);
        $group[] = $form->addButtonSave($text, 'submitQuestion', true);
        $form->addGroup($group);

        if (!empty($this->iid)) {
            $form->setDefaults($defaults);
        } else {
            if ($this->isContent == 1) {
                $form->setDefaults($defaults);
            }
        }

        $form->setConstants(
            [
                'nb_matches' => $nb_matches,
                'nb_options' => $nb_options,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function processAnswersCreation($form, $exercise)
    {
        $nb_matches = $form->getSubmitValue('nb_matches');
        $nb_options = $form->getSubmitValue('nb_options');
        $this->weighting = 0;
        $position = 0;
        $objAnswer = new Answer($this->iid);

        // Insert the options
        for ($i = 1; $i <= $nb_options; $i++) {
            $position++;
            $option = $form->getSubmitValue('option['.$i.']');
            $objAnswer->createAnswer($option, 0, '', 0, $position);
        }

        // Insert the answers
        for ($i = 1; $i <= $nb_matches; $i++) {
            $position++;
            $answer = $form->getSubmitValue('answer['.$i.']');
            $matches = $form->getSubmitValue('matches['.$i.']');
            $weighting = $form->getSubmitValue('weighting['.$i.']');
            $this->weighting += $weighting;

            $objAnswer->createAnswer(
                $answer,
                $matches,
                '',
                $weighting,
                $position
            );
        }

        if (MATCHING_COMBINATION == $this->type) {
            $this->weighting = $form->getSubmitValue('questionWeighting');
        }

        $objAnswer->save();
        $this->save($exercise);
    }

    /**
     * {@inheritdoc}
     */
    public function return_header(Exercise $exercise, $counter = null, $score = [])
    {
        $header = parent::return_header($exercise, $counter, $score);
        $header .= '<table class="'.$this->question_table_class.'">';
        $header .= '<tr>';

        $header .= '<th>'.get_lang('ElementList').'</th>';
        if (!in_array($exercise->results_disabled, [
            RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER,
        ])
        ) {
            $header .= '<th>'.get_lang('Choice').'</th>';
        }

        if ($exercise->showExpectedChoice()) {
            if ($exercise->showExpectedChoiceColumn()) {
                $header .= '<th>'.get_lang('ExpectedChoice').'</th>';
            }
            $header .= '<th>'.get_lang('Status').'</th>';
        } else {
            if ($exercise->showExpectedChoiceColumn()) {
                $header .= '<th>'.get_lang('CorrespondsTo').'</th>';
            }
        }
        $header .= '</tr>';

        return $header;
    }

    /**
     * Check if a answer is correct.
     *
     * @param int $position
     * @param int $answer
     * @param int $questionId
     *
     * @return bool
     */
    public static function isCorrect($position, $answer, $questionId)
    {
        $em = Database::getManager();

        $count = $em
            ->createQuery('
                SELECT COUNT(a) From ChamiloCourseBundle:CQuizAnswer a
                WHERE a.iid = :position AND a.correct = :answer AND a.questionId = :question
            ')
            ->setParameters([
                'position' => $position,
                'answer' => $answer,
                'question' => $questionId,
            ])
            ->getSingleScalarResult();

        return $count ? true : false;
    }
}
