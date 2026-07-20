<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Helpers\FileIntegrityChecker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:file-integrity:baseline',
    description: 'Generate (or regenerate) the trusted file-integrity baseline from the current file tree.',
)]
class FileIntegrityBaselineCommand extends Command
{
    public function __construct(
        private readonly FileIntegrityChecker $checker
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp(
            'Run this once after installing Chamilo, or whenever you want to explicitly '
            .'trust the current file tree as the new reference (e.g. after a manual update). '
            .'See also app:file-integrity:snooze, which adopts the tree automatically during '
            .'a maintenance window instead of requiring this command to be remembered.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $count = $this->checker->generateBaseline();

        $io->success(\sprintf('File integrity baseline generated: %d files captured.', $count));

        return Command::SUCCESS;
    }
}
