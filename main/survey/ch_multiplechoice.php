<?php
/* For licensing terms, see /license.txt */

/**
 * Class ch_multiplechoice.
 */
class ch_multiplechoice extends survey_question
{
    /**
     * @param array $survey_data
     * @param array $formData
     *
     * @return FormValidator
     */
    public function createForm($survey_data, $formData)
    {
        parent::createForm($survey_data, $formData);

        $options = [
            'horizontal' => get_lang('Horizontal'),
            'vertical' => get_lang('Vertical'),
        ];
        $this->getForm()->addRadio('horizontalvertical', get_lang('DisplayAnswersHorVert'), $options);

        $formData['horizontalvertical'] = isset($formData['horizontalvertical']) ? $formData['horizontalvertical'] : 'horizontal';
        $this->getForm()->setDefaults($formData);

        $config = ['ToolbarSet' => 'Survey', 'Width' => '100%', 'Height' => '120'];
        $total = count($formData['answers']);

        if (is_array($formData['answers'])) {
            foreach ($formData['answers'] as $key => $value) {
                $this->getForm()->addHtmlEditor('answers['.$key.']', null, false, false, $config);
                if ($total > 2) {
                    $this->getForm()->addButton("delete_answer[$key]", get_lang('Delete'), 'trash', 'danger');
                }
            }
        }

        if (isset($formData['answersid']) && !empty($formData['answersid'])) {
            foreach ($formData['answersid'] as $value) {
                $this->getForm()->addHidden('answersid[]', $value);
            }
        }

        parent::addRemoveButtons($formData);
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
