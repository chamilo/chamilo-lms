<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command\Concerns;

use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Shared numeric-option parsing for console commands that take
 * positive/bounded integer options (limits, batch sizes, IDs). Extracted from
 * ProcessAchievementCertificatesCommand and reused by
 * ProcessAllAchievementCertificatesCommand to avoid duplicating validation
 * logic across the two.
 */
trait BoundedConsoleOptionsTrait
{
    private function positiveOption(InputInterface $input, string $name): ?int
    {
        $rawValue = trim((string) $input->getOption($name));

        if ('' === $rawValue || !ctype_digit($rawValue) || (int) $rawValue <= 0) {
            return null;
        }

        return (int) $rawValue;
    }

    private function optionalPositiveOption(InputInterface $input, string $name): ?int
    {
        $rawValue = trim((string) $input->getOption($name));

        if ('' === $rawValue) {
            return null;
        }

        if (!ctype_digit($rawValue) || (int) $rawValue <= 0) {
            throw new RuntimeException(\sprintf('--%s must be a positive integer.', $name));
        }

        return (int) $rawValue;
    }

    private function boundedPositiveOption(
        InputInterface $input,
        string $name,
        int $maximum
    ): int {
        $value = $this->optionalPositiveOption($input, $name);

        if (null === $value || $value > $maximum) {
            throw new RuntimeException(\sprintf('--%s must be between 1 and %d.', $name, $maximum));
        }

        return $value;
    }

    private function boundedNonNegativeOption(
        InputInterface $input,
        string $name,
        int $maximum
    ): int {
        $rawValue = trim((string) $input->getOption($name));

        if ('' === $rawValue || !ctype_digit($rawValue)) {
            throw new RuntimeException(\sprintf('--%s must be a non-negative integer.', $name));
        }

        $value = (int) $rawValue;

        if ($value > $maximum) {
            throw new RuntimeException(\sprintf('--%s must be between 0 and %d.', $name, $maximum));
        }

        return $value;
    }
}
