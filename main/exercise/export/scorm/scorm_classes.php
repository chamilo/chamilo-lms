<?php
/* For licensing terms, see /license.txt */

/**
 * This class handles the export to SCORM of a multiple choice question
 * (be it single answer or multiple answers).
 *
 * @package chamilo.exercise.scorm
 */
class ScormAnswerMultipleChoice extends Answer
{
    /**
     * Return HTML code for possible answers.
     */
    public function export()
    {
        $js = '';
        $html = '<tr><td colspan="2"><table width="100%">';
        $type = $this->getQuestionType();
        $jstmpw = 'questions_answers_ponderation['.$this->questionJSId.'] = new Array();';
        $jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'][0] = 0;';
        $jstmpw .= 'questions_answers_correct['.$this->questionJSId.'] = new Array();';

        //not sure if we are going to export also the MULTIPLE_ANSWER_COMBINATION to SCORM
        //if ($type == MCMA  || $type == MULTIPLE_ANSWER_COMBINATION ) {
        if ($type == MCMA) {
            $id = 1;
            $jstmp = '';
            $jstmpc = '';
            foreach ($this->answer as $i => $answer) {
                $identifier = 'question_'.$this->questionJSId.'_multiple_'.$i;
                $html .=
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
                $jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.']['.$i.'] = '.$this->weighting[$i].";";
                $jstmpw .= 'questions_answers_correct['.$this->questionJSId.']['.$i.'] = '.$this->correct[$i].';';
                $id++;
            }
            $js .= 'questions_answers['.$this->questionJSId.'] = new Array('.substr($jstmp, 0, -1).');'."\n";
            $js .= 'questions_types['.$this->questionJSId.'] = \'mcma\';'."\n";
            $js .= $jstmpw;
        } elseif ($type == MULTIPLE_ANSWER_COMBINATION) {
            $js = '';
            $id = 1;
            $jstmp = '';
            $jstmpc = '';
            foreach ($this->answer as $i => $answer) {
                $identifier = 'question_'.$this->questionJSId.'_exact_'.$i;
                $html .=
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
                $jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.']['.$i.'] = '.$this->weighting[$i].";";
                $jstmpw .= 'questions_answers_correct['.$this->questionJSId.']['.$i.'] = '.$this->correct[$i].";";
                $id++;
            }
            $js .= 'questions_answers['.$this->questionJSId.'] = new Array('.substr($jstmp, 0, -1).');';
            $js .= 'questions_types['.$this->questionJSId.'] = "exact";';
            $js .= $jstmpw;
        } else {
            $id = 1;
            $jstmp = '';
            $jstmpc = '';
            foreach ($this->answer as $i => $answer) {
                $identifier = 'question_'.$this->questionJSId.'_unique_'.$i;
                $identifier_name = 'question_'.$this->questionJSId.'_unique_answer';
                $html .=
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
                $jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.']['.$i.'] = '.$this->weighting[$i].";";
                $jstmpw .= 'questions_answers_correct['.$this->questionJSId.']['.$i.'] = '.$this->correct[$i].';';
                $id++;
            }
            $js .= 'questions_answers['.$this->questionJSId.'] = new Array('.substr($jstmp, 0, -1).');';
            $js .= 'questions_types['.$this->questionJSId.'] = \'mcua\';';
            $js .= $jstmpw;
        }
        $html .= '</table></td></tr>';

        return [$js, $html];
    }
}

