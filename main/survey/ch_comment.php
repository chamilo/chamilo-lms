<?php
/* For licensing terms, see /license.txt */

/**
 * Class ch_comment
 */
class ch_comment extends survey_question
{
    /**
     * @param FormValidator $form
     * @param array $questionData
     * @param string $answers
     */
    public function render(FormValidator $form, $questionData = array(), $answers = '')
    {
        if (is_array($answers)) {
            $content = implode('', $answers);
        } else {
            $content = $answers;
        }

        $name = 'question'.$questionData['question_id'];
        $form->addTextarea($name, null);
        $form->setDefaults([$name => $answers]);
    }
}

