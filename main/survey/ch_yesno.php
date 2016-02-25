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

        // The options
        $config = array('ToolbarSet' => 'Survey', 'Width' => '100%', 'Height' => '120');
        $this->getForm()->addHtmlEditor('answers[0]', get_lang('AnswerOptions'), true, false, $config);
        $this->getForm()->addHtmlEditor('answers[1]', null, true, false, $config);
    }

    /**
     * @param FormValidator $form
     * @param array $questionData
     * @param array $answers
     */
    public function render(FormValidator $form, $questionData = array(), $answers = null)
    {
        if (is_array($questionData['options'])) {
            if ($questionData['display'] == 'vertical') {
                $class = 'radio';
            } else {
                $class = 'radio-inline';
            }

            $name = 'question' . $questionData['question_id'];

            $form->addRadio(
                $name,
                null,
                $questionData['options'],
                ['radio-class' => $class, 'label-class' => $class]
            );

            if (!empty($answers)) {
                $form->setDefaults([$name => $answers]);
            }
        }
    }
}
