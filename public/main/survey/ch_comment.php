<?php
/* For licensing terms, see /license.txt */

/**
 * Class ch_comment.
 */
class ch_comment extends survey_question
{
    /**
     * @param array  $questionData
     * @param string $answers
     */
    public function render(FormValidator $form, $questionData = [], $answers = '')
    {
        $name = 'question'.$questionData['question_id'];
        $form->addTextarea($name, null);
        $form->setDefaults([$name => $answers]);
    }
}
