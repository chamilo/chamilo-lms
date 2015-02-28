<?php
/* For licensing terms, see /license.txt */

/**
 * Class ch_multipleresponse
 */
class ch_multipleresponse extends survey_question
{
    /**
     * @param array $surveyData
     * @param array $formData
     */
    public function createForm($surveyData, $formData)
    {
        parent::createForm($surveyData, $formData);
        $options = array(
            'horizontal' => get_lang('Horizontal'),
            'vertical' => get_lang('Vertical')
        );
        $this->getForm()->addRadio('horizontalvertical', get_lang('DisplayAnswersHorVert'), $options);

        $formData['horizontalvertical'] = isset($formData['horizontalvertical']) ? $formData['horizontalvertical'] : 'horizontal';
        $this->getForm()->setDefaults($formData);

        $config = array('ToolbarSet' => 'Survey', 'Width' => '100%', 'Height' => '120');
        if (is_array($formData['answers'])) {
            foreach ($formData['answers'] as $key => $value) {
                $this->getForm()->addHtmlEditor('answers['.$key.']', null, false, false, $config);
                /*
                $this->html .= '	<tr>';
                $this->html .= '		<td align="right"><label for="answers['.$key.']">'.($key+1).'</label></td>';
                //$this->html .= '		<td><input type="text" name="answers['.$key.']" id="answers['.$key.']" value="'.$form_content['answers'][$key].'" /></td>';
                //$this->html .= '		<td width="550">'.api_return_html_area('answers['.$key.']', api_html_entity_decode(stripslashes($form_content['answers'][$key]), ENT_QUOTES), '', '', null, ).'</td>';
                $this->html .= '		<td>';
                if ($key<$total_number_of_answers-1) {
                    $this->html .= '			<input style="width:22px" type="image" src="../img/icons/22/down.png"  value="move_down['.$key.']" name="move_down['.$key.']"/>';
                }
                if ($key>0) {
                    $this->html .= '			<input style="width:22px" type="image" src="../img/icons/22/up.png"  value="move_up['.$key.']" name="move_up['.$key.']"/>';
                }
                if ($total_number_of_answers> 2) {
                    $this->html .= '			<input style="width:22px" type="image" src="../img/icons/22/delete.png"  value="delete_answer['.$key.']" name="delete_answer['.$key.']"/>';
                }
                $this->html .= ' 		</td>';
                $this->html .= '	</tr>';*/
            }
        }

        parent :: addRemoveButtons($formData);
    }

    /**
     * @param FormValidator $form
     * @param array $questionData
     * @param array $answers
     */
    public function render(FormValidator $form, $questionData = array(), $answers = array())
    {
        if ($questionData['display'] == 'vertical') {
            $class = '';
        } else {
            $class = 'inline';
        }

        foreach ($questionData['options'] as $key => & $value) {
            /*if ($questionData['display'] == 'vertical') {
                $this->html .= '<label class="checkbox"><input name="question'.$questionData['question_id'].'[]" type="checkbox" value="'.$key.'"';
            } else {
                $this->html .= '<label class="checkbox inline"><input name="question'.$questionData['question_id'].'[]" type="checkbox" value="'.$key.'"';
            }
            if (is_array($answers)) {
                if (in_array($key, $answers)) {
                    $this->html .= 'checked="checked"';
                }
            }
            if (substr_count($value, '<p>') == 1) {
                $this->html .= '/>'.substr($value, 3, (strlen($value) - 7)).'</label>';
                if ($questionData['display'] == 'vertical') {
                    $this->html .= '<br />';
                }
            } else {
                $this->html .= '/>'.$value.'</label>';
            }*/

            /*$form->addCheckBox(
                'question'.$questionData['question_id'].'[]',
                $key,
                $value,
                array('label-class' => $class)
            );*/
        }

        $form->addCheckBoxGroup(
            'question'.$questionData['question_id'].'[]',
            null,
            $questionData['options'],
            array('label-class' => $class)
        );


    }
}
