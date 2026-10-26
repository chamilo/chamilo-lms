<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Migrations;

use Chamilo\CoreBundle\Migrations\Schema\V310\Version20261026193000;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class Version20261026193000Test extends TestCase
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
            .'selected_value TEXT, title TEXT, comment TEXT'
            .')'
        );

        $this->connection->executeStatement(
            'INSERT INTO settings (access_url, variable, category, selected_value, title, comment) VALUES '
            ."(1, 'meta_twitter_site', 'tracking', '@first', 'Twitter Site account', 'Old Twitter text'), "
            ."(2, 'meta_twitter_site', 'tracking', '@second', 'Twitter Site account', 'Old Twitter text'), "
            ."(1, 'meta_twitter_creator', 'tracking', '@creator', 'Twitter Creator account', 'Old Twitter text'), "
            ."(1, 'meta_image_path', 'tracking', 'home/image.png', 'Meta image path', 'Old Twitter text'), "
            ."(1, 'meta_title', 'tracking', 'My title', 'OpenGraph meta title', 'Unrelated text'), "
            ."(1, 'meta_twitter_site', 'other', '@other', 'Other category', 'Keep untouched')"
        );
    }

    protected function tearDown(): void
    {
        $this->connection->close();
    }

    public function testUpdatesLabelsAcrossAccessUrlsWithoutChangingValuesOrOtherSettings(): void
    {
        $this->executeMigration();
        $this->executeMigration();

        self::assertSame(6, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM settings'));
        self::assertSame('X Site account', $this->getField('title', 'meta_twitter_site', 1));
        self::assertSame('X Site account', $this->getField('title', 'meta_twitter_site', 2));
        self::assertSame('X Creator account', $this->getField('title', 'meta_twitter_creator', 1));
        self::assertStringContainsString('X card', $this->getField('comment', 'meta_image_path', 1));
        self::assertSame('@first', $this->getField('selected_value', 'meta_twitter_site', 1));
        self::assertSame('@second', $this->getField('selected_value', 'meta_twitter_site', 2));
        self::assertSame('@creator', $this->getField('selected_value', 'meta_twitter_creator', 1));
        self::assertSame('home/image.png', $this->getField('selected_value', 'meta_image_path', 1));
        self::assertSame('OpenGraph meta title', $this->getField('title', 'meta_title', 1));
        self::assertSame('Other category', $this->getField('title', 'meta_twitter_site', 1, 'other'));
    }

    private function getField(string $field, string $variable, int $accessUrl, string $category = 'tracking'): string
    {
        // The field name is selected from a constant test-only allowlist.
        self::assertContains($field, ['title', 'comment', 'selected_value']);

        return (string) $this->connection->fetchOne(
            "SELECT {$field} FROM settings WHERE variable = ? AND access_url = ? AND category = ?",
            [$variable, $accessUrl, $category],
        );
    }

    private function executeMigration(): void
    {
        $migration = new Version20261026193000($this->connection, new NullLogger());
        $migration->up($this->connection->createSchemaManager()->introspectSchema());

        foreach ($migration->getSql() as $query) {
            $this->connection->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
        }
    }
}
