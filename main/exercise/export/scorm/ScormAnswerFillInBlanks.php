<?php

/* For licensing terms, see /license.txt */

/**
 * This class handles the SCORM export of fill-in-the-blanks questions.
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
        $js = '';
        // get all enclosed answers
        $blankList = [];
        foreach ($this->answer as $i => $answer) {
            $blankList[] = '['.$answer.']';
        }

        // splits text and weightings that are joined with the character '::'
        $listAnswerInfo = FillBlanks::getAnswerInfo($answer);
        //$switchableAnswerSet = $listAnswerInfo['switchable'];

        // display empty [input] with the right width for student to fill it
        $answer = '';
        $answerList = [];
        for ($i = 0; $i < count($listAnswerInfo['common_words']) - 1; $i++) {
            // display the common words
            $answer .= $listAnswerInfo['common_words'][$i];
            // display the blank word
            $attributes['style'] = 'width:'.$listAnswerInfo['input_size'][$i].'px';
            $answer .= FillBlanks::getFillTheBlankHtml(
                $this->questionJSId,
                $this->questionJSId + 1,
                '',
                $attributes,
                $answer,
                $listAnswerInfo,
                true,
                $i,
                'question_'.$this->questionJSId.'_fib_'.($i + 1)
            );
            $answerList[] = $i + 1;
        }

        // display the last common word
        $answer .= $listAnswerInfo['common_words'][$i];

        // because [] is parsed here we follow this procedure:
        // 1. find everything between the [ and ] tags
        $jstmpw = 'questions_answers_ponderation['.$this->questionJSId.'] = new Array();'."\n";
        $jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'][0] = 0;'."\n";

        foreach ($listAnswerInfo['weighting'] as $key => $weight) {
            $jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.']['.($key + 1).'] = '.$weight.";\n";
        }

        $wordList = "'".implode("', '", $listAnswerInfo['words'])."'";
        $answerList = "'".implode("', '", $answerList)."'";

        $html = '<tr><td colspan="2"><table width="100%">';
        $html .= '<tr>
            <td>
            '.$answer.'
            </td>
            </tr></table></td></tr>';
        $js .= 'questions_answers['.$this->questionJSId.'] = new Array('.$answerList.');'."\n";
        $js .= 'questions_answers_correct['.$this->questionJSId.'] = new Array('.$wordList.');'."\n";
        $js .= 'questions_types['.$this->questionJSId.'] = \'fib\';'."\n";
        $js .= $jstmpw;

        return [$js, $html];
    }
}
