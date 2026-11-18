<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use const ENT_QUOTES;
use const ENT_XML1;

/**
 * Chamilo-specific hints for a QTI 2.1 single-choice item with a fixed "Don't know" option.
 * The label is optional in QTI and does not change standard choiceInteraction semantics.
 */
final class Qti2UniqueAnswerUnknownMarker
{
    public const LABEL = 'chamilo:unique-answer-unknown';
    public const UNKNOWN_IDENTIFIER = 'chamilo_unknown';
    public const UNKNOWN_POSITION = 666;

    public static function isMarked(string $label): bool
    {
        return self::LABEL === $label;
    }

    public static function identifier(int $id, int $position): string
    {
        return self::UNKNOWN_POSITION === $position ? self::UNKNOWN_IDENTIFIER : 'answer_'.$id;
    }

    /**
     * Score a single-choice response from its mapping, including zero for the fixed unknown answer.
     * A named response identifier avoids relying on predefined template variable names.
     */
    public static function responseProcessing(string $responseId): string
    {
        $identifier = htmlspecialchars($responseId, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return "  <responseProcessing>\n"
            .'    <setOutcomeValue identifier="SCORE"><mapResponse identifier="'.$identifier.'"/></setOutcomeValue>'."\n"
            ."  </responseProcessing>\n";
    }

    /**
     * @param array<string, mixed> $question
     */
    public static function hasValidUnknownChoice(array $question): bool
    {
        $answers = $question['answer'] ?? null;
        $correct = $question['correct_answers'] ?? null;
        if (!\is_array($answers) || !\is_array($correct)
            || \count($answers) < 2 || !\array_key_exists(self::UNKNOWN_IDENTIFIER, $answers)
            || 1 !== \count($correct)) {
            return false;
        }

        $unknown = $answers[self::UNKNOWN_IDENTIFIER];
        if (!\is_array($unknown) || '' === trim(strip_tags((string) ($unknown['value'] ?? '')))) {
            return false;
        }

        $correctIdentifier = (string) reset($correct);

        return self::UNKNOWN_IDENTIFIER !== $correctIdentifier && \array_key_exists($correctIdentifier, $answers);
    }
}
