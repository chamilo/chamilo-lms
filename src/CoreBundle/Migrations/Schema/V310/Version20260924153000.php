<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260924153000 extends AbstractMigrationChamilo
{
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
        return 'Add inbound e-mail reply support for direct messages.';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('message')) {
            $table = $schema->getTable('message');

            if (!$table->hasColumn('mail_answer')) {
                $this->addSql('ALTER TABLE message ADD mail_answer TINYINT(1) NOT NULL DEFAULT 0');
            }

            if (!$table->hasColumn('mail_inbound_id')) {
                $this->addSql('ALTER TABLE message ADD mail_inbound_id VARCHAR(64) DEFAULT NULL');
            }

            if (!$table->hasIndex('uniq_message_mail_inbound_id')) {
                $this->addSql('CREATE UNIQUE INDEX uniq_message_mail_inbound_id ON message (mail_inbound_id)');
            }
        }

        if ($schema->hasTable('message_rel_user')) {
            $table = $schema->getTable('message_rel_user');

            if (!$table->hasColumn('mail_reply_token')) {
                $this->addSql('ALTER TABLE message_rel_user ADD mail_reply_token VARCHAR(64) DEFAULT NULL');
            }

            if (!$table->hasIndex('uniq_message_rel_user_mail_reply_token')) {
                $this->addSql(
                    'CREATE UNIQUE INDEX uniq_message_rel_user_mail_reply_token ON message_rel_user (mail_reply_token)'
                );
            }
        }

        foreach (['settings', 'settings_current'] as $table) {
            if (!$this->supportsSettingInsert($schema, $table)) {
                continue;
            }

            $this->insertSetting(
                $table,
                'enable_inbound_mail',
                'messages_hide_mail_content',
                'false',
                'Allow e-mail replies to messages',
                'Allow recipients of direct message notifications to reply by e-mail. Incoming messages must be routed to the Chamilo inbound-mail command by the mail infrastructure.'
            );
            $this->insertSetting(
                $table,
                'inbound_mail_address',
                'mailer_from_email',
                '',
                'Inbound e-mail address',
                'Base address used for reply routing, for example replies@example.com. Chamilo adds a unique recipient token using plus addressing.'
            );
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
                \sprintf(
                    'DELETE FROM %s WHERE variable IN (?, ?) AND category = ?',
                    $table
                ),
                ['enable_inbound_mail', 'inbound_mail_address', 'mail']
            );
        }

        if ($schema->hasTable('message_rel_user')) {
            $table = $schema->getTable('message_rel_user');
            if ($table->hasIndex('uniq_message_rel_user_mail_reply_token')) {
                $this->addSql('DROP INDEX uniq_message_rel_user_mail_reply_token ON message_rel_user');
            }
            if ($table->hasColumn('mail_reply_token')) {
                $this->addSql('ALTER TABLE message_rel_user DROP COLUMN mail_reply_token');
            }
        }

        if ($schema->hasTable('message')) {
            $table = $schema->getTable('message');
            if ($table->hasIndex('uniq_message_mail_inbound_id')) {
                $this->addSql('DROP INDEX uniq_message_mail_inbound_id ON message');
            }
            if ($table->hasColumn('mail_inbound_id')) {
                $this->addSql('ALTER TABLE message DROP COLUMN mail_inbound_id');
            }
            if ($table->hasColumn('mail_answer')) {
                $this->addSql('ALTER TABLE message DROP COLUMN mail_answer');
            }
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

    private function insertSetting(
        string $table,
        string $variable,
        string $sourceVariable,
        string $selectedValue,
        string $title,
        string $comment,
    ): void {
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
    ?,
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
                $variable,
                $selectedValue,
                $title,
                $comment,
                $sourceVariable,
                $variable,
            ]
        );
    }
}
