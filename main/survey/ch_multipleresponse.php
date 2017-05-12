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
        if ($questionData['display'] == 'vertical') {
            $class = 'checkbox';
        } else {
            $class = 'checkbox-inline';
        }

        $name = 'question'.$questionData['question_id'];

        $form->addCheckBoxGroup(
            $name,
            null,
            $questionData['options'],
            array('checkbox-class' => $class, 'label-class' => $class)
        );

        $defaults = [];

        if (!empty($answers)) {
            foreach ($answers as $answer) {
                $defaults[$name.'['.$answer.']'] = true;
            }
        }

        $form->setDefaults($defaults);

    }
}
