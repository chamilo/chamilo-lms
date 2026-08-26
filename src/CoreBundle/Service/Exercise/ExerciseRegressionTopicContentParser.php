<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use InvalidArgumentException;
use JsonException;

use const JSON_THROW_ON_ERROR;

final class ExerciseRegressionTopicContentParser
{
    private const int MAX_SHORT_TEXT_LENGTH = 600;
    private const int MAX_LONG_TEXT_LENGTH = 5000;

    /**
     * @return array<string, mixed>
     */
    public function parse(string $raw): array
    {
        $raw = trim($raw);
        if ('' === $raw) {
            throw new InvalidArgumentException('The AI topic generator returned an empty response.');
        }

        $start = strpos($raw, '{');
        $end = strrpos($raw, '}');
        if (false === $start || false === $end || $end < $start) {
            throw new InvalidArgumentException('The AI topic generator did not return a JSON object.');
        }

        $json = substr($raw, $start, $end - $start + 1);

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('The AI topic generator returned invalid JSON.', 0, $exception);
        }

        if (!\is_array($decoded)) {
            throw new InvalidArgumentException('The AI topic generator returned an invalid content object.');
        }

        return [
            'single_choice' => $this->choiceBlock($decoded, 'single_choice', false, 3),
            'multiple_choice' => $this->choiceBlock($decoded, 'multiple_choice', true, 4),
            'fill_blanks' => $this->fillBlanksBlock($decoded),
            'matching' => $this->matchingBlock($decoded),
            'open' => $this->promptBlock($decoded, 'open'),
            'true_false' => $this->trueFalseBlock($decoded),
            'oral' => $this->promptBlock($decoded, 'oral'),
            'media' => $this->mediaBlock($decoded),
            'calculated' => $this->calculatedBlock($decoded),
            'image_choice' => $this->imageChoiceBlock($decoded),
            'ordering' => $this->orderingBlock($decoded),
            'annotation' => $this->promptBlock($decoded, 'annotation'),
            'reading' => $this->readingBlock($decoded),
            'upload' => $this->promptBlock($decoded, 'upload'),
            'dropdown' => $this->choiceBlock($decoded, 'dropdown', false, 3),
            'hotspot' => $this->hotspotBlock($decoded),
            'office' => $this->promptBlock($decoded, 'office'),
            'page_break' => $this->pageBreakBlock($decoded),
        ];
    }

    /**
     * @param array<string, mixed> $root
     *
     * @return array<string, mixed>
     */
    private function choiceBlock(array $root, string $key, bool $multiple, int $optionCount): array
    {
        $block = $this->block($root, $key);
        $options = $this->stringList($block, 'options', $optionCount, $optionCount);

        if ($multiple) {
            $indexes = $this->integerList($block, 'correct_indexes', 2, $optionCount - 1);
            if (\count($indexes) < 2) {
                throw new InvalidArgumentException('The AI topic generator must provide at least two correct options for '.$key.'.');
            }

            return [
                'question' => $this->shortString($block, 'question'),
                'options' => $options,
                'correct_indexes' => $indexes,
                'feedback' => $this->optionalShortString($block, 'feedback'),
            ];
        }

        $correctIndex = $this->integer($block, 'correct_index', 0, $optionCount - 1);

        return [
            'question' => $this->shortString($block, 'question'),
            'options' => $options,
            'correct_index' => $correctIndex,
            'feedback' => $this->optionalShortString($block, 'feedback'),
        ];
    }

    /**
     * @param array<string, mixed> $root
     *
     * @return array<string, mixed>
     */
    private function fillBlanksBlock(array $root): array
    {
        $block = $this->block($root, 'fill_blanks');
        $template = $this->longString($block, 'template');
        if (!str_contains($template, '{blank1}') || !str_contains($template, '{blank2}')) {
            throw new InvalidArgumentException('The fill_blanks template must contain {blank1} and {blank2}.');
        }

        return [
            'question' => $this->shortString($block, 'question'),
            'template' => $template,
            'answers' => $this->stringList($block, 'answers', 2, 2),
            'feedback' => $this->optionalShortString($block, 'feedback'),
        ];
    }

    /**
     * @param array<string, mixed> $root
     *
     * @return array<string, mixed>
     */
    private function matchingBlock(array $root): array
    {
        $block = $this->block($root, 'matching');
        $pairs = $block['pairs'] ?? null;
        if (!\is_array($pairs) || 2 !== \count($pairs)) {
            throw new InvalidArgumentException('The matching block must contain exactly two pairs.');
        }

        $normalized = [];
        foreach (array_values($pairs) as $pair) {
            if (!\is_array($pair)) {
                throw new InvalidArgumentException('Each matching pair must be an object.');
            }

            $normalized[] = [
                'left' => $this->shortString($pair, 'left'),
                'right' => $this->shortString($pair, 'right'),
            ];
        }

        return [
            'question' => $this->shortString($block, 'question'),
            'pairs' => $normalized,
            'feedback' => $this->optionalShortString($block, 'feedback'),
        ];
    }

    /**
     * @param array<string, mixed> $root
     *
     * @return array<string, mixed>
     */
    private function promptBlock(array $root, string $key): array
    {
        $block = $this->block($root, $key);

        return [
            'question' => $this->longString($block, 'question'),
        ];
    }

    /**
     * @param array<string, mixed> $root
     *
     * @return array<string, mixed>
     */
    private function trueFalseBlock(array $root): array
    {
        $block = $this->block($root, 'true_false');

        return [
            'question' => $this->shortString($block, 'question'),
            'true_statement' => $this->shortString($block, 'true_statement'),
            'false_statement' => $this->shortString($block, 'false_statement'),
            'feedback' => $this->optionalShortString($block, 'feedback'),
        ];
    }

    /**
     * @param array<string, mixed> $root
     *
     * @return array<string, mixed>
     */
    private function mediaBlock(array $root): array
    {
        $block = $this->block($root, 'media');

        return [
            'title' => $this->shortString($block, 'title'),
            'context' => $this->longString($block, 'context'),
        ];
    }

    /**
     * @param array<string, mixed> $root
     *
     * @return array<string, mixed>
     */
    private function calculatedBlock(array $root): array
    {
        $block = $this->block($root, 'calculated');

        return [
            'question' => $this->shortString($block, 'question'),
            'context' => $this->shortString($block, 'context'),
            'unit' => $this->optionalShortString($block, 'unit'),
            'feedback' => $this->optionalShortString($block, 'feedback'),
        ];
    }

    /**
     * @param array<string, mixed> $root
     *
     * @return array<string, mixed>
     */
    private function imageChoiceBlock(array $root): array
    {
        $block = $this->block($root, 'image_choice');

        return [
            'question' => $this->shortString($block, 'question'),
            'correct_label' => $this->shortString($block, 'correct_label'),
            'wrong_label' => $this->shortString($block, 'wrong_label'),
            'feedback' => $this->optionalShortString($block, 'feedback'),
        ];
    }

    /**
     * @param array<string, mixed> $root
     *
     * @return array<string, mixed>
     */
    private function orderingBlock(array $root): array
    {
        $block = $this->block($root, 'ordering');

        return [
            'question' => $this->shortString($block, 'question'),
            'items' => $this->stringList($block, 'items', 2, 2),
            'feedback' => $this->optionalShortString($block, 'feedback'),
        ];
    }

    /**
     * @param array<string, mixed> $root
     *
     * @return array<string, mixed>
     */
    private function readingBlock(array $root): array
    {
        $block = $this->block($root, 'reading');

        return [
            'title' => $this->shortString($block, 'title'),
            'passage' => $this->longString($block, 'passage'),
        ];
    }

    /**
     * @param array<string, mixed> $root
     *
     * @return array<string, mixed>
     */
    private function hotspotBlock(array $root): array
    {
        $block = $this->block($root, 'hotspot');

        return [
            'question' => $this->shortString($block, 'question'),
            'target_label' => $this->shortString($block, 'target_label'),
        ];
    }

    /**
     * @param array<string, mixed> $root
     *
     * @return array<string, mixed>
     */
    private function pageBreakBlock(array $root): array
    {
        $block = $this->block($root, 'page_break');

        return [
            'title' => $this->shortString($block, 'title'),
            'text' => $this->shortString($block, 'text'),
        ];
    }

    /**
     * @param array<string, mixed> $root
     *
     * @return array<string, mixed>
     */
    private function block(array $root, string $key): array
    {
        $block = $root[$key] ?? null;
        if (!\is_array($block)) {
            throw new InvalidArgumentException('The AI topic generator response is missing the '.$key.' block.');
        }

        return $block;
    }

    /**
     * @param array<string, mixed> $block
     */
    private function shortString(array $block, string $key): string
    {
        return $this->stringValue($block, $key, self::MAX_SHORT_TEXT_LENGTH, false);
    }

    /**
     * @param array<string, mixed> $block
     */
    private function optionalShortString(array $block, string $key): string
    {
        return $this->stringValue($block, $key, self::MAX_SHORT_TEXT_LENGTH, true);
    }

    /**
     * @param array<string, mixed> $block
     */
    private function longString(array $block, string $key): string
    {
        return $this->stringValue($block, $key, self::MAX_LONG_TEXT_LENGTH, false);
    }

    /**
     * @param array<string, mixed> $block
     */
    private function stringValue(array $block, string $key, int $maxLength, bool $optional): string
    {
        $value = $block[$key] ?? null;
        if (null === $value && $optional) {
            return '';
        }
        if (!\is_string($value)) {
            throw new InvalidArgumentException('The '.$key.' value must be a string.');
        }

        $value = trim(strip_tags($value));
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        if ('' === $value && !$optional) {
            throw new InvalidArgumentException('The '.$key.' value cannot be empty.');
        }
        if (mb_strlen($value) > $maxLength) {
            throw new InvalidArgumentException('The '.$key.' value is too long.');
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $block
     *
     * @return list<string>
     */
    private function stringList(array $block, string $key, int $minCount, int $maxCount): array
    {
        $values = $block[$key] ?? null;
        if (!\is_array($values)) {
            throw new InvalidArgumentException('The '.$key.' value must be an array.');
        }

        $values = array_values($values);
        if (\count($values) < $minCount || \count($values) > $maxCount) {
            throw new InvalidArgumentException('The '.$key.' array has an invalid number of items.');
        }

        $normalized = [];
        foreach ($values as $value) {
            if (!\is_string($value)) {
                throw new InvalidArgumentException('Every '.$key.' item must be a string.');
            }

            $value = trim(strip_tags($value));
            $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
            if ('' === $value || mb_strlen($value) > self::MAX_SHORT_TEXT_LENGTH) {
                throw new InvalidArgumentException('One '.$key.' item is empty or too long.');
            }
            $normalized[] = $value;
        }

        if (\count(array_unique(array_map('mb_strtolower', $normalized))) !== \count($normalized)) {
            throw new InvalidArgumentException('The '.$key.' array must not contain duplicate items.');
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $block
     *
     * @return list<int>
     */
    private function integerList(array $block, string $key, int $minValue, int $maxValue): array
    {
        $values = $block[$key] ?? null;
        if (!\is_array($values)) {
            throw new InvalidArgumentException('The '.$key.' value must be an array.');
        }

        $normalized = [];
        foreach ($values as $value) {
            if (!\is_int($value) && !(\is_string($value) && ctype_digit($value))) {
                throw new InvalidArgumentException('Every '.$key.' item must be an integer.');
            }

            $integer = (int) $value;
            if ($integer < 0 || $integer > $maxValue) {
                throw new InvalidArgumentException('One '.$key.' item is outside the allowed range.');
            }
            $normalized[] = $integer;
        }

        $normalized = array_values(array_unique($normalized));
        sort($normalized);

        if (\count($normalized) < $minValue) {
            throw new InvalidArgumentException('The '.$key.' array does not contain enough values.');
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $block
     */
    private function integer(array $block, string $key, int $minValue, int $maxValue): int
    {
        $value = $block[$key] ?? null;
        if (!\is_int($value) && !(\is_string($value) && ctype_digit($value))) {
            throw new InvalidArgumentException('The '.$key.' value must be an integer.');
        }

        $integer = (int) $value;
        if ($integer < $minValue || $integer > $maxValue) {
            throw new InvalidArgumentException('The '.$key.' value is outside the allowed range.');
        }

        return $integer;
    }
}
