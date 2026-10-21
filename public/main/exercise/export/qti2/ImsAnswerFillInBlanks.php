<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Service\Exercise\Qti2FillBlanksWriter;

/**
 * Exports a Fill in blanks question: typed blanks as textEntryInteraction and drop-down menus as
 * inlineChoiceInteraction, each blank with its own "fill_N" response and score.
 * The markup is shared with the API exporter (Qti2FillBlanksWriter).
 */
class ImsAnswerFillInBlanks extends Answer implements ImsAnswerInterface
{
    /**
     * Export the text with the blanks as interactions.
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
        $fillBlanks = $this->getFillBlanks();

        return null !== $fillBlanks ? $fillBlanks['body'] : '';
    }

    public function imsExportResponsesDeclaration($questionIdent, Question $question = null)
    {
        $caseInsensitive = null !== $question && 'case:false' === $question->extra;
        $fillBlanks = $this->getFillBlanks($caseInsensitive);

        return null !== $fillBlanks ? $fillBlanks['declarations'] : '';
    }

    /**
     * @return array{declarations: string, body: string, blankCount: int}|null
     */
    private function getFillBlanks(bool $caseInsensitive = false): ?array
    {
        // The whole question (text, blanks and scores) is stored in its first answer row.
        foreach ($this->getAnswersList(true) as $answer) {
            return Qti2FillBlanksWriter::write((string) $answer['answer'], $caseInsensitive);
        }

        return null;
    }
}
