<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Service\Update\UpdatePostApplyChecker;
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
    name: 'chamilo:update:post-apply',
    description: 'Inspect a staged update after file application and report recommended manual follow-up actions.',
)]
final class UpdatePostApplyCheckCommand extends Command
{
    public function __construct(
        private readonly UpdatePostApplyChecker $postApplyChecker,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('staging-path', InputArgument::REQUIRED, 'Path to a staged update directory under var/update/staging.')
            ->setHelp(
                <<<'HELP'
Builds a post-apply report after staged update files have been applied.

This command does not run Composer, Yarn, database migrations or cache commands.
It only reports recommended manual commands based on the staged package contents.

Example:
  php bin/console chamilo:update:post-apply var/update/staging/2.1.0-20260603163014-bc4b52a8
HELP
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $stagingPath = (string) $input->getArgument('staging-path');

        try {
            $result = $this->postApplyChecker->check($stagingPath);
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

        $io->section('Update post-apply checks');
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
                strtoupper($action['severity']),
                $action['title'],
                implode("\n", $action['commands']),
            ];
        }

        if ([] !== $actionRows) {
            $io->section('Recommended manual actions');
            $io->table(['Severity', 'Action', 'Commands'], $actionRows);
        }

        $io->success('Update post-apply checks completed.');
        $io->definitionList(
            ['Staging directory' => $result->getStagingPath() ?? 'unknown'],
            ['Metadata file' => $result->getMetadataPath() ?? 'unknown'],
        );

        if ($output->isVerbose()) {
            $io->writeln(json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}');
        }

        return Command::SUCCESS;
    }
}
