<?php
/* For licensing terms, see /license.txt */

/**
 * MatchingDraggable.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
class MatchingDraggable extends Question
{
    public $typePicture = 'matchingdrag.png';
    public $explanationLangVar = 'MatchingDraggable';

    /**
     * Class constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = MATCHING_DRAGGABLE;
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
        if (isset($this->id)) {
            $answer = new Answer($this->id);
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
        } elseif (!empty($this->id)) {
            if ($answer->nbrAnswers > 0) {
                $nb_matches = $nb_options = 0;
                for ($i = 1; $i <= $answer->nbrAnswers; $i++) {
                    if ($answer->isCorrect($i)) {
                        $nb_matches++;
                        $defaults['answer['.$nb_matches.']'] = $answer->selectAnswer($i);
                        $defaults['weighting['.$nb_matches.']'] = float_format($answer->selectWeighting($i), 1);
                        $defaults['matches['.$nb_matches.']'] = $answer->correct[$i];
                    } else {
                        $nb_options++;
                        $defaults['option['.$nb_options.']'] = $answer->selectAnswer($i);
                    }
                }
            }
        } else {
            $defaults['answer[1]'] = get_lang('First step');
            $defaults['answer[2]'] = get_lang('Second step');
            $defaults['matches[2]'] = '2';
            $defaults['option[1]'] = get_lang('Note down the address');
            $defaults['option[2]'] = get_lang('Contact the emergency services');
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

        // DISPLAY MATCHES
        $html = '<table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th width="10">'.get_lang('N°').'</th>
                    <th width="85%">'.get_lang('Answer').'</th>
                    <th width="15%">'.get_lang('Matches To').'</th>
                    <th width="10">'.get_lang('Score').'</th>
                </tr>
            </thead>
            <tbody>';

        $form->addHeader(get_lang('Match them'));
        $form->addHtml($html);

        if ($nb_matches < 1) {
            $nb_matches = 1;
            echo Display::return_message(get_lang('You have to create at least one answer'), 'normal');
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

            //$form->addText("answer[$i]", null);
            $form->addHtmlEditor(
                "answer[$i]",
                null,
                null,
                false,
                $editorConfig
            );

            $form->addSelect("matches[$i]", null, $matches);
            $form->addText("weighting[$i]", null, true, ['style' => 'width: 60px;', 'value' => 10]);
            $form->addHtml('</tr>');
        }

        $form->addHtml('</tbody></table>');

        // DISPLAY OPTIONS
        $html = '<table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th width="15%">'.get_lang('N°').'</th>
                    <th width="85%">'.get_lang('Answer').'</th>
                </tr>
            </thead>
            <tbody>';

        $form->addHtml($html);

        if ($nb_options < 1) {
            $nb_options = 1;
            echo Display::return_message(get_lang('You have to create at least one answer'), 'normal');
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
        global $text;
        $group = [];
        // setting the save button here and not in the question class.php
        $group[] = $form->addButtonDelete(get_lang('Remove element'), 'lessOptions', true);
        $group[] = $form->addButtonCreate(get_lang('Add element'), 'moreOptions', true);
        $group[] = $form->addButtonSave($text, 'submitQuestion', true);
        $form->addGroup($group);

        if (!empty($this->id)) {
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
        $objAnswer = new Answer($this->id);

        // Insert the options
        for ($i = 1; $i <= $nb_options; $i++) {
            $position++;
            $option = $form->getSubmitValue("option[$i]");
            $objAnswer->createAnswer($option, 0, '', 0, $position);
        }

        // Insert the answers
        for ($i = 1; $i <= $nb_matches; $i++) {
            $position++;
            $answer = $form->getSubmitValue("answer[$i]");
            $matches = $form->getSubmitValue("matches[$i]");
            $weighting = $form->getSubmitValue("weighting[$i]");
            $this->weighting += $weighting;

            $objAnswer->createAnswer(
                $answer,
                $matches,
                '',
                $weighting,
                $position
            );
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
        $header .= '<table class="matching '.$this->question_table_class.'"><tr>';
        $header .= '<th>'.get_lang('Elements list').'</th>';
        if (!in_array($exercise->results_disabled, [
            RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER,
            //RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING,
        ])
        ) {
            $header .= '<th>'.get_lang('Your choice').'</th>';
        }

        if ($exercise->showExpectedChoice()) {
            if ($exercise->showExpectedChoiceColumn()) {
                $header .= '<th>'.get_lang('Expected choice').'</th>';
            }
            $header .= '<th>'.get_lang('Status').'</th>';
        } else {
            $header .= '<th>'.get_lang('Corresponds to').'</th>';
        }
        $header .= '</tr>';

        return $header;
    }
}
