<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Service\Update\UpdateManifestClient;
use Chamilo\CoreBundle\Service\Update\UpdatePackageDownloader;
use Chamilo\CoreBundle\Service\Update\UpdatePackageVerifier;
use Chamilo\CoreBundle\Service\Update\UpdatePreflightChecker;
use Chamilo\CoreBundle\Service\Update\UpdateStagingManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'chamilo:update:stage',
    description: 'Verify and extract a Chamilo update package into a staging directory without applying it.',
)]
final class UpdateStagePackageCommand extends Command
{
    public function __construct(
        private readonly UpdateManifestClient $manifestClient,
        private readonly UpdatePackageDownloader $packageDownloader,
        private readonly UpdatePackageVerifier $packageVerifier,
        private readonly UpdatePreflightChecker $preflightChecker,
        private readonly UpdateStagingManager $stagingManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('manifest', InputArgument::REQUIRED, 'HTTPS URL or local path to the update manifest JSON.')
            ->addOption('package-path', null, InputOption::VALUE_REQUIRED, 'Local package path. If omitted, the package URL from the manifest is downloaded.')
            ->addOption('signature-path', null, InputOption::VALUE_REQUIRED, 'Local Minisign signature path. If omitted, the signature URL from the manifest is downloaded when needed.')
            ->addOption('trusted-public-key', null, InputOption::VALUE_REQUIRED, 'Trusted Minisign public key used to verify the update package.')
            ->addOption('skip-signature', null, InputOption::VALUE_NONE, 'Skip signature verification. Only use this for local development tests.')
            ->setHelp(
                <<<'HELP'
Verifies a Chamilo update package, runs preflight checks, and extracts the package to var/update/staging.

This command does not apply the update. It does not replace files, run migrations, or modify the database.

Example:
  php bin/console chamilo:update:stage /tmp/chamilo-update.json --package-path=/tmp/chamilo.zip --skip-signature
HELP
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $manifestSource = (string) $input->getArgument('manifest');
        $packagePath = $this->readNullableStringOption($input, 'package-path');
        $signaturePath = $this->readNullableStringOption($input, 'signature-path');
        $trustedPublicKey = $this->readNullableStringOption($input, 'trusted-public-key');
        $skipSignature = (bool) $input->getOption('skip-signature');

        try {
            $manifest = $this->manifestClient->load($manifestSource);

            if (null === $packagePath) {
                $io->section('Downloading update package');
                $packagePath = $this->packageDownloader->download($manifest->getPackageUrl());
                $io->text('Package downloaded to '.$packagePath);
            }

            if (!$skipSignature && null === $signaturePath && null !== $manifest->getSignatureUrl()) {
                $io->section('Downloading update signature');
                $signaturePath = $this->packageDownloader->download($manifest->getSignatureUrl());
                $io->text('Signature downloaded to '.$signaturePath);
            }

            $io->section('Verifying update package');
            $verificationResult = $this->packageVerifier->verify(
                $packagePath,
                $manifest,
                $signaturePath,
                $trustedPublicKey,
                $skipSignature,
            );

            foreach ($verificationResult->getWarnings() as $warning) {
                $io->warning($warning);
            }

            if (!$verificationResult->isValid()) {
                foreach ($verificationResult->getErrors() as $error) {
                    $io->error($error);
                }

                return Command::FAILURE;
            }

            $io->section('Running preflight checks');
            $preflightResult = $this->preflightChecker->check($manifest, $packagePath);

            foreach ($preflightResult->getWarnings() as $warning) {
                $io->warning($warning);
            }

            if (!$preflightResult->isValid()) {
                foreach ($preflightResult->getErrors() as $error) {
                    $io->error($error);
                }

                return Command::FAILURE;
            }

            $io->section('Preparing staging directory');
            $stagingResult = $this->stagingManager->stage($manifest, $packagePath);
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $rows = [];
        foreach ($stagingResult->getChecks() as $check) {
            $rows[] = [
                strtoupper($check['status']),
                $check['key'],
                $check['message'],
            ];
        }

        if ([] !== $rows) {
            $io->table(['Status', 'Check', 'Message'], $rows);
        }

        foreach ($stagingResult->getWarnings() as $warning) {
            $io->warning($warning);
        }

        if (!$stagingResult->isValid()) {
            foreach ($stagingResult->getErrors() as $error) {
                $io->error($error);
            }

            return Command::FAILURE;
        }

        $io->success('Update package staged successfully.');
        $io->definitionList(
            ['Staging directory' => $stagingResult->getStagingPath() ?? 'unknown'],
            ['Application path' => $stagingResult->getApplicationPath() ?? 'unknown'],
        );

        if ($output->isVerbose()) {
            $io->writeln(json_encode($stagingResult->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}');
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
