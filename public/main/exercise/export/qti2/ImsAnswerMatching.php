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
        $maxAssociation = max(count($this->leftList), count($this->rightList));

        $out = '<matchInteraction responseIdentifier="'.$questionIdent.'" maxAssociations="'.$maxAssociation.'">'."\n";
        $out .= $questionStatment;

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
        $out = '  <responseDeclaration identifier="'.$questionIdent.'" cardinality="single" baseType="identifier">'."\n";
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
}
