<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V210;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260729215300 extends AbstractMigrationChamilo
{
    private const COMPLETION_BATCH_SIZE = 20000;
    private const MANUAL_COMPLETION_BATCH_SIZE = 25000;

    public function getDescription(): string
    {
        return 'Backfill available legacy LP evidence in the current database without requiring already-removed legacy sources.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->requireTable($schema, 'c_lp_view');
        $this->requireColumns($schema, 'c_lp_view', ['iid', 'completion_date']);

        $hasCompletionSource = $schema
            ->getTable('c_lp_view')
            ->hasColumn('compdate')
        ;
        $hasParamedicSource = $schema->hasTable('paramedic');
        $hasTrackProgressSource = $schema->hasTable('track_progress');

        if ($hasCompletionSource) {
            $this->prepareCompletionDateBackfill($schema);
        } else {
            $existingCompletionDates = (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM c_lp_view WHERE completion_date IS NOT NULL'
            );

            $this->write(\sprintf(
                'Legacy column c_lp_view.compdate is not present; completion-date backfill skipped. Existing normalized dates: %d.',
                $existingCompletionDates
            ));
        }

        if ($hasParamedicSource) {
            $this->prepareLabMetadataBackfill($schema);
        } else {
            $this->write(
                'Legacy table paramedic is not present; lab-title and lab-week backfill skipped.'
            );
        }

        if ($hasTrackProgressSource) {
            $this->prepareManualCompletionBackfill($schema);
        } else {
            $this->write(
                'Legacy table track_progress is not present; manual-completion backfill skipped.'
            );
        }

        if (!$hasCompletionSource && !$hasParamedicSource && !$hasTrackProgressSource) {
            $this->write(
                'No legacy LP evidence sources are present; the migration completed as a safe no-op for this already-normalized schema.'
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            true,
            'This migration preserves historical completion evidence and is intentionally irreversible.'
        );
    }

    private function prepareCompletionDateBackfill(Schema $schema): void
    {
        $this->requireColumns(
            $schema,
            'c_lp_view',
            ['iid', 'compdate', 'completion_date']
        );

        $completionConflicts = (int) $this->connection->fetchOne(<<<'SQL'
SELECT COUNT(*)
FROM c_lp_view
WHERE compdate IS NOT NULL
  AND CAST(compdate AS CHAR) NOT IN ('', '0000-00-00', '0000-00-00 00:00:00')
  AND completion_date IS NOT NULL
  AND completion_date <> DATE(compdate)
SQL);
        $this->abortIf(
            $completionConflicts > 0,
            \sprintf(
                'Legacy LP evidence migration refused because %d completion-date conflicts already exist.',
                $completionConflicts
            )
        );

        $completionPending = (int) $this->connection->fetchOne(<<<'SQL'
SELECT COUNT(*)
FROM c_lp_view
WHERE compdate IS NOT NULL
  AND CAST(compdate AS CHAR) NOT IN ('', '0000-00-00', '0000-00-00 00:00:00')
  AND completion_date IS NULL
SQL);

        $this->write(\sprintf(
            'Completion dates pending: %d.',
            $completionPending
        ));

        $this->queueCompletionDateBackfill();
    }

    private function prepareLabMetadataBackfill(Schema $schema): void
    {
        $this->requireColumns(
            $schema,
            'paramedic',
            ['id', 'cId', 'lpId', 'title', 'weekofday']
        );
        $this->requireTable($schema, 'c_lp');
        $this->requireColumns($schema, 'c_lp', ['iid', 'resource_node_id']);
        $this->requireTable($schema, 'resource_link');
        $this->requireColumns(
            $schema,
            'resource_link',
            ['resource_node_id', 'c_id']
        );
        $this->requireExtraFieldStorage($schema);

        $labTitleFieldId = $this->extraFieldId('lab_title', 6);
        $labWeekFieldId = $this->extraFieldId('lab_week', 6);

        $paramedicValueConflicts = (int) $this->connection->fetchOne(<<<'SQL'
SELECT COUNT(*)
FROM (
    SELECT target.iid
    FROM paramedic source
    INNER JOIN c_lp target
        ON target.iid = source.lpId
    WHERE EXISTS (
        SELECT 1
        FROM resource_link resource_link
        WHERE resource_link.resource_node_id = target.resource_node_id
          AND resource_link.c_id = source.cId
    )
    GROUP BY target.iid
    HAVING COUNT(DISTINCT NULLIF(TRIM(source.title), '')) > 1
        OR COUNT(DISTINCT NULLIF(TRIM(source.weekofday), '')) > 1
) conflicting_metadata
SQL);
        $this->abortIf(
            $paramedicValueConflicts > 0,
            \sprintf(
                'Legacy LP evidence migration refused because %d LPs have conflicting legacy lab metadata.',
                $paramedicValueConflicts
            )
        );

        $labTitleConflicts = $this->existingLabValueConflicts(
            $labTitleFieldId,
            'title'
        );
        $labWeekConflicts = $this->existingLabValueConflicts(
            $labWeekFieldId,
            'weekofday'
        );
        $this->abortIf(
            $labTitleConflicts + $labWeekConflicts > 0,
            \sprintf(
                'Legacy LP evidence migration refused because %d conflicting normalized lab values already exist.',
                $labTitleConflicts + $labWeekConflicts
            )
        );

        $paramedicSummary = $this->connection->fetchAssociative(<<<'SQL'
SELECT
    COUNT(*) AS source_rows,
    SUM(match_count = 1) AS matched_rows,
    SUM(match_count = 0) AS unresolved_rows,
    SUM(match_count > 1) AS ambiguous_rows
FROM (
    SELECT
        source.id,
        COUNT(target.iid) AS match_count
    FROM paramedic source
    LEFT JOIN c_lp target
        ON target.iid = source.lpId
       AND EXISTS (
           SELECT 1
           FROM resource_link resource_link
           WHERE resource_link.resource_node_id = target.resource_node_id
             AND resource_link.c_id = source.cId
       )
    GROUP BY source.id
) mapped
SQL) ?: [];

        $this->write(\sprintf(
            'Paramedic rows: %d matched, %d unresolved, %d ambiguous.',
            (int) ($paramedicSummary['matched_rows'] ?? 0),
            (int) ($paramedicSummary['unresolved_rows'] ?? 0),
            (int) ($paramedicSummary['ambiguous_rows'] ?? 0)
        ));

        $this->queueLabMetadataBackfill(
            $labTitleFieldId,
            $labWeekFieldId
        );
    }

    private function prepareManualCompletionBackfill(Schema $schema): void
    {
        $this->requireColumns(
            $schema,
            'track_progress',
            ['progressId', 'cId', 'userId', 'lpId', 'complete']
        );
        $this->requireColumns(
            $schema,
            'c_lp_view',
            ['iid', 'c_id', 'lp_id', 'user_id', 'progress']
        );
        $this->requireExtraFieldStorage($schema);

        $manualCompletionFieldId = $this->extraFieldId(
            'manual_completion',
            20
        );

        $manualCompletionConflicts = $this
            ->existingManualCompletionConflicts(
                $manualCompletionFieldId
            )
        ;
        $this->abortIf(
            $manualCompletionConflicts > 0,
            \sprintf(
                'Legacy LP evidence migration refused because %d conflicting manual-completion values already exist.',
                $manualCompletionConflicts
            )
        );

        $trackProgressSummary = $this->connection->fetchAssociative(<<<'SQL'
SELECT
    COUNT(*) AS source_distinct_rows,
    SUM(target_count = 1) AS uniquely_matched_rows,
    SUM(target_count = 0) AS unresolved_rows,
    SUM(target_count > 1) AS ambiguous_rows,
    SUM(target_count = 1 AND max_progress >= 100) AS covered_by_progress,
    SUM(target_count = 1 AND COALESCE(max_progress, 0) < 100) AS manual_completion_rows
FROM (
    SELECT
        source.cId,
        source.userId,
        source.lpId,
        COUNT(target.iid) AS target_count,
        MAX(target.progress) AS max_progress
    FROM (
        SELECT DISTINCT cId, userId, lpId
        FROM track_progress
        WHERE complete = 1
    ) source
    LEFT JOIN c_lp_view target
        ON target.c_id = source.cId
       AND target.user_id = source.userId
       AND target.lp_id = source.lpId
    GROUP BY source.cId, source.userId, source.lpId
) mapped
SQL) ?: [];

        $this->write(\sprintf(
            'Track progress: %d covered by progress, %d manual fallback, %d unresolved, %d ambiguous.',
            (int) ($trackProgressSummary['covered_by_progress'] ?? 0),
            (int) ($trackProgressSummary['manual_completion_rows'] ?? 0),
            (int) ($trackProgressSummary['unresolved_rows'] ?? 0),
            (int) ($trackProgressSummary['ambiguous_rows'] ?? 0)
        ));

        $this->queueManualCompletionBackfill(
            $manualCompletionFieldId
        );
    }

    private function queueCompletionDateBackfill(): void
    {
        $bounds = $this->connection->fetchAssociative(<<<'SQL'
SELECT MIN(iid) AS min_iid, MAX(iid) AS max_iid
FROM c_lp_view
WHERE compdate IS NOT NULL
  AND CAST(compdate AS CHAR) NOT IN ('', '0000-00-00', '0000-00-00 00:00:00')
  AND completion_date IS NULL
SQL) ?: [];

        $minIid = isset($bounds['min_iid'])
            ? (int) $bounds['min_iid']
            : 0;
        $maxIid = isset($bounds['max_iid'])
            ? (int) $bounds['max_iid']
            : 0;

        if ($minIid <= 0 || $maxIid < $minIid) {
            return;
        }

        for (
            $start = $minIid;
            $start <= $maxIid;
            $start += self::COMPLETION_BATCH_SIZE
        ) {
            $end = min(
                $maxIid,
                $start + self::COMPLETION_BATCH_SIZE - 1
            );

            $this->addSql(<<<'SQL'
UPDATE c_lp_view
SET completion_date = DATE(compdate)
WHERE iid BETWEEN :start_iid AND :end_iid
  AND compdate IS NOT NULL
  AND CAST(compdate AS CHAR) NOT IN ('', '0000-00-00', '0000-00-00 00:00:00')
  AND completion_date IS NULL
SQL, [
                'start_iid' => $start,
                'end_iid' => $end,
            ]);
        }
    }

    private function queueLabMetadataBackfill(
        int $labTitleFieldId,
        int $labWeekFieldId
    ): void {
        $this->addSql(
            'DROP TEMPORARY TABLE IF EXISTS tmp_legacy_lab_metadata_values'
        );
        $this->addSql(<<<'SQL'
CREATE TEMPORARY TABLE tmp_legacy_lab_metadata_values ENGINE = InnoDB AS
SELECT
    target.iid AS lp_iid,
    MAX(NULLIF(TRIM(source.title), '')) AS lab_title,
    MAX(NULLIF(TRIM(source.weekofday), '')) AS lab_week
FROM paramedic source
INNER JOIN c_lp target
    ON target.iid = source.lpId
WHERE EXISTS (
    SELECT 1
    FROM resource_link resource_link
    WHERE resource_link.resource_node_id = target.resource_node_id
      AND resource_link.c_id = source.cId
)
GROUP BY target.iid
SQL);
        $this->addSql(
            'ALTER TABLE tmp_legacy_lab_metadata_values ADD PRIMARY KEY (lp_iid)'
        );

        $this->addSql(<<<'SQL'
UPDATE extra_field_values existing
INNER JOIN tmp_legacy_lab_metadata_values source
    ON source.lp_iid = existing.item_id
SET existing.field_value = source.lab_title,
    existing.updated_at = NOW()
WHERE existing.field_id = :field_id
  AND source.lab_title IS NOT NULL
  AND TRIM(COALESCE(existing.field_value, '')) = ''
SQL, ['field_id' => $labTitleFieldId]);
        $this->addSql(<<<'SQL'
INSERT INTO extra_field_values
    (field_id, field_value, item_id, created_at, updated_at)
SELECT
    :field_id,
    source.lab_title,
    source.lp_iid,
    NOW(),
    NOW()
FROM tmp_legacy_lab_metadata_values source
LEFT JOIN extra_field_values existing
    ON existing.field_id = :field_id
   AND existing.item_id = source.lp_iid
WHERE source.lab_title IS NOT NULL
  AND existing.id IS NULL
SQL, ['field_id' => $labTitleFieldId]);

        $this->addSql(<<<'SQL'
UPDATE extra_field_values existing
INNER JOIN tmp_legacy_lab_metadata_values source
    ON source.lp_iid = existing.item_id
SET existing.field_value = source.lab_week,
    existing.updated_at = NOW()
WHERE existing.field_id = :field_id
  AND source.lab_week IS NOT NULL
  AND TRIM(COALESCE(existing.field_value, '')) = ''
SQL, ['field_id' => $labWeekFieldId]);
        $this->addSql(<<<'SQL'
INSERT INTO extra_field_values
    (field_id, field_value, item_id, created_at, updated_at)
SELECT
    :field_id,
    source.lab_week,
    source.lp_iid,
    NOW(),
    NOW()
FROM tmp_legacy_lab_metadata_values source
LEFT JOIN extra_field_values existing
    ON existing.field_id = :field_id
   AND existing.item_id = source.lp_iid
WHERE source.lab_week IS NOT NULL
  AND existing.id IS NULL
SQL, ['field_id' => $labWeekFieldId]);

        $this->addSql(
            'DROP TEMPORARY TABLE IF EXISTS tmp_legacy_lab_metadata_values'
        );
    }

    private function queueManualCompletionBackfill(
        int $manualCompletionFieldId
    ): void {
        $bounds = $this->connection->fetchAssociative(
            'SELECT MIN(iid) AS min_iid, MAX(iid) AS max_iid FROM c_lp_view'
        ) ?: [];
        $minIid = isset($bounds['min_iid'])
            ? (int) $bounds['min_iid']
            : 0;
        $maxIid = isset($bounds['max_iid'])
            ? (int) $bounds['max_iid']
            : 0;

        $this->addSql(
            'DROP TEMPORARY TABLE IF EXISTS tmp_legacy_track_progress_distinct'
        );
        $this->addSql(<<<'SQL'
CREATE TEMPORARY TABLE tmp_legacy_track_progress_distinct (
    c_id INT NOT NULL,
    user_id INT NOT NULL,
    lp_id INT NOT NULL,
    PRIMARY KEY (c_id, user_id, lp_id)
) ENGINE = InnoDB
SQL);
        $this->addSql(<<<'SQL'
INSERT IGNORE INTO tmp_legacy_track_progress_distinct
    (c_id, user_id, lp_id)
SELECT cId, userId, lpId
FROM track_progress
WHERE complete = 1
  AND cId IS NOT NULL
  AND userId IS NOT NULL
  AND lpId IS NOT NULL
SQL);

        $this->addSql(
            'DROP TEMPORARY TABLE IF EXISTS tmp_legacy_manual_completion_candidates'
        );
        $this->addSql(<<<'SQL'
CREATE TEMPORARY TABLE tmp_legacy_manual_completion_candidates ENGINE = InnoDB AS
SELECT MIN(target.iid) AS item_id
FROM tmp_legacy_track_progress_distinct source
INNER JOIN c_lp_view target
    ON target.c_id = source.c_id
   AND target.user_id = source.user_id
   AND target.lp_id = source.lp_id
GROUP BY source.c_id, source.user_id, source.lp_id
HAVING COUNT(target.iid) = 1
   AND COALESCE(MAX(target.progress), 0) < 100
SQL);
        $this->addSql(
            'ALTER TABLE tmp_legacy_manual_completion_candidates ADD PRIMARY KEY (item_id)'
        );

        $this->addSql(<<<'SQL'
UPDATE extra_field_values existing
INNER JOIN tmp_legacy_manual_completion_candidates source
    ON source.item_id = existing.item_id
SET existing.field_value = :field_value,
    existing.updated_at = NOW()
WHERE existing.field_id = :field_id
  AND TRIM(COALESCE(existing.field_value, '')) = ''
SQL, [
            'field_id' => $manualCompletionFieldId,
            'field_value' => '1',
        ]);

        if ($minIid > 0 && $maxIid >= $minIid) {
            for (
                $start = $minIid;
                $start <= $maxIid;
                $start += self::MANUAL_COMPLETION_BATCH_SIZE
            ) {
                $end = min(
                    $maxIid,
                    $start
                        + self::MANUAL_COMPLETION_BATCH_SIZE
                        - 1
                );

                $this->addSql(<<<'SQL'
INSERT INTO extra_field_values
    (field_id, field_value, item_id, created_at, updated_at)
SELECT
    :field_id,
    :field_value,
    source.item_id,
    NOW(),
    NOW()
FROM tmp_legacy_manual_completion_candidates source
LEFT JOIN extra_field_values existing
    ON existing.field_id = :field_id
   AND existing.item_id = source.item_id
WHERE source.item_id BETWEEN :start_iid AND :end_iid
  AND existing.id IS NULL
SQL, [
                    'field_id' => $manualCompletionFieldId,
                    'field_value' => '1',
                    'start_iid' => $start,
                    'end_iid' => $end,
                ]);
            }
        }

        $this->addSql(
            'DROP TEMPORARY TABLE IF EXISTS tmp_legacy_manual_completion_candidates'
        );
        $this->addSql(
            'DROP TEMPORARY TABLE IF EXISTS tmp_legacy_track_progress_distinct'
        );
    }

    private function extraFieldId(
        string $variable,
        int $itemType
    ): int {
        $rows = $this->connection->fetchFirstColumn(
            'SELECT id
             FROM extra_field
             WHERE variable = :variable
               AND item_type = :item_type
             ORDER BY id',
            [
                'variable' => $variable,
                'item_type' => $itemType,
            ]
        );

        $this->abortIf(
            1 !== \count($rows),
            \sprintf(
                'Expected exactly one %s extra field with item type %d, found %d.',
                $variable,
                $itemType,
                \count($rows)
            )
        );

        return (int) $rows[0];
    }

    private function existingLabValueConflicts(
        int $fieldId,
        string $legacyColumn
    ): int {
        $allowedColumns = ['title', 'weekofday'];
        $this->abortIf(
            !\in_array($legacyColumn, $allowedColumns, true),
            \sprintf(
                'Unsupported legacy lab column: %s.',
                $legacyColumn
            )
        );

        $sql = \sprintf(<<<'SQL'
SELECT COUNT(*)
FROM (
    SELECT mapped.lp_iid
    FROM (
        SELECT
            target.iid AS lp_iid,
            MAX(NULLIF(TRIM(source.%1$s), '')) AS expected_value
        FROM paramedic source
        INNER JOIN c_lp target
            ON target.iid = source.lpId
        WHERE EXISTS (
            SELECT 1
            FROM resource_link resource_link
            WHERE resource_link.resource_node_id = target.resource_node_id
              AND resource_link.c_id = source.cId
        )
        GROUP BY target.iid
    ) mapped
    INNER JOIN extra_field_values existing
        ON existing.field_id = :field_id
       AND existing.item_id = mapped.lp_iid
    WHERE mapped.expected_value IS NOT NULL
    GROUP BY mapped.lp_iid, mapped.expected_value
    HAVING COUNT(existing.id) > 1
        OR SUM(
            CASE
                WHEN TRIM(COALESCE(existing.field_value, '')) <> ''
                 AND TRIM(existing.field_value) <> mapped.expected_value
                THEN 1 ELSE 0
            END
        ) > 0
) conflicts
SQL, $legacyColumn);

        return (int) $this->connection->fetchOne(
            $sql,
            ['field_id' => $fieldId]
        );
    }

    private function existingManualCompletionConflicts(
        int $fieldId
    ): int {
        return (int) $this->connection->fetchOne(<<<'SQL'
SELECT COUNT(*)
FROM (
    SELECT mapped.item_id
    FROM (
        SELECT MIN(target.iid) AS item_id
        FROM (
            SELECT DISTINCT cId, userId, lpId
            FROM track_progress
            WHERE complete = 1
        ) source
        INNER JOIN c_lp_view target
            ON target.c_id = source.cId
           AND target.user_id = source.userId
           AND target.lp_id = source.lpId
        GROUP BY source.cId, source.userId, source.lpId
        HAVING COUNT(target.iid) = 1
           AND COALESCE(MAX(target.progress), 0) < 100
    ) mapped
    INNER JOIN extra_field_values existing
        ON existing.field_id = :field_id
       AND existing.item_id = mapped.item_id
    GROUP BY mapped.item_id
    HAVING COUNT(existing.id) > 1
        OR SUM(
            CASE
                WHEN TRIM(COALESCE(existing.field_value, '')) NOT IN ('', '1')
                THEN 1 ELSE 0
            END
        ) > 0
) conflicts
SQL, ['field_id' => $fieldId]);
    }

    private function requireExtraFieldStorage(
        Schema $schema
    ): void {
        $this->requireTable($schema, 'extra_field');
        $this->requireColumns(
            $schema,
            'extra_field',
            ['id', 'item_type', 'variable']
        );
        $this->requireTable($schema, 'extra_field_values');
        $this->requireColumns(
            $schema,
            'extra_field_values',
            [
                'id',
                'field_id',
                'field_value',
                'item_id',
                'created_at',
                'updated_at',
            ]
        );
    }

    /**
     * @param list<string> $columns
     */
    private function requireColumns(
        Schema $schema,
        string $tableName,
        array $columns
    ): void {
        $this->requireTable($schema, $tableName);
        $table = $schema->getTable($tableName);

        foreach ($columns as $column) {
            $this->abortIf(
                !$table->hasColumn($column),
                \sprintf(
                    'Required target column %s.%s does not exist.',
                    $tableName,
                    $column
                )
            );
        }
    }

    private function requireTable(
        Schema $schema,
        string $tableName
    ): void {
        $this->abortIf(
            !$schema->hasTable($tableName),
            \sprintf(
                'Required target table %s does not exist.',
                $tableName
            )
        );
    }
}
