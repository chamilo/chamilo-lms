<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\TempUploadHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'cache:clear-uploads',
    description: 'Clear temporary uploaded files (async upload chunks/cache).',
)]
final class ClearTempUploadsCommand extends Command
{
    public function __construct(
        private readonly TempUploadHelper $helper
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('older-than', null, InputOption::VALUE_REQUIRED, 'Minutes threshold (0 = delete all)', '60')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Preview only, do not delete')
            ->addOption('dir', null, InputOption::VALUE_REQUIRED, 'Override temp upload directory')
            ->setHelp(
                <<<'HELP'
Clears the configured temporary uploads directory (async upload chunks/cache).
Options:
  --older-than=MIN   Only delete files older than MIN minutes (default: 60). Use 0 to delete all files.
  --dry-run          Report what would be deleted without deleting.
  --dir=PATH         Override the configured temp directory for this run.

Examples:
  php bin/console cache:clear-uploads --older-than=60
  php bin/console cache:clear-uploads --dry-run
  php bin/console cache:clear-uploads --dir=/var/www/chamilo/var/uploads_tmp
HELP
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $kernelContainer = $this->getApplication()?->getKernel()?->getContainer();
        if ($kernelContainer) {
            Container::setContainer($kernelContainer);
        }

        $io = new SymfonyStyle($input, $output);
        $olderThan = (int) $input->getOption('older-than');
        $dryRun = (bool) $input->getOption('dry-run');
        $dir = $input->getOption('dir');

        if ($olderThan < 0) {
            $io->error('Option --older-than must be >= 0.');

            return Command::INVALID;
        }

        // Select helper (allow --dir override)
        $targetHelper = $this->helper;
        if (!empty($dir)) {
            // quick override instance (no service registration needed)
            $targetHelper = new TempUploadHelper($dir);
            if (!is_dir($targetHelper->getTempDir()) || !is_readable($targetHelper->getTempDir())) {
                $io->error(\sprintf('Directory not readable: %s', $targetHelper->getTempDir()));

                return Command::FAILURE;
            }
        }

        $tempDir = $targetHelper->getTempDir();

        // Run purge
        $stats = $targetHelper->purge($olderThan, $dryRun);

        $mb = $stats['bytes'] / 1048576;
        if ($dryRun) {
            $io->note(\sprintf(
                'DRY RUN: %d files (%.2f MB) would be removed in %s',
                $stats['files'],
                $mb,
                $tempDir
            ));
        } else {
            $io->success(\sprintf(
                'CLEANED: %d files removed (%.2f MB) in %s',
                $stats['files'],
                $mb,
                $tempDir
            ));
        }

        return Command::SUCCESS;
    }
}
