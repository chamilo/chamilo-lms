<?php

/* For licensing terms, see /license.txt */

/**
 * This class handles the SCORM export of matching questions.
 */
class ScormAnswerMatching extends Answer
{
    /**
     * Export the question part as a matrix-choice, with only one possible answer per line.
     *
     * @author Amand Tihon <amand@alrj.org>
     */
    public function export()
    {
        $js = '';
        // prepare list of right proposition to allow
        // - easiest display
        // - easiest randomisation if needed one day
        // (here I use array_values to change array keys from $code1 $code2 ... to 0 1 ...)
        // get max length of displayed array
        $nbrAnswers = $this->selectNbrAnswers();
        $counter = 1;
        $questionId = $this->questionJSId;
        $jstmpw = 'questions_answers_ponderation['.$questionId.'] = new Array();'."\n";
        $jstmpw .= 'questions_answers_ponderation['.$questionId.'][0] = 0;'."\n";

        // Options (A, B, C, ...) that will be put into the list-box
        $options = [];
        $letter = 'A';
        for ($answerId = 1; $answerId <= $nbrAnswers; $answerId++) {
            $answerCorrect = $this->isCorrect($answerId);
            $answer = $this->selectAnswer($answerId);
            $realAnswerId = $this->selectAutoId($answerId);
            if (!$answerCorrect) {
                $options[$realAnswerId]['Lettre'] = $letter;
                // answers that will be shown at the right side
                $options[$realAnswerId]['Reponse'] = $answer;
                $letter++;
            }
        }

        $html = [];
        $jstmp = '';
        $jstmpc = '';

        // Answers
        for ($answerId = 1; $answerId <= $nbrAnswers; $answerId++) {
            $identifier = 'question_'.$questionId.'_matching_';
            $answer = $this->selectAnswer($answerId);
            $answerCorrect = $this->isCorrect($answerId);
            $weight = $this->selectWeighting($answerId);
            $jstmp .= $answerId.',';

            if ($answerCorrect) {
                $html[] = '<tr class="option_row">';
                $html[] = '<td width="40%" valign="top">&nbsp;'.$answer.'</td>';
                $html[] = '<td width="20%" align="center">&nbsp;&nbsp;';
                $html[] = '<select name="'.$identifier.$counter.'" id="'.$identifier.$counter.'">';
                $html[] = ' <option value="0">--</option>';
                // fills the list-box
                foreach ($options as $key => $val) {
                    $html[] = '<option value="'.$key.'">'.$val['Lettre'].'</option>';
                }

                $html[] = '</select>&nbsp;&nbsp;</td>';
                $html[] = '<td width="40%" valign="top">';
                foreach ($options as $key => $val) {
                    $html[] = '<b>'.$val['Lettre'].'.</b> '.$val['Reponse'].'<br />';
                }
                $html[] = '</td></tr>';

                $jstmpc .= '['.$answerCorrect.','.$counter.'],';

                $myWeight = explode('@', $weight);
                if (2 == count($myWeight)) {
                    $weight = $myWeight[0];
                } else {
                    $weight = $myWeight[0];
                }
                $jstmpw .= 'questions_answers_ponderation['.$questionId.']['.$counter.'] = '.$weight.";\n";
                $counter++;
            }
        }

        $js .= 'questions_answers['.$questionId.'] = new Array('.substr($jstmp, 0, -1).');'."\n";
        $js .= 'questions_answers_correct['.$questionId.'] = new Array('.substr($jstmpc, 0, -1).');'."\n";
        $js .= 'questions_types['.$questionId.'] = \'matching\';'."\n";
        $js .= $jstmpw;

        $htmlResult = '<tr><td colspan="2"><table id="question_'.$questionId.'" width="100%">';
        $htmlResult .= implode("\n", $html);
        $htmlResult .= '</table></td></tr>'."\n";

        return [$js, $htmlResult];
    }
}
