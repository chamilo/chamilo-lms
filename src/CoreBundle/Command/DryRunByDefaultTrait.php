<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Shared "read-only by default" model of the repair commands, the one of RepairQuizRelationsFromLegacyCommand:
 * nothing is written unless --apply is given, and --apply must carry the exact count observed by the read-only run
 * (--expected-count), so a repair never touches more (or other) rows than the operator reviewed.
 * --dry-run is kept as an accepted no-op, so scripts that already pass it keep working.
 */
trait DryRunByDefaultTrait
{
    protected function addApplyOptions(bool $withExpectedCount = true): void
    {
        $this->addOption(
            'apply',
            null,
            InputOption::VALUE_NONE,
            'Write the repair. Without this option the command is read-only (dry-run).'
        );

        if ($withExpectedCount) {
            $this->addOption(
                'expected-count',
                null,
                InputOption::VALUE_REQUIRED,
                'Exact number of rows the read-only run reported. Required with --apply; the repair is refused if it differs.'
            );
        }

        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Deprecated no-op: the command is read-only unless --apply is given.'
        );
    }

    /**
     * @throws RuntimeException when --apply and --dry-run are combined
     */
    protected function isApply(InputInterface $input): bool
    {
        $apply = (bool) $input->getOption('apply');

        if ($apply && (bool) $input->getOption('dry-run')) {
            throw new RuntimeException('--apply and --dry-run cannot be combined.');
        }

        return $apply;
    }

    /**
     * @throws RuntimeException when --expected-count is missing/invalid, or differs from the observed count
     */
    protected function assertExpectedCount(InputInterface $input, int $actual): void
    {
        $raw = $input->getOption('expected-count');
        if (null === $raw || '' === trim((string) $raw) || !ctype_digit(trim((string) $raw))) {
            throw new RuntimeException('--expected-count is required with --apply and must be a non-negative integer.');
        }

        $expected = (int) trim((string) $raw);
        if ($expected !== $actual) {
            throw new RuntimeException(\sprintf('Repair refused: the repair would affect %d row(s), expected %d.', $actual, $expected));
        }
    }

    /**
     * Fails fast, before any work, when --apply is given without --expected-count.
     *
     * @throws RuntimeException
     */
    protected function requireExpectedCountOption(InputInterface $input): void
    {
        $raw = $input->getOption('expected-count');
        if (null === $raw || '' === trim((string) $raw) || !ctype_digit(trim((string) $raw))) {
            throw new RuntimeException('--expected-count is required with --apply and must be a non-negative integer.');
        }
    }
}
