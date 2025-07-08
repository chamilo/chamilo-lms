<?php

/* For licensing terms, see /license.txt */

/**
 * This class handles the SCORM export of free-answer questions.
 */
class ScormAnswerFree extends Answer
{
    /**
     * Export the text with missing words.
     *
     * As a side effect, it stores two lists in the class :
     * the missing words and their respective weightings.
     */
    public function export()
    {
        $js = '';
        $identifier = 'question_'.$this->questionJSId.'_free';
        // currently the free answers cannot be displayed, so ignore the textarea
        $html = '<tr><td colspan="2">';
        $type = $this->getQuestionType();

        if (ORAL_EXPRESSION == $type) {
            $html = '<tr><td colspan="2">'.get_lang('This learning object or activity is not SCORM compliant. That\'s why it is not exportable.').'</td></tr>';

            return [$js, $html];
        }

        $html .= '<textarea minlength="20" name="'.$identifier.'" id="'.$identifier.'" ></textarea>';
        $html .= '</td></tr>';
        $js .= 'questions_answers['.$this->questionJSId.'] = new Array();';
        $js .= 'questions_answers_correct['.$this->questionJSId.'] = "";';
        $js .= 'questions_types['.$this->questionJSId.'] = \'free\';';
        $jstmpw = 'questions_answers_ponderation['.$this->questionJSId.'] = "0";';
        $js .= $jstmpw;

        return [$js, $html];
    }
}
