<?php
/* For licensing terms, see /license.txt */

/**
 * Class ch_open.
 */
class ch_open extends survey_question
{
    /**
     * @param array  $questionData
     * @param string $answers
     */
    public function render(FormValidator $form, $questionData = [], $answers = '')
    {
        if (is_array($answers)) {
            $content = implode('', $answers);
        } else {
            $content = $answers;
        }

        $name = 'question'.$questionData['question_id'];
        $form->addTextarea($name, null);
        $form->setDefaults([$name => $content]);
    }
}
