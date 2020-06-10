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
        $header = '<script>';
        $header .= file_get_contents(api_get_path(SYS_CODE_PATH).'inc/lib/javascript/hotspot/js/hotspot.js');
        $header .= '</script>';

        if ($this->standalone) {
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

        $answerList = '<div 
            style="padding: 10px; 
            margin-left: -8px; 
            border: 1px solid #4271b5; 
            height: 448px; 
            width: 200px;"><b>'.get_lang('HotspotZones').'</b><ol>';
        for ($answerId = 1; $answerId <= $nbrAnswers; $answerId++) {
            $answerList .= '<li>'.$this->selectAnswer($answerId).'</li>';
        }
        $answerList .= '</ol></div>';
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
                    $answerList
                </td>
            <tr>
HTML;
        $html .= '</table></td></tr>';

        // currently the free answers cannot be displayed, so ignore the textarea
        $html = '<tr><td colspan="2">'.get_lang('ThisItemIsNotExportable').'</td></tr>';

        return [$js, $html];
    }
}
