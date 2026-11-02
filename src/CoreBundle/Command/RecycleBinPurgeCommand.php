<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Service\RecycleBin\RecycleBinRetentionPolicy;
use Chamilo\CoreBundle\Service\RecycleBin\RecycleBinRetentionService;
use Chamilo\CoreBundle\Settings\SettingsManager;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * Run through a trusted server-side scheduler or by a platform operator.
 * Defaults to preview; --execute explicitly permits permanent archive deletion.
 */
#[AsCommand(
    name: 'chamilo:recycle-bin:purge',
    description: 'Preview or permanently purge expired exercise and learning path recycle archives.',
)]
final class RecycleBinPurgeCommand extends Command
{
    public function __construct(
        private readonly RecycleBinRetentionService $retentionService,
        private readonly SettingsManager $settingsManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('hours', null, InputOption::VALUE_REQUIRED, 'Override the configured retention period, in hours (1-87600).', null)
            ->addOption('scan-limit', null, InputOption::VALUE_REQUIRED, 'Maximum archive candidates inspected per table (1-1000).', '250')
            ->addOption('after-lp-id', null, InputOption::VALUE_REQUIRED, 'Resume scanning after this LP archive id.', '0')
            ->addOption('after-exercise-id', null, InputOption::VALUE_REQUIRED, 'Resume scanning after this exercise archive id.', '0')
            ->addOption('execute', null, InputOption::VALUE_NONE, 'Permanently delete expired archives; otherwise preview only.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $override = $input->getOption('hours');
            $configured = null === $override
                ? $this->settingsManager->getSetting('tracking.recycle_bin_retention_hours')
                : $override;
            $hours = RecycleBinRetentionPolicy::configuredHours($configured);
        } catch (InvalidArgumentException $exception) {
            $io->error($exception->getMessage());

            return Command::INVALID;
        }
        $limit = $this->positiveOption($input, 'scan-limit', 1000);
        $afterLp = $this->nonNegativeOption($input, 'after-lp-id');
        $afterExercise = $this->nonNegativeOption($input, 'after-exercise-id');
        if (null === $limit || null === $afterLp || null === $afterExercise) {
            $io->error('Invalid options: hours must be 1-87600, scan-limit 1-1000, and resume ids non-negative integers.');

            return Command::INVALID;
        }

        $execute = true === $input->getOption('execute');
        $io->title('Recycle bin retention');
        $io->definitionList(['Retention hours' => (string) $hours]);
        $io->warning($execute
            ? 'Permanent deletion enabled: expired snapshots cannot be restored after this operation.'
            : 'Preview only: no database rows will be changed.');

        try {
            $summary = $this->retentionService->run($hours, $limit, $execute, $afterLp, $afterExercise);
        } catch (Throwable $exception) {
            $io->error('Recycle bin cleanup failed: '.$exception->getMessage());

            return Command::FAILURE;
        }

        $rows = [];
        foreach ($summary as $table => $stats) {
            $rows[] = [$table, $stats['scanned'], $stats['eligible'], $stats['protected'], $stats['purged'], $stats['last_id']];
        }
        $io->table(['Table', 'Scanned', 'Eligible', 'Protected', 'Purged', 'Last archive ID'], $rows);
        $io->note('If the scan limit was reached, use the corresponding --after-*-id to review further candidates.');

        return Command::SUCCESS;
    }

    private function positiveOption(InputInterface $input, string $name, int $max): ?int
    {
        $value = $input->getOption($name);
        if (!\is_string($value) || !preg_match('/^[0-9]+$/D', $value)) {
            return null;
        }
        $number = (int) $value;

        return $number >= 1 && $number <= $max ? $number : null;
    }

    private function nonNegativeOption(InputInterface $input, string $name): ?int
    {
        $value = $input->getOption($name);
        if (!\is_string($value) || !preg_match('/^[0-9]+$/D', $value) || \strlen($value) > 10) {
            return null;
        }
        $number = (int) $value;

        return $number >= 0 && $number <= 2147483647 ? $number : null;
    }
}
