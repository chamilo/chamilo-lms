<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Service\HotPotatoes\HotPotatoesPackageInspector;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:hotpotatoes:inspect', description: 'Check a Hot Potatoes export without importing it.')]
final class InspectHotPotatoesCommand extends Command
{
    public function __construct(
        private readonly HotPotatoesPackageInspector $inspector
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('path', InputArgument::REQUIRED, 'Path to an HTML or ZIP export');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $path = (string) $input->getArgument('path');

        try {
            $result = $this->inspector->inspect($path, basename($path));
        } catch (RuntimeException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success('Hot Potatoes package structure is valid for preflight inspection.');
        $io->definitionList(
            ['Format' => $result['format']],
            ['Files' => (string) $result['files']],
            ['Uncompressed bytes' => (string) $result['uncompressedBytes']],
        );
        $io->section('HTML entry points');
        $io->listing($result['entrypoints']);
        $io->note('Preflight only: the package was not imported, scanned for active content, or approved for browser playback.');

        return Command::SUCCESS;
    }
}
