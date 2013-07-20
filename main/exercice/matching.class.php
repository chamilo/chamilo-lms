<?php
/* For licensing terms, see /license.txt */
/**
 * CLASS Matching
 *
 *    This class allows to instantiate an object of type MULTIPLE_ANSWER (MULTIPLE CHOICE, MULTIPLE ANSWER),
 *    extending the class question
 *
 * @author Eric Marguin
 * @package chamilo.exercise
 **/
/**
 * Code
 */

/**
 * Matching questions type class
 * @package chamilo.exercise
 */
class Matching extends Question
{
    static $typePicture = 'matching.gif';
    static $explanationLangVar = 'Matching';

    /**
     * Constructor
     */
    function Matching()
    {
        parent::question();
        $this->type      = MATCHING;
        $this->isContent = $this->getIsContent();
    }

    /**
     * Redefines Question::createAnswersForm
     * @param the formvalidator instance
     */
    function createAnswersForm($form)
    {
        $defaults       = array();
        $navigator_info = api_get_navigator();

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
                $answer = new Answer($this->id);
                $answer->read();
                if (count($answer->nbrAnswers) > 0) {
                    $a_matches  = $a_options = array();
                    $nb_matches = $nb_options = 0;
                    foreach ($answer->answer as $i => $answer_item) {
                        if ($answer->isCorrect($i)) {
                            $nb_matches++;
                            $defaults['answer['.$nb_matches.']']    = $answer->selectAnswer($i);
                            $defaults['weighting['.$nb_matches.']'] = Text::float_format($answer->selectWeighting($i), 1);
                            $correct_answer_id = $answer->correct[$i];
                            $defaults['matches['.$nb_matches.']'] = $answer->getCorrectAnswerPosition($correct_answer_id);
                        } else {
                            $nb_options++;
                            $defaults['option['.$nb_options.']'] = $answer->selectAnswer($i);
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
        for ($i = 1; $i <= $nb_options; ++$i) {
            // fill the array with A, B, C.....
            $a_matches[$i] = chr(64 + $i);
        }

        $form->addElement('hidden', 'nb_matches', $nb_matches);
        $form->addElement('hidden', 'nb_options', $nb_options);

        // DISPLAY MATCHES
        $html = '<table class="data_table">
					<tr>
						<th width="10px">
							'.get_lang('Number').'
						</th>
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
            $puce  = $form->createElement('text', null, null, 'value="'.$i.'"');
            $puce->freeze();
            $group[] = $puce;

            $group[] = $form->createElement('text', 'answer['.$i.']', null, 'size="60" style="margin-left: 0em;"');
            $group[] = $form->createElement('select', 'matches['.$i.']', null, $a_matches);
            $group[] = $form->createElement(
                'text',
                'weighting['.$i.']',
                null,
                array('class' => 'span1', 'value' => 10)
            );
            $form->addGroup($group, null, null, '</td><td>');
            $form->addElement('html', '</td></tr>');
        }

        $form->addElement('html', '</table></div></div>');
        $group = array();

        if ($navigator_info['name'] == 'Internet Explorer' && $navigator_info['version'] == '6') {
            $group[] = $form->createElement('submit', 'lessMatches', get_lang('DelElem'), 'class="btn minus"');
            $group[] = $form->createElement('submit', 'moreMatches', get_lang('AddElem'), 'class="btn plus"');
        } else {
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
        }

        $form->addGroup($group);

        // DISPLAY OPTIONS
        $html = '<table class="data_table">
					<tr style="text-align: center;">
						<th width="10px">
							'.get_lang('Number').'
						</th>
						<th width="90%"
							'.get_lang('Answer').'
						</th>
					</tr>';
        //$form -> addElement ('html', $html);
        $form->addElement('label', null, $html);

        if ($nb_options < 1) {
            $nb_options = 1;
            Display::display_normal_message(get_lang('YouHaveToCreateAtLeastOneAnswer'));
        }

        for ($i = 1; $i <= $nb_options; ++$i) {
            $form->addElement('html', '<tr><td>');
            $group = array();
            $puce  = $form->createElement('text', null, null, 'value="'.chr(64 + $i).'"');
            $puce->freeze();
            $group[] = $puce;
            $group[] = $form->createElement('text', 'option['.$i.']', null, array('class' => 'span6'));
            $form->addGroup($group, null, null, '</td><td>');
            $form->addElement('html', '</td></tr>');
        }

        $form->addElement('html', '</table></div></div>');
        $group = array();

        if ($navigator_info['name'] == 'Internet Explorer' && $navigator_info['version'] == '6') {
            // setting the save button here and not in the question class.php
            $group[] = $form->createElement('submit', 'submitQuestion', $this->submitText, 'class="'.$this->submitClass.'"');
            $group[] = $form->createElement('submit', 'lessOptions', get_lang('DelElem'), 'class="minus"');
            $group[] = $form->createElement('submit', 'moreOptions', get_lang('AddElem'), 'class="plus"');
        } else {
            // setting the save button here and not in the question class.php
            $group[] = $form->createElement('style_submit_button', 'lessOptions', get_lang('DelElem'), 'class="minus"');
            $group[] = $form->createElement('style_submit_button', 'moreOptions', get_lang('AddElem'), ' class="plus"');
            $group[] = $form->createElement('style_submit_button', 'submitQuestion', $this->submitText, 'class="'.$this->submitClass.'"');
        }

        $form->addGroup($group);

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
     * @param the formvalidator instance
     */
    function processAnswersCreation($form)
    {

        $nb_matches      = $form->getSubmitValue('nb_matches');
        $nb_options      = $form->getSubmitValue('nb_options');
        $this->weighting = 0;
        $objAnswer       = new Answer($this->id);

        $position = 0;

        // insert the options
        for ($i = 1; $i <= $nb_options; ++$i) {
            $position++;
            $option = $form->getSubmitValue('option['.$i.']');
            $objAnswer->createAnswer($option, 0, '', 0, $position);
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
    function return_header($feedback_type = null, $counter = null, $score = null, $show_media = false, $hideTitle = 0)
    {
        $header = parent::return_header($feedback_type, $counter, $score, $show_media, $hideTitle);
        if ($this->type == MATCHING) {
            $header .= '<table class="'.$this->question_table_class.'">';
            $header .= '<tr>
                    <th>'.get_lang('ElementList').'</th>
                    <th>'.get_lang('CorrespondsTo').'</th>
                  </tr>';
        }
        return $header;
    }
}
