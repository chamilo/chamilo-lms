<?php
/* For licensing terms, see /license.txt */

/**
 * Class ch_score.
 */
class ch_score extends survey_question
{
    /**
     * @param array $survey_data
     * @param array $formData
     */
    public function createForm($survey_data, $formData)
    {
        parent::createForm($survey_data, $formData);
        $this->getForm()->addText('maximum_score', get_lang('MaximumScore'));
        $config = ['ToolbarSet' => 'Survey', 'Width' => '100%', 'Height' => '120'];
        if (is_array($formData['answers'])) {
            foreach ($formData['answers'] as $key => $value) {
                $this->getForm()->addHtmlEditor(
                    'answers['.$key.']',
                    null,
                    false,
                    false,
                    $config
                );
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
        $defaults = [];
        foreach ($questionData['options'] as $key => &$value) {
            $options = [
                '--' => '--',
            ];
            for ($i = 1; $i <= $questionData['maximum_score']; $i++) {
                $options[$i] = $i;
            }

            $name = 'question'.$questionData['question_id'].'['.$key.']';

            $form->addHidden('question_id', $questionData['question_id']);
            $form->addSelect(
                $name,
                $value,
                $options
            );

            if (!empty($answers)) {
                if (in_array($key, array_keys($answers))) {
                    $defaults[$name] = $answers[$key];
                }
            }
        }

        if (!empty($defaults)) {
            $form->setDefaults($defaults);
        }
    }
}
