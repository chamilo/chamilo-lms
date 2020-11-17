<?php

/**
 * Class.
 */
class ImsAnswerFillInBlanks extends Answer implements ImsAnswerInterface
{
    private $answerList = [];
    private $gradeList = [];

    /**
     * Export the text with missing words.
     */
    public function imsExportResponses($questionIdent, $questionStatment)
    {
        $this->answerList = $this->getAnswersList(true);
        $text = isset($this->answerText) ? $this->answerText : '';
        if (is_array($this->answerList)) {
            foreach ($this->answerList as $key => $answer) {
                $key = $answer['id'];
                $answer = $answer['answer'];
                $len = api_strlen($answer);
                $text = str_replace('['.$answer.']', '<textEntryInteraction responseIdentifier="fill_'.$key.'" expectedLength="'.api_strlen($answer).'"/>', $text);
            }
        }

        return $text;
    }

    public function imsExportResponsesDeclaration($questionIdent, Question $question = null)
    {
        $this->answerList = $this->getAnswersList(true);
        $this->gradeList = $this->getGradesList();
        $out = '';
        if (is_array($this->answerList)) {
            foreach ($this->answerList as $answer) {
                $answerKey = $answer['id'];
                $answer = $answer['answer'];
                $out .= '  <responseDeclaration identifier="fill_'.$answerKey.'" cardinality="single" baseType="identifier">'."\n";
                $out .= '    <correctResponse>'."\n";
                $out .= '      <value><![CDATA['.formatExerciseQtiText($answer).']]></value>'."\n";
                $out .= '    </correctResponse>'."\n";
                if (isset($this->gradeList[$answerKey])) {
                    $out .= '    <mapping>'."\n";
                    $out .= '      <mapEntry mapKey="'.$answer.'" mappedValue="'.$this->gradeList[$answerKey].'"/>'."\n";
                    $out .= '    </mapping>'."\n";
                }

                $out .= '  </responseDeclaration>'."\n";
            }
        }

        return $out;
    }
}
