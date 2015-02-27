<?php
/* For licensing terms, see /license.txt */

/**
 * Class ch_multiplechoice
 */
class ch_multiplechoice extends survey_question
{
    /**
     * @param array $survey_data
     * @param array $formData
     * @return FormValidator
     */
    public function create_form($survey_data, $formData)
    {
        parent::create_form($survey_data, $formData);

        $options = array(
            'horizontal' => get_lang('Horizontal'),
            'vertical' => get_lang('Vertical')
        );
        $this->getForm()->addRadio('horizontalvertical', get_lang('DisplayAnswersHorVert'), $options);

        $formData['horizontalvertical'] = isset($formData['horizontalvertical']) ? $formData['horizontalvertical'] : 'horizontal';

        $config = array('ToolbarSet' => 'Survey', 'Width' => '100%', 'Height' => '120');
        $total = count($formData['answers']);
        if (is_array($formData['answers'])) {
            foreach ($formData['answers'] as $key => $value) {

                $this->getForm()->addHtmlEditor('answers['.$key.']', null, false, false, $config);

                if ($key < $total-1) {
                    //$this->getForm()->addButton("move_down[$key]", get_lang('Down'));
                }

                if ($key > 0) {
                    //$this->getForm()->addButton("move_up[$key]", get_lang('Up'));
                }

                if ($total> 2) {
                    $this->getForm()->addButton("delete_answer[$key]", get_lang('Delete'));
                }
            }
        }
        $this->getForm()->setDefaults($formData);

        return parent :: add_remove_buttons($formData);
    }

    /**
     * @param FormValidator $form
     * @param array $questionData
     * @param array $answers
     */
    public function render(FormValidator $form, $questionData = array(), $answers = array())
    {
        $question = new ch_yesno();
        $question->render($form, $questionData, $answers);
    }
}
