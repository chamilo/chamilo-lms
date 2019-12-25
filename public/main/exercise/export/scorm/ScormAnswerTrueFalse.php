<?php

/* For licensing terms, see /license.txt */

/**
 * This class handles the SCORM export of true/false questions.
 */
class ScormAnswerTrueFalse extends Answer
{
    /**
     * Return the XML flow for the possible answers.
     * That's one <response_lid>, containing several <flow_label>.
     *
     * @author Amand Tihon <amand@alrj.org>
     */
    public function export()
    {
        $js = '';
        $html = '<tr><td colspan="2"><table width="100%">';
        $identifier = 'question_'.$this->questionJSId.'_tf';
        $identifier_true = $identifier.'_true';
        $identifier_false = $identifier.'_false';
        $html .=
            '<tr>
                <td align="center" width="5%">
                <input name="'.$identifier_true.'" id="'.$identifier_true.'" value="'.$this->trueGrade.'" type="radio" />
                </td>
                <td width="95%">
                <label for="'.$identifier_true.'">'.get_lang('True').'</label>
                </td>
                </tr>';
        $html .=
            '<tr>
            <td align="center" width="5%">
            <input name="'.$identifier_false.'" id="'.$identifier_false.'" value="'.$this->falseGrade.'" type="radio" />
            </td>
            <td width="95%">
            <label for="'.$identifier_false.'">'.get_lang('False').'</label>
            </td>
            </tr></table></td></tr>';
        $js .= 'questions_answers['.$this->questionJSId.'] = new Array(\'true\',\'false\');'."\n";
        $js .= 'questions_types['.$this->questionJSId.'] = \'tf\';'."\n";
        if ('TRUE' === $this->response) {
            $js .= 'questions_answers_correct['.$this->questionJSId.'] = new Array(\'true\');'."\n";
        } else {
            $js .= 'questions_answers_correct['.$this->questionJSId.'] = new Array(\'false\');'."\n";
        }
        $jstmpw = 'questions_answers_ponderation['.$this->questionJSId.'] = new Array();'."\n";
        $jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'][0] = 0;'."\n";
        $jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'][1] = '.$this->weighting[1].";\n";
        $js .= $jstmpw;

        return [$js, $html];
    }
}
