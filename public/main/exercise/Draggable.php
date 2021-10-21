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
    public $explanationLangVar = 'Sequence ordering';

    public function __construct()
    {
        parent::__construct();
        $this->type = DRAGGABLE;
        $this->isContent = $this->getIsContent();
    }

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
        } elseif (!empty($this->id)) {
            $defaults['orientation'] = in_array($this->extra, ['h', 'v']) ? $this->extra : 'h';

            $answer = new Answer($this->id);
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
            $defaults['answer[1]'] = get_lang('First step');
            $defaults['answer[2]'] = get_lang('Second step');
            $defaults['matches[2]'] = '2';
            $defaults['option[1]'] = get_lang('Note down the address');
            $defaults['option[2]'] = get_lang('Contact the emergency services');
            $defaults['orientation'] = 'h';
        }

        for ($i = 1; $i <= $nb_matches; $i++) {
            $matches[$i] = $i;
        }

        $form->addElement('hidden', 'nb_matches', $nb_matches);
        $form->addElement('hidden', 'nb_options', $nb_options);

        $form->addRadio(
            'orientation',
            get_lang('Choose orientation'),
            ['h' => get_lang('Horizontal'), 'v' => get_lang('Vertical')]
        );

        // DISPLAY MATCHES
        $html = '<table class="table table-striped table-hover">
            <thead>
                <tr>
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
            $form->addButtonDelete(get_lang('Remove element'), 'lessMatches', true),
            $form->addButtonCreate(get_lang('Add element'), 'moreMatches', true),
            $form->addButtonSave($text, 'submitQuestion', true),
        ];

        $form->addGroup($group);

        if (!empty($this->id)) {
            $form->setDefaults($defaults);
        } else {
            $form->setDefaults(['orientation' => 'h']);

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

    public function processAnswersCreation($form, $exercise)
    {
        $this->extra = $form->exportValue('orientation');
        $nb_matches = $form->getSubmitValue('nb_matches');
        $this->weighting = 0;
        $position = 0;
        $objAnswer = new Answer($this->id);
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

    public function return_header(Exercise $exercise, $counter = null, $score = [])
    {
        $header = parent::return_header($exercise, $counter, $score);
        $header .= '<table class="'.$this->question_table_class.'"><tr>';

        if ($exercise->showExpectedChoice()) {
            $header .= '<th>'.get_lang('Your choice').'</th>';
            if ($exercise->showExpectedChoiceColumn()) {
                $header .= '<th>'.get_lang('Expected choice').'</th>';
            }
        } else {
            $header .= '<th>'.get_lang('Elements list').'</th>';
        }
        $header .= '<th class="text-center">'.get_lang('Status').'</th>';
        $header .= '</tr>';

        return $header;
    }
}
