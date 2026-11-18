<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use DOMElement;

use const ENT_HTML5;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const ENT_XML1;
use const SORT_NUMERIC;

/**
 * QTI 2.1 orderInteraction markup and strict reading shared by both import/export paths.
 * Chamilo stores one zero-weight option row per position and weighted item rows whose
 * "correct" value is the target position; position-specific credit is preserved.
 */
final class Qti2OrderingItem
{
    /**
     * @param list<array{identifier: string, text: string, target: int, weight: float}> $items
     */
    public static function write(string $responseId, string $prompt, array $items, string $orientation = 'h'): ?string
    {
        $count = \count($items);
        if ($count < 2) {
            return null;
        }

        $ordered = [];
        $seen = [];
        $identifierSeen = [];
        $total = 0.0;
        foreach ($items as $item) {
            $target = $item['target'];
            $identifier = $item['identifier'];
            $weight = $item['weight'];
            if ($target < 1 || $target > $count || isset($seen[$target]) || isset($identifierSeen[$identifier]) || !preg_match('/^[A-Za-z_][A-Za-z0-9_.-]*$/D', $identifier)
                || !is_finite($weight) || $weight < 0) {
                return null;
            }
            $seen[$target] = true;
            $identifierSeen[$identifier] = true;
            $ordered[$target] = $identifier;
            $total += $weight;
        }
        ksort($ordered, SORT_NUMERIC);

        $id = self::escape($responseId);
        $xml = '  <responseDeclaration identifier="'.$id.'" cardinality="ordered" baseType="identifier">'."\n";
        $xml .= "    <correctResponse>\n";
        foreach ($ordered as $identifier) {
            $xml .= '      <value>'.self::escape($identifier).'</value>'."\n";
        }
        $xml .= "    </correctResponse>\n  </responseDeclaration>\n";
        $xml .= '  <outcomeDeclaration identifier="SCORE" cardinality="single" baseType="float" normalMaximum="'.self::score($total).'"/>'."\n";
        $xml .= "  <itemBody>\n";
        $xml .= '    <orderInteraction responseIdentifier="'.$id.'" shuffle="true" orientation="'.('v' === $orientation ? 'vertical' : 'horizontal').'">'."\n";
        $xml .= '      <prompt><![CDATA['.self::cdata($prompt).']]></prompt>'."\n";
        foreach ($items as $item) {
            $xml .= '      <simpleChoice identifier="'.self::escape($item['identifier']).'">'
                .'<![CDATA['.self::cdata($item['text']).']]></simpleChoice>'."\n";
        }
        $xml .= "    </orderInteraction>\n  </itemBody>\n";

        $xml .= "  <responseProcessing>\n";
        $xml .= '    <setOutcomeValue identifier="SCORE"><baseValue baseType="float">0</baseValue></setOutcomeValue>'."\n";
        foreach ($items as $item) {
            if (0.0 === $item['weight']) {
                continue;
            }
            $xml .= "    <responseCondition>\n      <responseIf>\n";
            $xml .= '        <match><index n="'.$item['target'].'"><variable identifier="'.$id.'"/></index>'
                .'<baseValue baseType="identifier">'.self::escape($item['identifier']).'</baseValue></match>'."\n";
            $xml .= '        <setOutcomeValue identifier="SCORE"><sum><variable identifier="SCORE"/>'
                .'<baseValue baseType="float">'.self::score($item['weight']).'</baseValue></sum></setOutcomeValue>'."\n";
            $xml .= "      </responseIf>\n    </responseCondition>\n";
        }
        $xml .= "  </responseProcessing>\n";

        return $xml;
    }