/**
 * This class handles the SCORM export of true/false questions.
 *
 * @package chamilo.exercise.scorm
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
        if ($this->response === 'TRUE') {
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
        $weights = explode(',', $weight);
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
            $answer = api_substr_replace(
                $answer,
                '<input type="text" name="question_'.$this->questionJSId.'_fib_'.$i.'" id="question_'.$this->questionJSId.'_fib_'.$i.'" size="10" value="" />',
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

/**
 * This class handles the SCORM export of matching questions.
 *
 * @package chamilo.exercise.scorm
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
        $html = '<tr><td colspan="2"><table width="100%">';
        // prepare list of right proposition to allow
        // - easiest display
        // - easiest randomisation if needed one day
        // (here I use array_values to change array keys from $code1 $code2 ... to 0 1 ...)
        // get max length of displayed array

        $nbrAnswers = $this->selectNbrAnswers();
        $cpt1 = 'A';
        $cpt2 = 1;
        $Select = [];
        $qId = $this->questionJSId;
        $s = '';
        $jstmp = '';
        $jstmpc = '';
        $jstmpw = 'questions_answers_ponderation['.$this->questionJSId.'] = new Array();'."\n";
        $jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'][0] = 0;'."\n";

        for ($answerId = 1; $answerId <= $nbrAnswers; $answerId++) {
            $identifier = 'question_'.$qId.'_matching_';
            $answer = $this->selectAnswer($answerId);
            $answerCorrect = $this->isCorrect($answerId);
            $weight = $this->selectWeighting($answerId);
            $jstmp .= $answerId.',';

            if (!$answerCorrect) {
                // options (A, B, C, ...) that will be put into the list-box
                $Select[$answerId]['Lettre'] = $cpt1;
                // answers that will be shown at the right side
                $Select[$answerId]['Reponse'] = $answer;
                $cpt1++;
            } else {
                $s .= '<tr>';
                $s .= '<td width="40%" valign="top"><b>'.$cpt2.'</b>.&nbsp;'.$answer."</td>";
                $s .= '<td width="20%" align="center">&nbsp;&nbsp;<select name="'.$identifier.$cpt2.'" id="'.$identifier.$cpt2.'">';
                $s .= ' <option value="0">--</option>';
                // fills the list-box
                foreach ($Select as $key => $val) {
                    $s .= '<option value="'.$key.'">'.$val['Lettre'].'</option>';
                }  // end foreach()

                $s .= '</select>&nbsp;&nbsp;</td>';
                $s .= '<td width="40%" valign="top">';
                if (isset($Select[$cpt2])) {
                    $s .= '<b>'.$Select[$cpt2]['Lettre'].'.</b> '.$Select[$cpt2]['Reponse'];
                } else {
                    $s .= '&nbsp;';
                }
                $s .= "</td></tr>";

                $jstmpc .= '['.$answerCorrect.','.$cpt2.'],';

                $my_weight = explode('@', $weight);
                if (count($my_weight) == 2) {
                    $weight = $my_weight[0];
                } else {
                    $weight = $my_weight[0];
                }
                $jstmpw .= 'questions_answers_ponderation['.$qId.']['.$cpt2.'] = '.$weight.";\n";
                $cpt2++;

                // if the left side of the "matching" has been completely shown
                if ($answerId == $nbrAnswers) {
                    // if there remain answers to be shown on the right side
                    while (isset($Select[$cpt2])) {
                        $s .= '<tr>';
                        $s .= '<td width="60%" colspan="2">&nbsp;</td>';
                        $s .= '<td width="40%" valign="top">';
                        $s .= '<b>'.$Select[$cpt2]['Lettre'].'.</b> '.$Select[$cpt2]['Reponse'];
                        $s .= "</td></tr>";
                        $cpt2++;
                    }
                    // end while()
                }  // end if()
            }
        }
        $js .= 'questions_answers['.$this->questionJSId.'] = new Array('.substr($jstmp, 0, -1).');'."\n";
        $js .= 'questions_answers_correct['.$this->questionJSId.'] = new Array('.substr($jstmpc, 0, -1).');'."\n";
        $js .= 'questions_types['.$this->questionJSId.'] = \'matching\';'."\n";
        $js .= $jstmpw;
        $html .= $s;
        $html .= '</table></td></tr>'."\n";

        return [$js, $html];
    }
}

/**
 * This class handles the SCORM export of free-answer questions.
 *
 * @package chamilo.exercise.scorm
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

        if ($type == ORAL_EXPRESSION) {
            /*
            $template = new Template('');
            $template->assign('directory', '/tmp/');
            $template->assign('user_id', api_get_user_id());

            $layout = $template->get_template('document/record_audio.tpl');
            $html .= $template->fetch($layout);*/

            $html = '<tr><td colspan="2">'.get_lang('ThisItemIsNotExportable').'</td></tr>';

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

