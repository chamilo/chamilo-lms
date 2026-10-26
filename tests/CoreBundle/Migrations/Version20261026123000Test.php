<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Migrations;

use Chamilo\CoreBundle\Migrations\Schema\V310\Version20261026123000;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class Version20261026123000Test extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        $configuration = new Configuration();
        $configuration->setSchemaManagerFactory(new DefaultSchemaManagerFactory());
        $this->connection = DriverManager::getConnection(
            ['driver' => 'pdo_sqlite', 'memory' => true],
            $configuration,
        );
        $this->connection->executeStatement(
            'CREATE TABLE settings ('
            .'id INTEGER PRIMARY KEY AUTOINCREMENT, access_url INTEGER, variable TEXT, category TEXT, '
            .'selected_value TEXT, title TEXT, comment TEXT, access_url_changeable INTEGER, access_url_locked INTEGER'
            .')'
        );
    }

    protected function tearDown(): void
    {
        $this->connection->close();
    }

    public function testInsertsDisabledSettingAndCanRunAgain(): void
    {
        $this->executeMigration();
        $this->executeMigration();

        self::assertSame(1, (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM settings WHERE variable = 'automatic_update_checks'"
        ));
        self::assertSame('false', $this->connection->fetchOne(
            "SELECT selected_value FROM settings WHERE variable = 'automatic_update_checks'"
        ));
        self::assertSame('Automatic update checks', $this->connection->fetchOne(
            "SELECT title FROM settings WHERE variable = 'automatic_update_checks'"
        ));
    }

    public function testKeepsExistingAdministratorChoice(): void
    {
        $this->connection->executeStatement(
            'INSERT INTO settings (access_url, variable, category, selected_value, title, comment) '
            ."VALUES (1, 'automatic_update_checks', 'admin', 'true', 'automatic_update_checks', '')"
        );

        $this->executeMigration();

        self::assertSame('true', $this->connection->fetchOne(
            "SELECT selected_value FROM settings WHERE variable = 'automatic_update_checks'"
        ));
        self::assertSame('Automatic update checks', $this->connection->fetchOne(
            "SELECT title FROM settings WHERE variable = 'automatic_update_checks'"
        ));
    }

    private function executeMigration(): void
    {
        $migration = new Version20261026123000($this->connection, new NullLogger());
        $migration->up($this->connection->createSchemaManager()->introspectSchema());

        foreach ($migration->getSql() as $query) {
            $this->connection->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
        }
    }
}
