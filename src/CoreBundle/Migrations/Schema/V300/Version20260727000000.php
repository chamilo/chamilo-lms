<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V300;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Convert the legacy Doctrine "array" columns (PHP serialize) to "json".
 *
 * The DBAL "array" type is deprecated in DBAL 3 and removed in DBAL 4. Each
 * affected column is switched from LONGTEXT/(DC2Type:array) to a native JSON
 * column; its existing PHP-serialized data is re-encoded as JSON *before* the
 * type change so MySQL accepts the values when validating the JSON column.
 */
final class Version20260727000000 extends AbstractMigrationChamilo
{
    /**
     * Affected columns as [table, primary key, column, nullable].
     *
     * @var array<int, array{0: string, 1: string, 2: string, 3: bool}>
     */
    private const array COLUMNS = [
        ['user', 'id', 'roles', false],
        ['fos_group', 'id', 'roles', false],
        ['sys_announcement', 'id', 'roles', false],
        ['c_quiz', 'iid', 'page_result_configuration', false],
        ['gradebook_link', 'id', 'user_score_list', true],
        ['gradebook_evaluation', 'id', 'user_score_list', true],
        ['asset', 'id', 'metadata', true],
        ['resource_file', 'id', 'metadata', true],
        ['extra_field_saved_search', 'id', 'value', true],
    ];

    public function getDescription(): string
    {
        return 'Convert legacy Doctrine array columns (PHP serialize) to json';
    }

    public function isTransactional(): bool
    {
        // Per-row data updates mixed with column-type ALTERs (which auto-commit on
        // MySQL). Running outside a transaction keeps a retry idempotent.
        return false;
    }

    public function up(Schema $schema): void
    {
        // 1) Re-encode data serialize -> JSON while the column is still LONGTEXT.
        foreach (self::COLUMNS as [$table, $pk, $column, $nullable]) {
            $this->reencodeColumn($table, $pk, $column, $nullable, true);
        }

        // 2) Switch the column type to native JSON now that the data is valid JSON.
        foreach (self::COLUMNS as [$table, , $column, $nullable]) {
            $nullSql = $nullable ? 'DEFAULT NULL' : 'NOT NULL';
            $this->connection->executeStatement(
                \sprintf("ALTER TABLE %s CHANGE %s %s JSON %s COMMENT '(DC2Type:json)'", $table, $column, $column, $nullSql)
            );
        }
    }

    public function down(Schema $schema): void
    {
        // 1) Switch the column back to LONGTEXT so it accepts PHP-serialized data.
        foreach (self::COLUMNS as [$table, , $column, $nullable]) {
            $nullSql = $nullable ? 'DEFAULT NULL' : 'NOT NULL';
            $this->connection->executeStatement(
                \sprintf("ALTER TABLE %s CHANGE %s %s LONGTEXT %s COMMENT '(DC2Type:array)'", $table, $column, $column, $nullSql)
            );
        }

        // 2) Re-encode data JSON -> serialize.
        foreach (self::COLUMNS as [$table, $pk, $column, $nullable]) {
            $this->reencodeColumn($table, $pk, $column, $nullable, false);
        }
    }

    /**
     * Re-encode a column's non-null values in chunks: serialize -> JSON when
     * $toJson is true, JSON -> serialize otherwise.
     */
    private function reencodeColumn(string $table, string $pk, string $column, bool $nullable, bool $toJson): void
    {
        // Not necessarily numeric: some primary keys (e.g. Asset::$id) are
        // UUIDs stored as BINARY(16), not auto-increment integers. Casting
        // the cursor to (int) silently mangles a UUID's raw bytes to 0 (PHP
        // finds no leading digits), so the cursor never advances and the
        // WHERE/UPDATE clauses stop matching real rows — an infinite loop
        // that never actually converts the data. Keep the cursor as the raw
        // value MySQL returns; '' sorts before any real id (numeric 0 for
        // int columns, empty-string-is-less-than for binary columns).
        $lastId = '';

        do {
            $rows = $this->connection->fetchAllAssociative(
                \sprintf(
                    'SELECT %1$s AS pk, %2$s AS val FROM %3$s WHERE %1$s > :lastId AND %2$s IS NOT NULL ORDER BY %1$s ASC LIMIT 500',
                    $pk,
                    $column,
                    $table
                ),
                ['lastId' => $lastId]
            );

            foreach ($rows as $row) {
                $lastId = $row['pk'];
                $value = (string) $row['val'];
                $converted = $toJson
                    ? $this->serializedToJson($value, $nullable)
                    : $this->jsonToSerialized($value, $nullable);

                $this->connection->executeStatement(
                    \sprintf('UPDATE %1$s SET %2$s = :val WHERE %3$s = :pk', $table, $column, $pk),
                    ['val' => $converted, 'pk' => $lastId]
                );
            }
        } while ([] !== $rows);
    }

    private function serializedToJson(string $value, bool $nullable): ?string
    {
        if ('' === $value) {
            return $nullable ? null : '[]';
        }

        $decoded = @unserialize($value, ['allowed_classes' => false]);
        if (false === $decoded) {
            // Not PHP-serialized: keep it if it is already JSON (idempotent re-run).
            return json_validate($value) ? $value : ($nullable ? null : '[]');
        }

        $json = json_encode($decoded);

        return false === $json ? ($nullable ? null : '[]') : $json;
    }

    private function jsonToSerialized(string $value, bool $nullable): ?string
    {
        if ('' === $value) {
            return $nullable ? null : serialize([]);
        }

        if (!json_validate($value)) {
            // Not JSON: assume it is already serialized.
            return $value;
        }

        return serialize(json_decode($value, true) ?? []);
    }
}
