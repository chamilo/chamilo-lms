<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Wiki;

final class WikiDiscussionScoreCalculator
{
    public function normalize(mixed $score): ?int
    {
        if (null === $score) {
            return null;
        }

        $value = trim((string) $score);
        if ('' === $value || '-' === $value || !ctype_digit($value)) {
            return null;
        }

        $normalized = (int) $value;

        return $normalized >= 0 && $normalized <= 10 ? $normalized : null;
    }

    /**
     * @param array<int, mixed> $scores
     */
    public function average(array $scores): float
    {
        $normalized = [];

        foreach ($scores as $score) {
            $value = $this->normalize($score);
            if (null !== $value) {
                $normalized[] = $value;
            }
        }

        if ([] === $normalized) {
            return 0.0;
        }

        return round(array_sum($normalized) / \count($normalized), 2);
    }
}
