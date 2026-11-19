<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizQuestionOption;
use DOMElement;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const ENT_XML1;

/**
 * Preserves Chamilo's per-statement True/False/Don't know responses in QTI 2.1.
 * No other matchInteraction is converted into a true/false question.
 */
final class Qti2TrueFalseItem
{
    public const TYPE_PARTIAL = 11;
    public const TYPE_COMBINATION = 12;
    public const TYPE_CERTAINTY = 22;
    public const LABEL_PARTIAL = 'chamilo:multiple-true-false';
    public const LABEL_COMBINATION = 'chamilo:multiple-true-false-combination';
    public const LABEL_CERTAINTY = 'chamilo:multiple-true-false-certainty';
    public const CERTAINTY_SUFFIX = '_CERTAINTY';

    public static function label(int $type): ?string
    {
        return match ($type) {
            self::TYPE_PARTIAL => self::LABEL_PARTIAL,
            self::TYPE_COMBINATION => self::LABEL_COMBINATION,
            self::TYPE_CERTAINTY => self::LABEL_CERTAINTY,
            default => null,
        };
    }

    public static function type(string $label): ?int
    {
        return match ($label) {
            self::LABEL_PARTIAL => self::TYPE_PARTIAL,
            self::LABEL_COMBINATION => self::TYPE_COMBINATION,
            self::LABEL_CERTAINTY => self::TYPE_CERTAINTY,
            default => null,
        };
    }

