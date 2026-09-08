<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;

#[AsCommand(
    name: 'chamilo:database:detect-server-version',
    description: 'Detect the connected DB server version and pin it into .env.local as DATABASE_SERVER_VERSION.',
)]
class DetectDatabaseServerVersionCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp(
            'config/packages/doctrine.yaml pins DATABASE_SERVER_VERSION so Doctrine never needs '
            .'a live connection just to resolve its platform (needed for any console command to '
            .'boot, not just this one). The web installer detects and writes the real value '
            .'automatically; this command exists for setups that configure .env/.env.local by '
            .'hand and skip the web installer (Docker entrypoints, CI, manual upgrades). Run it '
            .'once a real database is reachable, and re-run it after migrating to a different '
            .'DB server or major version.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $version = $this->connection->fetchOne('SELECT VERSION()');
        } catch (Throwable $e) {
            $io->error('Could not connect to the configured database: '.$e->getMessage());

            return Command::FAILURE;
        }

        if (!\is_string($version) || '' === trim($version)) {
            $io->error('The database did not return a usable version string.');

            return Command::FAILURE;
        }

        $version = trim($version);
        $envLocalFile = $this->projectDir.'/.env.local';
        $line = "DATABASE_SERVER_VERSION='".str_replace("'", "\\'", $version)."'";

        $contents = is_file($envLocalFile) ? (string) file_get_contents($envLocalFile) : '';
        if (preg_match('/^DATABASE_SERVER_VERSION=.*$/m', $contents)) {
            $contents = preg_replace('/^DATABASE_SERVER_VERSION=.*$/m', $line, $contents);
        } else {
            $contents = rtrim($contents)."\n".$line."\n";
        }

        file_put_contents($envLocalFile, ltrim($contents, "\n"));

        $io->success(\sprintf('Detected "%s" and pinned it as DATABASE_SERVER_VERSION in %s', $version, $envLocalFile));

        return Command::SUCCESS;
    }
}
