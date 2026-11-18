<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Service\Exercise\Qti2ExactCombinationWriter;
use Chamilo\CoreBundle\Service\Exercise\Qti2UniqueAnswerUnknownMarker;

/**
 * Unique answer, Multiple answer, True/false and Exact combination questions as a QTI2 choiceInteraction.
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
        // Both multiple answer and exact combination allow selecting more than one option.
        $maxChoices = \in_array((int) $this->getQuestionType(), [MCMA, MULTIPLE_ANSWER_COMBINATION], true)
            ? ' maxChoices="0"'
            : '';
        $out = '    <choiceInteraction responseIdentifier="'.$questionIdent.'"'.$maxChoices.' >'."\n";
        $out .= '      <prompt><![CDATA['.formatExerciseQtiText($questionStatment).']]></prompt>'."\n";
        $type = (int) $this->getQuestionType();
        if (is_array($this->answerList)) {
            foreach ($this->answerList as $current_answer) {
                $identifier = $this->choiceIdentifier($current_answer, $type);
                $unknown = Qti2UniqueAnswerUnknownMarker::UNKNOWN_IDENTIFIER === $identifier;
                $out .= '<simpleChoice identifier="'.$identifier.'" fixed="'.($unknown ? 'true' : 'false').'">
                         <![CDATA['.formatExerciseQtiText($current_answer['answer']).']]>';
                if (isset($current_answer['comment']) && '' != $current_answer['comment']) {
                    $out .= '<feedbackInline identifier="'.$identifier.'">
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
        if (MULTIPLE_ANSWER_COMBINATION == $type) {
            $correct = [];
            foreach (is_array($this->answerList) ? $this->answerList : [] as $current_answer) {
                if (1 == $current_answer['correct']) {
                    $correct[] = 'answer_'.$current_answer['id'];
                }
            }

            return Qti2ExactCombinationWriter::declarations($questionIdent, $correct, $this->getExactCombinationScore($question));
        }

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
                $identifier = $this->choiceIdentifier($current_answer, $type);
                if ($current_answer['correct'] && Qti2UniqueAnswerUnknownMarker::UNKNOWN_IDENTIFIER !== $identifier) {
                    $out .= '      <value>'.$identifier.'</value>'."\n";
                }
            }
            $out .= '    </correctResponse>'."\n";
        }

        // Add the grading
        if (is_array($this->answerList)) {
            $out .= '    <mapping>'."\n";
            foreach ($this->answerList as $current_answer) {
                if (isset($current_answer['grade'])) {
                    $identifier = $this->choiceIdentifier($current_answer, $type);
                    $weight = Qti2UniqueAnswerUnknownMarker::UNKNOWN_IDENTIFIER === $identifier ? 0 : $current_answer['grade'];
                    $out .= ' <mapEntry mapKey="'.$identifier.'" mappedValue="'.$weight.'" />'."\n";
                }
            }
            $out .= '    </mapping>'."\n";
        }

        $out .= '  </responseDeclaration>'."\n";
        if (UNIQUE_ANSWER_NO_OPTION === $type) {
            $out .= '  <outcomeDeclaration identifier="SCORE" cardinality="single" baseType="float">'
                .'<defaultValue><value>0</value></defaultValue></outcomeDeclaration>'."\n";
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $answer
     */
    private function choiceIdentifier(array $answer, int $type): string
    {
        $position = (int) ($this->position[$answer['id']] ?? 0);

        return UNIQUE_ANSWER_NO_OPTION === $type
            ? Qti2UniqueAnswerUnknownMarker::identifier((int) $answer['id'], $position)
            : 'answer_'.$answer['id'];
    }

    /**
     * The score of an Exact combination question: Chamilo grants the weight of the first answer when the
     * exact set of correct answers is ticked (Exercise::manage_answer()).
     */
    public function getExactCombinationScore(?Question $question = null): float
    {
        return Qti2ExactCombinationWriter::score(
            [(float) ($this->weighting[1] ?? 0)],
            null !== $question ? (float) $question->weighting : 0.0
        );
    }
}
