<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Component\Gradebook\CourseCompletionRuleEvaluator;
use Chamilo\CoreBundle\Entity\ExtraField;
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
    name: 'chamilo:migration:genericize-legacy-rule-sources',
    description: 'Replace customer-specific legacy rule source markers with generic values.'
)]
final class GenericizeLegacyRuleSourcesCommand extends Command
{
    private const array SOURCE_MAPPINGS = [
        [
            'label' => 'Course completion rules',
            'item_type' => ExtraField::COURSE_FIELD_TYPE,
            'variable' => CourseCompletionRuleEvaluator::COURSE_RULE_FIELD_VARIABLE,
            'generic_source' => 'legacy_course_completion_rule',
        ],
        [
            'label' => 'Final-exam access rules',
            'item_type' => ExtraField::EXERCISE_FIELD_TYPE,
            'variable' => 'final_exam_access_rule',
            'generic_source' => 'legacy_final_exam_access_rule',
        ],
    ];

    public function __construct(
        private readonly Connection $connection
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Report the exact changes without modifying the database.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Genericize legacy rule sources');

        $dryRun = (bool) $input->getOption('dry-run');
        $summary = [
            'legacy_rows' => 0,
            'generic_rows' => 0,
            'updated_rows' => 0,
        ];

        try {
            $this->assertRequiredTables();

            $plannedMappings = [];
            $rows = [];

            foreach (self::SOURCE_MAPPINGS as $mapping) {
                $legacySources = $this->findNonGenericSources($mapping);
                if (\count($legacySources) > 1) {
                    throw new RuntimeException(\sprintf('%s contains multiple non-generic source markers; no value was changed.', $mapping['label']));
                }

                $legacySource = $legacySources[0] ?? null;
                $legacyRows = null === $legacySource
                    ? 0
                    : $this->countRows($mapping, $legacySource);
                $genericRows = $this->countRows($mapping, $mapping['generic_source']);

                $summary['legacy_rows'] += $legacyRows;
                $summary['generic_rows'] += $genericRows;

                $plannedMappings[] = $mapping + ['legacy_source' => $legacySource];
                $rows[] = [
                    $mapping['label'],
                    $mapping['variable'],
                    $legacySource ?? 'none',
                    $mapping['generic_source'],
                    $legacyRows,
                    $genericRows,
                ];
            }

            $io->table(
                [
                    'Rule type',
                    'Extra field',
                    'Detected legacy source',
                    'Generic source',
                    'Legacy rows',
                    'Generic rows',
                ],
                $rows
            );

            if ($dryRun) {
                $io->definitionList(
                    ['Mode' => 'dry-run'],
                    ['Rows to update' => $summary['legacy_rows']],
                    ['Already generic' => $summary['generic_rows']],
                    ['Database changes' => 0]
                );
                $io->success('Read-only source-marker evaluation completed.');

                return Command::SUCCESS;
            }

            if (0 === $summary['legacy_rows']) {
                $io->definitionList(
                    ['Mode' => 'write'],
                    ['Rows updated' => 0],
                    ['Already generic' => $summary['generic_rows']]
                );
                $io->success('No legacy source markers required migration.');

                return Command::SUCCESS;
            }

            $this->connection->beginTransaction();

            foreach ($plannedMappings as $mapping) {
                $legacySource = $mapping['legacy_source'];
                if (null === $legacySource) {
                    continue;
                }

                $currentSources = $this->findNonGenericSources($mapping);
                if ([$legacySource] !== $currentSources) {
                    throw new RuntimeException(\sprintf('%s source markers changed during execution; no value was committed.', $mapping['label']));
                }

                $expectedRows = $this->countRows($mapping, $legacySource);
                $updatedRows = $this->updateRows($mapping, $legacySource);
                if ($updatedRows !== $expectedRows) {
                    throw new RuntimeException(\sprintf('%s expected %d updates but changed %d rows.', $mapping['label'], $expectedRows, $updatedRows));
                }

                $summary['updated_rows'] += $updatedRows;
            }

            $remainingLegacyRows = 0;
            foreach (self::SOURCE_MAPPINGS as $mapping) {
                foreach ($this->findNonGenericSources($mapping) as $source) {
                    $remainingLegacyRows += $this->countRows($mapping, $source);
                }
            }

            if (0 !== $remainingLegacyRows) {
                throw new RuntimeException(\sprintf('%d legacy source marker rows remain after the update.', $remainingLegacyRows));
            }

            $this->connection->commit();

            $io->definitionList(
                ['Mode' => 'write'],
                ['Rows updated' => $summary['updated_rows']],
                ['Previously generic' => $summary['generic_rows']],
                ['Legacy rows remaining' => 0]
            );
            $io->success('Legacy rule sources were migrated to generic values.');

            return Command::SUCCESS;
        } catch (Throwable $exception) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            $io->error($exception->getMessage());

            return Command::FAILURE;
        }
    }

    private function assertRequiredTables(): void
    {
        foreach (['extra_field', 'extra_field_values'] as $tableName) {
            if (!$this->connection->createSchemaManager()->tablesExist([$tableName])) {
                throw new RuntimeException(\sprintf('Required table "%s" was not found.', $tableName));
            }
        }
    }

    /**
     * @param array{
     *     item_type: int,
     *     variable: string,
     *     generic_source: string,
     *     ...
     * } $mapping
     *
     * @return list<string>
     */
    private function findNonGenericSources(array $mapping): array
    {
        $sources = $this->connection->fetchFirstColumn(
            <<<'SQL'
SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(efv.field_value, '$.source')) AS source
FROM extra_field ef
INNER JOIN extra_field_values efv ON efv.field_id = ef.id
WHERE ef.item_type = :itemType
  AND ef.variable = :variable
  AND JSON_VALID(efv.field_value) = 1
  AND JSON_TYPE(JSON_EXTRACT(efv.field_value, '$.source')) = 'STRING'
  AND TRIM(JSON_UNQUOTE(JSON_EXTRACT(efv.field_value, '$.source'))) <> ''
  AND JSON_UNQUOTE(JSON_EXTRACT(efv.field_value, '$.source')) <> :genericSource
ORDER BY source
SQL,
            [
                'itemType' => $mapping['item_type'],
                'variable' => $mapping['variable'],
                'genericSource' => $mapping['generic_source'],
            ]
        );

        return array_values(array_map('strval', $sources));
    }

    /**
     * @param array{
     *     item_type: int,
     *     variable: string,
     *     generic_source: string,
     *     ...
     * } $mapping
     */
    private function countRows(array $mapping, string $source): int
    {
        return (int) $this->connection->fetchOne(
            <<<'SQL'
SELECT COUNT(*)
FROM extra_field ef
INNER JOIN extra_field_values efv ON efv.field_id = ef.id
WHERE ef.item_type = :itemType
  AND ef.variable = :variable
  AND JSON_VALID(efv.field_value) = 1
  AND JSON_UNQUOTE(JSON_EXTRACT(efv.field_value, '$.source')) = :source
SQL,
            [
                'itemType' => $mapping['item_type'],
                'variable' => $mapping['variable'],
                'source' => $source,
            ]
        );
    }

    /**
     * @param array{
     *     item_type: int,
     *     variable: string,
     *     generic_source: string,
     *     ...
     * } $mapping
     */
    private function updateRows(array $mapping, string $legacySource): int
    {
        return $this->connection->executeStatement(
            <<<'SQL'
UPDATE extra_field_values efv
INNER JOIN extra_field ef ON ef.id = efv.field_id
SET
    efv.field_value = JSON_SET(efv.field_value, '$.source', :genericSource),
    efv.updated_at = :updatedAt
WHERE ef.item_type = :itemType
  AND ef.variable = :variable
  AND JSON_VALID(efv.field_value) = 1
  AND JSON_UNQUOTE(JSON_EXTRACT(efv.field_value, '$.source')) = :legacySource
SQL,
            [
                'genericSource' => $mapping['generic_source'],
                'updatedAt' => date('Y-m-d H:i:s'),
                'itemType' => $mapping['item_type'],
                'variable' => $mapping['variable'],
                'legacySource' => $legacySource,
            ]
        );
    }
}
