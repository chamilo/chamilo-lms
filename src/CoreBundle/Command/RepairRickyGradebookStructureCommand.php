<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Component\Gradebook\CourseCompletionRuleEvaluator;
use Chamilo\CoreBundle\Entity\ExtraField;
use Doctrine\DBAL\Connection;
use JsonException;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'chamilo:migration:repair-ricky-gradebook-structure',
    description: 'Repair Ricky gradebook links from persisted course completion rules.'
)]
final class RepairRickyGradebookStructureCommand extends Command
{
    private const SOURCE = 'ricky_legacy_completion_rule';
    private const LINK_EXERCISE = 1;
    private const LINK_STUDENT_PUBLICATION = 3;
    private const LINK_FORUM_THREAD = 5;

    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Validate every change and roll the transaction back.')
            ->addOption('course-id', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Limit processing to one or more Chamilo 2 course IDs.')
            ->addOption('category-id', null, InputOption::VALUE_REQUIRED, 'Use this gradebook category. Valid only with one --course-id.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Repair Ricky gradebook structure');

        $dryRun = (bool) $input->getOption('dry-run');
        $courseIds = array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): int => (int) $value,
            (array) $input->getOption('course-id')
        ))));
        $forcedCategoryId = (int) ($input->getOption('category-id') ?? 0);

        if ($forcedCategoryId > 0 && 1 !== count($courseIds)) {
            $io->error('--category-id requires exactly one --course-id.');

            return Command::INVALID;
        }

        $summary = [
            'selected_courses' => 0,
            'processed_courses' => 0,
            'skipped_incomplete' => 0,
            'ambiguous_categories' => 0,
            'repaired_exercise_links' => 0,
            'created_exercise_links' => 0,
            'created_work_links' => 0,
            'created_forum_links' => 0,
            'updated_weights' => 0,
            'validated_evaluations' => 0,
            'already_correct' => 0,
            'conflicts' => 0,
        ];

        try {
            $rules = $this->loadRules($courseIds);
            $summary['selected_courses'] = count($rules);

            if ([] === $rules) {
                throw new RuntimeException('No persisted Ricky completion rules matched the requested course IDs.');
            }

            $this->connection->beginTransaction();

            foreach ($rules as $row) {
                $courseId = (int) $row['course_id'];
                $courseCode = (string) $row['course_code'];
                $rule = $this->decodeRule((string) $row['field_value']);

                if (null === $rule) {
                    ++$summary['conflicts'];
                    $io->warning(sprintf('Course %d (%s) has invalid JSON configuration.', $courseId, $courseCode));

                    continue;
                }

                if (empty($rule['migration_complete'])) {
                    ++$summary['skipped_incomplete'];
                    $io->warning(sprintf('Course %d (%s) was skipped because its rule is incomplete.', $courseId, $courseCode));

                    continue;
                }

                $categoryId = $forcedCategoryId > 0
                    ? $this->validateForcedCategory($forcedCategoryId, $courseId)
                    : $this->resolveCategoryId($courseId, (array) ($rule['components'] ?? []));

                if (null === $categoryId) {
                    ++$summary['ambiguous_categories'];
                    $io->warning(sprintf('Course %d (%s) has no uniquely identifiable gradebook category.', $courseId, $courseCode));

                    continue;
                }

                $courseConflictsBefore = $summary['conflicts'];
                foreach ((array) ($rule['components'] ?? []) as $component) {
                    if (!\is_array($component)) {
                        continue;
                    }

                    $type = (string) ($component['type'] ?? '');
                    $resourceId = $this->positiveIntOrNull($component['resource_id'] ?? null);
                    $sourceId = $this->positiveIntOrNull($component['source_resource_id'] ?? null);
                    $weight = (float) ($component['weight'] ?? 0.0);

                    if (null === $resourceId || $weight <= 0.0) {
                        ++$summary['conflicts'];
                        $io->warning(sprintf(
                            'Course %d (%s) has an unusable %s component; no gradebook change was made for it.',
                            $courseId,
                            $courseCode,
                            '' !== $type ? $type : 'unknown'
                        ));

                        continue;
                    }

                    match ($type) {
                        'exercise' => $this->syncExerciseLink($courseId, $categoryId, $sourceId, $resourceId, $weight, $summary, $io),
                        'work' => $this->syncSimpleLink(self::LINK_STUDENT_PUBLICATION, 'work', 'c_student_publication', $courseId, $categoryId, $resourceId, $weight, $summary, $io),
                        'forum' => $this->syncSimpleLink(self::LINK_FORUM_THREAD, 'forum', 'c_forum_thread', $courseId, $categoryId, $resourceId, $weight, $summary, $io),
                        'evaluation' => $this->validateEvaluation($courseId, $categoryId, $resourceId, $weight, $summary, $io),
                        default => null,
                    };
                }

                if ($summary['conflicts'] === $courseConflictsBefore) {
                    ++$summary['processed_courses'];
                    $io->writeln(sprintf('Prepared: %d (%s), category %d', $courseId, $courseCode, $categoryId));
                }
            }

            if ($dryRun || $summary['conflicts'] > 0) {
                $this->connection->rollBack();
            } else {
                $this->connection->commit();
            }

            $io->definitionList(
                ['Mode' => $dryRun ? 'dry-run' : 'write'],
                ['Selected courses' => $summary['selected_courses']],
                ['Processed courses' => $summary['processed_courses']],
                ['Skipped incomplete' => $summary['skipped_incomplete']],
                ['Ambiguous categories' => $summary['ambiguous_categories']],
                ['Repaired exercise links' => $summary['repaired_exercise_links']],
                ['Created exercise links' => $summary['created_exercise_links']],
                ['Created work links' => $summary['created_work_links']],
                ['Created forum links' => $summary['created_forum_links']],
                ['Updated weights' => $summary['updated_weights']],
                ['Validated evaluations' => $summary['validated_evaluations']],
                ['Already correct' => $summary['already_correct']],
                ['Conflicts' => $summary['conflicts']]
            );

            if ($summary['conflicts'] > 0) {
                $io->error('No database changes were committed because at least one conflict was detected.');

                return Command::FAILURE;
            }

            $io->success($dryRun
                ? 'Ricky gradebook structure dry-run completed without changing data.'
                : 'Ricky gradebook structure was repaired successfully.'
            );

            return Command::SUCCESS;
        } catch (Throwable $exception) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            $io->error($exception->getMessage());

            return Command::FAILURE;
        }
    }

    /** @return list<array{course_id: int|string, course_code: string, field_value: string}> */
    private function loadRules(array $courseIds): array
    {
        $sql = <<<'SQL'
SELECT
    c.id AS course_id,
    c.code AS course_code,
    efv.field_value
FROM extra_field ef
INNER JOIN extra_field_values efv ON efv.field_id = ef.id
INNER JOIN course c ON c.id = efv.item_id
WHERE ef.item_type = :itemType
  AND ef.variable = :variable
  AND JSON_VALID(efv.field_value) = 1
  AND JSON_UNQUOTE(JSON_EXTRACT(efv.field_value, '$.source')) = :source
SQL;
        $params = [
            'itemType' => ExtraField::COURSE_FIELD_TYPE,
            'variable' => CourseCompletionRuleEvaluator::COURSE_RULE_FIELD_VARIABLE,
            'source' => self::SOURCE,
        ];
        $types = [];

        if ([] !== $courseIds) {
            $sql .= ' AND c.id IN (:courseIds)';
            $params['courseIds'] = $courseIds;
            $types['courseIds'] = \Doctrine\DBAL\ArrayParameterType::INTEGER;
        }

        $sql .= ' ORDER BY c.id';

        return $this->connection->executeQuery($sql, $params, $types)->fetchAllAssociative();
    }

    private function validateForcedCategory(int $categoryId, int $courseId): int
    {
        $matched = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM gradebook_category WHERE id = :categoryId AND c_id = :courseId',
            ['categoryId' => $categoryId, 'courseId' => $courseId]
        );
        if (1 !== $matched) {
            throw new RuntimeException(sprintf('Gradebook category %d does not belong to course %d.', $categoryId, $courseId));
        }

        return $categoryId;
    }

    /** @param list<array<string, mixed>> $components */
    private function resolveCategoryId(int $courseId, array $components): ?int
    {
        $categories = $this->connection->fetchAllAssociative(
            'SELECT id FROM gradebook_category WHERE c_id = :courseId ORDER BY id',
            ['courseId' => $courseId]
        );
        if (1 === count($categories)) {
            return (int) $categories[0]['id'];
        }
        if ([] === $categories) {
            return null;
        }

        $scores = [];
        foreach ($categories as $category) {
            $categoryId = (int) $category['id'];
            $scores[$categoryId] = 0;
            foreach ($components as $component) {
                $type = (string) ($component['type'] ?? '');
                $resourceId = $this->positiveIntOrNull($component['resource_id'] ?? null);
                $sourceId = $this->positiveIntOrNull($component['source_resource_id'] ?? null);

                if ('evaluation' === $type && null !== $resourceId) {
                    $scores[$categoryId] += (int) $this->connection->fetchOne(
                        'SELECT COUNT(*) FROM gradebook_evaluation WHERE id = :id AND category_id = :categoryId',
                        ['id' => $resourceId, 'categoryId' => $categoryId]
                    );
                    continue;
                }

                $linkType = match ($type) {
                    'exercise' => self::LINK_EXERCISE,
                    'work' => self::LINK_STUDENT_PUBLICATION,
                    'forum' => self::LINK_FORUM_THREAD,
                    default => null,
                };
                if (null === $linkType) {
                    continue;
                }

                $ids = array_values(array_unique(array_filter([$resourceId, $sourceId])));
                if ([] === $ids) {
                    continue;
                }

                $scores[$categoryId] += (int) $this->connection->fetchOne(
                    'SELECT COUNT(*) FROM gradebook_link WHERE category_id = :categoryId AND type = :type AND ref_id IN (:ids)',
                    ['categoryId' => $categoryId, 'type' => $linkType, 'ids' => $ids],
                    ['ids' => \Doctrine\DBAL\ArrayParameterType::INTEGER]
                );
            }
        }

        arsort($scores);
        $bestScore = reset($scores);
        if (false === $bestScore || (int) $bestScore <= 0) {
            return null;
        }
        $bestIds = array_keys(array_filter($scores, static fn (int $score): bool => $score === (int) $bestScore));
        if (1 === count($bestIds)) {
            return (int) $bestIds[0];
        }

        return $this->resolveCategoryByCertificateHistory(array_map('intval', $bestIds));
    }

    /** @param list<int> $categoryIds */
    private function resolveCategoryByCertificateHistory(array $categoryIds): ?int
    {
        if ([] === $categoryIds) {
            return null;
        }

        $certificateCounts = array_fill_keys($categoryIds, 0);
        $rows = $this->connection->executeQuery(
            'SELECT cat_id AS category_id, COUNT(*) AS certificate_count FROM gradebook_certificate WHERE cat_id IN (:categoryIds) GROUP BY cat_id',
            ['categoryIds' => $categoryIds],
            ['categoryIds' => \Doctrine\DBAL\ArrayParameterType::INTEGER]
        )->fetchAllAssociative();

        foreach ($rows as $row) {
            $categoryId = (int) $row['category_id'];
            if (array_key_exists($categoryId, $certificateCounts)) {
                $certificateCounts[$categoryId] = (int) $row['certificate_count'];
            }
        }

        arsort($certificateCounts);
        $bestCount = reset($certificateCounts);
        if (false === $bestCount || (int) $bestCount <= 0) {
            return null;
        }

        $bestIds = array_keys(array_filter(
            $certificateCounts,
            static fn (int $count): bool => $count === (int) $bestCount
        ));

        return 1 === count($bestIds) ? (int) $bestIds[0] : null;
    }

    private function syncExerciseLink(
        int $courseId,
        int $categoryId,
        ?int $sourceId,
        int $resourceId,
        float $weight,
        array &$summary,
        SymfonyStyle $io
    ): void {
        if (!$this->resourceBelongsToCourse('c_quiz', $resourceId, $courseId)) {
            ++$summary['conflicts'];
            $io->warning(sprintf('Exercise %d is not linked to course %d.', $resourceId, $courseId));

            return;
        }

        $ids = array_values(array_unique(array_filter([$resourceId, $sourceId])));
        $links = $this->connection->executeQuery(
            'SELECT id, ref_id, weight FROM gradebook_link WHERE category_id = :categoryId AND type = :type AND ref_id IN (:ids) ORDER BY id',
            ['categoryId' => $categoryId, 'type' => self::LINK_EXERCISE, 'ids' => $ids],
            ['ids' => \Doctrine\DBAL\ArrayParameterType::INTEGER]
        )->fetchAllAssociative();

        $targetLinks = array_values(array_filter($links, static fn (array $link): bool => (int) $link['ref_id'] === $resourceId));
        $sourceLinks = null === $sourceId || $sourceId === $resourceId
            ? []
            : array_values(array_filter($links, static fn (array $link): bool => (int) $link['ref_id'] === $sourceId));

        if (count($targetLinks) > 1 || count($sourceLinks) > 1 || ([] !== $targetLinks && [] !== $sourceLinks)) {
            ++$summary['conflicts'];
            $io->warning(sprintf('Exercise mapping %s -> %d has duplicate or conflicting gradebook links in category %d.', $sourceId ?? '-', $resourceId, $categoryId));

            return;
        }

        if (1 === count($targetLinks)) {
            $this->syncWeight((int) $targetLinks[0]['id'], (float) $targetLinks[0]['weight'], $weight, $summary);
            ++$summary['already_correct'];

            return;
        }

        if (1 === count($sourceLinks)) {
            $this->connection->update('gradebook_link', ['ref_id' => $resourceId, 'weight' => $weight], ['id' => (int) $sourceLinks[0]['id']]);
            ++$summary['repaired_exercise_links'];

            return;
        }

        $this->createLink(self::LINK_EXERCISE, $resourceId, $categoryId, $courseId, $weight);
        ++$summary['created_exercise_links'];
    }

    private function syncSimpleLink(
        int $linkType,
        string $label,
        string $resourceTable,
        int $courseId,
        int $categoryId,
        int $resourceId,
        float $weight,
        array &$summary,
        SymfonyStyle $io
    ): void {
        if (!$this->resourceBelongsToCourse($resourceTable, $resourceId, $courseId)) {
            ++$summary['conflicts'];
            $io->warning(sprintf('%s %d is not linked to course %d.', ucfirst($label), $resourceId, $courseId));

            return;
        }

        $links = $this->connection->fetchAllAssociative(
            'SELECT id, weight FROM gradebook_link WHERE category_id = :categoryId AND type = :type AND ref_id = :refId ORDER BY id',
            ['categoryId' => $categoryId, 'type' => $linkType, 'refId' => $resourceId]
        );
        if (count($links) > 1) {
            ++$summary['conflicts'];
            $io->warning(sprintf('%s %d has duplicate gradebook links in category %d.', ucfirst($label), $resourceId, $categoryId));

            return;
        }
        if (1 === count($links)) {
            $this->syncWeight((int) $links[0]['id'], (float) $links[0]['weight'], $weight, $summary);
            ++$summary['already_correct'];

            return;
        }

        $this->createLink($linkType, $resourceId, $categoryId, $courseId, $weight);
        if ('work' === $label) {
            ++$summary['created_work_links'];
        } else {
            ++$summary['created_forum_links'];
        }
    }

    private function validateEvaluation(
        int $courseId,
        int $categoryId,
        int $evaluationId,
        float $weight,
        array &$summary,
        SymfonyStyle $io
    ): void {
        $evaluation = $this->connection->fetchAssociative(
            'SELECT id, category_id, c_id, weight FROM gradebook_evaluation WHERE id = :id',
            ['id' => $evaluationId]
        );
        if (false === $evaluation || (int) $evaluation['c_id'] !== $courseId || (int) $evaluation['category_id'] !== $categoryId) {
            ++$summary['conflicts'];
            $io->warning(sprintf('Evaluation %d is not in course %d category %d.', $evaluationId, $courseId, $categoryId));

            return;
        }

        $this->syncEvaluationWeight($evaluationId, (float) $evaluation['weight'], $weight, $summary);
        ++$summary['validated_evaluations'];
    }

    private function resourceBelongsToCourse(string $table, int $resourceId, int $courseId): bool
    {
        $count = (int) $this->connection->fetchOne(
            sprintf(
                'SELECT COUNT(*) FROM %s resource INNER JOIN resource_link rl ON rl.resource_node_id = resource.resource_node_id WHERE resource.iid = :resourceId AND rl.c_id = :courseId AND rl.deleted_at IS NULL AND rl.session_id IS NULL AND rl.usergroup_id IS NULL AND rl.group_id IS NULL AND rl.user_id IS NULL',
                $table
            ),
            ['resourceId' => $resourceId, 'courseId' => $courseId]
        );

        return 1 === $count;
    }

    private function createLink(int $type, int $refId, int $categoryId, int $courseId, float $weight): void
    {
        $this->connection->insert('gradebook_link', [
            'type' => $type,
            'ref_id' => $refId,
            'category_id' => $categoryId,
            'created_at' => date('Y-m-d H:i:s'),
            'weight' => $weight,
            'visible' => 1,
            'locked' => 0,
            'c_id' => $courseId,
            'best_score' => null,
            'average_score' => null,
            'score_weight' => null,
            'user_score_list' => null,
            'min_score' => null,
        ]);
    }

    private function syncWeight(int $linkId, float $currentWeight, float $expectedWeight, array &$summary): void
    {
        if (abs($currentWeight - $expectedWeight) < 0.00001) {
            return;
        }
        $this->connection->update('gradebook_link', ['weight' => $expectedWeight], ['id' => $linkId]);
        ++$summary['updated_weights'];
    }

    private function syncEvaluationWeight(int $evaluationId, float $currentWeight, float $expectedWeight, array &$summary): void
    {
        if (abs($currentWeight - $expectedWeight) < 0.00001) {
            return;
        }
        $this->connection->update('gradebook_evaluation', ['weight' => $expectedWeight], ['id' => $evaluationId]);
        ++$summary['updated_weights'];
    }

    /** @return array<string, mixed>|null */
    private function decodeRule(string $raw): ?array
    {
        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return \is_array($decoded) ? $decoded : null;
    }

    private function positiveIntOrNull(mixed $value): ?int
    {
        $value = (int) $value;

        return $value > 0 ? $value : null;
    }
}
