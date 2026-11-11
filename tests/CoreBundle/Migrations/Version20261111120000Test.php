<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Migrations;

use Chamilo\CoreBundle\Migrations\Schema\V310\Version20261111120000;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * The de-duplication logic runs as plain SQL against fixture rows (SQLite in memory, no DDL on a shared database);
 * the DDL part is only checked for being emitted, or not, depending on whether the index already exists.
 */
final class Version20261111120000Test extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        $configuration = new Configuration();
        $configuration->setSchemaManagerFactory(new DefaultSchemaManagerFactory());
        $this->connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $configuration);
        $this->connection->executeStatement(
            'CREATE TABLE usergroup_rel_user (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, usergroup_id INTEGER, relation_type INTEGER NOT NULL)'
        );
    }

    protected function tearDown(): void
    {
        $this->connection->close();
    }

    private function insert(int $id, ?int $user, ?int $group, int $type): void
    {
        $this->connection->executeStatement(
            'INSERT INTO usergroup_rel_user (id, user_id, usergroup_id, relation_type) VALUES (?, ?, ?, ?)',
            [$id, $user, $group, $type]
        );
    }

    /**
     * @return list<int>
     */
    private function ids(): array
    {
        return array_map('intval', $this->connection->fetchFirstColumn('SELECT id FROM usergroup_rel_user ORDER BY id'));
    }

    public function testKeepsTheLowestIdWhenDuplicatesHaveTheSameRelation(): void
    {
        $this->insert(1, 10, 100, 2);
        $this->insert(2, 10, 100, 2);
        $this->insert(3, 10, 100, 2);

        $this->connection->executeStatement(Version20261111120000::dedupeSql());

        $this->assertSame([1], $this->ids());
    }

    public function testKeepsTheStrongestRelationEvenWhenItIsNotTheOldestRow(): void
    {
        $this->insert(1, 10, 100, 2);  // reader, oldest
        $this->insert(2, 10, 100, 1);  // admin: wins
        $this->insert(3, 10, 100, 5);  // moderator
        $this->insert(4, 11, 100, 3);  // pending invitation
        $this->insert(5, 11, 100, 2);  // reader: wins over a pending invitation
        $this->insert(6, 12, 100, 2);  // reader
        $this->insert(7, 12, 100, 7);  // HRM: loses against reader

        $this->connection->executeStatement(Version20261111120000::dedupeSql());

        $this->assertSame([2, 5, 6], $this->ids());
    }

    public function testLeavesDistinctPairsAndOrphansUntouched(): void
    {
        $this->insert(1, 10, 100, 2);
        $this->insert(2, 10, 101, 2);  // same user, other group
        $this->insert(3, 11, 100, 2);  // same group, other user
        $this->insert(4, null, 100, 2);
        $this->insert(5, null, 100, 2); // NULL user: not a pair, kept
        $this->insert(6, 12, null, 2);

        $this->connection->executeStatement(Version20261111120000::dedupeSql());

        $this->assertSame([1, 2, 3, 4, 5, 6], $this->ids());
    }

    public function testRunningTheDedupeTwiceChangesNothingTheSecondTime(): void
    {
        $this->insert(1, 10, 100, 2);
        $this->insert(2, 10, 100, 2);

        $this->connection->executeStatement(Version20261111120000::dedupeSql());
        $this->connection->executeStatement(Version20261111120000::dedupeSql());

        $this->assertSame([1], $this->ids());
    }

    public function testUpEmitsTheDedupeThenTheUniqueIndexWhenTheIndexIsMissing(): void
    {
        $sql = $this->runUp();

        $this->assertCount(2, $sql);
        $this->assertStringStartsWith('DELETE FROM usergroup_rel_user', $sql[0]);
        $this->assertSame('ALTER TABLE usergroup_rel_user ADD UNIQUE INDEX uniq_usergroup_user (usergroup_id, user_id)', $sql[1]);
    }

    public function testUpIsIdempotentWhenTheIndexAlreadyExists(): void
    {
        $this->connection->executeStatement('CREATE UNIQUE INDEX uniq_usergroup_user ON usergroup_rel_user (usergroup_id, user_id)');

        $this->assertSame([], $this->runUp());
    }

    public function testDownDropsOnlyTheIndexAndOnlyWhenItExists(): void
    {
        $migration = new Version20261111120000($this->connection, new NullLogger());
        $migration->down($this->connection->createSchemaManager()->introspectSchema());
        $this->assertSame([], $migration->getSql());

        $this->connection->executeStatement('CREATE UNIQUE INDEX uniq_usergroup_user ON usergroup_rel_user (usergroup_id, user_id)');
        $migration = new Version20261111120000($this->connection, new NullLogger());
        $migration->down($this->connection->createSchemaManager()->introspectSchema());
        $this->assertCount(1, $migration->getSql());
        $this->assertSame('ALTER TABLE usergroup_rel_user DROP INDEX uniq_usergroup_user', $migration->getSql()[0]->getStatement());
    }

    /**
     * @return list<string>
     */
    private function runUp(): array
    {
        $migration = new Version20261111120000($this->connection, new NullLogger());
        $migration->up($this->connection->createSchemaManager()->introspectSchema());

        return array_map(static fn ($query): string => preg_replace('/\s+/', ' ', $query->getStatement()), $migration->getSql());
    }
}
