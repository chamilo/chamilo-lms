<?php
/* For licensing terms, see /license.txt */
/**
 * CLASS Draggable
 *
 *    This class allows to instantiate an object of type DRAGGABLE,
 *    extending the class question
 *
 * @author Julio Montoya
 * @package chamilo.exercise
 **/
/**
 * Code
 */
class Draggable extends Matching
{
    static $typePicture = 'matching.gif';
    static $explanationLangVar = 'Draggable';

    /**
     * Constructor
     */
    public function Draggable()
    {
        parent::question();
        $this->type      = DRAGGABLE;
        $this->isContent = $this->getIsContent();
    }

    /**
     * Function which redefines Question::createAnswersForm
     * @param FormValidator instance
     */
    public function createAnswersForm($form)
    {
        $defaults = array();

        $nb_matches = $nb_options = 2;
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

        } else {
            if (!empty($this->id)) {
                $answer = new Answer($this->id, api_get_course_int_id());
                $answer->read();
                if (count($answer->nbrAnswers) > 0) {
                    $nb_matches = $nb_options = 0;
                    //for ($i = 1; $i <= $answer->nbrAnswers; $i++) {
                    foreach ($answer->answer as $answerId => $answer_item) {
                        //$answer_id = $answer->getRealAnswerIdFromList($answerId);
                        if ($answer->isCorrect($answerId)) {
                            $nb_matches++;
                            $defaults['answer['.$nb_matches.']']    = $answer->selectAnswer($answerId);
                            $defaults['weighting['.$nb_matches.']'] = Text::float_format($answer->selectWeighting($answerId), 1);
                            $defaults['matches['.$nb_matches.']']   = $answer->correct[$answerId];//$nb_matches;
                        } else {
                            $nb_options++;
                            $defaults['option['.$nb_options.']'] = $nb_options;

                        }
                    }

                }
            } else {
                $defaults['answer[1]']  = get_lang('DefaultMakeCorrespond1');
                $defaults['answer[2]']  = get_lang('DefaultMakeCorrespond2');
                $defaults['matches[2]'] = '2';
                $defaults['option[1]']  = get_lang('DefaultMatchingOptA');
                $defaults['option[2]']  = get_lang('DefaultMatchingOptB');
            }
        }

        $a_matches = array();
        for ($i = 1; $i <= $nb_matches; ++$i) {
            $a_matches[$i] = $i; // fill the array with A, B, C.....
        }

        $form->addElement('hidden', 'nb_matches', $nb_matches);
        $form->addElement('hidden', 'nb_options', $nb_options);

        // DISPLAY MATCHES
        $html = '<table class="data_table">
					<tr>
						<th width="40%">
							'.get_lang('Answer').'
						</th>
						<th width="40%">
							'.get_lang('MatchesTo').'
						</th>
						<th width="50px">
							'.get_lang('Weighting').'
						</th>
					</tr>';

        $form->addElement('label', get_lang('MakeCorrespond').'<br /> '.Display::return_icon('fill_field.png'), $html);

        if ($nb_matches < 1) {
            $nb_matches = 1;
            Display::display_normal_message(get_lang('YouHaveToCreateAtLeastOneAnswer'));
        }

        for ($i = 1; $i <= $nb_matches; ++$i) {
            $form->addElement('html', '<tr><td>');
            $group = array();

            $group[] = $form->createElement('text', 'answer['.$i.']', null, ' size="60" style="margin-left: 0em;"');
            $group[] = $form->createElement('select', 'matches['.$i.']', null, $a_matches);
            $group[] = $form->createElement(
                'text',
                'weighting['.$i.']',
                null,
                array('class' => 'span1', 'value' => 10, )
            );
            $form->addGroup($group, null, null, '</td><td>');
            $form->addElement('html', '</td></tr>');

            $defaults['option['.$i.']'] = $i;
        }

        $form->addElement('html', '</table></div></div>');
        $group = array();

        $group[] = $form->createElement(
            'style_submit_button',
            'moreMatches',
            get_lang('AddElem'),
            'class="btn plus"'
        );
        $group[] = $form->createElement(
            'style_submit_button',
            'lessMatches',
            get_lang('DelElem'),
            'class="btn minus"'
        );

        $group[] = $form->createElement('style_submit_button', 'submitQuestion', $this->submitText, 'class="'.$this->submitClass.'"');
        $form->addGroup($group);
        $form->addElement('html', '</table></div></div>');
        if (!empty($this->id)) {
            $form->setDefaults($defaults);
        } else {
            if ($this->isContent == 1) {
                $form->setDefaults($defaults);
            }
        }
        $form->setConstants(array('nb_matches' => $nb_matches, 'nb_options' => $nb_options));
    }


    /**
     * abstract function which creates the form to create / edit the answers of the question
     * @param FormValidator instance
     */
    public function processAnswersCreation($form)
    {
        $nb_matches      = $form->getSubmitValue('nb_matches');
        $this->weighting = 0;
        $objAnswer       = new Answer($this->id);

        $position = 0;

        // insert the options
        for ($i = 1; $i <= $nb_matches; ++$i) {
            $position++;
            //$option = $form->getSubmitValue('option['.$i.']');
            $objAnswer->createAnswer($position, 0, '', 0, $position);
        }

        // insert the answers
        for ($i = 1; $i <= $nb_matches; ++$i) {
            $position++;
            $answer    = $form->getSubmitValue('answer['.$i.']');
            $matches   = $form->getSubmitValue('matches['.$i.']');
            $weighting = $form->getSubmitValue('weighting['.$i.']');
            $this->weighting += $weighting;
            $objAnswer->createAnswer($answer, $matches, '', $weighting, $position);
        }
        $objAnswer->save();
        $this->save();
    }

    /**
     * {@inheritdoc}
     */
    public function return_header($feedback_type = null, $counter = null, $score = null, $show_media = false, $hideTitle = 0)
    {
        $header = parent::return_header($feedback_type, $counter, $score, $show_media, $hideTitle);
        $header .= '<table class="'.$this->question_table_class.'">';
        $header .= '<tr>
                <th>'.get_lang('ElementList').'</th>
                <th>'.get_lang('Status').'</th>
              </tr>';
        return $header;
    }
}
