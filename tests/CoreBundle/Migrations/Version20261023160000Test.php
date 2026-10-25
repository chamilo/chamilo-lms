<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Migrations;

use Chamilo\CoreBundle\Migrations\Schema\V310\Version20261023160000;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Posts migrated from 1.11 with status 0 must keep their 1.11 behaviour: shown to learners in a
 * forum that is not moderated, waiting for moderation in a moderated one. Every other status is
 * left as it is.
 */
final class Version20261023160000Test extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        $configuration = new Configuration();
        $configuration->setSchemaManagerFactory(new DefaultSchemaManagerFactory());

        $this->connection = DriverManager::getConnection(
            ['driver' => 'pdo_sqlite', 'memory' => true],
            $configuration
        );
        $this->connection->executeStatement('CREATE TABLE c_forum_forum (iid INTEGER PRIMARY KEY, moderated BOOLEAN DEFAULT NULL)');
        $this->connection->executeStatement('CREATE TABLE c_forum_post (iid INTEGER PRIMARY KEY, forum_id INTEGER NOT NULL, status INTEGER DEFAULT NULL)');
        $this->connection->executeStatement('INSERT INTO c_forum_forum (iid, moderated) VALUES (1, 0), (2, NULL), (3, 1)');
    }

    protected function tearDown(): void
    {
        $this->connection->close();
    }

    public function testLegacyStatusZeroKeepsItsBehaviourOutsideAndInsideModeratedForums(): void
    {
        $this->connection->executeStatement(
            'INSERT INTO c_forum_post (iid, forum_id, status) VALUES (10, 1, 0), (11, 2, 0), (12, 3, 0)'
        );

        $this->executeMigration();

        $this->assertNull($this->getPostStatus(10), 'Shown to learners again in a forum that is not moderated.');
        $this->assertNull($this->getPostStatus(11), 'A forum without moderation value is not moderated.');
        $this->assertSame(2, $this->getPostStatus(12), 'Still waiting for moderation in a moderated forum.');
    }

    public function testOtherStatusesAreLeftUntouched(): void
    {
        $this->connection->executeStatement(
            'INSERT INTO c_forum_post (iid, forum_id, status) VALUES (20, 1, NULL), (21, 1, 1), (22, 3, NULL), (23, 3, 1), (24, 3, 2), (25, 3, 3)'
        );

        $this->executeMigration();

        $this->assertNull($this->getPostStatus(20));
        $this->assertSame(1, $this->getPostStatus(21));
        $this->assertNull($this->getPostStatus(22));
        $this->assertSame(1, $this->getPostStatus(23));
        $this->assertSame(2, $this->getPostStatus(24));
        $this->assertSame(3, $this->getPostStatus(25));
    }

    private function getPostStatus(int $postId): ?int
    {
        $status = $this->connection->fetchOne('SELECT status FROM c_forum_post WHERE iid = ?', [$postId]);

        return null === $status ? null : (int) $status;
    }

    private function executeMigration(): void
    {
        $migration = new Version20261023160000($this->connection, new NullLogger());
        $migration->up($this->connection->createSchemaManager()->introspectSchema());

        foreach ($migration->getSql() as $query) {
            $this->connection->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
        }
    }
}