    public static function write(CQuizQuestion $question, string $responseId): ?string
    {
        $type = (int) $question->getType();
        $label = self::label($type);
        if (null === $label) {
            return null;
        }

        $optionPositions = [];
        foreach ($question->getOptions() as $option) {
            if ($option instanceof CQuizQuestionOption && null !== $option->getIid()) {
                $optionPositions[(int) $option->getIid()] = (int) $option->getPosition();
            }
        }

        $items = [];
        $answers = [];
        foreach ($question->getAnswers() as $answer) {
            if (!$answer instanceof CQuizAnswer) {
                continue;
            }
            $answers[] = $answer;
        }
        usort($answers, static fn (CQuizAnswer $a, CQuizAnswer $b): int => $a->getPosition() <=> $b->getPosition());
        foreach ($answers as $answer) {
            $rawChoice = (int) $answer->getCorrect();
            // The legacy type-12 editor stores checked/unchecked as 1/0 without option entities.
            $choice = self::TYPE_COMBINATION === $type && [] === $optionPositions && \in_array($rawChoice, [0, 1], true)
                ? (1 === $rawChoice ? 1 : 2)
                : ($optionPositions[$rawChoice] ?? $rawChoice);
            if (!\in_array($choice, [1, 2], true) || null === $answer->getIid()) {
                return null;
            }
            $items[] = [
                'identifier' => 'statement_'.(int) $answer->getIid(),
                'answer' => (string) $answer->getAnswer(),
                'choice' => $choice,
            ];
        }
        if ([] === $items) {
            return null;
        }

        $scores = [1.0, -0.5, 0.0];
        if (\in_array($type, [self::TYPE_PARTIAL, self::TYPE_CERTAINTY], true) && '' !== trim((string) $question->getExtra())) {
            $scores = array_map('floatval', \array_slice(array_pad(explode(':', (string) $question->getExtra()), 3, '0'), 0, 3));
        }
        if (self::TYPE_CERTAINTY === $type && 0.0 !== $scores[2]) {
            return null;
        }
        foreach ($scores as $score) {
            if (!is_finite($score)) {
                return null;
            }
        }
        $score = 0.0;
        if (self::TYPE_COMBINATION === $type) {
            $score = Qti2ExactCombinationWriter::score(
                [(float) ($answers[0]?->getPonderation() ?? 0.0)],
                (float) $question->getPonderation()
            );
        }
        $id = self::xml($responseId);
        $xml = '<assessmentItem xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1"'
            .' identifier="'.$id.'" title="'.self::xml((string) $question->getQuestion()).'" label="'.$label.'">'."\n";
        $xml .= '  <responseDeclaration identifier="'.$id.'" cardinality="multiple" baseType="directedPair">'."\n";
        $xml .= "    <correctResponse>\n";
        foreach ($items as $item) {
            $xml .= '      <value>'.$item['identifier'].' choice_'.(1 === $item['choice'] ? 'true' : 'false').'</value>'."\n";
        }
        $xml .= "    </correctResponse>\n";
        if (\in_array($type, [self::TYPE_PARTIAL, self::TYPE_CERTAINTY], true)) {
            $xml .= "    <mapping>\n";
            foreach ($items as $item) {
                $choices = self::TYPE_CERTAINTY === $type ? ['true' => 1, 'false' => 2] : ['true' => 1, 'false' => 2, 'unknown' => 3];
                foreach ($choices as $option => $position) {
                    $mapped = 3 === $position ? $scores[2] : ($item['choice'] === $position ? $scores[0] : $scores[1]);
                    $xml .= '      <mapEntry mapKey="'.$item['identifier'].' choice_'.$option.'" mappedValue="'.self::score($mapped).'"/>'."\n";
                }
            }
            $xml .= "    </mapping>\n";
        }
        $xml .= "  </responseDeclaration>\n";
        if (self::TYPE_CERTAINTY === $type) {
            $xml .= '  <responseDeclaration identifier="'.$id.self::CERTAINTY_SUFFIX.'" cardinality="multiple" baseType="directedPair"/>'."\n";
        }
        $maximum = self::TYPE_COMBINATION === $type ? $score : max(0.0, $scores[0] * \count($items));
        $xml .= '  <outcomeDeclaration identifier="SCORE" cardinality="single" baseType="float" normalMaximum="'.self::score($maximum).'"/>'."\n";
        $xml .= "  <itemBody>\n";
        $xml .= '    <matchInteraction responseIdentifier="'.$id.'" shuffle="false" maxAssociations="'.\count($items).'">'."\n";
        $xml .= '      <prompt><![CDATA['.self::cdata((string) $question->getQuestion()).']]></prompt>'."\n";
        $xml .= "      <simpleMatchSet>\n";
        foreach ($items as $item) {
            $xml .= '        <simpleAssociableChoice identifier="'.$item['identifier'].'" matchMax="1"><![CDATA['.self::cdata($item['answer']).']]></simpleAssociableChoice>'."\n";
        }
        $xml .= "      </simpleMatchSet>\n      <simpleMatchSet>\n";
        $labels = self::TYPE_CERTAINTY === $type ? ['true' => 'True', 'false' => 'False'] : ['true' => 'True', 'false' => 'False', 'unknown' => "Don't know"];
        foreach ($labels as $identifier => $text) {
            $xml .= '        <simpleAssociableChoice identifier="choice_'.$identifier.'" matchMax="'.\count($items).'">'.self::xml($text).'</simpleAssociableChoice>'."\n";
        }
        $xml .= "      </simpleMatchSet>\n    </matchInteraction>\n";
        if (self::TYPE_CERTAINTY === $type) {
            // A separate, ungraded QTI response retains each learner's certainty selection.
            $xml .= '    <matchInteraction responseIdentifier="'.$id.self::CERTAINTY_SUFFIX.'" shuffle="false" maxAssociations="'.\count($items).'">'."\n";
            $xml .= "      <simpleMatchSet>\n";
            foreach ($items as $item) {
                $xml .= '        <simpleAssociableChoice identifier="'.$item['identifier'].'" matchMax="1"><![CDATA['.self::cdata($item['answer']).']]></simpleAssociableChoice>'."\n";
            }
            $xml .= "      </simpleMatchSet>\n      <simpleMatchSet>\n";
            foreach ([50, 60, 70, 80, 90, 100] as $level) {
                $xml .= '        <simpleAssociableChoice identifier="certainty_'.$level.'" matchMax="'.\count($items).'">'.$level.'%</simpleAssociableChoice>'."\n";
            }
            $xml .= "      </simpleMatchSet>\n    </matchInteraction>\n";
        }
        $xml .= "  </itemBody>\n";
        if (self::TYPE_COMBINATION === $type) {
            $xml .= Qti2ExactCombinationWriter::responseProcessing($responseId, $score);
        } elseif (self::TYPE_CERTAINTY === $type) {
            // Certainty does not affect Chamilo's score; score only the True/False matches.
            $xml .= '  <responseProcessing><setOutcomeValue identifier="SCORE"><mapResponse identifier="'.$id.'"/></setOutcomeValue></responseProcessing>'."\n";
        } else {
            // mapResponse scores the selected pairs; an unanswered statement scores as Don't know.
            $xml .= '  <responseProcessing><setOutcomeValue identifier="SCORE"><sum><mapResponse identifier="'.$id.'"/>';
            $xml .= '<product><baseValue baseType="float">'.self::score($scores[2]).'</baseValue>';
            $xml .= '<subtract><baseValue baseType="integer">'.\count($items).'</baseValue>';
            $xml .= '<containerSize><variable identifier="'.$id.'"/></containerSize></subtract></product>';
            $xml .= "</sum></setOutcomeValue></responseProcessing>\n";
        }

        return $xml."</assessmentItem>\n";
    }

