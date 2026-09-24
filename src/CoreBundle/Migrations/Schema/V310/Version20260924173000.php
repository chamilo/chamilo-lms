<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260924173000 extends AbstractMigrationChamilo
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
        return 'Add an inbound mailbox DSN for automatic e-mail reply collection.';
    }

    public function up(Schema $schema): void
    {
        if (!$this->supportsSettingInsert($schema, 'settings')) {
            return;
        }

        $this->addSql(
            <<<'SQL'
INSERT INTO settings (
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
    'inbound_mail_dsn',
    source.subkey,
    source.type,
    'mail',
    '',
    'Inbound mailbox DSN',
    'Mailbox connection used to collect inbound replies, for example imaps://user:password@imap.example.com:993/INBOX. URL-encode reserved characters in credentials.',
    source.scope,
    source.subkeytext,
    source.access_url_changeable,
    source.access_url_locked,
    source.value_template_id
FROM settings source
WHERE source.variable = 'inbound_mail_address'
  AND NOT EXISTS (
      SELECT 1
      FROM settings existing
      WHERE existing.variable = 'inbound_mail_dsn'
        AND (
            existing.access_url = source.access_url
            OR (existing.access_url IS NULL AND source.access_url IS NULL)
        )
        AND (
            existing.subkey = source.subkey
            OR (existing.subkey IS NULL AND source.subkey IS NULL)
        )
  )
SQL
        );
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('settings')) {
            return;
        }

        $table = $schema->getTable('settings');
        if (!$table->hasColumn('variable') || !$table->hasColumn('category')) {
            return;
        }

        $this->addSql(
            "DELETE FROM settings WHERE variable = 'inbound_mail_dsn' AND category = 'mail'"
        );
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
}
