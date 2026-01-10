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
        $this->answerList = $this->getAnswersList(true);

        // Keep legacy behavior: prefer $this->answerText (it usually contains the [..] placeholders).
        // Fallback to the provided statement if answerText is empty.
        $text = isset($this->answerText) ? (string) $this->answerText : '';
        if ($text === '') {
            $text = (string) $questionStatment;
        }

        if (is_array($this->answerList)) {
            foreach ($this->answerList as $key => $answer) {
                $key = $answer['id'];
                $answer = $answer['answer'];
                $len = api_strlen($answer);

                $text = str_replace(
                    '['.$answer.']',
                    '<textEntryInteraction responseIdentifier="fill_'.$key.'" expectedLength="'.api_strlen($answer).'"/>',
                    $text
                );
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