    /**
     * @return array{items: list<array{answer: string, choice: int}>, scores: array{float, float, float}, score: float}|null
     */
    public static function read(DOMElement $interaction, int $type): ?array
    {
        if ('matchInteraction' !== self::name($interaction) || null === self::label($type)) {
            return null;
        }
        $body = $interaction->parentNode;
        $item = $body?->parentNode;
        if (!$item instanceof DOMElement || 'assessmentItem' !== self::name($item)
            || self::label($type) !== $item->getAttribute('label')) {
            return null;
        }
        $sets = self::children($interaction, 'simpleMatchSet');
        if (2 !== \count($sets)) {
            return null;
        }
        $right = [];
        foreach (self::children($sets[1], 'simpleAssociableChoice') as $option) {
            $right[$option->getAttribute('identifier')] = trim($option->textContent);
        }
        $expectedOptionCount = self::TYPE_CERTAINTY === $type ? 2 : 3;
        if ($expectedOptionCount !== \count($right)
            || !isset($right['choice_true'], $right['choice_false'])
            || (self::TYPE_CERTAINTY !== $type && !isset($right['choice_unknown']))) {
            return null;
        }
        $declaration = null;
        $responseId = $interaction->getAttribute('responseIdentifier');
        foreach (self::children($item, 'responseDeclaration') as $candidate) {
            if ($candidate->getAttribute('identifier') === $responseId) {
                $declaration = $candidate;

                break;
            }
        }
        if (null === $declaration || '' === $responseId || 'multiple' !== $declaration->getAttribute('cardinality')
            || 'directedPair' !== $declaration->getAttribute('baseType')) {
            return null;
        }
        $correct = self::children($declaration, 'correctResponse')[0] ?? null;
        if (null === $correct) {
            return null;
        }
        $pairs = [];
        foreach (self::children($correct, 'value') as $node) {
            $parts = preg_split('/\s+/', trim($node->textContent));
            if (2 !== \count($parts) || !\in_array($parts[1], ['choice_true', 'choice_false'], true) || isset($pairs[$parts[0]])) {
                return null;
            }
            $pairs[$parts[0]] = 'choice_true' === $parts[1] ? 1 : 2;
        }
        $items = [];
        foreach (self::children($sets[0], 'simpleAssociableChoice') as $row) {
            $identifier = $row->getAttribute('identifier');
            if ('' === $identifier || !isset($pairs[$identifier]) || isset($items[$identifier])) {
                return null;
            }
            $items[$identifier] = [
                'answer' => QtiImportHtmlSanitizer::sanitize(trim($row->textContent)),
                'choice' => $pairs[$identifier],
            ];
        }
        if ([] === $items || \count($items) !== \count($pairs)) {
            return null;
        }
        if (self::TYPE_CERTAINTY === $type && !self::hasValidCertaintyInteraction($item, $interaction, array_keys($items))) {
            return null;
        }
        $processing = self::children($item, 'responseProcessing')[0] ?? null;
        if (null === $processing) {
            return null;
        }
        if (self::TYPE_COMBINATION === $type) {
            $score = Qti2ExactCombinationWriter::readScore($processing);
            if (null === $score) {
                return null;
            }

            return ['items' => array_values($items), 'scores' => [0.0, 0.0, 0.0], 'score' => $score];
        }
        $mapping = self::children($declaration, 'mapping')[0] ?? null;
        if (null === $mapping || 0 === $processing->getElementsByTagName('mapResponse')->length) {
            return null;
        }
        $weights = [];
        foreach (self::children($mapping, 'mapEntry') as $entry) {
            $key = $entry->getAttribute('mapKey');
            $value = trim($entry->getAttribute('mappedValue'));
            if ('' === $key || !is_numeric($value) || !is_finite((float) $value)) {
                return null;
            }
            $weights[$key] = (float) $value;
        }
        $scores = null;
        foreach ($items as $identifier => $row) {
            $correctKey = $identifier.' choice_'.(1 === $row['choice'] ? 'true' : 'false');
            $wrongKey = $identifier.' choice_'.(1 === $row['choice'] ? 'false' : 'true');
            $unknownKey = $identifier.' choice_unknown';
            if (!isset($weights[$correctKey], $weights[$wrongKey])
                || (self::TYPE_CERTAINTY !== $type && !isset($weights[$unknownKey]))) {
                return null;
            }
            $current = [$weights[$correctKey], $weights[$wrongKey], self::TYPE_CERTAINTY === $type ? 0.0 : $weights[$unknownKey]];
            if (null !== $scores && $scores !== $current) {
                return null;
            }
            $scores = $current;
        }

        return ['items' => array_values($items), 'scores' => $scores, 'score' => 0.0];
    }

