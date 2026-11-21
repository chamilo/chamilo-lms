<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Migrations\MigrationIssueReporter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

final class DoctrineMigrationsMigrateCommandDecorator extends Command
{
    public const string SKIP_ATTENDANCES_FLAG = 'CHAMILO_MIGRATION_SKIP_ATTENDANCES';

    public function __construct(
        private readonly Command $inner,
        private readonly MigrationIssueReporter $issueReporter,
        private readonly ManagerRegistry $registry
    ) {
        parent::__construct($inner->getName() ?: 'doctrine:migrations:migrate');
    }

    protected function configure(): void
    {
        $this->setName($this->inner->getName() ?: 'doctrine:migrations:migrate');
        $this->setDescription((string) $this->inner->getDescription());
        $this->setHelp((string) $this->inner->getHelp());

        $definition = clone $this->inner->getDefinition();
        $this->setDefinition($definition);

        $this->addOption(
            'skip-attendances',
            null,
            InputOption::VALUE_NONE,
            'When enabled, only migrate attendances linked to gradebook (type=7). Others will be skipped.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->issueReporter->resetSummary();
        $exitCode = null;
        $skipAttendances = (bool) $input->getOption('skip-attendances');

        if ($skipAttendances) {
            $_SERVER[self::SKIP_ATTENDANCES_FLAG] = '1';
            $_ENV[self::SKIP_ATTENDANCES_FLAG] = '1';
            putenv(self::SKIP_ATTENDANCES_FLAG.'=1');
        }

        try {
            $innerDefinition = $this->inner->getDefinition();
            $data = [];

            foreach ($innerDefinition->getArguments() as $name => $argument) {
                $value = $input->getArgument($name);
                if (null !== $value) {
                    $data[$name] = $value;
                }
            }

            foreach ($innerDefinition->getOptions() as $name => $option) {
                $value = $input->getOption($name);

                if ($option->acceptValue()) {
                    if (null !== $value) {
                        $data['--'.$name] = $value;
                    }
                } else {
                    if (true === $value) {
                        $data['--'.$name] = true;
                    }
                }
            }

            $innerInput = new ArrayInput($data, $innerDefinition);
            $innerInput->setInteractive($input->isInteractive());

            $exitCode = $this->inner->run($innerInput, $output);

            return $exitCode;
        } finally {
            unset($_SERVER[self::SKIP_ATTENDANCES_FLAG], $_ENV[self::SKIP_ATTENDANCES_FLAG]);
            putenv(self::SKIP_ATTENDANCES_FLAG);

            // Dry-run/write-sql must never consume a pending upgrade report.
            if ((!$input->hasOption('dry-run') || !$input->getOption('dry-run'))
                && (!$input->hasOption('write-sql') || !$input->getOption('write-sql'))
            ) {
                $this->outputMigrationIssueSummary($input, $output, $exitCode);
            }
        }
    }

    private function outputMigrationIssueSummary(InputInterface $input, OutputInterface $output, ?int $exitCode): void
    {
        try {
            $connectionName = $input->hasOption('conn') ? $input->getOption('conn') : null;
            $managerName = $input->hasOption('em') ? $input->getOption('em') : null;
            if (\is_string($managerName) && '' !== $managerName) {
                $manager = $this->registry->getManager($managerName);
                if (!$manager instanceof EntityManagerInterface) {
                    throw new RuntimeException('Cannot resolve the migration database connection.');
                }
                $connection = $manager->getConnection();
            } else {
                $connection = $this->registry->getConnection(\is_string($connectionName) && '' !== $connectionName ? $connectionName : null);
            }
            $version = $input->hasArgument('version') ? $input->getArgument('version') : null;
            $completed = 0 === $exitCode && !$input->isInteractive() && (null === $version || 'latest' === $version);
            $summary = $this->issueReporter->summarize($connection, $completed);
            if (null !== $summary) {
                $output->writeln(\sprintf(
                    '%s: %d recoverable data issue(s). Full report: %s. Review affected content before use.',
                    match (true) {
                        $completed => 'Migration completed with data warnings',
                        0 === $exitCode => 'Migration data warnings (report retained)',
                        default => 'Migration did not complete; data issue snapshots retained',
                    },
                    $summary['count'],
                    $summary['path']
                ), OutputInterface::OUTPUT_RAW);
            }
        } catch (Throwable) {
            // Never hide a genuine migration failure or change an already-committed
            // migration's exit status because its final report could not be read.
            $this->issueReporter->markSummaryFailed();
            $output->writeln('Unable to summarize or archive migration data issues. Check var/log/migration-data-issues and its permissions.');
        }
    }
}
