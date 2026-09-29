<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Service\Update\UpdateAvailabilityChecker;
use Chamilo\CoreBundle\Service\Update\UpdateConfiguration;
use Chamilo\CoreBundle\Service\Update\UpdateManifestClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'chamilo:update:check',
    description: 'Check a Chamilo update manifest without applying any change.',
)]
final class UpdateCheckCommand extends Command
{
    public function __construct(
        private readonly UpdateManifestClient $manifestClient,
        private readonly UpdateAvailabilityChecker $availabilityChecker,
        private readonly UpdateConfiguration $updateConfiguration,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'manifest',
                InputArgument::OPTIONAL,
                'HTTPS URL or local path to the update manifest JSON. Defaults to the configured official update feed.',
            )
            ->addOption('current-version', null, InputOption::VALUE_REQUIRED, 'Installed version to compare with the manifest version.')
            ->setHelp(
                <<<'HELP'
Checks a Chamilo update manifest and reports the release metadata.

When no manifest is provided, the command uses the official/default manifest source configured by
the update manager.

This command does not download, verify, extract or apply an update package.

Examples:
  php bin/console chamilo:update:check
  php bin/console chamilo:update:check https://download.example.org/chamilo/stable.json
  php bin/console chamilo:update:check /tmp/chamilo-update.json --current-version=2.1.0
HELP
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $source = $input->getArgument('manifest');

        if (!\is_string($source) || '' === trim($source)) {
            $source = $this->updateConfiguration->getDefaultManifestSource();
        }

        if (null === $source || '' === trim($source)) {
            $io->error('No update manifest source is configured.');

            return Command::FAILURE;
        }

        $source = trim($source);
        $currentVersion = $input->getOption('current-version');

        try {
            $manifest = $this->manifestClient->load($source);
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->title('Chamilo update manifest');
        $io->definitionList(
            ['Manifest source' => $source],
            ['Channel' => $manifest->getChannel()],
            ['Version' => $manifest->getVersion()],
            ['Released at' => $manifest->getReleasedAt()],
            ['Package URL' => $manifest->getPackageUrl()],
            ['Package SHA-256' => $manifest->getPackageSha256()],
            ['Signature type' => $manifest->getSignatureType() ?? 'none'],
            ['Signature URL' => $manifest->getSignatureUrl() ?? 'none'],
            ['Signature key ID' => $manifest->getSignatureKeyId() ?? 'none'],
        );

        $availability = $this->availabilityChecker->check(
            $manifest,
            \is_string($currentVersion) ? $currentVersion : null,
        )->toArray();

        $io->definitionList(
            ['Installed version' => (string) $availability['installedVersion']],
            ['Availability status' => (string) $availability['status']],
            ['Next step' => (string) $availability['nextStep']],
        );

        if (true === $availability['updateAvailable']) {
            $io->success((string) $availability['message']);

            return Command::SUCCESS;
        }

        if (true === $availability['sameVersion']) {
            $io->success((string) $availability['message']);

            return Command::SUCCESS;
        }

        if (true === $availability['downgrade']) {
            $io->warning((string) $availability['message']);

            return Command::SUCCESS;
        }

        $io->warning((string) $availability['message']);

        return Command::SUCCESS;
    }
}
