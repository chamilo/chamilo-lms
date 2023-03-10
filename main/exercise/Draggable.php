<?php

/* For licensing terms, see /license.txt */

/**
 * Class Draggable.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
class Draggable extends Question
{
    public $typePicture = 'ordering.png';
    public $explanationLangVar = 'Draggable';

    /**
     * Class constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = DRAGGABLE;
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
        if ($form->isSubmitted()) {
            $nb_matches = $form->getSubmitValue('nb_matches');
            $nb_options = $form->getSubmitValue('nb_options');

            if (isset($_POST['lessMatches'])) {
                $nb_matches--;
            }

            if (isset($_POST['moreMatches'])) {
                $nb_matches++;
            }

            if (isset($_POST['lessOptions'])) {
                $nb_options--;
            }

            if (isset($_POST['moreOptions'])) {
                $nb_options++;
            }
        } elseif (!empty($this->iid)) {
            $defaults['orientation'] = in_array($this->extra, ['h', 'v']) ? $this->extra : 'v';

            $answer = new Answer($this->iid);
            $answer->read();

            if ($answer->nbrAnswers > 0) {
                $nb_matches = $nb_options = 0;
                for ($i = 1; $i <= $answer->nbrAnswers; $i++) {
                    if ($answer->isCorrect($i)) {
                        $nb_matches++;
                        $defaults['answer['.$nb_matches.']'] = $answer->selectAnswer($i);
                        $defaults['weighting['.$nb_matches.']'] = float_format($answer->selectWeighting($i), 1);
                        $answerInfo = $answer->getAnswerByAutoId($answer->correct[$i]);
                        $defaults['matches['.$nb_matches.']'] = isset($answerInfo['answer']) ? $answerInfo['answer'] : '';
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
            $defaults['orientation'] = 'v';
        }

        for ($i = 1; $i <= $nb_matches; $i++) {
            $matches[$i] = $i;
        }

        $form->addElement('hidden', 'nb_matches', $nb_matches);
        $form->addElement('hidden', 'nb_options', $nb_options);

        $form->addRadio(
            'orientation',
            get_lang('ChooseOrientation'),
            ['v' => get_lang('Vertical'), 'h' => get_lang('Horizontal')]
        );

        // DISPLAY MATCHES
        $html = '<table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th width="85%">'.get_lang('Answer').'</th>
                    <th width="15%">'.get_lang('MatchesTo').'</th>
                    <th width="10">'.get_lang('Weighting').'</th>
                </tr>
            </thead>
            <tbody>';

        $form->addHeader(get_lang('MakeCorrespond'));
        $form->addHtml($html);

        if ($nb_matches < 1) {
            $nb_matches = 1;
            echo Display::return_message(get_lang('YouHaveToCreateAtLeastOneAnswer'), 'normal');
        }

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
            $form->addText("answer[$i]", null);
            $form->addSelect("matches[$i]", null, $matches);
            $form->addText("weighting[$i]", null, true, ['value' => 10, 'style' => 'width: 60px;']);
            $form->addHtml('</tr>');
        }

        $form->addHtml('</tbody></table>');

        $renderer->setElementTemplate(
            '<div class="form-group"><div class="col-sm-offset-2">{element}',
            'lessMatches'
        );
        $renderer->setElementTemplate('{element}</div></div>', 'moreMatches');

        global $text;

        $group = [
            $form->addButtonDelete(get_lang('DelElem'), 'lessMatches', true),
            $form->addButtonCreate(get_lang('AddElem'), 'moreMatches', true),
            $form->addButtonSave($text, 'submitQuestion', true),
        ];

        $form->addGroup($group);

        if (!empty($this->iid)) {
            $form->setDefaults($defaults);
        } else {
            $form->setDefaults(['orientation' => 'v']);

            if (1 == $this->isContent) {
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
        $this->extra = $form->exportValue('orientation');
        $nb_matches = $form->getSubmitValue('nb_matches');
        $this->weighting = 0;
        $position = 0;
        $objAnswer = new Answer($this->iid);
        // Insert the options
        for ($i = 1; $i <= $nb_matches; $i++) {
            $position++;
            $objAnswer->createAnswer($position, 0, '', 0, $position);
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

        $objAnswer->save();
        $this->save($exercise);
    }

    /**
     * {@inheritdoc}
     */
    public function return_header(Exercise $exercise, $counter = null, $score = [])
    {
        $header = parent::return_header($exercise, $counter, $score);
        $header .= '<table class="'.$this->question_table_class.'"><tr>';

        if ($exercise->showExpectedChoice()) {
            $header .= '<th>'.get_lang('YourChoice').'</th>';
            if ($exercise->showExpectedChoiceColumn()) {
                $header .= '<th>'.get_lang('ExpectedChoice').'</th>';
            }
        } else {
            $header .= '<th>'.get_lang('ElementList').'</th>';
            $header .= '<th>'.get_lang('YourChoice').'</th>';
            $header .= '<th>'.get_lang('ExpectedChoice').'</th>';
        }
        $header .= '<th>'.get_lang('Status').'</th>';
        $header .= '</tr>';

        return $header;
    }
}
