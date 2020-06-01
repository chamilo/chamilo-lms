<?php
/* For licensing terms, see /license.txt */

/**
 * Class ch_multipleresponse.
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
        $options = [
            'horizontal' => get_lang('Horizontal'),
            'vertical' => get_lang('Vertical'),
        ];
        $this->getForm()->addRadio('horizontalvertical', get_lang('DisplayAnswersHorVert'), $options);

        $formData['horizontalvertical'] = isset($formData['horizontalvertical']) ? $formData['horizontalvertical'] : 'horizontal';
        $this->getForm()->setDefaults($formData);

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
    public function render(
        FormValidator $form,
        $questionData = [],
        $answers = []
    ) {
        $class = 'checkbox-inline';
        $labelClass = 'checkbox-inline';
        if ('vertical' == $questionData['display']) {
            $class = 'checkbox-vertical';
        }

        $name = 'question'.$questionData['question_id'];
        $form->addCheckBoxGroup(
            $name,
            null,
            $questionData['options'],
            ['checkbox-class' => $class, 'label-class' => $labelClass]
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
