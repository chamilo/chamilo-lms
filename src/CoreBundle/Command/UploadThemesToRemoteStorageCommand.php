<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;

use const DIRECTORY_SEPARATOR;

#[AsCommand(
    name: 'chamilo:remote-storage:upload-themes',
    description: 'Upload the themes shipped in var/themes to the configured remote storage.',
)]
final class UploadThemesToRemoteStorageCommand extends Command
{
    public function __construct(
        #[Autowire(service: 'oneup_flysystem.themes_filesystem')]
        private readonly FilesystemOperator $filesystem,
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('overwrite', null, InputOption::VALUE_NONE, 'Replace files that already exist on the remote storage')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Report what would be uploaded, without writing anything')
            ->setHelp(
                <<<'HELP'
The themes bundled with Chamilo live in var/themes, which is also the location the default
(local) Flysystem adapter serves them from. When the themes filesystem is configured to use
a remote storage (Azure, S3, Google Cloud) that storage starts out empty, so logos, colors
and images are missing until those files are uploaded.

Run this command after pointing the themes filesystem at a remote storage, and after any
update that changes the bundled themes. It does nothing when no remote storage is configured
and the themes filesystem is the local var/themes directory.

Existing files are kept unless --overwrite is given, so re-running it never discards logos
or colors uploaded by an administrator.

Examples:
  php bin/console chamilo:remote-storage:upload-themes
  php bin/console chamilo:remote-storage:upload-themes --dry-run
  php bin/console chamilo:remote-storage:upload-themes --overwrite
HELP
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $overwrite = (bool) $input->getOption('overwrite');
        $dryRun = (bool) $input->getOption('dry-run');

        $source = $this->projectDir.'/var/themes';

        if (!is_dir($source)) {
            $io->error(\sprintf('Source directory not found: %s', $source));

            return Command::FAILURE;
        }

        if ($this->targetIsSourceDirectory($source)) {
            $io->note(\sprintf('No remote storage configured: the themes filesystem is %s. Nothing to upload.', $source));

            return Command::SUCCESS;
        }

        $uploaded = 0;
        $skipped = 0;
        $failed = 0;

        foreach ((new Finder())->files()->in($source)->sortByName() as $file) {
            $path = str_replace(DIRECTORY_SEPARATOR, '/', $file->getRelativePathname());

            try {
                if (!$overwrite && $this->filesystem->fileExists($path)) {
                    ++$skipped;

                    if ($io->isVerbose()) {
                        $io->writeln(\sprintf('  kept      %s', $path));
                    }

                    continue;
                }

                if (!$dryRun) {
                    $stream = fopen($file->getPathname(), 'rb');

                    if (!\is_resource($stream)) {
                        throw new RuntimeException(\sprintf('Could not read %s', $file->getPathname()));
                    }

                    try {
                        $this->filesystem->writeStream($path, $stream);
                    } finally {
                        // The Azure adapter hands the handle to a Guzzle PSR-7 stream, whose
                        // destructor closes it, so it is often already closed here. Static
                        // analysis reports this check as always true; it is not.
                        if (\is_resource($stream)) {
                            fclose($stream);
                        }
                    }
                }

                ++$uploaded;

                if ($io->isVerbose()) {
                    $io->writeln(\sprintf('  uploaded  %s', $path));
                }
            } catch (FilesystemException|RuntimeException $e) {
                ++$failed;
                $io->warning(\sprintf('%s: %s', $path, $e->getMessage()));
            }
        }

        $summary = \sprintf('%d file(s) uploaded, %d kept, %d failed.', $uploaded, $skipped, $failed);

        if ($failed > 0) {
            $io->error($summary);

            return Command::FAILURE;
        }

        if ($dryRun) {
            $io->note('DRY RUN: '.$summary);

            return Command::SUCCESS;
        }

        $io->success($summary);

        return Command::SUCCESS;
    }

    /**
     * Tells whether the themes filesystem is the source directory itself, which is the case
     * when no remote storage is configured and the default local adapter is in use. Adapters
     * do not expose their location, so this writes a probe file and checks whether it shows
     * up in the source directory.
     */
    private function targetIsSourceDirectory(string $source): bool
    {
        $probe = '.chamilo-themes-upload-'.uniqid('', true);

        try {
            $this->filesystem->write($probe, '');
            $isSameDirectory = is_file($source.'/'.$probe);
            $this->filesystem->delete($probe);
        } catch (FilesystemException) {
            return false;
        }

        return $isSameDirectory;
    }
}
