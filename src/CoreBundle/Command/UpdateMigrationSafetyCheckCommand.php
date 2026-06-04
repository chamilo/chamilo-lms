<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Service\Update\UpdateMigrationSafetyChecker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'chamilo:update:migration-safety',
    description: 'Review staged Doctrine migrations and run a dry-run before database migration execution.',
)]
final class UpdateMigrationSafetyCheckCommand extends Command
{
    public function __construct(
        private readonly UpdateMigrationSafetyChecker $migrationSafetyChecker,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('staging-path', InputArgument::REQUIRED, 'Path to a staged update directory under var/update/staging.')
            ->setHelp(
                <<<'HELP'
Reviews Doctrine migrations detected in a staged update.

This command does not execute migrations and does not create a database backup.
It reads POST-APPLY-CHECKS.json, lists staged migration classes, runs:

  php bin/console doctrine:migrations:migrate --dry-run --no-interaction

and writes MIGRATION-SAFETY-CHECKS.json in the staging directory.

Example:
  php bin/console chamilo:update:migration-safety var/update/staging/2.1.0-20260603163014-bc4b52a8
HELP
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $stagingPath = (string) $input->getArgument('staging-path');

        try {
            $result = $this->migrationSafetyChecker->check($stagingPath);
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->section('Update migration safety review');

        $rows = [];
        foreach ($result->getChecks() as $check) {
            $rows[] = [
                strtoupper($check['status']),
                $check['key'],
                $check['message'],
            ];
        }

        if ([] !== $rows) {
            $io->table(['Status', 'Check', 'Message'], $rows);
        }

        foreach ($result->getWarnings() as $warning) {
            $io->warning($warning);
        }

        if ([] !== $result->getMigrations()) {
            $migrationRows = [];
            foreach ($result->getMigrations() as $migration) {
                $migrationRows[] = [
                    $migration['class'],
                    $migration['path'],
                    $migration['description'],
                ];
            }

            $io->table(['Class', 'Path', 'Description'], $migrationRows);
        }

        if (null !== $result->getDryRunCommand()) {
            $io->definitionList(
                ['Dry-run command' => $result->getDryRunCommand()],
                ['Dry-run exit code' => (string) ($result->getDryRunExitCode() ?? '')],
                ['Metadata file' => $result->getMetadataPath() ?? 'not written'],
            );

            if ('' !== $result->getDryRunOutput()) {
                $io->section('Dry-run output');
                $io->writeln($result->getDryRunOutput());
            }
        }

        if (!$result->isValid()) {
            foreach ($result->getErrors() as $error) {
                $io->error($error);
            }

            return Command::FAILURE;
        }

        $io->success('Migration safety review completed.');

        if ($output->isVerbose()) {
            $io->writeln(json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}');
        }

        return Command::SUCCESS;
    }
}
