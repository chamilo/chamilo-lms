<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V300;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class Version20260807125041 extends AbstractMigrationChamilo
{
    private const string VARIABLE = 'mcp_allowed_roles';

    private const array DEFAULT_ALLOWED_ROLES = [
        'ADMIN' => true,
        'COURSEMANAGER' => true,
        'STUDENT' => false,
        'DRH' => false,
        'SESSIONADMIN' => false,
        'STUDENT_BOSS' => false,
        'INVITEE' => false,
    ];

    public function getDescription(): string
    {
        return 'Restore the MCP allowed-roles value template omitted by an overwritten fixture group.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('settings_value_template')) {
            return;
        }

        $templateJson = json_encode(
            self::DEFAULT_ALLOWED_ROLES,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        $this->addSql(
            'INSERT INTO settings_value_template (variable, json_example, created_at, updated_at) '
            .'SELECT ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP '
            .'WHERE NOT EXISTS (SELECT 1 FROM settings_value_template WHERE variable = ?)',
            [self::VARIABLE, $templateJson, self::VARIABLE]
        );
        $this->addSql(
            'UPDATE settings_value_template SET json_example = ?, updated_at = CURRENT_TIMESTAMP WHERE variable = ?',
            [$templateJson, self::VARIABLE]
        );

        if (!$schema->hasTable('settings') || !$schema->getTable('settings')->hasColumn('value_template_id')) {
            return;
        }

        $this->addSql(
            'UPDATE settings SET value_template_id = '
            .'(SELECT id FROM settings_value_template WHERE variable = ?) WHERE variable = ?',
            [self::VARIABLE, self::VARIABLE]
        );
    }

    public function down(Schema $schema): void
    {
        // Preserve the template and administrator settings repaired by this migration.
    }
}
