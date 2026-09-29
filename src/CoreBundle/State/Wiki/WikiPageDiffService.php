<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Wiki;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const PREG_SPLIT_DELIM_CAPTURE;
use const PREG_SPLIT_NO_EMPTY;

final class WikiPageDiffService
{
    private const int MAX_WORD_TOKENS = 600;

    /**
     * @return array<int, array{type:string, content:string}>
     */
    public function compareLines(string $oldContent, string $newContent): array
    {
        $oldLines = $this->splitLines($oldContent);
        $newLines = $this->splitLines($newContent);
        $deleted = array_diff_assoc($oldLines, $newLines);
        $added = array_diff_assoc($newLines, $oldLines);
        $moved = [];

        foreach ($added as $newIndex => $candidate) {
            foreach ($deleted as $oldIndex => $content) {
                if ($candidate !== $content) {
                    continue;
                }

                $moved[$newIndex] = $candidate;
                unset($added[$newIndex], $deleted[$oldIndex]);

                break;
            }
        }

        $changes = [];
        $maximum = max(\count($oldLines), \count($newLines));

        for ($index = 0; $index < $maximum; ++$index) {
            if (isset($deleted[$index], $added[$index])) {
                $changes[] = ['type' => 'deleted', 'content' => $this->normalizeLine($deleted[$index])];
                $changes[] = ['type' => 'added', 'content' => $this->normalizeLine($added[$index])];

                continue;
            }

            if (isset($deleted[$index])) {
                $changes[] = ['type' => 'deleted', 'content' => $this->normalizeLine($deleted[$index])];

                continue;
            }

            if (isset($added[$index])) {
                $changes[] = ['type' => 'added', 'content' => $this->normalizeLine($added[$index])];

                continue;
            }

            if (isset($moved[$index])) {
                $changes[] = ['type' => 'moved', 'content' => $this->normalizeLine($newLines[$index] ?? $moved[$index])];

                continue;
            }

            if (isset($newLines[$index])) {
                $changes[] = ['type' => 'equal', 'content' => $this->normalizeLine($newLines[$index])];
            }
        }

        return $changes;
    }

    /**
     * @return array<int, array{type:string, text:string}>
     */
    public function compareWords(string $oldContent, string $newContent): array
    {
        $oldTokens = $this->splitWords($this->toPlainText($oldContent));
        $newTokens = $this->splitWords($this->toPlainText($newContent));
        [$prefixLength, $suffixLength] = $this->commonBounds($oldTokens, $newTokens);
        $oldMiddleLength = \count($oldTokens) - $prefixLength - $suffixLength;
        $newMiddleLength = \count($newTokens) - $prefixLength - $suffixLength;
        $oldMiddle = \array_slice($oldTokens, $prefixLength, max(0, $oldMiddleLength));
        $newMiddle = \array_slice($newTokens, $prefixLength, max(0, $newMiddleLength));
        $segments = [];

        $this->appendSegment($segments, 'equal', implode('', \array_slice($oldTokens, 0, $prefixLength)));

        if (\count($oldMiddle) + \count($newMiddle) > self::MAX_WORD_TOKENS) {
            $this->appendSegment($segments, 'deleted', implode('', $oldMiddle));
            $this->appendSegment($segments, 'added', implode('', $newMiddle));
        } else {
            foreach ($this->buildWordDiff($oldMiddle, $newMiddle) as $segment) {
                $this->appendSegment($segments, $segment['type'], $segment['text']);
            }
        }

        if ($suffixLength > 0) {
            $this->appendSegment(
                $segments,
                'equal',
                implode('', \array_slice($oldTokens, \count($oldTokens) - $suffixLength)),
            );
        }

        return $segments;
    }

    /**
     * @param array<int, string> $oldTokens
     * @param array<int, string> $newTokens
     *
     * @return array{0:int, 1:int}
     */
    private function commonBounds(array $oldTokens, array $newTokens): array
    {
        $oldCount = \count($oldTokens);
        $newCount = \count($newTokens);
        $prefixLength = 0;
        $maximumPrefix = min($oldCount, $newCount);

        while ($prefixLength < $maximumPrefix && $oldTokens[$prefixLength] === $newTokens[$prefixLength]) {
            ++$prefixLength;
        }

        $suffixLength = 0;
        $maximumSuffix = min($oldCount - $prefixLength, $newCount - $prefixLength);

        while ($suffixLength < $maximumSuffix
            && $oldTokens[$oldCount - $suffixLength - 1] === $newTokens[$newCount - $suffixLength - 1]
        ) {
            ++$suffixLength;
        }

        return [$prefixLength, $suffixLength];
    }

    /**
     * @param array<int, string> $oldTokens
     * @param array<int, string> $newTokens
     *
     * @return array<int, array{type:string, text:string}>
     */
    private function buildWordDiff(array $oldTokens, array $newTokens): array
    {
        $oldCount = \count($oldTokens);
        $newCount = \count($newTokens);
        $matrix = array_fill(0, $oldCount + 1, array_fill(0, $newCount + 1, 0));

        for ($oldIndex = $oldCount - 1; $oldIndex >= 0; --$oldIndex) {
            for ($newIndex = $newCount - 1; $newIndex >= 0; --$newIndex) {
                $matrix[$oldIndex][$newIndex] = $oldTokens[$oldIndex] === $newTokens[$newIndex]
                    ? $matrix[$oldIndex + 1][$newIndex + 1] + 1
                    : max($matrix[$oldIndex + 1][$newIndex], $matrix[$oldIndex][$newIndex + 1]);
            }
        }

        $segments = [];
        $oldIndex = 0;
        $newIndex = 0;

        while ($oldIndex < $oldCount && $newIndex < $newCount) {
            if ($oldTokens[$oldIndex] === $newTokens[$newIndex]) {
                $this->appendSegment($segments, 'equal', $oldTokens[$oldIndex]);
                ++$oldIndex;
                ++$newIndex;

                continue;
            }

            if ($matrix[$oldIndex + 1][$newIndex] >= $matrix[$oldIndex][$newIndex + 1]) {
                $this->appendSegment($segments, 'deleted', $oldTokens[$oldIndex]);
                ++$oldIndex;

                continue;
            }

            $this->appendSegment($segments, 'added', $newTokens[$newIndex]);
            ++$newIndex;
        }

        while ($oldIndex < $oldCount) {
            $this->appendSegment($segments, 'deleted', $oldTokens[$oldIndex]);
            ++$oldIndex;
        }

        while ($newIndex < $newCount) {
            $this->appendSegment($segments, 'added', $newTokens[$newIndex]);
            ++$newIndex;
        }

        return $segments;
    }

    /**
     * @param array<int, array{type:string, text:string}> $segments
     */
    private function appendSegment(array &$segments, string $type, string $text): void
    {
        if ('' === $text) {
            return;
        }

        $lastIndex = \count($segments) - 1;
        if ($lastIndex >= 0 && $segments[$lastIndex]['type'] === $type) {
            $segments[$lastIndex]['text'] .= $text;

            return;
        }

        $segments[] = [
            'type' => $type,
            'text' => $text,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function splitLines(string $content): array
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $content);

        return explode("\n", $normalized);
    }

    /**
     * @return array<int, string>
     */
    private function splitWords(string $content): array
    {
        $tokens = preg_split('/(\s+)/u', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        return \is_array($tokens) ? $tokens : [$content];
    }

    private function toPlainText(string $content): string
    {
        return html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function normalizeLine(string $line): string
    {
        return '' === trim($line) ? '&nbsp;' : $line;
    }
}
