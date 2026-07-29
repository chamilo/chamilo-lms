<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V300;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class Version20260728170000 extends AbstractMigrationChamilo
{
    private const string ROLES_JSON = '{"ADMIN":true,"COURSEMANAGER":true,"STUDENT":false,"DRH":false,"SESSIONADMIN":false,"STUDENT_BOSS":false,"INVITEE":false}';

    /**
     * @var list<array{variable: string, value: string, title: string, comment: string}>
     */
    private const array SETTINGS = [
        [
            'variable' => 'mcp_enabled',
            'value' => 'false',
            'title' => 'Enable MCP server',
            'comment' => 'Enables the Chamilo MCP endpoint and the personal MCP API key interface. When disabled, existing API keys, OAuth access tokens and JWT credentials cannot be used on /mcp.',
        ],
        [
            'variable' => 'mcp_allowed_roles',
            'value' => self::ROLES_JSON,
            'title' => 'Allow MCP by roles',
            'comment' => 'JSON map of Chamilo user roles allowed to use MCP. A user must match at least one enabled role. This restriction also applies to previously generated API keys and existing OAuth connections.',
        ],
    ];

    public function getDescription(): string
    {
        return 'Add MCP enablement and role-based access settings.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('settings')) {
            return;
        }

        foreach (self::SETTINGS as $setting) {
            $this->addSql(
                'INSERT INTO settings (variable, category, title, comment, selected_value, access_url_changeable) '
                .'SELECT ?, ?, ?, ?, ?, 0 WHERE NOT EXISTS (SELECT 1 FROM settings WHERE variable = ?)',
                [
                    $setting['variable'],
                    'security',
                    $setting['title'],
                    $setting['comment'],
                    $setting['value'],
                    $setting['variable'],
                ],
            );
            $this->addSql(
                'UPDATE settings SET category = ?, title = ?, comment = ? WHERE variable = ?',
                ['security', $setting['title'], $setting['comment'], $setting['variable']],
            );
        }

        if (!$schema->hasTable('settings_value_template')) {
            return;
        }

        $templateJson = json_encode(
            [
                'ADMIN' => true,
                'COURSEMANAGER' => true,
                'STUDENT' => false,
                'DRH' => false,
                'SESSIONADMIN' => false,
                'STUDENT_BOSS' => false,
                'INVITEE' => false,
            ],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        $this->addSql(
            'INSERT INTO settings_value_template (variable, json_example, created_at, updated_at) '
            .'SELECT ?, ?, NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM settings_value_template WHERE variable = ?)',
            ['mcp_allowed_roles', $templateJson, 'mcp_allowed_roles'],
        );
        $this->addSql(
            'UPDATE settings_value_template SET json_example = ?, updated_at = NOW() WHERE variable = ?',
            [$templateJson, 'mcp_allowed_roles'],
        );

        if ($schema->getTable('settings')->hasColumn('value_template_id')) {
            $this->addSql(
                'UPDATE settings SET value_template_id = '
                .'(SELECT id FROM settings_value_template WHERE variable = ?) WHERE variable = ?',
                ['mcp_allowed_roles', 'mcp_allowed_roles'],
            );
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('settings')) {
            $this->addSql(
                'DELETE FROM settings WHERE category = ? AND variable IN (?, ?)',
                ['security', 'mcp_enabled', 'mcp_allowed_roles'],
            );
        }

        if ($schema->hasTable('settings_value_template')) {
            $this->addSql('DELETE FROM settings_value_template WHERE variable = ?', ['mcp_allowed_roles']);
        }
    }
}
