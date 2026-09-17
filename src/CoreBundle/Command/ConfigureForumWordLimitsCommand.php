<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Doctrine\DBAL\Connection;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'chamilo:migration:configure-forum-word-limits',
    description: 'Configure minimum word counts for forum replies.'
)]
final class ConfigureForumWordLimitsCommand extends Command
{
    private const string FIRST_VARIABLE = 'first_reply_min_words';
    private const string SUBSEQUENT_VARIABLE = 'subsequent_reply_min_words';

    public function __construct(
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'first',
                null,
                InputOption::VALUE_REQUIRED,
                'Minimum words when the user has no previous post in the forum thread.'
            )
            ->addOption(
                'subsequent',
                null,
                InputOption::VALUE_REQUIRED,
                'Minimum words when the user already has a post in the forum thread.'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Show the change without writing it.'
            )
        ;
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $io = new SymfonyStyle($input, $output);

        $firstRaw = $input->getOption('first');
        $subsequentRaw = $input->getOption('subsequent');

        if (
            null === $firstRaw
            || null === $subsequentRaw
            || !preg_match('/^\d+$/', (string) $firstRaw)
            || !preg_match('/^\d+$/', (string) $subsequentRaw)
        ) {
            $io->error(
                'Options --first and --subsequent are required and must be non-negative integers.'
            );

            return Command::INVALID;
        }

        $first = (int) $firstRaw;
        $subsequent = (int) $subsequentRaw;
        $dryRun = (bool) $input->getOption('dry-run');

        $schemaManager = $this->connection->createSchemaManager();

        $candidateTables = ['settings', 'settings_current'];
        $tables = [];

        foreach ($candidateTables as $table) {
            if (!$schemaManager->tablesExist([$table])) {
                continue;
            }

            $availableVariables = (int) $this->connection->fetchOne(
                \sprintf(
                    <<<'SQL'
SELECT COUNT(DISTINCT variable)
FROM %s
WHERE category = 'forum'
  AND variable IN (?, ?)
SQL,
                    $table
                ),
                [
                    self::FIRST_VARIABLE,
                    self::SUBSEQUENT_VARIABLE,
                ]
            );

            if (2 === $availableVariables) {
                $tables[] = $table;
            }
        }

        if ([] === $tables) {
            $io->error(
                'Forum word-limit settings are missing. Run the corresponding migration first.'
            );

            return Command::FAILURE;
        }

        $io->definitionList(
            ['First reply minimum' => $first],
            ['Subsequent reply minimum' => $subsequent],
            ['Tables' => implode(', ', $tables)],
            ['Dry run' => $dryRun ? 'yes' : 'no'],
        );

        if ($dryRun) {
            $io->success('Dry-run completed. No settings were changed.');

            return Command::SUCCESS;
        }

        $this->connection->beginTransaction();

        try {
            foreach ($tables as $table) {
                $this->connection->executeStatement(
                    \sprintf(
                        <<<'SQL'
UPDATE %s
SET selected_value = CASE variable
    WHEN ? THEN ?
    WHEN ? THEN ?
    ELSE selected_value
END
WHERE category = 'forum'
  AND variable IN (?, ?)
SQL,
                        $table
                    ),
                    [
                        self::FIRST_VARIABLE,
                        (string) $first,
                        self::SUBSEQUENT_VARIABLE,
                        (string) $subsequent,
                        self::FIRST_VARIABLE,
                        self::SUBSEQUENT_VARIABLE,
                    ]
                );

                $invalidRows = (int) $this->connection->fetchOne(
                    \sprintf(
                        <<<'SQL'
SELECT COUNT(*)
FROM %s
WHERE category = 'forum'
  AND (
      (variable = ? AND selected_value <> ?)
      OR
      (variable = ? AND selected_value <> ?)
  )
SQL,
                        $table
                    ),
                    [
                        self::FIRST_VARIABLE,
                        (string) $first,
                        self::SUBSEQUENT_VARIABLE,
                        (string) $subsequent,
                    ]
                );

                if (0 !== $invalidRows) {
                    throw new RuntimeException(\sprintf('Forum word-limit settings verification failed for table %s.', $table));
                }
            }

            $this->connection->commit();
        } catch (Throwable $exception) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success(\sprintf(
            'Forum word limits configured: first=%d subsequent=%d.',
            $first,
            $subsequent
        ));

        return Command::SUCCESS;
    }
}
