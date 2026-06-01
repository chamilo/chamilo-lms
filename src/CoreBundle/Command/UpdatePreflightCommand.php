<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Service\Update\UpdateManifestClient;
use Chamilo\CoreBundle\Service\Update\UpdatePreflightChecker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'chamilo:update:preflight',
    description: 'Run environment checks before applying a Chamilo update package.',
)]
final class UpdatePreflightCommand extends Command
{
    public function __construct(
        private readonly UpdateManifestClient $manifestClient,
        private readonly UpdatePreflightChecker $preflightChecker,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('manifest', InputArgument::REQUIRED, 'HTTPS URL or local path to the update manifest JSON.')
            ->addOption('package-path', null, InputOption::VALUE_REQUIRED, 'Local update package path used for package and disk-space checks.')
            ->setHelp(
                <<<'HELP'
Runs preflight checks for a Chamilo update without applying it.

The command checks:
  - update directory permissions;
  - free disk space;
  - PHP version requirement from the manifest;
  - package path readability and extension;
  - target version direction;
  - Git working tree state when the installation is a Git checkout;
  - project metadata needed by later update stages.

Example:
  php bin/console chamilo:update:preflight /tmp/chamilo-update.json --package-path=/tmp/chamilo.zip
HELP
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $manifestSource = (string) $input->getArgument('manifest');
        $packagePath = $this->readNullableStringOption($input, 'package-path');

        try {
            $manifest = $this->manifestClient->load($manifestSource);
            $result = $this->preflightChecker->check($manifest, $packagePath);
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

        $io->section('Update preflight checks');
        $io->table(['Status', 'Check', 'Message'], $rows);

        foreach ($result->getWarnings() as $warning) {
            $io->warning($warning);
        }

        if (!$result->isValid()) {
            foreach ($result->getErrors() as $error) {
                $io->error($error);
            }

            return Command::FAILURE;
        }

        $io->success('Update preflight checks completed.');

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
