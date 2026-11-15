<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Doctrine\DBAL\Connection;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * Recalculates tag.count from the relation rows that use the tag (user_rel_tag, extra_field_rel_tag and
 * portfolio_rel_tag). Earlier versions of the user tag code (UserManager::add_tag / delete_user_tags) let the
 * counters drift: a new tag started at 1 without any link, deleting links did not always decrement, and so on.
 *
 * Read-only by default; --apply --expected-count=N writes (see DryRunByDefaultTrait). No migration on purpose:
 * the counters are only a display/sort hint, and the operator decides when to resync them.
 */
#[AsCommand(
    name: 'app:repair-user-tag-counters',
    description: 'Recalculates the usage counter of tags from their relation rows. Read-only unless --apply is given.',
)]
final class RepairUserTagCountersCommand extends Command
{
    use DryRunByDefaultTrait;

    private const int MAX_DETAIL_ROWS = 100;

    private const string ACTUAL_COUNT_SQL = '(SELECT COUNT(*) FROM user_rel_tag urt WHERE urt.tag_id = t.id)
        + (SELECT COUNT(*) FROM extra_field_rel_tag efrt WHERE efrt.tag_id = t.id)
        + (SELECT COUNT(*) FROM portfolio_rel_tag prt WHERE prt.tag_id = t.id)';

    public function __construct(
        private readonly Connection $connection
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addApplyOptions();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Repair tag usage counters');

        try {
            $apply = $this->isApply($input);
            if ($apply) {
                $this->requireExpectedCountOption($input);
            }

            $drifted = $this->findDriftedTags();
            $this->printDrift($drifted, $io);

            if (!$apply) {
                $io->note(\sprintf('Read-only mode. No database changes were made. To write, re-run with --apply --expected-count=%d.', \count($drifted)));

                return Command::SUCCESS;
            }

            $this->assertExpectedCount($input, \count($drifted));

            if ([] === $drifted) {
                $io->success('Nothing to repair. Every tag counter matches its relation rows.');

                return Command::SUCCESS;
            }

            $updated = $this->applyRepair();
            $remaining = \count($this->findDriftedTags());
            if ($remaining > 0) {
                throw new RuntimeException(\sprintf('Post-repair verification failed: %d tag counter(s) still differ.', $remaining));
            }

            $io->success(\sprintf('Repair completed. Updated=%d; remaining drifted counters=0.', $updated));

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * @return list<array{id: int, tag: string, stored: int, actual: int}>
     */
    private function findDriftedTags(): array
    {
        // Not "AS stored": STORED is a reserved word in MySQL (not in MariaDB).
        $rows = $this->connection->fetchAllAssociative(
            'SELECT t.id, t.tag, t.count AS stored_count, ('.self::ACTUAL_COUNT_SQL.') AS actual
             FROM tag t
             WHERE t.count <> ('.self::ACTUAL_COUNT_SQL.')
             ORDER BY t.id'
        );

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'tag' => (string) $row['tag'],
                'stored' => (int) $row['stored_count'],
                'actual' => (int) $row['actual'],
            ],
            $rows
        );
    }

    /**
     * @param list<array{id: int, tag: string, stored: int, actual: int}> $drifted
     */
    private function printDrift(array $drifted, SymfonyStyle $io): void
    {
        $io->definitionList(
            ['Tags in total' => (int) $this->connection->fetchOne('SELECT COUNT(*) FROM tag')],
            ['Tags with a drifted counter' => \count($drifted)]
        );

        if ([] === $drifted) {
            return;
        }

        $io->table(
            ['Tag id', 'Tag', 'Stored count', 'Actual links'],
            array_map(
                static fn (array $row): array => [$row['id'], $row['tag'], $row['stored'], $row['actual']],
                \array_slice($drifted, 0, self::MAX_DETAIL_ROWS)
            )
        );

        if (\count($drifted) > self::MAX_DETAIL_ROWS) {
            $io->writeln(\sprintf('... %d more not listed.', \count($drifted) - self::MAX_DETAIL_ROWS));
        }
    }

    private function applyRepair(): int
    {
        $this->connection->beginTransaction();

        try {
            $updated = (int) $this->connection->executeStatement(
                'UPDATE tag t SET t.count = ('.self::ACTUAL_COUNT_SQL.') WHERE t.count <> ('.self::ACTUAL_COUNT_SQL.')'
            );
            $this->connection->commit();
        } catch (Throwable $e) {
            $this->connection->rollBack();

            throw $e;
        }

        return $updated;
    }
}
