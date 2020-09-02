<?php

/* For licensing terms, see /license.txt */

/**
 * Class.
 */
class ImsAnswerMultipleChoice extends Answer implements ImsAnswerInterface
{
    /**
     * Return the XML flow for the possible answers.
     *
     * @param string $questionIdent
     * @param string $questionStatment
     * @param string $questionDesc
     * @param string $questionMedia
     *
     * @return string
     */
    public function imsExportResponses($questionIdent, $questionStatment, $questionDesc = '', $questionMedia = '')
    {
        // @todo getAnswersList() converts the answers using api_html_entity_decode()
        $this->answerList = $this->getAnswersList(true);
        $out = '    <choiceInteraction responseIdentifier="'.$questionIdent.'" >'."\n";
        $out .= '      <prompt><![CDATA['.formatExerciseQtiText($questionStatment).']]></prompt>'."\n";
        if (is_array($this->answerList)) {
            foreach ($this->answerList as $current_answer) {
                $out .= '<simpleChoice identifier="answer_'.$current_answer['id'].'" fixed="false">
                         <![CDATA['.formatExerciseQtiText($current_answer['answer']).']]>';
                if (isset($current_answer['comment']) && '' != $current_answer['comment']) {
                    $out .= '<feedbackInline identifier="answer_'.$current_answer['id'].'">
                             <![CDATA['.formatExerciseQtiText($current_answer['comment']).']]>
                             </feedbackInline>';
                }
                $out .= '</simpleChoice>'."\n";
            }
        }
        $out .= '    </choiceInteraction>'."\n";

        return $out;
    }

    /**
     * Return the XML flow of answer ResponsesDeclaration.
     */
    public function imsExportResponsesDeclaration($questionIdent, Question $question = null)
    {
        $this->answerList = $this->getAnswersList(true);
        $type = $this->getQuestionType();
        if (MCMA == $type) {
            $cardinality = 'multiple';
        } else {
            $cardinality = 'single';
        }

        $out = '  <responseDeclaration identifier="'.$questionIdent.'" cardinality="'.$cardinality.'" baseType="identifier">'."\n";

        // Match the correct answers.
        if (is_array($this->answerList)) {
            $out .= '    <correctResponse>'."\n";
            foreach ($this->answerList as $current_answer) {
                if ($current_answer['correct']) {
                    $out .= '      <value>answer_'.$current_answer['id'].'</value>'."\n";
                }
            }
            $out .= '    </correctResponse>'."\n";
        }

        // Add the grading
        if (is_array($this->answerList)) {
            $out .= '    <mapping>'."\n";
            foreach ($this->answerList as $current_answer) {
                if (isset($current_answer['grade'])) {
                    $out .= ' <mapEntry mapKey="answer_'.$current_answer['id'].'" mappedValue="'.$current_answer['grade'].'" />'."\n";
                }
            }
            $out .= '    </mapping>'."\n";
        }

        $out .= '  </responseDeclaration>'."\n";

        return $out;
    }
}
