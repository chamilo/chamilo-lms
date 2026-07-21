<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Gradebook;

use Chamilo\CoreBundle\Entity\ExtraField;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use JsonException;
use RuntimeException;

use const JSON_THROW_ON_ERROR;

/**
 * Read-only evaluator for configurable course-completion rules.
 *
 * Rules are stored as JSON in a hidden course extra field. This component is
 * intentionally client-agnostic: it does not contain course codes, legacy IDs
 * or customer-specific formulas.
 */
final class CourseCompletionRuleEvaluator
{
    public const COURSE_RULE_FIELD_VARIABLE = 'course_completion_rule';
    public const RULE_VERSION = 1;

    public function __construct(
        private readonly Connection $connection
    ) {}

    public function supports(int $courseId): bool
    {
        return null !== $this->getRuleForCourse($courseId);
    }

    /**
     * @return array{
     *     supported: bool,
     *     complete: bool,
     *     course_code: string,
     *     course_id: int,
     *     user_id: int,
     *     session_id: int,
     *     minimum_score: float,
     *     score: float|null,
     *     partial_score: float,
     *     finished: bool,
     *     components: list<array<string, int|float|string|null>>,
     *     errors: list<string>,
     *     warnings: list<string>
     * }
     */
    public function evaluate(
        int $userId,
        int $courseId,
        string $courseCode,
        float $minimumScore,
        int $sessionId = 0
    ): array {
        $courseCode = trim($courseCode);
        $rule = $this->getRuleForCourse($courseId);

        if (null === $rule) {
            return $this->unsupportedResult(
                $userId,
                $courseId,
                $courseCode,
                $minimumScore,
                $sessionId
            );
        }

        $errors = $this->validateRule($rule);
        $warnings = [];
        $components = [];
        $partialScore = 0.0;

        if ($minimumScore <= 0.0) {
            $warnings[] = 'The gradebook certificate minimum score is not configured.';
        }

        $errors = array_merge($errors, $this->validateResources($courseId, $rule['components']));

        $forumIds = [];
        $workIds = [];
        $evaluationIds = [];
        $exerciseTrackingIds = [];

        foreach ($rule['components'] as $component) {
            $type = (string) ($component['type'] ?? '');
            $resourceId = $this->positiveIntOrNull($component['resource_id'] ?? null);

            if ('forum' === $type && null !== $resourceId) {
                $forumIds[] = $resourceId;
            } elseif ('work' === $type && null !== $resourceId) {
                $workIds[] = $resourceId;
            } elseif ('evaluation' === $type && null !== $resourceId) {
                $evaluationIds[] = $resourceId;
            } elseif ('exercise' === $type && null !== $resourceId) {
                foreach ($this->getExerciseTrackingIds($component) as $trackingId) {
                    $exerciseTrackingIds[] = $trackingId;
                }
            }
        }

        $forumPostCounts = $this->getForumPostCounts(
            $userId,
            $courseId,
            array_values(array_unique($forumIds))
        );
        $workResults = $this->getWorkResults(
            $userId,
            $courseId,
            array_values(array_unique($workIds))
        );
        $evaluationResults = $this->getEvaluationResults(
            $userId,
            $courseId,
            array_values(array_unique($evaluationIds))
        );
        $exerciseResults = $this->getExerciseResults(
            $userId,
            $courseId,
            $sessionId,
            array_values(array_unique($exerciseTrackingIds))
        );

        foreach ($rule['components'] as $component) {
            $type = (string) ($component['type'] ?? '');
            $resourceId = $this->positiveIntOrNull($component['resource_id'] ?? null);
            $sourceResourceId = $this->positiveIntOrNull($component['source_resource_id'] ?? null);
            $displayResourceId = $sourceResourceId ?? $resourceId ?? 0;
            $weight = (float) ($component['weight'] ?? 0.0);
            $status = trim((string) ($component['status'] ?? 'configured'));

            if (null === $resourceId) {
                $components[] = [
                    'type' => $type,
                    'resource_id' => $displayResourceId,
                    'mapped_resource_id' => null,
                    'attempts' => 0,
                    'raw_score' => null,
                    'raw_max' => null,
                    'weight' => $weight,
                    'score' => null,
                    'status' => '' !== $status ? $status : 'unresolved',
                ];

                continue;
            }

            if ('forum' === $type) {
                $postCount = $forumPostCounts[$resourceId] ?? 0;
                $onePostPoints = (float) ($component['one_post_points'] ?? 0.0);
                $twoPlusPoints = (float) ($component['two_plus_points'] ?? $weight);
                $componentScore = match (true) {
                    $postCount >= 2 => $twoPlusPoints,
                    1 === $postCount => $onePostPoints,
                    default => 0.0,
                };

                $partialScore += $componentScore;
                $components[] = [
                    'type' => $type,
                    'resource_id' => $displayResourceId,
                    'mapped_resource_id' => $resourceId,
                    'attempts' => $postCount,
                    'raw_score' => null,
                    'raw_max' => null,
                    'weight' => $weight,
                    'score' => $componentScore,
                    'status' => '' !== $status ? $status : 'evaluated',
                ];

                continue;
            }

            if ('work' === $type) {
                $qualification = $workResults[$resourceId]['qualification'] ?? null;
                $componentScore = null === $qualification
                    ? 0.0
                    : ((float) $qualification * ($weight / 100.0));

                $partialScore += $componentScore;
                $components[] = [
                    'type' => $type,
                    'resource_id' => $displayResourceId,
                    'mapped_resource_id' => $resourceId,
                    'attempts' => $workResults[$resourceId]['attempts'] ?? 0,
                    'raw_score' => $qualification,
                    'raw_max' => 100.0,
                    'weight' => $weight,
                    'score' => $componentScore,
                    'status' => '' !== $status ? $status : 'evaluated',
                ];

                continue;
            }

            if ('evaluation' === $type) {
                $rawScore = $evaluationResults[$resourceId] ?? null;
                $componentScore = null === $rawScore
                    ? 0.0
                    : ((float) $rawScore * ($weight / 100.0));

                $partialScore += $componentScore;
                $components[] = [
                    'type' => $type,
                    'resource_id' => $displayResourceId,
                    'mapped_resource_id' => $resourceId,
                    'attempts' => null === $rawScore ? 0 : 1,
                    'raw_score' => $rawScore,
                    'raw_max' => 100.0,
                    'weight' => $weight,
                    'score' => $componentScore,
                    'status' => '' !== $status ? $status : 'evaluated',
                ];

                continue;
            }

            if ('exercise' === $type) {
                $bestRatio = null;
                $attempts = 0;
                foreach ($this->getExerciseTrackingIds($component) as $trackingId) {
                    if (!isset($exerciseResults[$trackingId])) {
                        continue;
                    }

                    $attempts += $exerciseResults[$trackingId]['attempts'];
                    $candidateRatio = $exerciseResults[$trackingId]['best_ratio'];
                    $bestRatio = null === $bestRatio
                        ? $candidateRatio
                        : max($bestRatio, $candidateRatio);
                }

                $componentScore = null === $bestRatio
                    ? 0.0
                    : ((float) $bestRatio * $weight);

                $partialScore += $componentScore;
                $components[] = [
                    'type' => $type,
                    'resource_id' => $displayResourceId,
                    'mapped_resource_id' => $resourceId,
                    'attempts' => $attempts,
                    'raw_score' => null === $bestRatio ? null : ((float) $bestRatio * 100.0),
                    'raw_max' => 100.0,
                    'weight' => $weight,
                    'score' => $componentScore,
                    'status' => '' !== $status ? $status : 'evaluated',
                ];
            }
        }

        $errors = array_values(array_unique($errors));
        $warnings = array_values(array_unique($warnings));
        $complete = [] === $errors;
        $score = $complete ? $partialScore : null;
        $finished = $complete && $minimumScore > 0.0 && $partialScore >= $minimumScore;

        return [
            'supported' => true,
            'complete' => $complete,
            'course_code' => $courseCode,
            'course_id' => $courseId,
            'user_id' => $userId,
            'session_id' => $sessionId,
            'minimum_score' => $minimumScore,
            'score' => $score,
            'partial_score' => $partialScore,
            'finished' => $finished,
            'components' => $components,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getRuleForCourse(int $courseId): ?array
    {
        if ($courseId <= 0) {
            return null;
        }

        $rows = $this->connection->fetchAllAssociative(
            <<<'SQL'
SELECT value.id, value.field_value
FROM extra_field field
INNER JOIN extra_field_values value
    ON value.field_id = field.id
WHERE field.item_type = :itemType
  AND field.variable = :variable
  AND value.item_id = :courseId
ORDER BY value.id
SQL,
            [
                'itemType' => ExtraField::COURSE_FIELD_TYPE,
                'variable' => self::COURSE_RULE_FIELD_VARIABLE,
                'courseId' => $courseId,
            ]
        );

        if ([] === $rows) {
            return null;
        }

        if (1 !== \count($rows)) {
            throw new RuntimeException(\sprintf('Course %d has %d completion rule values; exactly one is required.', $courseId, \count($rows)));
        }

        $rawRule = trim((string) $rows[0]['field_value']);
        if ('' === $rawRule) {
            throw new RuntimeException(\sprintf('Course %d has an empty completion rule.', $courseId));
        }

        try {
            $rule = json_decode($rawRule, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(\sprintf('Course %d has an invalid JSON completion rule.', $courseId), 0, $exception);
        }

        if (!\is_array($rule)) {
            throw new RuntimeException(\sprintf('Course %d has an invalid completion rule.', $courseId));
        }

        return $rule;
    }

    /**
     * @param array<string, mixed> $rule
     *
     * @return list<string>
     */
    private function validateRule(array $rule): array
    {
        $errors = [];

        if (self::RULE_VERSION !== (int) ($rule['version'] ?? 0)) {
            $errors[] = \sprintf(
                'Unsupported course completion rule version: %s.',
                (string) ($rule['version'] ?? 'missing')
            );
        }

        if (!isset($rule['components']) || !\is_array($rule['components'])) {
            return array_merge($errors, ['The course completion rule does not define a component list.']);
        }

        foreach ($rule['components'] as $index => $component) {
            if (!\is_array($component)) {
                $errors[] = \sprintf('Completion component %d is invalid.', $index + 1);

                continue;
            }

            $type = (string) ($component['type'] ?? '');
            if (!\in_array($type, ['forum', 'work', 'evaluation', 'exercise'], true)) {
                $errors[] = \sprintf('Completion component %d has unsupported type "%s".', $index + 1, $type);
            }

            $expectedCalculation = match ($type) {
                'forum' => 'forum_post_count_points',
                'work', 'evaluation' => 'percentage_weighted',
                'exercise' => 'best_percentage',
                default => null,
            };
            if (null !== $expectedCalculation
                && $expectedCalculation !== (string) ($component['calculation'] ?? '')
            ) {
                $errors[] = \sprintf(
                    'Completion component %d (%s) has an unsupported calculation.',
                    $index + 1,
                    $type
                );
            }

            $weight = $component['weight'] ?? null;
            if (!\is_int($weight) && !\is_float($weight)) {
                $errors[] = \sprintf('Completion component %d has an invalid weight.', $index + 1);
            } elseif ((float) $weight < 0.0) {
                $errors[] = \sprintf('Completion component %d has a negative weight.', $index + 1);
            }

            $resourceId = $this->positiveIntOrNull($component['resource_id'] ?? null);
            if (null === $resourceId) {
                $sourceId = $this->positiveIntOrNull($component['source_resource_id'] ?? null);
                $errors[] = \sprintf(
                    'Completion component %d (%s%s) has no verified resource ID.',
                    $index + 1,
                    $type,
                    null === $sourceId ? '' : \sprintf(' source %d', $sourceId)
                );
            }

            if ('forum' === $type) {
                foreach (['one_post_points', 'two_plus_points'] as $key) {
                    $value = $component[$key] ?? null;
                    if (!\is_int($value) && !\is_float($value)) {
                        $errors[] = \sprintf('Forum component %d is missing %s.', $index + 1, $key);
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * @param list<array<string, mixed>> $components
     *
     * @return list<string>
     */
    private function validateResources(int $courseId, array $components): array
    {
        $errors = [];
        $forumIds = [];
        $workIds = [];
        $evaluationIds = [];
        $exerciseIds = [];

        foreach ($components as $component) {
            if (!\is_array($component)) {
                continue;
            }

            $resourceId = $this->positiveIntOrNull($component['resource_id'] ?? null);
            if (null === $resourceId) {
                continue;
            }

            match ((string) ($component['type'] ?? '')) {
                'forum' => $forumIds[] = $resourceId,
                'work' => $workIds[] = $resourceId,
                'evaluation' => $evaluationIds[] = $resourceId,
                'exercise' => $exerciseIds[] = $resourceId,
                default => null,
            };
        }

        $forumIds = array_values(array_unique($forumIds));
        $missingForumIds = array_values(array_diff(
            $forumIds,
            $this->findLinkedResourceIds('c_forum_thread', 'iid', $courseId, $forumIds)
        ));
        if ([] !== $missingForumIds) {
            $errors[] = 'Missing linked forum thread(s): '.implode(', ', $missingForumIds).'.';
        }

        $workIds = array_values(array_unique($workIds));
        $missingWorkIds = array_values(array_diff(
            $workIds,
            $this->findLinkedResourceIds('c_student_publication', 'iid', $courseId, $workIds)
        ));
        if ([] !== $missingWorkIds) {
            $errors[] = 'Missing linked assignment(s): '.implode(', ', $missingWorkIds).'.';
        }

        $evaluationIds = array_values(array_unique($evaluationIds));
        if ([] !== $evaluationIds) {
            $resolvedEvaluationIds = array_map(
                'intval',
                $this->connection->fetchFirstColumn(
                    'SELECT id FROM gradebook_evaluation WHERE c_id = :courseId AND id IN (:ids)',
                    ['courseId' => $courseId, 'ids' => $evaluationIds],
                    ['ids' => ArrayParameterType::INTEGER]
                )
            );
            $missingEvaluationIds = array_values(array_diff($evaluationIds, $resolvedEvaluationIds));
            if ([] !== $missingEvaluationIds) {
                $errors[] = 'Missing gradebook evaluation(s): '.implode(', ', $missingEvaluationIds).'.';
            }
        }

        $exerciseIds = array_values(array_unique($exerciseIds));
        $missingExerciseIds = array_values(array_diff(
            $exerciseIds,
            $this->findLinkedResourceIds('c_quiz', 'iid', $courseId, $exerciseIds)
        ));
        if ([] !== $missingExerciseIds) {
            $errors[] = 'Missing linked exercise(s): '.implode(', ', $missingExerciseIds).'.';
        }

        return $errors;
    }

    /**
     * @param list<int> $ids
     *
     * @return list<int>
     */
    private function findLinkedResourceIds(
        string $tableName,
        string $idColumn,
        int $courseId,
        array $ids
    ): array {
        if ([] === $ids) {
            return [];
        }

        $sql = \sprintf(
            <<<'SQL'
SELECT DISTINCT entity.%s
FROM %s entity
INNER JOIN resource_link link
    ON link.resource_node_id = entity.resource_node_id
   AND link.c_id = :courseId
   AND link.deleted_at IS NULL
   AND link.session_id IS NULL
   AND link.usergroup_id IS NULL
   AND link.group_id IS NULL
   AND link.user_id IS NULL
WHERE entity.%s IN (:ids)
SQL,
            $idColumn,
            $tableName,
            $idColumn
        );

        return array_map(
            'intval',
            $this->connection->fetchFirstColumn(
                $sql,
                ['courseId' => $courseId, 'ids' => $ids],
                ['ids' => ArrayParameterType::INTEGER]
            )
        );
    }

    /**
     * @param list<int> $threadIds
     *
     * @return array<int, int>
     */
    private function getForumPostCounts(int $userId, int $courseId, array $threadIds): array
    {
        if ([] === $threadIds) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            <<<'SQL'
SELECT post.thread_id, COUNT(*) AS post_count
FROM c_forum_post post
INNER JOIN c_forum_thread thread
    ON thread.iid = post.thread_id
INNER JOIN resource_link thread_link
    ON thread_link.resource_node_id = thread.resource_node_id
   AND thread_link.c_id = :courseId
   AND thread_link.deleted_at IS NULL
   AND thread_link.session_id IS NULL
   AND thread_link.usergroup_id IS NULL
   AND thread_link.group_id IS NULL
   AND thread_link.user_id IS NULL
WHERE post.poster_id = :userId
  AND post.thread_id IN (:threadIds)
GROUP BY post.thread_id
SQL,
            ['courseId' => $courseId, 'userId' => $userId, 'threadIds' => $threadIds],
            ['threadIds' => ArrayParameterType::INTEGER]
        );

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['thread_id']] = (int) $row['post_count'];
        }

        return $counts;
    }

    /**
     * @param list<int> $workIds
     *
     * @return array<int, array{qualification: float, attempts: int}>
     */
    private function getWorkResults(int $userId, int $courseId, array $workIds): array
    {
        if ([] === $workIds) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            <<<'SQL'
SELECT submission.parent_id, submission.qualification, submission.iid
FROM c_student_publication submission
INNER JOIN c_student_publication assignment
    ON assignment.iid = submission.parent_id
INNER JOIN resource_link assignment_link
    ON assignment_link.resource_node_id = assignment.resource_node_id
   AND assignment_link.c_id = :courseId
   AND assignment_link.deleted_at IS NULL
   AND assignment_link.session_id IS NULL
   AND assignment_link.usergroup_id IS NULL
   AND assignment_link.group_id IS NULL
   AND assignment_link.user_id IS NULL
WHERE submission.user_id = :userId
  AND (
      submission.active IN (0, 1)
      OR (
          submission.active = 2
          AND submission.accepted = 1
          AND submission.date_of_qualification IS NOT NULL
      )
  )
  AND submission.parent_id IN (:workIds)
ORDER BY
    submission.parent_id,
    CASE WHEN submission.active IN (0, 1) THEN 0 ELSE 1 END,
    submission.iid
SQL,
            ['courseId' => $courseId, 'userId' => $userId, 'workIds' => $workIds],
            ['workIds' => ArrayParameterType::INTEGER]
        );

        $results = [];
        foreach ($rows as $row) {
            $workId = (int) $row['parent_id'];
            if (!isset($results[$workId])) {
                $results[$workId] = [
                    'qualification' => (float) $row['qualification'],
                    'attempts' => 0,
                ];
            }
            ++$results[$workId]['attempts'];
        }

        return $results;
    }

    /**
     * @param list<int> $evaluationIds
     *
     * @return array<int, float>
     */
    private function getEvaluationResults(int $userId, int $courseId, array $evaluationIds): array
    {
        if ([] === $evaluationIds) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            <<<'SQL'
SELECT result.evaluation_id, result.score, result.id
FROM gradebook_result result
INNER JOIN gradebook_evaluation evaluation
    ON evaluation.id = result.evaluation_id
   AND evaluation.c_id = :courseId
WHERE result.user_id = :userId
  AND result.evaluation_id IN (:evaluationIds)
ORDER BY result.evaluation_id, result.id
SQL,
            ['courseId' => $courseId, 'userId' => $userId, 'evaluationIds' => $evaluationIds],
            ['evaluationIds' => ArrayParameterType::INTEGER]
        );

        $results = [];
        foreach ($rows as $row) {
            $evaluationId = (int) $row['evaluation_id'];
            if (!\array_key_exists($evaluationId, $results) && null !== $row['score']) {
                $results[$evaluationId] = (float) $row['score'];
            }
        }

        return $results;
    }

    /**
     * @param list<int> $exerciseIds
     *
     * @return array<int, array{best_ratio: float, attempts: int}>
     */
    private function getExerciseResults(
        int $userId,
        int $courseId,
        int $sessionId,
        array $exerciseIds
    ): array {
        if ([] === $exerciseIds) {
            return [];
        }

        $sessionSql = $sessionId > 0
            ? ' AND attempt.session_id = :sessionId'
            : ' AND (attempt.session_id IS NULL OR attempt.session_id = 0)';
        $parameters = [
            'courseId' => $courseId,
            'userId' => $userId,
            'exerciseIds' => $exerciseIds,
        ];
        if ($sessionId > 0) {
            $parameters['sessionId'] = $sessionId;
        }

        $rows = $this->connection->fetchAllAssociative(
            "SELECT
                attempt.exe_exo_id,
                MAX(CASE WHEN attempt.max_score > 0 THEN attempt.score / attempt.max_score ELSE 0 END) AS best_ratio,
                COUNT(*) AS attempts
             FROM track_e_exercises attempt
             WHERE attempt.c_id = :courseId
               AND attempt.exe_user_id = :userId
               AND attempt.exe_exo_id IN (:exerciseIds)
               AND attempt.status <> 'incomplete'{$sessionSql}
             GROUP BY attempt.exe_exo_id",
            $parameters,
            ['exerciseIds' => ArrayParameterType::INTEGER]
        );

        $results = [];
        foreach ($rows as $row) {
            $results[(int) $row['exe_exo_id']] = [
                'best_ratio' => (float) $row['best_ratio'],
                'attempts' => (int) $row['attempts'],
            ];
        }

        return $results;
    }

    /**
     * @param array<string, mixed> $component
     */
    private function getExerciseTrackingIds(array $component): array
    {
        $resourceId = $this->positiveIntOrNull($component['resource_id'] ?? null);
        if (null === $resourceId) {
            return [];
        }

        $trackingIds = [$resourceId];
        $configuredTrackingIds = $component['tracking_resource_ids'] ?? [];
        if (\is_array($configuredTrackingIds)) {
            foreach ($configuredTrackingIds as $trackingId) {
                $trackingId = $this->positiveIntOrNull($trackingId);
                if (null !== $trackingId) {
                    $trackingIds[] = $trackingId;
                }
            }
        }

        return array_values(array_unique($trackingIds));
    }

    private function positiveIntOrNull(mixed $value): ?int
    {
        if (!\is_int($value) && !\is_string($value)) {
            return null;
        }

        $intValue = (int) $value;

        return $intValue > 0 ? $intValue : null;
    }

    /**
     * @return array{
     *     supported: false,
     *     complete: false,
     *     course_code: string,
     *     course_id: int,
     *     user_id: int,
     *     session_id: int,
     *     minimum_score: float,
     *     score: null,
     *     partial_score: float,
     *     finished: false,
     *     components: list<array<string, int|float|string|null>>,
     *     errors: list<string>,
     *     warnings: list<string>
     * }
     */
    private function unsupportedResult(
        int $userId,
        int $courseId,
        string $courseCode,
        float $minimumScore,
        int $sessionId
    ): array {
        return [
            'supported' => false,
            'complete' => false,
            'course_code' => $courseCode,
            'course_id' => $courseId,
            'user_id' => $userId,
            'session_id' => $sessionId,
            'minimum_score' => $minimumScore,
            'score' => null,
            'partial_score' => 0.0,
            'finished' => false,
            'components' => [],
            'errors' => [\sprintf('No course completion rule is configured for course %s.', $courseCode)],
            'warnings' => [],
        ];
    }
}
