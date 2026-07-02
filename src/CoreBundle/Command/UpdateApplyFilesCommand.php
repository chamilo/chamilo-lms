<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Service\Update\UpdateFileApplier;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'chamilo:update:apply-files',
    description: 'Apply staged Chamilo update files using the generated apply plan, backup and lock.',
)]
final class UpdateApplyFilesCommand extends Command
{
    public function __construct(
        private readonly UpdateFileApplier $fileApplier,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('staging-path', InputArgument::REQUIRED, 'Staging directory containing APPLY-PLAN.json.')
            ->addOption('confirm', null, InputOption::VALUE_NONE, 'Required confirmation to replace files in the Chamilo installation.')
            ->addOption('operation-id', null, InputOption::VALUE_REQUIRED, 'Optional operation id used to write live progress logs.')
            ->setHelp(
                <<<'HELP'
Applies staged update files to the Chamilo installation.

This command:
  - reads APPLY-PLAN.json from the staging directory;
  - acquires var/update/update.lock;
  - backs up files that will be replaced;
  - copies staged files to the installation;
  - writes apply audit metadata;
  - rolls back copied files if the file-copy step fails.

It does not run database migrations, Composer, Yarn, cache clear or maintenance mode.

Example:
  php bin/console chamilo:update:apply-files var/update/staging/2.1.0-20260603190119-fb34a717 --confirm
HELP
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $stagingPath = (string) $input->getArgument('staging-path');
        $confirmed = (bool) $input->getOption('confirm');
        $operationId = $this->readNullableStringOption($input, 'operation-id');

        if (!$confirmed) {
            $io->error('This command replaces Chamilo installation files. Re-run with --confirm to continue.');

            return Command::FAILURE;
        }

        try {
            $result = $this->fileApplier->apply($stagingPath, true, $operationId);
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

        if ([] !== $rows) {
            $io->section('Update file application');
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

        $io->success('Staged update files applied successfully.');
        $io->definitionList(
            ['Backup directory' => $result->getBackupPath() ?? 'unknown'],
            ['Audit file' => $result->getAuditPath() ?? 'unknown'],
        );

        if ($output->isVerbose()) {
            $io->writeln(json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}');
        }

        return Command::SUCCESS;
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
