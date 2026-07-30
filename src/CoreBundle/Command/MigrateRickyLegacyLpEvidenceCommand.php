<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Doctrine\DBAL\Connection;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'chamilo:migration:migrate-ricky-legacy-lp-evidence',
    description: 'Migrate legacy Ricky LP dates and metadata into generic Chamilo 2 structures.'
)]
final class MigrateRickyLegacyLpEvidenceCommand extends Command
{
    private const PHASE_ALL = 'all';
    private const PHASE_COMPLETION_DATES = 'completion-dates';
    private const PHASE_LAB_METADATA = 'lab-metadata';
    private const PHASE_MANUAL_COMPLETION = 'manual-completion';

    private const VALID_PHASES = [
        self::PHASE_ALL,
        self::PHASE_COMPLETION_DATES,
        self::PHASE_LAB_METADATA,
        self::PHASE_MANUAL_COMPLETION,
    ];

    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'completion-source-database',
                null,
                InputOption::VALUE_REQUIRED,
                'Database containing the post-upgrade c_lp_view.compdate values.'
            )
            ->addOption(
                'legacy-source-database',
                null,
                InputOption::VALUE_REQUIRED,
                'Database containing the legacy paramedic and track_progress tables.'
            )
            ->addOption(
                'phase',
                null,
                InputOption::VALUE_REQUIRED,
                'Phase: all, completion-dates, lab-metadata or manual-completion.',
                self::PHASE_ALL
            )
            ->addOption(
                'batch-size',
                null,
                InputOption::VALUE_REQUIRED,
                'Rows scanned per write batch.',
                '5000'
            )
            ->addOption(
                'max-batches',
                null,
                InputOption::VALUE_REQUIRED,
                'Maximum write batches per phase. Use 0 for all pending batches.',
                '0'
            )
            ->addOption(
                'after-source-id',
                null,
                InputOption::VALUE_REQUIRED,
                'Manual-completion cursor. Start after this legacy track_progress.progressId.',
                '0'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Report pending, conflicting and unresolved rows without changing data.'
            )
            ->addOption(
                'apply',
                null,
                InputOption::VALUE_NONE,
                'Apply the selected migration phases.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Migrate Ricky legacy LP evidence');

        try {
            $dryRun = (bool) $input->getOption('dry-run');
            $apply = (bool) $input->getOption('apply');

            if ($dryRun === $apply) {
                throw new RuntimeException('Select exactly one mode: --dry-run or --apply.');
            }

            $phase = strtolower(trim((string) $input->getOption('phase')));
            if (!\in_array($phase, self::VALID_PHASES, true)) {
                throw new RuntimeException(sprintf(
                    '--phase must be one of: %s.',
                    implode(', ', self::VALID_PHASES)
                ));
            }

            $batchSize = $this->boundedPositiveOption($input, 'batch-size', 20000);
            $maxBatches = $this->boundedNonNegativeOption($input, 'max-batches', 100000);
            $afterSourceId = $this->boundedNonNegativeOption($input, 'after-source-id', PHP_INT_MAX);
            $targetDatabase = $this->currentDatabase();
            $completionSource = $this->validatedDatabaseOption(
                $input,
                'completion-source-database',
                \in_array($phase, [self::PHASE_ALL, self::PHASE_COMPLETION_DATES], true)
            );
            $legacySource = $this->validatedDatabaseOption(
                $input,
                'legacy-source-database',
                \in_array($phase, [self::PHASE_ALL, self::PHASE_LAB_METADATA, self::PHASE_MANUAL_COMPLETION], true)
            );

            $io->definitionList(
                ['Mode' => $dryRun ? 'dry-run' : 'apply'],
                ['Phase' => $phase],
                ['Target database' => $targetDatabase],
                ['Completion source' => $completionSource ?? 'not required'],
                ['Legacy source' => $legacySource ?? 'not required'],
                ['Batch size' => $batchSize],
                ['Maximum batches' => 0 === $maxBatches ? 'all' : $maxBatches],
                ['Manual-completion cursor' => $afterSourceId]
            );

            $summary = [];

            if (\in_array($phase, [self::PHASE_ALL, self::PHASE_COMPLETION_DATES], true)) {
                $summary['Completion dates'] = $this->migrateCompletionDates(
                    $completionSource,
                    $targetDatabase,
                    $batchSize,
                    $maxBatches,
                    $dryRun
                );
            }

            if (\in_array($phase, [self::PHASE_ALL, self::PHASE_LAB_METADATA], true)) {
                $summary['Lab metadata'] = $this->migrateLabMetadata(
                    $legacySource,
                    $targetDatabase,
                    $dryRun
                );
            }

            if (\in_array($phase, [self::PHASE_ALL, self::PHASE_MANUAL_COMPLETION], true)) {
                $summary['Manual completion'] = $this->migrateManualCompletion(
                    $legacySource,
                    $targetDatabase,
                    $batchSize,
                    $maxBatches,
                    $afterSourceId,
                    $dryRun
                );
            }

            $rows = [];
            foreach ($summary as $label => $values) {
                $rows[] = [
                    $label,
                    $values['pending'] ?? 0,
                    $values['migrated'] ?? 0,
                    $values['already'] ?? 0,
                    $values['conflicts'] ?? 0,
                    $values['unresolved'] ?? 0,
                    $values['ambiguous'] ?? 0,
                    $values['remaining'] ?? 0,
                ];
            }

            $io->table(
                ['Phase', 'Pending', 'Migrated', 'Already', 'Conflicts', 'Unresolved', 'Ambiguous', 'Remaining'],
                $rows
            );

            if (isset($summary['Manual completion']['last_source_id'])) {
                $io->note(sprintf(
                    'Manual-completion next cursor: --after-source-id=%d',
                    (int) $summary['Manual completion']['last_source_id']
                ));
            }

            $conflicts = array_sum(array_map(
                static fn (array $values): int => (int) ($values['conflicts'] ?? 0),
                $summary
            ));

            if ($conflicts > 0) {
                $io->warning('Conflicting existing values were preserved and require manual review.');
            }

            if ($dryRun) {
                $io->success('Read-only LP evidence evaluation completed.');
            } else {
                $io->success('Selected LP evidence phases were migrated safely.');
            }

            return Command::SUCCESS;
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * @return array<string, int>
     */
    private function migrateCompletionDates(
        string $sourceDatabase,
        string $targetDatabase,
        int $batchSize,
        int $maxBatches,
        bool $dryRun
    ): array {
        $this->requireTable($sourceDatabase, 'c_lp_view');
        $this->requireColumn($sourceDatabase, 'c_lp_view', 'iid');
        $this->requireColumn($sourceDatabase, 'c_lp_view', 'compdate');
        $this->requireTable($targetDatabase, 'c_lp_view');
        $this->requireColumn($targetDatabase, 'c_lp_view', 'iid');
        $this->requireColumn($targetDatabase, 'c_lp_view', 'completion_date');

        $validDate = $this->validLegacyDateCondition('source.compdate');
        $pendingSql = sprintf(
            'SELECT COUNT(*)
             FROM `%s`.c_lp_view source
             INNER JOIN `%s`.c_lp_view target ON target.iid = source.iid
             WHERE %s
               AND target.completion_date IS NULL',
            $sourceDatabase,
            $targetDatabase,
            $validDate
        );
        $conflictSql = sprintf(
            'SELECT COUNT(*)
             FROM `%s`.c_lp_view source
             INNER JOIN `%s`.c_lp_view target ON target.iid = source.iid
             WHERE %s
               AND target.completion_date IS NOT NULL
               AND target.completion_date <> source.compdate',
            $sourceDatabase,
            $targetDatabase,
            $validDate
        );

        $pending = (int) $this->connection->fetchOne($pendingSql);
        $conflicts = (int) $this->connection->fetchOne($conflictSql);

        if ($conflicts > 0 && !$dryRun) {
            throw new RuntimeException(sprintf(
                'Completion-date migration refused because %d conflicting dates already exist.',
                $conflicts
            ));
        }

        if ($dryRun || 0 === $pending) {
            return $this->phaseSummary($pending, 0, 0, $conflicts, 0, 0, $pending);
        }

        $migrated = 0;
        $batches = 0;

        while (0 === $maxBatches || $batches < $maxBatches) {
            $lastIid = $this->connection->fetchOne(sprintf(
                'SELECT MAX(pending.iid)
                 FROM (
                     SELECT source.iid
                     FROM `%s`.c_lp_view source
                     INNER JOIN `%s`.c_lp_view target ON target.iid = source.iid
                     WHERE %s
                       AND target.completion_date IS NULL
                     ORDER BY source.iid
                     LIMIT %d
                 ) pending',
                $sourceDatabase,
                $targetDatabase,
                $validDate,
                $batchSize
            ));

            if (false === $lastIid || null === $lastIid) {
                break;
            }

            $updated = $this->connection->executeStatement(sprintf(
                'UPDATE `%s`.c_lp_view target
                 INNER JOIN `%s`.c_lp_view source ON source.iid = target.iid
                 SET target.completion_date = source.compdate
                 WHERE source.iid <= :lastIid
                   AND %s
                   AND target.completion_date IS NULL',
                $targetDatabase,
                $sourceDatabase,
                $validDate
            ), ['lastIid' => (int) $lastIid]);

            if (0 === $updated) {
                break;
            }

            $migrated += $updated;
            ++$batches;
        }

        $remaining = (int) $this->connection->fetchOne($pendingSql);

        return $this->phaseSummary($pending, $migrated, 0, $conflicts, 0, 0, $remaining);
    }

    /**
     * @return array<string, int>
     */
    private function migrateLabMetadata(
        string $sourceDatabase,
        string $targetDatabase,
        bool $dryRun
    ): array {
        $this->requireTable($sourceDatabase, 'paramedic');
        $this->requireTable($targetDatabase, 'c_lp');
        $this->requireTable($targetDatabase, 'resource_link');
        $this->requireTable($targetDatabase, 'extra_field');
        $this->requireTable($targetDatabase, 'extra_field_values');

        $titleFieldId = $this->extraFieldId($targetDatabase, 'lab_title', 6);
        $weekFieldId = $this->extraFieldId($targetDatabase, 'lab_week', 6);

        $mappingSql = sprintf(
            'SELECT
                 source.id AS source_id,
                 source.title,
                 source.weekofday,
                 target.iid AS lp_iid
             FROM `%s`.paramedic source
             INNER JOIN `%s`.c_lp target ON target.iid = source.lpId
             WHERE EXISTS (
                 SELECT 1
                 FROM `%s`.resource_link resource_link
                 WHERE resource_link.resource_node_id = target.resource_node_id
                   AND resource_link.c_id = source.cId
             )
             ORDER BY source.id',
            $sourceDatabase,
            $targetDatabase,
            $targetDatabase
        );
        $rows = $this->connection->fetchAllAssociative($mappingSql);
        $sourceCount = (int) $this->connection->fetchOne(sprintf(
            'SELECT COUNT(*) FROM `%s`.paramedic',
            $sourceDatabase
        ));
        $unresolved = max(0, $sourceCount - \count($rows));

        $pending = 0;
        $migrated = 0;
        $already = 0;
        $conflicts = 0;

        foreach ($rows as $row) {
            $lpIid = (int) $row['lp_iid'];
            $values = [
                $titleFieldId => trim((string) ($row['title'] ?? '')),
                $weekFieldId => trim((string) ($row['weekofday'] ?? '')),
            ];

            foreach ($values as $fieldId => $value) {
                if ('' === $value) {
                    continue;
                }

                $existing = $this->connection->fetchAllAssociative(sprintf(
                    'SELECT id, field_value
                     FROM `%s`.extra_field_values
                     WHERE field_id = :fieldId
                       AND item_id = :itemId
                     ORDER BY id',
                    $targetDatabase
                ), [
                    'fieldId' => $fieldId,
                    'itemId' => $lpIid,
                ]);

                if ([] === $existing) {
                    ++$pending;
                    if (!$dryRun) {
                        $this->connection->executeStatement(sprintf(
                            'INSERT INTO `%s`.extra_field_values
                                (field_id, field_value, item_id, created_at, updated_at)
                             VALUES (:fieldId, :fieldValue, :itemId, NOW(), NOW())',
                            $targetDatabase
                        ), [
                            'fieldId' => $fieldId,
                            'fieldValue' => $value,
                            'itemId' => $lpIid,
                        ]);
                        ++$migrated;
                    }
                    continue;
                }

                if (1 !== \count($existing)) {
                    ++$conflicts;
                    continue;
                }

                $current = trim((string) ($existing[0]['field_value'] ?? ''));
                if ($current === $value) {
                    ++$already;
                    continue;
                }

                if ('' !== $current) {
                    ++$conflicts;
                    continue;
                }

                ++$pending;
                if (!$dryRun) {
                    $this->connection->executeStatement(sprintf(
                        'UPDATE `%s`.extra_field_values
                         SET field_value = :fieldValue, updated_at = NOW()
                         WHERE id = :id',
                        $targetDatabase
                    ), [
                        'fieldValue' => $value,
                        'id' => (int) $existing[0]['id'],
                    ]);
                    ++$migrated;
                }
            }
        }

        return $this->phaseSummary(
            $pending,
            $migrated,
            $already,
            $conflicts,
            $unresolved,
            0,
            $dryRun ? $pending : max(0, $pending - $migrated)
        );
    }

    /**
     * @return array<string, int>
     */
    private function migrateManualCompletion(
        string $sourceDatabase,
        string $targetDatabase,
        int $batchSize,
        int $maxBatches,
        int $afterSourceId,
        bool $dryRun
    ): array {
        $this->requireTable($sourceDatabase, 'track_progress');
        $this->requireTable($targetDatabase, 'c_lp_view');
        $this->requireTable($targetDatabase, 'extra_field');
        $this->requireTable($targetDatabase, 'extra_field_values');

        $fieldId = $this->extraFieldId($targetDatabase, 'manual_completion', 20);
        $aggregateSql = sprintf(
            'SELECT
                 COUNT(*) AS source_distinct_rows,
                 SUM(target_count = 1) AS uniquely_matched_rows,
                 SUM(target_count = 0) AS unmatched_rows,
                 SUM(target_count > 1) AS ambiguous_rows,
                 SUM(target_count = 1 AND max_progress >= 100) AS covered_by_progress,
                 SUM(target_count = 1 AND COALESCE(max_progress, 0) < 100) AS requires_manual_fallback
             FROM (
                 SELECT
                     source.cId,
                     source.userId,
                     source.lpId,
                     COUNT(target.iid) AS target_count,
                     MAX(target.progress) AS max_progress
                 FROM (
                     SELECT DISTINCT cId, userId, lpId
                     FROM `%s`.track_progress
                     WHERE complete = :complete
                 ) source
                 LEFT JOIN `%s`.c_lp_view target
                   ON target.c_id = source.cId
                  AND target.user_id = source.userId
                  AND target.lp_id = source.lpId
                 GROUP BY source.cId, source.userId, source.lpId
             ) mapped',
            $sourceDatabase,
            $targetDatabase
        );
        $aggregate = $this->connection->fetchAssociative($aggregateSql, ['complete' => '1']) ?: [];
        $unresolved = (int) ($aggregate['unmatched_rows'] ?? 0);
        $ambiguous = (int) ($aggregate['ambiguous_rows'] ?? 0);

        $pendingSql = sprintf(
            'SELECT COUNT(*)
             FROM (
                 SELECT MIN(target.iid) AS item_id
                 FROM (
                     SELECT DISTINCT cId, userId, lpId
                     FROM `%s`.track_progress
                     WHERE complete = :complete
                 ) source
                 INNER JOIN `%s`.c_lp_view target
                   ON target.c_id = source.cId
                  AND target.user_id = source.userId
                  AND target.lp_id = source.lpId
                 GROUP BY source.cId, source.userId, source.lpId
                 HAVING COUNT(target.iid) = 1
                    AND COALESCE(MAX(target.progress), 0) < 100
             ) mapped
             LEFT JOIN `%s`.extra_field_values existing
               ON existing.field_id = :fieldId
              AND existing.item_id = mapped.item_id
             WHERE existing.id IS NULL',
            $sourceDatabase,
            $targetDatabase,
            $targetDatabase
        );
        $pending = (int) $this->connection->fetchOne($pendingSql, [
            'complete' => '1',
            'fieldId' => $fieldId,
        ]);
        $already = (int) $this->connection->fetchOne(sprintf(
            'SELECT COUNT(*)
             FROM `%s`.extra_field_values
             WHERE field_id = :fieldId
               AND field_value = :value',
            $targetDatabase
        ), [
            'fieldId' => $fieldId,
            'value' => '1',
        ]);
        $conflicts = (int) $this->connection->fetchOne(sprintf(
            'SELECT COUNT(*)
             FROM `%s`.extra_field_values
             WHERE field_id = :fieldId
               AND COALESCE(field_value, :empty) NOT IN (:empty, :value)',
            $targetDatabase
        ), [
            'fieldId' => $fieldId,
            'empty' => '',
            'value' => '1',
        ]);

        if ($dryRun || 0 === $pending) {
            return $this->phaseSummary(
                $pending,
                0,
                $already,
                $conflicts,
                $unresolved,
                $ambiguous,
                $pending
            ) + ['last_source_id' => $afterSourceId];
        }

        $migrated = 0;
        $batches = 0;
        $cursor = $afterSourceId;

        while (0 === $maxBatches || $batches < $maxBatches) {
            $lastProgressId = $this->connection->fetchOne(sprintf(
                'SELECT MAX(batch.progressId)
                 FROM (
                     SELECT progressId
                     FROM `%s`.track_progress
                     WHERE complete = :complete
                       AND progressId > :cursor
                     ORDER BY progressId
                     LIMIT %d
                 ) batch',
                $sourceDatabase,
                $batchSize
            ), [
                'complete' => '1',
                'cursor' => $cursor,
            ]);

            if (false === $lastProgressId || null === $lastProgressId) {
                break;
            }

            $inserted = $this->connection->executeStatement(sprintf(
                'INSERT INTO `%s`.extra_field_values
                    (field_id, field_value, item_id, created_at, updated_at)
                 SELECT :fieldId, :value, mapped.item_id, NOW(), NOW()
                 FROM (
                     SELECT MIN(target.iid) AS item_id
                     FROM `%s`.track_progress source
                     INNER JOIN `%s`.c_lp_view target
                       ON target.c_id = source.cId
                      AND target.user_id = source.userId
                      AND target.lp_id = source.lpId
                     WHERE source.complete = :complete
                       AND source.progressId > :cursor
                       AND source.progressId <= :lastProgressId
                     GROUP BY source.cId, source.userId, source.lpId
                     HAVING COUNT(DISTINCT target.iid) = 1
                        AND COALESCE(MAX(target.progress), 0) < 100
                 ) mapped
                 WHERE NOT EXISTS (
                     SELECT 1
                     FROM `%s`.extra_field_values existing
                     WHERE existing.field_id = :fieldId
                       AND existing.item_id = mapped.item_id
                 )',
                $targetDatabase,
                $sourceDatabase,
                $targetDatabase,
                $targetDatabase
            ), [
                'fieldId' => $fieldId,
                'value' => '1',
                'complete' => '1',
                'cursor' => $cursor,
                'lastProgressId' => (int) $lastProgressId,
            ]);

            $migrated += $inserted;
            $cursor = (int) $lastProgressId;
            ++$batches;
        }

        $remaining = (int) $this->connection->fetchOne($pendingSql, [
            'complete' => '1',
            'fieldId' => $fieldId,
        ]);

        return $this->phaseSummary(
            $pending,
            $migrated,
            $already,
            $conflicts,
            $unresolved,
            $ambiguous,
            $remaining
        ) + ['last_source_id' => $cursor];
    }

    private function currentDatabase(): string
    {
        $database = trim((string) $this->connection->fetchOne('SELECT DATABASE()'));
        if ('' === $database) {
            throw new RuntimeException('The current target database could not be resolved.');
        }

        return $this->validatedDatabaseName($database);
    }

    private function validatedDatabaseOption(
        InputInterface $input,
        string $name,
        bool $required
    ): ?string {
        $value = trim((string) $input->getOption($name));
        if ('' === $value) {
            if ($required) {
                throw new RuntimeException(sprintf('--%s is required for the selected phase.', $name));
            }

            return null;
        }

        $database = $this->validatedDatabaseName($value);
        $exists = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = :database',
            ['database' => $database]
        );

        if (1 !== $exists) {
            throw new RuntimeException(sprintf('Database "%s" does not exist.', $database));
        }

        return $database;
    }

    private function validatedDatabaseName(string $database): string
    {
        if (1 !== preg_match('/^[A-Za-z0-9_]+$/', $database)) {
            throw new RuntimeException(sprintf('Unsafe database name: "%s".', $database));
        }

        return $database;
    }

    private function requireTable(string $database, string $table): void
    {
        $exists = (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = :database
               AND TABLE_NAME = :table',
            [
                'database' => $database,
                'table' => $table,
            ]
        );

        if (1 !== $exists) {
            throw new RuntimeException(sprintf('Required table %s.%s does not exist.', $database, $table));
        }
    }

    private function requireColumn(string $database, string $table, string $column): void
    {
        $exists = (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = :database
               AND TABLE_NAME = :table
               AND COLUMN_NAME = :column',
            [
                'database' => $database,
                'table' => $table,
                'column' => $column,
            ]
        );

        if (1 !== $exists) {
            throw new RuntimeException(sprintf(
                'Required column %s.%s.%s does not exist.',
                $database,
                $table,
                $column
            ));
        }
    }

    private function extraFieldId(string $database, string $variable, int $itemType): int
    {
        $rows = $this->connection->fetchFirstColumn(sprintf(
            'SELECT id
             FROM `%s`.extra_field
             WHERE variable = :variable
               AND item_type = :itemType
             ORDER BY id',
            $database
        ), [
            'variable' => $variable,
            'itemType' => $itemType,
        ]);

        if (1 !== \count($rows)) {
            throw new RuntimeException(sprintf(
                'Expected one %s extra field with item type %d, found %d.',
                $variable,
                $itemType,
                \count($rows)
            ));
        }

        return (int) $rows[0];
    }

    private function validLegacyDateCondition(string $expression): string
    {
        return sprintf(
            '%1$s IS NOT NULL
             AND CAST(%1$s AS CHAR) NOT IN (\'\', \'0000-00-00\', \'0000-00-00 00:00:00\')',
            $expression
        );
    }

    /**
     * @return array<string, int>
     */
    private function phaseSummary(
        int $pending,
        int $migrated,
        int $already,
        int $conflicts,
        int $unresolved,
        int $ambiguous,
        int $remaining
    ): array {
        return [
            'pending' => $pending,
            'migrated' => $migrated,
            'already' => $already,
            'conflicts' => $conflicts,
            'unresolved' => $unresolved,
            'ambiguous' => $ambiguous,
            'remaining' => $remaining,
        ];
    }

    private function boundedPositiveOption(InputInterface $input, string $name, int $maximum): int
    {
        $raw = trim((string) $input->getOption($name));
        if ('' === $raw || !ctype_digit($raw)) {
            throw new RuntimeException(sprintf('--%s must be a positive integer.', $name));
        }

        $value = (int) $raw;
        if ($value < 1 || $value > $maximum) {
            throw new RuntimeException(sprintf('--%s must be between 1 and %d.', $name, $maximum));
        }

        return $value;
    }

    private function boundedNonNegativeOption(InputInterface $input, string $name, int $maximum): int
    {
        $raw = trim((string) $input->getOption($name));
        if ('' === $raw || !ctype_digit($raw)) {
            throw new RuntimeException(sprintf('--%s must be a non-negative integer.', $name));
        }

        $value = (int) $raw;
        if ($value > $maximum) {
            throw new RuntimeException(sprintf('--%s must be between 0 and %d.', $name, $maximum));
        }

        return $value;
    }
}
