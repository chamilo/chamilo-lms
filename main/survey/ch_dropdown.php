<?php
/* For licensing terms, see /license.txt */

/**
 * Class ch_dropdown.
 */
class ch_dropdown extends survey_question
{
    /**
     * @param array $survey_data
     * @param $formData
     */
    public function createForm($survey_data, $formData)
    {
        parent::createForm($survey_data, $formData);

        if (is_array($formData['answers'])) {
            foreach ($formData['answers'] as $key => $value) {
                $this->getForm()->addText('answers['.$key.']', $key + 1);
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
        $name = 'question'.$questionData['question_id'];
        $data = [0 => '--'] + $questionData['options'];
        $form->addSelect($name, null, $data);
        if (!empty($answers)) {
            $form->setDefaults([$name => $answers]);
        }
    }
}
