<?php
/* For licensing terms, see /license.txt */

/**
 * Class Draggable
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
class Draggable extends Question
{
    public static $typePicture = 'ordering.png';
    public static $explanationLangVar = 'Draggable';

    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = DRAGGABLE;
        $this->isContent = $this->getIsContent();
    }

    /**
     * Function which redefines Question::createAnswersForm
     * @param FormValidator $form
     */
    public function createAnswersForm($form)
    {
        $defaults = array();
        $nb_matches = $nb_options = 2;
        $matches = array();

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
        } else if (!empty($this->id)) {
            $answer = new Answer($this->id);
            $answer->read();

            if (count($answer->nbrAnswers) > 0) {
                $nb_matches = $nb_options = 0;

                for ($i = 1; $i <= $answer->nbrAnswers; $i++) {
                    if ($answer->isCorrect($i)) {
                        $nb_matches++;
                        $defaults['answer[' . $nb_matches . ']'] = $answer->selectAnswer($i);
                        $defaults['weighting[' . $nb_matches . ']'] = float_format($answer->selectWeighting($i), 1);
                        $answerInfo = $answer->getAnswerByAutoId($answer->correct[$i]);
                        $defaults['matches[' . $nb_matches . ']'] = isset($answerInfo['answer']) ? $answerInfo['answer'] : '';
                    } else {
                        $nb_options++;
                        $defaults['option[' . $nb_options . ']'] = $answer->selectAnswer($i);
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

        for ($i = 1; $i <= $nb_matches; ++$i) {
            $matches[$i] = $i;
        }

        $form->addElement('hidden', 'nb_matches', $nb_matches);
        $form->addElement('hidden', 'nb_options', $nb_options);

        // DISPLAY MATCHES
        $html = '<table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th width="85%">' . get_lang('Answer') . '</th>
                    <th width="15%">' . get_lang('MatchesTo') . '</th>
                    <th width="10">' . get_lang('Weighting') . '</th>
                </tr>
            </thead>
            <tbody>';

        $form->addHeader(get_lang('MakeCorrespond'));
        $form->addHtml($html);

        if ($nb_matches < 1) {
            $nb_matches = 1;
            Display::display_normal_message(get_lang('YouHaveToCreateAtLeastOneAnswer'));
        }

        for ($i = 1; $i <= $nb_matches; ++$i) {
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
            $form->addButtonSave($text, 'submitQuestion', true)
        ];

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
                'nb_options' => $nb_options
            ]
        );
    }

    /**
     * Abstract function which creates the form to create / edit the answers of the question
     * @param FormValidator $form
     */
    public function processAnswersCreation($form)
    {
        $nb_matches = $form->getSubmitValue('nb_matches');
        $this->weighting = 0;
        $position = 0;

        $objAnswer = new Answer($this->id);

        // Insert the options
        for ($i = 1; $i <= $nb_matches; ++$i) {
            $position++;

            $objAnswer->createAnswer($position, 0, '', 0, $position);
        }

        // Insert the answers
        for ($i = 1; $i <= $nb_matches; ++$i) {
            $position++;

            $answer = $form->getSubmitValue('answer[' . $i . ']');
            $matches = $form->getSubmitValue('matches[' . $i . ']');
            $weighting = $form->getSubmitValue('weighting[' . $i . ']');
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
        $this->save();
    }

    /**
     * Shows question title an description
     * @param string $feedback_type
     * @param int $counter
     * @param float $score
     * @return string
     */
    public function return_header($feedback_type = null, $counter = null, $score = null)
    {
        $header = parent::return_header($feedback_type, $counter, $score);
        $header .= '<table class="' . $this->question_table_class . '">
            <tr>
                <th>' . get_lang('ElementList') . '</th>
                <th>' . get_lang('Status') . '</th>
            </tr>';

        return $header;
    }
}
