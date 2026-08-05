<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Service\Update\UpdatePostApplyCommandRunner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;

#[AsCommand(
    name: 'chamilo:update:run-post-apply',
    description: 'Run controlled post-apply update commands recommended by the post-apply checks report.',
)]
final class UpdateRunPostApplyCommand extends Command
{
    public function __construct(
        private readonly UpdatePostApplyCommandRunner $commandRunner,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('staging-path', InputArgument::REQUIRED, 'Path to a staged update directory under var/update/staging.')
            ->addOption('action', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Action key to run. Can be repeated.')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Run all actions recommended by POST-APPLY-CHECKS.json.')
            ->addOption('confirm', null, InputOption::VALUE_NONE, 'Required confirmation to execute post-apply commands.')
            ->addOption('confirm-advanced', null, InputOption::VALUE_NONE, 'Required when running Composer, Yarn or database migration actions.')
            ->addOption('confirm-database-backup', null, InputOption::VALUE_NONE, 'Required when running database migrations. Confirms that a database backup exists.')
            ->addOption('confirm-database-migrations', null, InputOption::VALUE_NONE, 'Required when running database migrations after reviewing MIGRATION-SAFETY-CHECKS.json.')
            ->addOption('operation-id', null, InputOption::VALUE_REQUIRED, 'Optional operation id used to write live progress logs.')
            ->setHelp(
                <<<'HELP'
Runs controlled post-apply update commands from a fixed allowlist.

Allowed action keys:
  - composer_install
  - yarn_install
  - yarn_build
  - doctrine_migrations
  - cache_clear

This command does not accept arbitrary shell commands.
Composer, Yarn and database migration actions require --confirm-advanced.
Database migration actions also require --confirm-database-backup and --confirm-database-migrations.

Examples:
  php bin/console chamilo:update:run-post-apply var/update/staging/2.1.0-20260603163014-bc4b52a8 --action=cache_clear --confirm
  php bin/console chamilo:update:run-post-apply var/update/staging/2.1.0-20260603163014-bc4b52a8 --all --confirm --confirm-advanced --confirm-database-backup --confirm-database-migrations
HELP
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $stagingPath = (string) $input->getArgument('staging-path');
        $confirmed = (bool) $input->getOption('confirm');
        $confirmedAdvanced = (bool) $input->getOption('confirm-advanced');
        $confirmedDatabaseBackup = (bool) $input->getOption('confirm-database-backup');
        $confirmedDatabaseMigrations = (bool) $input->getOption('confirm-database-migrations');
        $operationId = $this->readNullableStringOption($input, 'operation-id');
        $actions = $this->readActionKeys($input);

        if (!$confirmed) {
            $io->error('This command executes post-apply update commands. Re-run with --confirm to continue.');

            return Command::FAILURE;
        }

        if ([] === $actions) {
            $io->error('Select at least one action with --action or use --all.');

            return Command::FAILURE;
        }

        try {
            $result = $this->commandRunner->run(
                $stagingPath,
                $actions,
                true,
                $operationId,
                $confirmedAdvanced,
                $confirmedDatabaseBackup,
                $confirmedDatabaseMigrations
            );
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $rows = [];
        foreach ($result->getChecks() as $check) {
            $rows[] = [
                strtoupper($check['status']),
                $check['key'],
                $check['message'],
            ];
        }

        $io->section('Update post-apply command run');
        if ([] !== $rows) {
            $io->table(['Status', 'Check', 'Message'], $rows);
        }

        foreach ($result->getWarnings() as $warning) {
            $io->warning($warning);
        }

        if (!$result->isValid()) {
            foreach ($result->getErrors() as $error) {
                $io->error($error);
            }

            return Command::FAILURE;
        }

        $actionRows = [];
        foreach ($result->getActions() as $action) {
            $actionRows[] = [
                strtoupper($action['status']),
                $action['key'],
                $action['command'],
                (string) ($action['durationSeconds'] ?? ''),
            ];
        }

        if ([] !== $actionRows) {
            $io->table(['Status', 'Action', 'Command', 'Duration'], $actionRows);
        }

        $io->success('Post-apply update commands completed.');
        $io->definitionList(
            ['Staging directory' => $result->getStagingPath() ?? 'unknown'],
            ['Metadata file' => $result->getMetadataPath() ?? 'unknown'],
            ['Operation id' => $result->getOperationId() ?? 'unknown'],
        );

        if ($output->isVerbose()) {
            $io->writeln(json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}');
        }

        return Command::SUCCESS;
    }

    /**
     * @return string[]
     */
    private function readActionKeys(InputInterface $input): array
    {
        if ((bool) $input->getOption('all')) {
            return ['__all__'];
        }

        $actions = $input->getOption('action');

        if (!\is_array($actions)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): string => \is_string($value) ? trim($value) : '',
            $actions,
        ))));
    }

    private function readNullableStringOption(InputInterface $input, string $name): ?string
    {
        $value = $input->getOption($name);

        if (!\is_string($value)) {
            return null;
        }

        $value = trim($value);

        return '' !== $value ? $value : null;
    }
}