/**
 * This class handles the SCORM export of hotpot questions.
 *
 * @package chamilo.exercise.scorm
 */
class ScormAnswerHotspot extends Answer
{
    /**
     * Returns the javascript code that goes with HotSpot exercises.
     *
     * @return string The JavaScript code
     */
    public function get_js_header()
    {
        if ($this->standalone) {
            $header = '<script>';
            $header .= file_get_contents('../inc/lib/javascript/hotspot/js/hotspot.js');
            $header .= '</script>';
            //because this header closes so many times the <script> tag, we have to reopen our own
            $header .= '<script>';
            $header .= 'questions_answers['.$this->questionJSId.'] = new Array();'."\n";
            $header .= 'questions_answers_correct['.$this->questionJSId.'] = new Array();'."\n";
            $header .= 'questions_types['.$this->questionJSId.'] = \'hotspot\';'."\n";
            $jstmpw = 'questions_answers_ponderation['.$this->questionJSId.'] = new Array();'."\n";
            $jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'][0] = 0;'."\n";
            $jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'][1] = 0;'.";\n";
            $header .= $jstmpw;
        } else {
            $header = '';
            $header .= 'questions_answers['.$this->questionJSId.'] = new Array();'."\n";
            $header .= 'questions_answers_correct['.$this->questionJSId.'] = new Array();'."\n";
            $header .= 'questions_types['.$this->questionJSId.'] = \'hotspot\';'."\n";
            $jstmpw = 'questions_answers_ponderation['.$this->questionJSId.'] = new Array();'."\n";
            $jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'][0] = 0;'."\n";
            $jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'][1] = 0;'."\n";
            $header .= $jstmpw;
        }

        return $header;
    }

    /**
     * Export the text with missing words.
     *
     * As a side effect, it stores two lists in the class :
     * the missing words and their respective weightings.
     */
    public function export()
    {
        $js = $this->get_js_header();
        $html = '<tr><td colspan="2"><table width="100%">';
        // some javascript must be added for that kind of questions
        $html .= '';

        // Get the answers, make a list
        $nbrAnswers = $this->selectNbrAnswers();

        $answer_list = '<div style="padding: 10px; margin-left: -8px; border: 1px solid #4271b5; height: 448px; width: 200px;"><b>'.get_lang('HotspotZones').'</b><ol>';
        for ($answerId = 1; $answerId <= $nbrAnswers; $answerId++) {
            $answer_list .= '<li>'.$this->selectAnswer($answerId).'</li>';
        }
        $answer_list .= '</ol></div>';
        $canClick = true;
        $relPath = api_get_path(REL_PATH);
        $html .= <<<HTML
            <tr>
                <td>
                    <div id="hotspot-{$this->questionJSId}"></div>
                    <script>
                        document.addEventListener('DOMContentListener', function () {
                            new HotspotQuestion({
                                questionId: {$this->questionJSId},
                                selector: '#hotspot-{$this->questionJSId}',
                                for: 'user',
                                relPath: '$relPath'
                            });
                        });
                    </script>
                </td>
                <td>
                    $answer_list
                </td>
            <tr>
HTML;
        $html .= '</table></td></tr>';

        // currently the free answers cannot be displayed, so ignore the textarea
        $html = '<tr><td colspan="2">'.get_lang('ThisItemIsNotExportable').'</td></tr>';

        return [$js, $html];
    }
}
