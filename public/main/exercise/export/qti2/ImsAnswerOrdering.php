<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Service\Exercise\Qti2OrderingItem;

/**
 * Legacy QTI adapter for Sequence ordering (type 18).
 */
class ImsAnswerOrdering extends Answer implements ImsAnswerInterface
{
    private string $itemBody = '';
    private string $declaration = '';
    private string $processing = '';

    public function imsExportResponsesDeclaration($questionIdent, Question $question = null)
    {
        $this->build($questionIdent, $question);

        return $this->declaration;
    }

    public function imsExportResponses($questionIdent, $questionStatment, $questionDesc = '', $questionMedia = '')
    {
        return $this->itemBody;
    }

    public function isValidOrdering(): bool
    {
        return '' !== $this->declaration;
    }

    public function getOrderingResponseProcessing(): string
    {
        return $this->processing;
    }

    private function build(string $responseId, ?Question $question): void
    {
        $this->read();
        $rows = $this->getAnswersList(true);
        $options = [];
        $items = [];
        foreach ($rows as $row) {
            if ((int) $row['correct'] === 0) {
                $options[$this->selectAutoId($row['id'])] = \count($options) + 1;
            }
        }
        foreach ($rows as $row) {
            $target = (int) $row['correct'];
            if ($target <= 0) {
                continue;
            }
            $items[] = [
                'identifier' => 'answer_'.$this->selectAutoId($row['id']),
                'text' => (string) $row['answer'],
                'target' => $options[$target] ?? $target,
                'weight' => max(0.0, (float) $row['grade']),
            ];
        }
        $xml = Qti2OrderingItem::write(
            $responseId,
            (string) ($question?->question ?? ''),
            $items,
            (string) ($question?->extra ?? 'h')
        );
        if (null === $xml) {
            return;
        }
        $bodyStart = strpos($xml, '  <itemBody>');
        $bodyEnd = strpos($xml, '  <responseProcessing>');
        if (false === $bodyStart || false === $bodyEnd) {
            return;
        }
        $this->declaration = substr($xml, 0, $bodyStart);
        $this->itemBody = substr($xml, $bodyStart + \strlen("  <itemBody>\n"), $bodyEnd - $bodyStart - \strlen("  <itemBody>\n") - \strlen("  </itemBody>\n"));
        $this->processing = substr($xml, $bodyEnd);
    }
}
