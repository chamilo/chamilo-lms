<?php
/* For licensing terms, see /license.txt */

/**
 * Class ch_open
 */
class ch_open extends survey_question
{
    public function render(FormValidator $form, $questionData = array(), $answers = array())
    {
        if (is_array($answers)) {
            $content = implode('', $answers);
        } else {
            $content = $answers;
        }

        $form->addTextarea("question".$questionData['question_id'], null);
         //<textarea name="question'.$questionData['question_id'].'" id="textarea" style="width: 400px; height: 130px;">'.$content.'</textarea>';

    }
}
