<?php
/* For licensing terms, see /license.txt */

/**
 * Class ch_yesno
 */
class ch_yesno extends survey_question
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

        /*// Horizontal or vertical
        $this->html .= '	<div class="control-group">';
        $this->html .= '		<label class="control-label">';
        $this->html .= 				get_lang('DisplayAnswersHorVert');
        $this->html .= '		</label>';
        $this->html .= '		<div class="controls">';
        $this->html .= '		  <input name="horizontalvertical" type="radio" value="horizontal" ';
        if (empty($form_content['horizontalvertical']) or $form_content['horizontalvertical'] == 'horizontal') {
            $this->html .= 'checked="checked"';
        }
        $this->html .= '/>'.get_lang('Horizontal').'<br />';
        $this->html .= '		  <input name="horizontalvertical" type="radio" value="vertical" ';
        if (isset($form_content['horizontalvertical']) && $form_content['horizontalvertical'] == 'vertical') {
            $this->html .= 'checked="checked"';
        }
        $this->html .= ' />'.get_lang('Vertical').'';
        $this->html .= '		</div>';
        $this->html .= '	</div>';*/

        // The options
        $config = array('ToolbarSet' => 'Survey', 'Width' => '100%', 'Height' => '120');
        $this->getForm()->addHtmlEditor('answers[0]', get_lang('AnswerOptions'), true, false, $config);
        $this->getForm()->addHtmlEditor('answers[1]', null, true, false, $config);

        /*$this->html .= '	<div class="row">';
        $this->html .= '		<label class="control-label">';
        $this->html .= 				get_lang('AnswerOptions');
        $this->html .= '		</label>';
        $this->html .= '		<div class="formw">';
        $this->html .= '			<table>';
        $this->html .= '	<tr>';
        $this->html .= '		<td align="right"><label for="answers[0]">1</label></td>';

        $this->html .= '		<td width="550">'.api_return_html_area('answers[0]', stripslashes($form_content['answers'][0]), '', '', null, array('ToolbarSet' => 'Survey', 'Width' => '100%', 'Height' => '120')).'</td>';
        $this->html .= '		<td><input style="width:22px" src="../img/icons/22/down.png"  type="image" class="down" value="move_down[0]" name="move_down[0]"/></td>';
        $this->html .= '	</tr>';
        $this->html .= '	<tr>';
        $this->html .= '		<td align="right"><label for="answers[1]">2</label></td>';
        //$this->html .= '		<td><input type="text" name="answers[1]" id="answers[1]" value="'.$form_content['answers'][1].'" /></td>';
        $this->html .= '		<td width="550">'.api_return_html_area('answers[1]', stripslashes($form_content['answers'][1]), '', '', null, array('ToolbarSet' => 'Survey', 'Width' => '100%', 'Height' => '120')).'</td>';
        $this->html .= '		<td><input style="width:22px" type="image" src="../img/icons/22/up.png" value="move_up[1]" name="move_up[1]" /></td>';
        $this->html .= '	</tr>';
        $this->html .= '			</table>';
        $this->html .= '		</div>';
        $this->html .= '	</div>';*/
    }

    /**
     * @param FormValidator $form
     * @param array $questionData
     * @param array $answers
     */
    public function render(FormValidator $form, $questionData = array(), $answers = array())
    {
        if (is_array($questionData['options'])) {
            if ($questionData['display'] == 'vertical') {
                $class = '';
            } else {
                $class = 'inline';
            }

            $form->addRadio(
                'question' . $questionData['question_id'],
                null,
                $questionData['options'],
                array('label-class' => $class)
            );
        }
    }
}
