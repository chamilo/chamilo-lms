<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use DOMElement;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const ENT_XML1;

/**
 * QTI 2.1 markup of a Chamilo "Exact combination" question (MULTIPLE_ANSWER_COMBINATION), shared by the
 * legacy and the API exporters and read back by both importers.
 *
 * The learner ticks any number of choices and gets the whole question score only when the ticked set is
 * exactly the set of correct choices, 0 otherwise (Exercise::manage_answer()). Chamilo stores that score
 * on the first answer, the other answers weighing 0.
 *
 * In QTI this is a multiple-cardinality identifier response with a correctResponse and no mapping, a
 * choiceInteraction without a choice limit, and a responseProcessing that sets SCORE to the question
 * score when the response matches the correct response (the QTI "match" operator compares multiple
 * containers as sets), and to 0 otherwise.
 */
final class Qti2ExactCombinationWriter
{
    /**
     * The score Chamilo grants for the exact combination: the weight of the first answer, falling back
     * to the question score when that answer carries none.
     *
     * @param list<float> $answerWeightsByPosition
     */
    public static function score(array $answerWeightsByPosition, float $questionWeighting): float
    {
        $first = $answerWeightsByPosition[0] ?? 0.0;

        return $first > 0 ? $first : max(0.0, $questionWeighting);
    }

    /**
     * @param list<string> $correctChoiceIdentifiers
     */
    public static function declarations(string $responseIdentifier, array $correctChoiceIdentifiers, float $score): string
    {
        $xml = '  <responseDeclaration identifier="'.self::attribute($responseIdentifier).'" cardinality="multiple" baseType="identifier">'."\n";
        if ([] !== $correctChoiceIdentifiers) {
            $xml .= "    <correctResponse>\n";
            foreach ($correctChoiceIdentifiers as $identifier) {
                $xml .= '      <value>'.self::text($identifier).'</value>'."\n";
            }
            $xml .= "    </correctResponse>\n";
        }
        $xml .= "  </responseDeclaration>\n";
        $xml .= '  <outcomeDeclaration identifier="SCORE" cardinality="single" baseType="float" normalMaximum="'.self::formatScore($score).'"/>'."\n";

        return $xml;
    }

    public static function responseProcessing(string $responseIdentifier, float $score): string
    {
        $identifier = self::attribute($responseIdentifier);

        return "  <responseProcessing>\n"
            ."    <responseCondition>\n"
            ."      <responseIf>\n"
            .'        <match><variable identifier="'.$identifier.'"/><correct identifier="'.$identifier.'"/></match>'."\n"
            .'        <setOutcomeValue identifier="SCORE"><baseValue baseType="float">'.self::formatScore($score).'</baseValue></setOutcomeValue>'."\n"
            ."      </responseIf>\n"
            ."      <responseElse>\n"
            .'        <setOutcomeValue identifier="SCORE"><baseValue baseType="float">0</baseValue></setOutcomeValue>'."\n"
            ."      </responseElse>\n"
            ."    </responseCondition>\n"
            ."  </responseProcessing>\n";
    }

    /**
     * Reads back the score of the responseProcessing written by responseProcessing(): a responseIf whose
     * condition is a match between a response and its correct value and which sets SCORE. Returns null
     * for any other processing (templates, mappings...), so other items keep their usual import.
     */
    public static function readScore(DOMElement $responseProcessing): ?float
    {
        if ('' !== $responseProcessing->getAttribute('template')) {
            return null;
        }

        foreach (self::descendants($responseProcessing, 'responseIf') as $responseIf) {
            $match = self::children($responseIf, 'match')[0] ?? null;
            if (null === $match || [] === self::children($match, 'correct') || [] === self::children($match, 'variable')) {
                continue;
            }

            foreach (self::children($responseIf, 'setOutcomeValue') as $setOutcome) {
                $baseValue = self::children($setOutcome, 'baseValue')[0] ?? null;
                if ('SCORE' === $setOutcome->getAttribute('identifier') && null !== $baseValue && is_numeric(trim($baseValue->textContent))) {
                    $score = (float) trim($baseValue->textContent);

                    return is_finite($score) ? max(0.0, $score) : null;
                }
            }
        }

        return null;
    }

    /**
     * @return list<DOMElement>
     */
    private static function children(DOMElement $parent, string $localName): array
    {
        $found = [];
        foreach ($parent->childNodes as $child) {
            if ($child instanceof DOMElement && $localName === ($child->localName ?? $child->nodeName)) {
                $found[] = $child;
            }
        }

        return $found;
    }

    /**
     * @return list<DOMElement>
     */
    private static function descendants(DOMElement $parent, string $localName): array
    {
        $found = [];
        foreach ($parent->getElementsByTagName('*') as $element) {
            if ($element instanceof DOMElement && $localName === ($element->localName ?? $element->nodeName)) {
                $found[] = $element;
            }
        }

        return $found;
    }

    private static function attribute(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_XML1, 'UTF-8');
    }

    private static function text(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_XML1, 'UTF-8');
    }

    private static function formatScore(float $score): string
    {
        $formatted = rtrim(rtrim(\sprintf('%.6F', $score), '0'), '.');

        return '' === $formatted || '-0' === $formatted ? '0' : $formatted;
    }
}
