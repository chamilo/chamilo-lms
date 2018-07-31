<?php
/* For licensing terms, see /license.txt */

/**
 * This class handles the SCORM export of fill-in-the-blanks questions.
 *
 * @package chamilo.exercise.scorm
 */
class ScormAnswerFillInBlanks extends Answer
{
    /**
     * Export the text with missing words.
     *
     * As a side effect, it stores two lists in the class :
     * the missing words and their respective weightings.
     */
    public function export()
    {
        global $charset;
        $js = '';
        $html = '<tr><td colspan="2"><table width="100%">';
        // get all enclosed answers
        $blankList = [];
        foreach ($this->answer as $i => $answer) {
            $blankList[] = '['.$answer.']';
        }

        // splits text and weightings that are joined with the character '::'
        list($answer, $weight) = explode('::', $answer);

        $switchable = explode('@', $weight);
        $isSetSwitchable = false;
        if (isset($switchable[1]) && $switchable[1] == 1) {
            $isSetSwitchable = true;
        }

        $weights = explode(',', $switchable[0]);
        // because [] is parsed here we follow this procedure:
        // 1. find everything between the [ and ] tags
        $i = 1;
        $jstmp = '';
        $jstmpc = '';
        $jstmpw = 'questions_answers_ponderation['.$this->questionJSId.'] = new Array();'."\n";
        $jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'][0] = 0;'."\n";
        $startlocations = api_strpos($answer, '[');
        $endlocations = api_strpos($answer, ']');
        while ($startlocations !== false && $endlocations !== false) {
            $texstring = api_substr($answer, $startlocations, ($endlocations - $startlocations) + 1);
            $replaceText = '<input 
                type="text" 
                name="question_'.$this->questionJSId.'_fib_'.$i.'" 
                id = "question_'.$this->questionJSId.'_fib_'.$i.'" 
                size="10" value="" 
                />';
            $answer = api_substr_replace(
                $answer,
                $replaceText,
                $startlocations,
                ($endlocations - $startlocations) + 1
            );
            $jstmp .= $i.',';
            if (!empty($texstring)) {
                $sub = api_substr($texstring, 1, -1);
                if (!empty($sub)) {
                    $jstmpc .= "'".api_htmlentities($sub, ENT_QUOTES, $charset)."',";
                }
            }
            $my_weight = explode('@', $weights[$i - 1]);
            if (count($my_weight) == 2) {
                $weight_db = $my_weight[0];
            } else {
                $weight_db = $my_weight[0];
            }
            $jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.']['.$i.'] = '.$weight_db.";\n";
            $i++;
            $startlocations = api_strpos($answer, '[');
            $endlocations = api_strpos($answer, ']');
        }

        $html .= '<tr>
            <td>
            '.$answer.'
            </td>
            </tr></table></td></tr>';
        $js .= 'questions_answers['.$this->questionJSId.'] = new Array('.api_substr($jstmp, 0, -1).');'."\n";
        $js .= 'questions_answers_correct['.$this->questionJSId.'] = new Array('.api_substr($jstmpc, 0, -1).');'."\n";
        $js .= 'questions_types['.$this->questionJSId.'] = \'fib\';'."\n";
        $js .= $jstmpw;

        return [$js, $html];
    }
}
