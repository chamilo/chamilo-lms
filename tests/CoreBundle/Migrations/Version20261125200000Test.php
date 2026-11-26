<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Migrations;

use Chamilo\CoreBundle\Migrations\Schema\V310\Version20261125200000;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use PHPUnit\Framework\TestCase;

/**
 * An old connection may only get an access URL when it cannot have come from another one: the user
 * is registered in a single URL. A connection of a user shared between URLs, or already
 * attributed, must keep its value, or the reports of one URL would show it as another URL's.
 */
final class Version20261125200000Test extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        $configuration = new Configuration();
        $configuration->setSchemaManagerFactory(new DefaultSchemaManagerFactory());
        $this->connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $configuration);
        $this->connection->executeStatement(
            'CREATE TABLE track_e_login (login_id INTEGER PRIMARY KEY, login_user_id INTEGER, access_url_id INTEGER NULL)'
        );
        $this->connection->executeStatement(
            'CREATE TABLE access_url_rel_user (id INTEGER PRIMARY KEY AUTOINCREMENT, access_url_id INTEGER, user_id INTEGER)'
        );
    }

    protected function tearDown(): void
    {
        $this->connection->close();
    }

    public function testOldConnectionsOfASingleUrlUserGetThatUrl(): void
    {
        $this->registerUser(10, [2]);
        $this->insertLogin(1, 10, null);
        $this->insertLogin(2, 10, null);

        $this->connection->executeStatement(Version20261125200000::attributeSql());

        $this->assertSame([1 => 2, 2 => 2], $this->urlsByLogin());
    }

    public function testConnectionsOfASharedUserStayUnattributed(): void
    {
        $this->registerUser(11, [1, 2]);
        $this->insertLogin(1, 11, null);

        $this->connection->executeStatement(Version20261125200000::attributeSql());

        $this->assertSame([1 => null], $this->urlsByLogin());
    }

    public function testAlreadyAttributedConnectionsKeepTheirUrl(): void
    {
        // The user now belongs to URL 2 only, but this connection was recorded on URL 1.
        $this->registerUser(12, [2]);
        $this->insertLogin(1, 12, 1);

        $this->connection->executeStatement(Version20261125200000::attributeSql());

        $this->assertSame([1 => 1], $this->urlsByLogin());
    }

    public function testConnectionsOfAUserWithoutUrlStayUnattributed(): void
    {
        $this->insertLogin(1, 13, null);

        $this->connection->executeStatement(Version20261125200000::attributeSql());

        $this->assertSame([1 => null], $this->urlsByLogin());
    }

    /**
     * @param list<int> $urlIds
     */
    private function registerUser(int $userId, array $urlIds): void
    {
        foreach ($urlIds as $urlId) {
            $this->connection->executeStatement(
                'INSERT INTO access_url_rel_user (access_url_id, user_id) VALUES (?, ?)',
                [$urlId, $userId]
            );
        }
    }

    private function insertLogin(int $loginId, int $userId, ?int $urlId): void
    {
        $this->connection->executeStatement(
            'INSERT INTO track_e_login (login_id, login_user_id, access_url_id) VALUES (?, ?, ?)',
            [$loginId, $userId, $urlId]
        );
    }

    /**
     * @return array<int, null|int>
     */
    private function urlsByLogin(): array
    {
        $result = [];
        foreach ($this->connection->fetchAllAssociative('SELECT login_id, access_url_id FROM track_e_login ORDER BY login_id') as $row) {
            $result[(int) $row['login_id']] = null === $row['access_url_id'] ? null : (int) $row['access_url_id'];
        }

        return $result;
    }
}
