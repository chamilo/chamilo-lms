<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Service\Update\UpdateConfiguration;
use Chamilo\CoreBundle\Service\Update\UpdateManifestClient;
use Chamilo\CoreBundle\Service\Update\UpdatePackageDownloader;
use Chamilo\CoreBundle\Service\Update\UpdatePackageVerifier;
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
    name: 'chamilo:update:verify',
    description: 'Download or read a Chamilo update package and verify hash, signature and archive safety.',
)]
final class UpdateVerifyPackageCommand extends Command
{
    public function __construct(
        private readonly UpdateManifestClient $manifestClient,
        private readonly UpdatePackageDownloader $packageDownloader,
        private readonly UpdatePackageVerifier $packageVerifier,
        private readonly UpdateConfiguration $updateConfiguration,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('manifest', InputArgument::REQUIRED, 'HTTPS URL or local path to the update manifest JSON.')
            ->addOption('package-path', null, InputOption::VALUE_REQUIRED, 'Local update package path. If omitted, the package URL from the manifest is downloaded.')
            ->addOption('signature-path', null, InputOption::VALUE_REQUIRED, 'Local signature path. If omitted and the manifest defines a signature URL, it is downloaded.')
            ->addOption('trusted-public-key', null, InputOption::VALUE_REQUIRED, 'Trusted public key for the configured signature verifier, e.g. Minisign public key. Defaults to CHAMILO_UPDATE_MINISIGN_PUBLIC_KEY when configured.')
            ->addOption('work-dir', null, InputOption::VALUE_REQUIRED, 'Directory used for downloaded update files.', 'var/update/downloads')
            ->addOption('skip-signature', null, InputOption::VALUE_NONE, 'Skip signature verification. Intended only for local development tests.')
            ->setHelp(
                <<<'HELP'
Verifies a Chamilo update package without applying it.

The command checks:
  - manifest format;
  - package SHA-256;
  - package signature when configured;
  - ZIP archive safety, including path traversal and symlink rejection.

Examples:
  php bin/console chamilo:update:verify /tmp/chamilo-update.json --package-path=/tmp/chamilo.zip --signature-path=/tmp/chamilo.zip.minisig --trusted-public-key=RW...
  php bin/console chamilo:update:verify https://download.example.org/chamilo/stable.json
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
        $trustedPublicKey = $this->readNullableStringOption($input, 'trusted-public-key') ?? $this->updateConfiguration->getTrustedPublicKey();
        $workDir = (string) $input->getOption('work-dir');
        $skipSignature = (bool) $input->getOption('skip-signature');

        if ($skipSignature && !$this->updateConfiguration->allowsSkipSignature()) {
            $io->error('Skipping update signature verification is disabled in this environment.');

            return Command::FAILURE;
        }

        try {
            $manifest = $this->manifestClient->load($manifestSource);

            if (null === $packagePath) {
                $io->section('Downloading update package');
                $packagePath = $this->packageDownloader->download($manifest->getPackageUrl(), $workDir);
                $io->text('Package downloaded to '.$packagePath);
            }

            if (!$skipSignature && null === $signaturePath && null !== $manifest->getSignatureUrl()) {
                $io->section('Downloading update signature');
                $signaturePath = $this->packageDownloader->download($manifest->getSignatureUrl(), $workDir);
                $io->text('Signature downloaded to '.$signaturePath);
            }

            $io->section('Verifying update package');
            $result = $this->packageVerifier->verify(
                $packagePath,
                $manifest,
                $signaturePath,
                $trustedPublicKey,
                $skipSignature,
            );
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
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

        $io->success('Update package verified successfully.');

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