    /**
     * @return array{orientation: string, items: list<array{answer: string, target: int, weight: float}>}|null
     */
    public static function read(DOMElement $interaction): ?array
    {
        if ('orderInteraction' !== self::name($interaction)) {
            return null;
        }

        $item = $interaction->parentNode?->parentNode;
        if (!$item instanceof DOMElement || 'assessmentItem' !== self::name($item)) {
            return null;
        }
        $responseId = $interaction->getAttribute('responseIdentifier');
        $declaration = null;
        foreach (self::children($item, 'responseDeclaration') as $candidate) {
            if ($responseId === $candidate->getAttribute('identifier')) {
                $declaration = $candidate;

                break;
            }
        }
        if (null === $declaration || '' === $responseId || 'ordered' !== $declaration->getAttribute('cardinality')
            || 'identifier' !== $declaration->getAttribute('baseType')) {
            return null;
        }
        $correct = self::children($declaration, 'correctResponse')[0] ?? null;
        if (null === $correct) {
            return null;
        }
        $correctIds = [];
        foreach (self::children($correct, 'value') as $value) {
            $correctIds[] = trim($value->textContent);
        }
        $choiceNodes = self::children($interaction, 'simpleChoice');
        $count = \count($choiceNodes);
        if ($count < 2 || $count !== \count($correctIds) || \count(array_unique($correctIds)) !== $count) {
            return null;
        }
        $targets = array_flip($correctIds);
        $weights = self::readWeights($item, $responseId, $correctIds);
        $items = [];
        $seen = [];
        foreach ($choiceNodes as $choice) {
            $identifier = $choice->getAttribute('identifier');
            if ('' === $identifier || !isset($targets[$identifier]) || isset($seen[$identifier])) {
                return null;
            }
            $seen[$identifier] = true;
            $items[] = [
                'answer' => QtiImportHtmlSanitizer::sanitize(html_entity_decode(trim($choice->textContent), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
                'target' => (int) $targets[$identifier] + 1,
                'weight' => $weights[$identifier] ?? 1.0,
            ];
        }

        return [
            'orientation' => 'vertical' === $interaction->getAttribute('orientation') ? 'v' : 'h',
            'items' => $items,
        ];
    }

    /**
     * Read only the positional scoring rules generated by this class. Other QTI scoring
     * models get safe unit weights rather than inventing their per-position semantics.
     *
     * @param list<string> $correctIds
     *
     * @return array<string, float>
     */
    private static function readWeights(DOMElement $item, string $responseId, array $correctIds): array
    {
        $processing = self::children($item, 'responseProcessing')[0] ?? null;
        if (null === $processing || '' !== $processing->getAttribute('template')) {
            return [];
        }
        $weights = [];
        foreach (self::children($processing, 'responseCondition') as $condition) {
            $responseIf = self::children($condition, 'responseIf')[0] ?? null;
            $match = null !== $responseIf ? (self::children($responseIf, 'match')[0] ?? null) : null;
            if (null === $match) {
                continue;
            }
            $index = self::children($match, 'index')[0] ?? null;
            $choiceValue = self::children($match, 'baseValue')[0] ?? null;
            if (null === $index || null === $choiceValue || 'identifier' !== $choiceValue->getAttribute('baseType')) {
                continue;
            }
            $variable = self::children($index, 'variable')[0] ?? null;
            $position = (int) $index->getAttribute('n');
            $identifier = trim($choiceValue->textContent);
            if (null === $variable || $variable->getAttribute('identifier') !== $responseId || $position <= 0
                || ($correctIds[$position - 1] ?? null) !== $identifier) {
                continue;
            }
            foreach (self::children($responseIf, 'setOutcomeValue') as $outcome) {
                if ('SCORE' !== $outcome->getAttribute('identifier')) {
                    continue;
                }
                $sum = self::children($outcome, 'sum')[0] ?? null;
                $score = null !== $sum ? (self::children($sum, 'baseValue')[0] ?? null) : null;
                if (null === $score || 'float' !== $score->getAttribute('baseType') || !is_numeric(trim($score->textContent))) {
                    continue;
                }
                $weight = (float) trim($score->textContent);
                if (is_finite($weight) && $weight >= 0) {
                    $weights[$identifier] = $weight;
                }
            }
        }

        // All-zero Chamilo questions carry an explicit SCORE=0 initialization but no rules.
        // Other QTI items without our processing keep the conservative unit-score fallback.
        if ([] === $weights) {
            $initial = self::children($processing, 'setOutcomeValue')[0] ?? null;
            $base = null !== $initial ? (self::children($initial, 'baseValue')[0] ?? null) : null;
            if (null !== $base && 'SCORE' === $initial->getAttribute('identifier')
                && 'float' === $base->getAttribute('baseType') && 0.0 === (float) trim($base->textContent)) {
                return array_fill_keys($correctIds, 0.0);
            }
        }
        if ([] !== $weights) {
            foreach ($correctIds as $identifier) {
                $weights[$identifier] ??= 0.0;
            }
        }

        return $weights;
    }

    /**
     * @return list<DOMElement>
     */
    private static function children(DOMElement $element, string $name): array
    {
        $nodes = [];
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement && self::name($child) === $name) {
                $nodes[] = $child;
            }
        }

        return $nodes;
    }

    private static function name(DOMElement $element): string
    {
        return $element->localName ?: $element->nodeName;
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_XML1, 'UTF-8');
    }

    private static function cdata(string $value): string
    {
        return str_replace(']]>', ']]]]><![CDATA[>', self::escape($value));
    }

    private static function score(float $value): string
    {
        $formatted = rtrim(rtrim(\sprintf('%.6F', $value), '0'), '.');

        return '' === $formatted ? '0' : $formatted;
    }
}
