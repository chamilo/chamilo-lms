<?php

/* For licensing terms, see /license.txt */

class ch_selectivedisplay extends ch_yesno
{
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
            $radioAttributes = [
                'radio-class' => $class,
                'label-class' => $labelClass,
                'class' => 'survey_selective_input',
            ];

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

    public static function getJs()
    {
        return '<script>
            $(function() {
                var hideQuestion = false;
                $(".survey_question").each(function() {
                    var questionClass = $(this).attr("class").trim();
                    if (hideQuestion) {
                        $(this).hide();
                        if (questionClass === "survey_question ch_selectivedisplay") {
                            $(this).show();
                        }
                    }
                    if (questionClass === "survey_question ch_selectivedisplay") {
                        hideQuestion = true;
                    }
                });

                $(".survey_selective_input").on("click", function() {
                   var parent = $(this).parent().parent().parent().parent();
                   var next = parent.nextAll();
                   var visible = $(this).attr("data-order") == 1;

                   next.each(function() {
                        if ($(this).attr("class") === "survey_question ch_selectivedisplay") {
                            return false;
                        }
                        if ($(this).attr("class") === "start-survey") {
                            return false;
                        }
                       if (visible) {
                           $(this).show();
                       } else {
                           $(this).hide();
                       }
                    });
                });
            });

            </script>';
    }
}
