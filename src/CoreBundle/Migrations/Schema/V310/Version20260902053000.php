<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V300;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260902053000 extends AbstractMigrationChamilo
{
    private const string FIRST_VARIABLE = 'first_reply_min_words';
    private const string SUBSEQUENT_VARIABLE = 'subsequent_reply_min_words';
    private const string SOURCE_VARIABLE = 'global_forums_course_id';

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
        return 'Add configurable minimum word counts for forum replies.';
    }

    public function up(Schema $schema): void
    {
        foreach (['settings', 'settings_current'] as $table) {
            if (!$this->supportsSettingInsert($schema, $table)) {
                continue;
            }

            $this->insertSetting(
                $table,
                self::FIRST_VARIABLE,
                'Minimum words for first forum reply',
                'Minimum reply length when the user has no previous post in the same forum thread.'
            );

            $this->insertSetting(
                $table,
                self::SUBSEQUENT_VARIABLE,
                'Minimum words for subsequent forum replies',
                'Minimum reply length when the user already has a post in the same forum thread.'
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

            if (
                !$tableSchema->hasColumn('variable')
                || !$tableSchema->hasColumn('category')
            ) {
                continue;
            }

            $this->addSql(
                sprintf(
                    'DELETE FROM %s WHERE category = ? AND variable IN (?, ?)',
                    $table
                ),
                [
                    'forum',
                    self::FIRST_VARIABLE,
                    self::SUBSEQUENT_VARIABLE,
                ]
            );
        }
    }

    private function supportsSettingInsert(
        Schema $schema,
        string $table,
    ): bool {
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

    private function insertSetting(
        string $table,
        string $variable,
        string $title,
        string $comment,
    ): void {
        $this->addSql(
            sprintf(
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
    'forum',
    '0',
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
                $variable,
                $title,
                $comment,
                self::SOURCE_VARIABLE,
                $variable,
            ]
        );
    }
}
