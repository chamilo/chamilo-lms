<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Migrations;

use Chamilo\CoreBundle\Migrations\Schema\V300\Version20260807125041;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

use const JSON_THROW_ON_ERROR;

final class Version20260807125041Test extends TestCase
{
    private const array DEFAULT_ALLOWED_ROLES = [
        'ADMIN' => true,
        'COURSEMANAGER' => true,
        'STUDENT' => false,
        'DRH' => false,
        'SESSIONADMIN' => false,
        'STUDENT_BOSS' => false,
        'INVITEE' => false,
    ];

    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $this->connection->executeStatement(
            'CREATE TABLE settings_value_template (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                variable VARCHAR(190) NOT NULL UNIQUE,
                description TEXT DEFAULT NULL,
                json_example TEXT DEFAULT NULL,
                created_at DATETIME DEFAULT NULL,
                updated_at DATETIME DEFAULT NULL
            )'
        );
        $this->connection->executeStatement(
            'CREATE TABLE settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                variable VARCHAR(190) NOT NULL,
                selected_value TEXT DEFAULT NULL,
                value_template_id INTEGER DEFAULT NULL
            )'
        );
    }

    protected function tearDown(): void
    {
        $this->connection->close();
    }

    public function testRepairsMissingTemplateAndLinksEverySettingIdempotently(): void
    {
        $this->connection->executeStatement(
            'INSERT INTO settings (variable, selected_value) VALUES (?, ?), (?, ?)',
            ['mcp_allowed_roles', '{"ADMIN":true}', 'mcp_allowed_roles', '{"STUDENT":true}']
        );

        $this->executeMigration();
        $templateId = (int) $this->connection->fetchOne(
            'SELECT id FROM settings_value_template WHERE variable = ?',
            ['mcp_allowed_roles']
        );
        $this->executeMigration();

        self::assertSame(
            1,
            (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM settings_value_template WHERE variable = ?',
                ['mcp_allowed_roles']
            )
        );
        self::assertSame(
            self::DEFAULT_ALLOWED_ROLES,
            json_decode(
                (string) $this->connection->fetchOne(
                    'SELECT json_example FROM settings_value_template WHERE id = ?',
                    [$templateId]
                ),
                true,
                512,
                JSON_THROW_ON_ERROR
            )
        );
        self::assertSame(
            [
                ['selected_value' => '{"ADMIN":true}', 'value_template_id' => $templateId],
                ['selected_value' => '{"STUDENT":true}', 'value_template_id' => $templateId],
            ],
            $this->connection->fetchAllAssociative(
                'SELECT selected_value, value_template_id FROM settings WHERE variable = ? ORDER BY id',
                ['mcp_allowed_roles']
            )
        );
    }

    public function testUpdatesStaleTemplateWithoutChangingUnrelatedSettings(): void
    {
        $this->connection->executeStatement(
            'INSERT INTO settings_value_template (variable, json_example) VALUES (?, ?), (?, ?)',
            ['unrelated', '{}', 'mcp_allowed_roles', '{"ADMIN":false}']
        );
        $unrelatedTemplateId = (int) $this->connection->fetchOne(
            'SELECT id FROM settings_value_template WHERE variable = ?',
            ['unrelated']
        );
        $mcpTemplateId = (int) $this->connection->fetchOne(
            'SELECT id FROM settings_value_template WHERE variable = ?',
            ['mcp_allowed_roles']
        );
        $this->connection->executeStatement(
            'INSERT INTO settings (variable, selected_value, value_template_id) VALUES (?, ?, ?), (?, ?, ?)',
            [
                'mcp_allowed_roles',
                '{"ADMIN":false}',
                $unrelatedTemplateId,
                'unrelated',
                'keep-me',
                $unrelatedTemplateId,
            ]
        );

        $this->executeMigration();

        self::assertSame(
            self::DEFAULT_ALLOWED_ROLES,
            json_decode(
                (string) $this->connection->fetchOne(
                    'SELECT json_example FROM settings_value_template WHERE id = ?',
                    [$mcpTemplateId]
                ),
                true,
                512,
                JSON_THROW_ON_ERROR
            )
        );
        self::assertSame(
            [
                [
                    'variable' => 'mcp_allowed_roles',
                    'selected_value' => '{"ADMIN":false}',
                    'value_template_id' => $mcpTemplateId,
                ],
                [
                    'variable' => 'unrelated',
                    'selected_value' => 'keep-me',
                    'value_template_id' => $unrelatedTemplateId,
                ],
            ],
            $this->connection->fetchAllAssociative(
                'SELECT variable, selected_value, value_template_id FROM settings ORDER BY id'
            )
        );
    }

    private function executeMigration(): void
    {
        $migration = new Version20260807125041($this->connection, new NullLogger());
        $migration->up($this->connection->createSchemaManager()->introspectSchema());

        foreach ($migration->getSql() as $query) {
            $this->connection->executeStatement(
                $query->getStatement(),
                $query->getParameters(),
                $query->getTypes()
            );
        }
    }
}
