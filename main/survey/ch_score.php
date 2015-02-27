<?php
/* For licensing terms, see /license.txt */

/**
 * Class ch_score
 */
class ch_score extends survey_question
{
    /**
     * @param array $survey_data
     * @param $form_content
     */
    public function create_form($survey_data, $formData)
    {
        parent::create_form($survey_data, $formData);

        $this->getForm()->addText('maximum_score', get_lang('MaximumScore'));

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

        return parent :: add_remove_buttons($formData);
    }

    /**
     * @param FormValidator $form
     * @param array $questionData
     * @param array $answers
     */
    public function render(FormValidator $form, $questionData = array(), $answers = array())
    {
        foreach ($questionData['options'] as $key => & $value) {
            $options = array();
            for ($i=1; $i <= $questionData['maximum_score']; $i++) {
                $options[$i] = $i;
            }

            $form->addSelect(
                'question'.$questionData['question_id'].'['.$key.'].', $value, $options
            );
            /*
            $this->html .= '<tr>
								<td>'.$value.'</td>';
            $this->html .= '	<td>';
            $this->html .= '<select name="question'.$form_content['question_id'].'['.$key.']">';
            $this->html .= '<option value="--">--</option>';

            $this->html .= '</select>';
            $this->html .= '	</td>';
            $this->html .= '</tr>';*/
        }
    }
}
