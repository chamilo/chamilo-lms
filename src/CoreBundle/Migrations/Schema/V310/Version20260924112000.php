<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260924112000 extends AbstractMigrationChamilo
{
    private const string SETTING_VARIABLE = 'enable_email_open_tracking';
    private const string SOURCE_SETTING_VARIABLE = 'messages_hide_mail_content';

    private const array REQUIRED_SETTING_COLUMNS = [
        'access_url',
        'variable',
        'subkey',
        'type',
        'category',
        'selected_value',
        'title',
        'comment',
        'scope',
        'subkeytext',
        'access_url_changeable',
        'access_url_locked',
        'value_template_id',
    ];

    public function getDescription(): string
    {
        return 'Add optional per-recipient e-mail open tracking for messages.';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('message_rel_user')) {
            $table = $schema->getTable('message_rel_user');

            if (!$table->hasColumn('mail_tracking_token')) {
                $this->addSql('ALTER TABLE message_rel_user ADD mail_tracking_token VARCHAR(64) DEFAULT NULL');
            }

            if (!$table->hasColumn('mail_opened_at')) {
                $this->addSql('ALTER TABLE message_rel_user ADD mail_opened_at DATETIME DEFAULT NULL');
            }

            if (!$table->hasIndex('uniq_message_rel_user_mail_tracking_token')) {
                $this->addSql(
                    'CREATE UNIQUE INDEX uniq_message_rel_user_mail_tracking_token ON message_rel_user (mail_tracking_token)'
                );
            }
        }

        foreach (['settings', 'settings_current'] as $table) {
            if ($this->supportsSettingInsert($schema, $table)) {
                $this->insertSetting($table);
            }
        }
    }

    public function down(Schema $schema): void
    {
        foreach (['settings', 'settings_current'] as $table) {
            if (!$schema->hasTable($table)) {
                continue;
            }

            $tableSchema = $schema->getTable($table);
            if (!$tableSchema->hasColumn('variable') || !$tableSchema->hasColumn('category')) {
                continue;
            }

            $this->addSql(
                \sprintf('DELETE FROM %s WHERE variable = ? AND category = ?', $table),
                [self::SETTING_VARIABLE, 'mail']
            );
        }

        if (!$schema->hasTable('message_rel_user')) {
            return;
        }

        $table = $schema->getTable('message_rel_user');
        if ($table->hasIndex('uniq_message_rel_user_mail_tracking_token')) {
            $this->addSql('DROP INDEX uniq_message_rel_user_mail_tracking_token ON message_rel_user');
        }
        if ($table->hasColumn('mail_opened_at')) {
            $this->addSql('ALTER TABLE message_rel_user DROP COLUMN mail_opened_at');
        }
        if ($table->hasColumn('mail_tracking_token')) {
            $this->addSql('ALTER TABLE message_rel_user DROP COLUMN mail_tracking_token');
        }
    }

    private function supportsSettingInsert(Schema $schema, string $table): bool
    {
        if (!$schema->hasTable($table)) {
            return false;
        }

        $tableSchema = $schema->getTable($table);
        foreach (self::REQUIRED_SETTING_COLUMNS as $column) {
            if (!$tableSchema->hasColumn($column)) {
                return false;
            }
        }

        return true;
    }

    private function insertSetting(string $table): void
    {
        $this->addSql(
            \sprintf(
                <<<'SQL'
INSERT INTO %1$s (
    access_url,
    variable,
    subkey,
    type,
    category,
    selected_value,
    title,
    comment,
    scope,
    subkeytext,
    access_url_changeable,
    access_url_locked,
    value_template_id
)
SELECT
    source.access_url,
    ?,
    source.subkey,
    source.type,
    'mail',
    'false',
    ?,
    ?,
    source.scope,
    source.subkeytext,
    source.access_url_changeable,
    source.access_url_locked,
    source.value_template_id
FROM %1$s source
WHERE source.variable = ?
  AND NOT EXISTS (
      SELECT 1
      FROM %1$s existing
      WHERE existing.variable = ?
        AND (
            existing.access_url = source.access_url
            OR (existing.access_url IS NULL AND source.access_url IS NULL)
        )
        AND (
            existing.subkey = source.subkey
            OR (existing.subkey IS NULL AND source.subkey IS NULL)
        )
  )
SQL,
                $table
            ),
            [
                self::SETTING_VARIABLE,
                'Track e-mail openings',
                'Adds a small tracking image to message notification e-mails and records when it is requested. This only indicates that the e-mail may have been opened; it does not prove it was read.',
                self::SOURCE_SETTING_VARIABLE,
                self::SETTING_VARIABLE,
            ]
        );
    }
}
