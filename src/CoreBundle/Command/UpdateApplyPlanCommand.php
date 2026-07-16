<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Service\Update\UpdateApplyPlanner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;

#[AsCommand(
    name: 'chamilo:update:apply-plan',
    description: 'Build a safe apply plan from a staged Chamilo update package without replacing files.',
)]
final class UpdateApplyPlanCommand extends Command
{
    public function __construct(
        private readonly UpdateApplyPlanner $applyPlanner,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('staging-path', InputArgument::REQUIRED, 'Path to a staged update directory under var/update/staging.')
            ->setHelp(
                <<<'HELP'
Builds an apply plan from a staged Chamilo update package.

This command does not replace files, run migrations, enable maintenance mode or modify the database.
It checks:
  - staging directory safety;
  - staging metadata;
  - update lock status;
  - planned backup path;
  - target write permissions;
  - files that would be replaced or created.

Example:
  php bin/console chamilo:update:apply-plan var/update/staging/2.1.0-20260603163014-bc4b52a8
HELP
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $stagingPath = (string) $input->getArgument('staging-path');

        try {
            $result = $this->applyPlanner->buildPlan($stagingPath);
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

        $io->section('Update apply plan');
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

        $io->success('Update apply plan is ready.');
        $io->definitionList(
            ['Staging directory' => $result->getStagingPath() ?? 'unknown'],
            ['Application path' => $result->getApplicationPath() ?? 'unknown'],
            ['Planned backup path' => $result->getBackupPath() ?? 'unknown'],
            ['Update lock path' => $result->getLockPath() ?? 'unknown'],
        );

        if ($output->isVerbose()) {
            $io->writeln(json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}');
        }

        return Command::SUCCESS;
    }
}
