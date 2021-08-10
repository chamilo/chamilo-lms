<?php

/* For licensing terms, see /license.txt */

/**
 * Class ch_yesno.
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

        $options = [
            'horizontal' => get_lang('Horizontal'),
            'vertical' => get_lang('Vertical'),
        ];
        $this->getForm()->addRadio('horizontalvertical', get_lang('DisplayAnswersHorVert'), $options);
        $formData['horizontalvertical'] = isset($formData['horizontalvertical']) ? $formData['horizontalvertical'] : 'horizontal';
        $this->getForm()->setDefaults($formData);

        // The options
        $config = [
            'ToolbarSet' => 'Survey',
            'Width' => '100%',
            'Height' => '120',
        ];

        $this->getForm()->addHtmlEditor(
            'answers[0]',
            get_lang('AnswerOptions'),
            true,
            false,
            $config
        );

        $this->getForm()->addHtmlEditor(
            'answers[1]',
            null,
            true,
            false,
            $config
        );

        if (isset($formData['answersid']) && !empty($formData['answersid'])) {
            foreach ($formData['answersid'] as $value) {
                $this->getForm()->addHidden('answersid[]', $value);
            }
        }
    }

    /**
     * @param array $questionData
     * @param array $answers
     */
    public function render(FormValidator $form, $questionData = [], $answers = null)
    {
        if (is_array($questionData['options'])) {
            $class = 'radio-inline';
            $labelClass = 'radio-inline';
            if ('vertical' === $questionData['display']) {
                $class = 'radio-vertical';
            }

            $name = 'question'.$questionData['question_id'];
            $radioAttributes = ['radio-class' => $class, 'label-class' => $labelClass];

            if (!empty($questionData['is_required'])) {
                $radioAttributes['required'] = 'required';
            }

            $form->addRadio(
                $name,
                null,
                $questionData['options'],
                $radioAttributes
            );

            if (!empty($answers)) {
                $form->setDefaults([$name => is_array($answers) ? current($answers) : $answers]);
            }
        }
    }
}
