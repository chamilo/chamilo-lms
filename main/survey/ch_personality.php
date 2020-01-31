<?php
/* For licensing terms, see /license.txt */

/**
 * Class ch_personality.
 */
class ch_personality extends survey_question
{
    /**
     * This function creates the form elements for the multiple response questions.
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version January 2007
     */
    public function createForm($survey_data, $form_content)
    {
        parent::createForm($survey_data, $form_content);
        $this->html .= '	<tr>';
        $this->html .= '		<td colspan="2"><strong>'.get_lang('DisplayAnswersHorVert').'</strong></td>';
        $this->html .= '	</tr>';
        // Horizontal or vertical
        $this->html .= '	<tr>';
        $this->html .= '		<td align="right" valign="top">&nbsp;</td>';
        $this->html .= '		<td>';
        $this->html .= '		  <input name="horizontalvertical" type="radio" value="horizontal" ';
        if (empty($form_content['horizontalvertical']) || $form_content['horizontalvertical'] == 'horizontal') {
            $this->html .= 'checked="checked"';
        }
        $this->html .= '/>'.get_lang('Horizontal').'</label><br />';
        $this->html .= '		  <input name="horizontalvertical" type="radio" value="vertical" ';

        if (isset($form_content['horizontalvertical']) && $form_content['horizontalvertical'] == 'vertical') {
            $this->html .= 'checked="checked"';
        }

        $this->html .= ' />'.get_lang('Vertical').'</label>';
        $this->html .= '		</td>';
        $this->html .= '		<td>&nbsp;</td>';
        $this->html .= '	</tr>';
        $this->html .= '		<tr>
								<td colspan="">&nbsp;</td>
							</tr>';

        // The options
        $this->html .= '	<tr>';
        $this->html .= '		<td colspan="3"><strong>'.get_lang('AnswerOptions').'</strong></td>';
        $this->html .= '	</tr>';
        $total_number_of_answers = count($form_content['answers']);

        $question_values = [];

        // Values of question options
        if (is_array($form_content['values'])) { // Check if data is correct
            foreach ($form_content['values'] as $key => &$value) {
                $question_values[] = '<input size="3" type="text" id="values['.$key.']" name="values['.$key.']" value="'.$value.'" />';
            }
        }
        $count = 0;
        if (is_array($form_content['answers'])) {
            foreach ($form_content['answers'] as $key => &$value) {
                $this->html .= '<tr>';
                $this->html .= '<td align="right"><label for="answers['.$key.']">'.($key + 1).'</label></td>';
                $this->html .= '<td width="550">'.api_return_html_area('answers['.$key.']', api_html_entity_decode(stripslashes($form_content['answers'][$key])), '', '', null, ['ToolbarSet' => 'Survey', 'Width' => '100%', 'Height' => '120']).'</td>';
                $this->html .= '<td>';

                if ($total_number_of_answers > 2) {
                    $this->html .= $question_values[$count];
                }

                if ($key < $total_number_of_answers - 1) {
                    $this->html .= '<input type="image" style="width:22px"   src="'.Display::returnIconPath('down.png').'"  value="move_down['.$key.']" name="move_down['.$key.']"/>';
                }
                if ($key > 0) {
                    $this->html .= '<input type="image" style="width:22px"   src="'.Display::returnIconPath('up.png').'"  value="move_up['.$key.']" name="move_up['.$key.']"/>';
                }
                if ($total_number_of_answers > 2) {
                    $this->html .= '<input type="image" style="width:22px"   src="'.Display::returnIconPath('delete.png').'"  value="delete_answer['.$key.']" name="delete_answer['.$key.']"/>';
                }
                $this->html .= '</td>';
                $this->html .= '</tr>';
                $count++;
            }
        }
    }

    /**
     * @param array $questionData
     * @param array $answers
     */
    public function render(FormValidator $form, $questionData = [], $answers = [])
    {
        $question = new ch_yesno();
        $question->render($form, $questionData, $answers);
    }
}
