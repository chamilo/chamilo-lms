<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V210;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260714230622 extends AbstractMigrationChamilo
{
    private const string VARIABLE = 'wysiwyg_translation_all_languages';

    private const array REQUIRED_COLUMNS = [
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
        return 'Add the AI WYSIWYG all-languages translation setting.';
    }

    public function up(Schema $schema): void
    {
        foreach (['settings', 'settings_current'] as $table) {
            if (!$this->supportsSettingInsert($schema, $table)) {
                continue;
            }

            $this->insertSetting($table);
        }
    }

    public function down(Schema $schema): void
    {
        foreach (['settings', 'settings_current'] as $table) {
            if (!$schema->hasTable($table)) {
                continue;
            }

            $tableSchema = $schema->getTable($table);

            if (
                !$tableSchema->hasColumn('variable')
                || !$tableSchema->hasColumn('category')
            ) {
                continue;
            }

            $this->addSql(
                \sprintf(
                    'DELETE FROM %s WHERE variable = ? AND category = ?',
                    $table
                ),
                [
                    self::VARIABLE,
                    'ai_helpers',
                ]
            );
        }
    }

    private function supportsSettingInsert(Schema $schema, string $table): bool
    {
        if (!$schema->hasTable($table)) {
            return false;
        }

        $tableSchema = $schema->getTable($table);

        foreach (self::REQUIRED_COLUMNS as $column) {
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
    ?,
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
            OR (
                existing.access_url IS NULL
                AND source.access_url IS NULL
            )
        )
        AND (
            existing.subkey = source.subkey
            OR (
                existing.subkey IS NULL
                AND source.subkey IS NULL
            )
        )
  )
SQL,
                $table
            ),
            [
                self::VARIABLE,
                'ai_helpers',
                'false',
                'Allow AI translation to all active languages in WYSIWYG editors',
                'Allows teachers to generate translations for all active platform languages in one WYSIWYG action. This may consume a large number of AI tokens.',
                'enable_ai_helpers',
                self::VARIABLE,
            ]
        );

        $this->write(
            \sprintf(
                'Added %s to %s when missing.',
                self::VARIABLE,
                $table
            )
        );
    }
}
