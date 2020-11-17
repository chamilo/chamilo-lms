<?php

/**
 * Class.
 */
class ImsAnswerFree extends Answer implements ImsAnswerInterface
{
    /**
     * @todo implement
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
        $questionDesc = formatExerciseQtiText($questionDesc);

        return '<extendedTextInteraction responseIdentifier="'.$questionIdent.'" >
            <prompt>
            '.$questionDesc.'
            </prompt>
            </extendedTextInteraction>';
    }

    public function imsExportResponsesDeclaration($questionIdent, Question $question = null)
    {
        $out = '  <responseDeclaration identifier="'.$questionIdent.'" cardinality="single" baseType="string">';
        $out .= '<outcomeDeclaration identifier="SCORE" cardinality="single" baseType="float">
                <defaultValue><value>'.$question->weighting.'</value></defaultValue></outcomeDeclaration>';
        $out .= '  </responseDeclaration>'."\n";

        return $out;
    }
}
