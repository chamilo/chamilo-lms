<?php

/* For licensing terms, see /license.txt */

/**
 * This class handles the export to SCORM of a multiple choice question
 * (be it single answer or multiple answers).
 */
class ScormAnswerMultipleChoice extends Answer
{
    /**
     * Return HTML code for possible answers.
     */
    public function export()
    {
        $js = [];
        $type = $this->getQuestionType();
        $questionId = $this->questionJSId;
        $jstmpw = 'questions_answers_ponderation['.$questionId.'] = new Array();';
        $jstmpw .= 'questions_answers_ponderation['.$questionId.'][0] = 0;';
        $jstmpw .= 'questions_answers_correct['.$questionId.'] = new Array();';
        $html = [];

        //not sure if we are going to export also the MULTIPLE_ANSWER_COMBINATION to SCORM
        //if ($type == MCMA  || $type == MULTIPLE_ANSWER_COMBINATION ) {
        if (MCMA == $type) {
            $id = 1;
            $jstmp = '';
            $jstmpc = '';
            foreach ($this->answer as $i => $answer) {
                $identifier = 'question_'.$questionId.'_multiple_'.$i;
                $html[] =
                    '<tr>
                    <td align="center" width="5%">
                    <input name="'.$identifier.'" id="'.$identifier.'" value="'.$i.'" type="checkbox" />
                    </td>
                    <td width="95%">
                    <label for="'.$identifier.'">'.Security::remove_XSS($this->answer[$i]).'</label>
                    </td>
                    </tr>';

                $jstmp .= $i.',';
                if ($this->correct[$i]) {
                    $jstmpc .= $i.',';
                }
                $jstmpw .= 'questions_answers_ponderation['.$questionId.']['.$i.'] = '.$this->weighting[$i].';';
                $jstmpw .= 'questions_answers_correct['.$questionId.']['.$i.'] = '.$this->correct[$i].';';
                $id++;
            }
            $js[] = 'questions_answers['.$questionId.'] = new Array('.substr($jstmp, 0, -1).');'."\n";
            $js[] = 'questions_types['.$questionId.'] = \'mcma\';'."\n";
            $js[] = $jstmpw;
        } elseif (MULTIPLE_ANSWER_COMBINATION == $type) {
            $js = [];
            $id = 1;
            $jstmp = '';
            $jstmpc = '';
            foreach ($this->answer as $i => $answer) {
                $identifier = 'question_'.$questionId.'_exact_'.$i;
                $html[] =
                    '<tr>
                    <td align="center" width="5%">
                    <input name="'.$identifier.'" id="'.$identifier.'" value="'.$i.'" type="checkbox" />
                    </td>
                    <td width="95%">
                    <label for="'.$identifier.'">'.Security::remove_XSS($this->answer[$i]).'</label>
                    </td>
                    </tr>';

                $jstmp .= $i.',';
                if ($this->correct[$i]) {
                    $jstmpc .= $i.',';
                }
                $jstmpw .= 'questions_answers_ponderation['.$questionId.']['.$i.'] = '.$this->weighting[$i].';';
                $jstmpw .= 'questions_answers_correct['.$questionId.']['.$i.'] = '.$this->correct[$i].';';
                $id++;
            }
            $js[] = 'questions_answers['.$questionId.'] = new Array('.substr($jstmp, 0, -1).');';
            $js[] = 'questions_types['.$questionId.'] = "exact";';
            $js[] = $jstmpw;
        } else {
            $id = 1;
            $jstmp = '';
            $jstmpc = '';
            foreach ($this->answer as $i => $answer) {
                $identifier = 'question_'.$questionId.'_unique_'.$i;
                $identifier_name = 'question_'.$questionId.'_unique_answer';
                $html[] =
                    '<tr>
                    <td align="center" width="5%">
                    <input name="'.$identifier_name.'" id="'.$identifier.'" value="'.$i.'" type="checkbox"/>
                    </td>
                    <td width="95%">
                    <label for="'.$identifier.'">'.Security::remove_XSS($this->answer[$i]).'</label>
                    </td>
                    </tr>';
                $jstmp .= $i.',';
                if ($this->correct[$i]) {
                    $jstmpc .= $i;
                }
                $jstmpw .= 'questions_answers_ponderation['.$questionId.']['.$i.'] = '.$this->weighting[$i].';';
                $jstmpw .= 'questions_answers_correct['.$questionId.']['.$i.'] = '.$this->correct[$i].';';
                $id++;
            }
            $js[] = 'questions_answers['.$questionId.'] = new Array('.substr($jstmp, 0, -1).');';
            $js[] = 'questions_types['.$questionId.'] = \'mcua\';';
            $js[] = $jstmpw;
        }

        $htmlResult = '<tr><td colspan="2"><table id="question_'.$questionId.'" width="100%">';
        $htmlResult .= implode("\n", $html);
        $htmlResult .= '</table></td></tr>';

        $js = implode("\n", $js);

        return [$js, $htmlResult];
    }
}
