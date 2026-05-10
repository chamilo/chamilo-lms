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
        $this->getForm()->addRadio('horizontalvertical', get_lang('Display'), $options);

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
        $displayMode = strtolower(trim((string) ($questionData['display'] ?? 'horizontal')));
        if ('vertical' !== $displayMode) {
            $displayMode = 'horizontal';
        }

        $isVertical = 'vertical' === $displayMode;
        $class = $isVertical ? 'checkbox-vertical' : 'checkbox-inline';
        $labelClass = $isVertical ? 'checkbox-vertical' : 'checkbox-inline';

        $name = 'question'.$questionData['question_id'];
        $form->addHtml('<div class="survey-answer-options survey-answer-options-'.$displayMode.'" data-display="'.$displayMode.'">');
        $form->addCheckBoxGroup(
            $name,
            null,
            $questionData['options'],
            ['checkbox-class' => $class, 'label-class' => $labelClass]
        );
        $form->addHtml('</div>');

        $defaults = [];
        if (!empty($answers)) {
            foreach ($answers as $answer) {
                $defaults[$name.'['.$answer.']'] = true;
            }
        }

        $form->setDefaults($defaults);
    }
}
