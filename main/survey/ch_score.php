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
    public function createForm($survey_data, $formData)
    {
        parent::createForm($survey_data, $formData);

        $this->getForm()->addText('maximum_score', get_lang('MaximumScore'));

        $config = array('ToolbarSet' => 'Survey', 'Width' => '100%', 'Height' => '120');
        if (is_array($formData['answers'])) {
            foreach ($formData['answers'] as $key => $value) {
                $this->getForm()->addHtmlEditor('answers['.$key.']', null, false, false, $config);
            }
        }

        parent::addRemoveButtons($formData);
    }

    /**
     * @param FormValidator $form
     * @param array $questionData
     * @param array $answers
     */
    public function render(FormValidator $form, $questionData = array(), $answers = array())
    {
        $defaults = [];
        foreach ($questionData['options'] as $key => & $value) {
            $options = array(
                '--' => '--'
            );
            for ($i = 1; $i <= $questionData['maximum_score']; $i++) {
                $options[$i] = $i;
            }

            $name = 'question'.$questionData['question_id'].'['.$key.']';

            $form->addSelect(
                $name, $value, $options
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
