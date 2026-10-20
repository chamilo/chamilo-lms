<?php

/**
 * Class.
 */
class ImsAnswerMatching extends Answer implements ImsAnswerInterface
{
    public $leftList = [];
    public $rightList = [];
    private $answerList = [];

    /**
     * Export the question part as a matrix-choice, with only one possible answer per line.
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
        $this->buildMatchingLists();
        $maxAssociation = max(count($this->leftList), count($this->rightList));

        $out = '<matchInteraction responseIdentifier="'.$questionIdent.'" maxAssociations="'.$maxAssociation.'">'."\n";
        $out .= '      <prompt><![CDATA['.formatExerciseQtiText($questionStatment).']]></prompt>'."\n";

        //add left column
        $out .= '  <simpleMatchSet>'."\n";
        if (is_array($this->leftList)) {
            foreach ($this->leftList as $leftKey => $leftElement) {
                $out .= '
                <simpleAssociableChoice identifier="left_'.$leftKey.'" >
                    <![CDATA['.formatExerciseQtiText($leftElement['answer']).']]>
                </simpleAssociableChoice>'."\n";
            }
        }

        $out .= '  </simpleMatchSet>'."\n";

        //add right column
        $out .= '  <simpleMatchSet>'."\n";
        $i = 0;

        if (is_array($this->rightList)) {
            foreach ($this->rightList as $rightKey => $rightElement) {
                $out .= '<simpleAssociableChoice identifier="right_'.$i.'" >
                        <![CDATA['.formatExerciseQtiText($rightElement['answer']).']]>
                        </simpleAssociableChoice>'."\n";
                $i++;
            }
        }
        $out .= '  </simpleMatchSet>'."\n";
        $out .= '</matchInteraction>'."\n";

        return $out;
    }

    public function imsExportResponsesDeclaration($questionIdent, Question $question = null)
    {
        $this->answerList = $this->getAnswersList(true);
        $this->buildMatchingLists();
        $out = '  <responseDeclaration identifier="'.$questionIdent.'" cardinality="multiple" baseType="directedPair">'."\n";
        $out .= '    <correctResponse>'."\n";

        $gradeArray = [];
        if (isset($this->leftList) && is_array($this->leftList)) {
            foreach ($this->leftList as $leftKey => $leftElement) {
                $i = 0;
                foreach ($this->rightList as $rightKey => $rightElement) {
                    if (($leftElement['match'] == $rightElement['code'])) {
                        $out .= '      <value>left_'.$leftKey.' right_'.$i.'</value>'."\n";
                        $gradeArray['left_'.$leftKey.' right_'.$i] = $leftElement['grade'];
                    }
                    $i++;
                }
            }
        }
        $out .= '    </correctResponse>'."\n";

        if (is_array($gradeArray)) {
            $out .= '    <mapping>'."\n";
            foreach ($gradeArray as $gradeKey => $grade) {
                $out .= '          <mapEntry mapKey="'.$gradeKey.'" mappedValue="'.$grade.'"/>'."\n";
            }
            $out .= '    </mapping>'."\n";
        }

        $out .= '  </responseDeclaration>'."\n";

        return $out;
    }

    /**
     * Split the answer rows into the items to match (left) and the options (right).
     * Option rows have correct = 0; item rows store the iid of their option in "correct".
     */
    private function buildMatchingLists(): void
    {
        $this->leftList = [];
        $this->rightList = [];
        foreach ($this->answerList as $answer) {
            if (empty($answer['correct'])) {
                $this->rightList[] = ['answer' => $answer['answer'], 'code' => $this->selectAutoId($answer['id'])];
            } else {
                $this->leftList[] = ['answer' => $answer['answer'], 'match' => $answer['correct'], 'grade' => $answer['grade']];
            }
        }
    }
}
