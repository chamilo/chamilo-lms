<?php
/* For licensing terms, see /license.txt */

/**
 * Class ch_dropdown
 */
class ch_dropdown extends survey_question
{
    /**
     * @param array $survey_data
     * @param $formData
     * @return FormValidator
     */
    public function createForm($survey_data, $formData)
    {
        parent::createForm($survey_data, $formData);

        // The answers
        /*$this->html .= '	<div class="row">';
        $this->html .= '		<label class="control-label">';
        $this->html .= 				get_lang('AnswerOptions');
        $this->html .= '		</label>';
        $this->html .= '		<div class="formw">';
        $total_number_of_answers = count($form_content['answers']);
        $this->html .= ' 			<table>';
        foreach ($form_content['answers'] as $key => & $value) {
            $this->html .= '	<tr>';
            $this->html .= '		<td align="right"><label for="answers['.$key.']">'.($key + 1).'</label></td>';
            $this->html .= '		<td><input type="text" name="answers['.$key.']" id="answers['.$key.']" value="'.stripslashes($form_content['answers'][$key]).'" /></td>';
            $this->html .= '		<td>';
            if ($key < $total_number_of_answers - 1) {
                $this->html .= '			<input type="image" style="width:22px"   src="../img/icons/22/down.png"  value="move_down['.$key.']" name="move_down['.$key.']"/>';
            }
            if ($key > 0) {
                $this->html .= '			<input type="image" style="width:22px"   src="../img/icons/22/up.png"  value="move_up['.$key.']" name="move_up['.$key.']"/>';
            }
            if ($total_number_of_answers> 2) {
                $this->html .= '			<input type="image" style="width:22px"   src="../img/icons/22/delete.png"  value="delete_answer['.$key.']" name="delete_answer['.$key.']"/>';
            }
            $this->html .= ' 		</td>';
            $this->html .= '	</tr>';
        }
        // The buttons for adding or removing
        $this->html .= ' 			</table>';
        $this->html .= '		</div>';
        $this->html .= '	</div>';*/

        if (is_array($formData['answers'])) {
            foreach ($formData['answers'] as $key => $value) {
                $this->getForm()->addText('answers['.$key.']', $key + 1);
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
        $form->addSelect("question".$questionData['question_id'], null, $questionData['options']);
    }
}


