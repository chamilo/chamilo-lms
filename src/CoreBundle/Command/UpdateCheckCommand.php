<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

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
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('manifest', InputArgument::REQUIRED, 'HTTPS URL or local path to the update manifest JSON.')
            ->addOption('current-version', null, InputOption::VALUE_REQUIRED, 'Installed version to compare with the manifest version.')
            ->setHelp(
                <<<'HELP'
Checks a Chamilo update manifest and reports the release metadata.

This command does not download, verify, extract or apply an update package.

Examples:
  php bin/console chamilo:update:check https://download.example.org/chamilo/stable.json
  php bin/console chamilo:update:check /tmp/chamilo-update.json --current-version=2.1.0
HELP
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $source = (string) $input->getArgument('manifest');
        $currentVersion = $input->getOption('current-version');

        try {
            $manifest = $this->manifestClient->load($source);
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->title('Chamilo update manifest');
        $io->definitionList(
            ['Channel' => $manifest->getChannel()],
            ['Version' => $manifest->getVersion()],
            ['Released at' => $manifest->getReleasedAt()],
            ['Package URL' => $manifest->getPackageUrl()],
            ['Package SHA-256' => $manifest->getPackageSha256()],
            ['Signature type' => $manifest->getSignatureType() ?? 'none'],
            ['Signature URL' => $manifest->getSignatureUrl() ?? 'none'],
        );

        if (\is_string($currentVersion) && '' !== trim($currentVersion)) {
            $comparison = version_compare($manifest->getVersion(), trim($currentVersion));

            if ($comparison > 0) {
                $io->success('An update is available.');
            } elseif (0 === $comparison) {
                $io->success('The installed version matches the manifest version.');
            } else {
                $io->warning('The manifest version is older than the installed version.');
            }
        }

        return Command::SUCCESS;
    }
}
