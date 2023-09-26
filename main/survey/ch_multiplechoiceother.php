<?php

/* For licensing terms, see /license.txt */

class ch_multiplechoiceother extends survey_question
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
                if ('other' === $value) {
                    continue;
                }
                $this->getForm()->addHtmlEditor('answers['.$key.']', null, false, false, $config);
                if ($total > 2) {
                    $this->getForm()->addButton("delete_answer[$key]", get_lang('Delete'), 'trash', 'danger');
                }
            }
        }

        if (isset($formData['answersid']) && !empty($formData['answersid'])) {
            $counter = 1;
            $total = count($formData['answersid']);
            foreach ($formData['answersid'] as $value) {
                if ($counter === $total) {
                    break;
                }
                $this->getForm()->addHidden('answersid[]', $value);
                $counter++;
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
        $otherId = 0;
        foreach ($questionData['options'] as $key => $option) {
            if ('other' === $option) {
                $otherId = $key;
            }
        }

        foreach ($questionData['options'] as &$option) {
            if ('other' === $option) {
                $option = '<p>'.get_lang('SurveyOtherAnswerSpecify').'</p>';
            }
        }
        $questionId = $questionData['question_id'];

        $display = 'display:none';
        $defaultOtherData = '';

        if (!empty($answers)) {
            $answers = self::decodeOptionValue($answers);
            if (isset($answers[1])) {

                $display = '';
                $defaultOtherData = $answers[1];
            }

            $answers = $answers[0];
        }

        $question->render($form, $questionData, $answers);
        $form->addHtml(
            '<script>
            $(function() {
                $("input:radio[name=\"question'.$questionId.'\"]").change(function() {
                    if ($(this).val() == "'.$otherId.'") {
                        $("#other_div_'.$questionId.'").show();
                    } else {
                        $("#other_div_'.$questionId.'").hide();
                        $("#other_question'.$questionId.'").val("");
                    }
                });
            });
            </script>'
        );

        $form->addHtml('<div id="other_div_'.$questionId.'" class="multiple_choice_other" style="'.$display.'">');
        $element = $form->addText(
            'other_question'.$questionId,
            get_lang('SurveyOtherAnswer'),
            false,
            ['id' => 'other_question'.$questionId]
        );
        $form->addHtml('</div>');

        if (!empty($answers) && !empty($defaultOtherData)) {
            $element->setValue($defaultOtherData);
            $element->freeze();
        }
    }

    public static function decodeOptionValue($value)
    {
        return explode('@:@', $value);
    }
}
