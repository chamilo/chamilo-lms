<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Component\LegacyCliBootstrapper;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
use Throwable;

#[AsCommand(
    name: 'chamilo:migration:migrate-calculated-answers',
    description: 'Migrates legacy single-formula Calculated question answers to the multi-variable/multi-formula format.'
)]
final class MigrateCalculatedAnswersCommand extends Command
{
    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly EntityManagerInterface $entityManager,
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Runs the migration inside a transaction that is rolled back, without writing anything.'
        );
    }

    /**
     * @psalm-suppress UndefinedFunction migrateCalculatedAnswer() is declared, with no namespace,
     * in the legacy .inc.php file required below -- Psalm does not scan that file (it is not
     * part of any composer autoload map), so it cannot see the declaration even though it exists
     * at runtime.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Migrate legacy calculated-answer questions');

        $dryRun = (bool) $input->getOption('dry-run');
        if ($dryRun) {
            $io->warning('Dry-run enabled. All database changes will be rolled back.');
        }

        LegacyCliBootstrapper::bootstrap($this->kernel, $this->entityManager);

        $migrationFile = $this->kernel->getProjectDir().'/public/main/exercise/calculated_answer_migration.inc.php';
        if (!is_file($migrationFile)) {
            throw new RuntimeException(\sprintf('Migration helper file was not found: %s', $migrationFile));
        }

        require_once $migrationFile;

        $this->connection->beginTransaction();

        try {
            migrateCalculatedAnswer();

            if ($dryRun) {
                $this->connection->rollBack();
            } else {
                $this->connection->commit();
            }
        } catch (Throwable $exception) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success($dryRun ? 'Dry-run completed.' : 'Calculated-answer migration completed.');

        return Command::SUCCESS;
    }
}