    /**
     * Check that certainty choices remain available for every statement after a QTI transfer.
     *
     * @param list<string> $statementIds
     */
    private static function hasValidCertaintyInteraction(DOMElement $item, DOMElement $primary, array $statementIds): bool
    {
        $responseId = $primary->getAttribute('responseIdentifier').self::CERTAINTY_SUFFIX;
        $declarations = self::children($item, 'responseDeclaration');
        $hasDeclaration = false;
        foreach ($declarations as $declaration) {
            if ($responseId === $declaration->getAttribute('identifier')
                && 'multiple' === $declaration->getAttribute('cardinality')
                && 'directedPair' === $declaration->getAttribute('baseType')) {
                $hasDeclaration = true;
            }
        }
        if (!$hasDeclaration) {
            return false;
        }
        $body = $primary->parentNode;
        if (!$body instanceof DOMElement) {
            return false;
        }
        $secondary = null;
        foreach (self::children($body, 'matchInteraction') as $candidate) {
            if ($responseId === $candidate->getAttribute('responseIdentifier')) {
                if (null !== $secondary) {
                    return false;
                }
                $secondary = $candidate;
            }
        }
        if (null === $secondary || (int) $secondary->getAttribute('maxAssociations') !== \count($statementIds)) {
            return false;
        }
        $sets = self::children($secondary, 'simpleMatchSet');
        if (2 !== \count($sets)) {
            return false;
        }
        $ids = [];
        foreach (self::children($sets[0], 'simpleAssociableChoice') as $choice) {
            $identifier = $choice->getAttribute('identifier');
            if ('' === $identifier || isset($ids[$identifier]) || 1 !== (int) $choice->getAttribute('matchMax')) {
                return false;
            }
            $ids[$identifier] = true;
        }
        if (\count($ids) !== \count($statementIds) || [] !== array_diff($statementIds, array_keys($ids))) {
            return false;
        }
        $levels = [];
        foreach (self::children($sets[1], 'simpleAssociableChoice') as $choice) {
            $levels[] = $choice->getAttribute('identifier');
            $level = substr($choice->getAttribute('identifier'), \strlen('certainty_'));
            if ($choice->textContent !== $level.'%') {
                return false;
            }
            if ((int) $choice->getAttribute('matchMax') !== \count($statementIds)) {
                return false;
            }
        }

        return ['certainty_50', 'certainty_60', 'certainty_70', 'certainty_80', 'certainty_90', 'certainty_100'] === $levels;
    }

    /**
     * @return list<DOMElement>
     */
    private static function children(DOMElement $node, string $name): array
    {
        $items = [];
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement && self::name($child) === $name) {
                $items[] = $child;
            }
        }

        return $items;
    }

    private static function name(DOMElement $node): string
    {
        return $node->localName ?: $node->nodeName;
    }

    private static function xml(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE | ENT_XML1, 'UTF-8');
    }

    private static function cdata(string $text): string
    {
        return str_replace(']]>', ']]]]><![CDATA[>', $text);
    }

    private static function score(float $number): string
    {
        $value = rtrim(rtrim(\sprintf('%.6F', $number), '0'), '.');

        return '' === $value || '-0' === $value ? '0' : $value;
    }
}
