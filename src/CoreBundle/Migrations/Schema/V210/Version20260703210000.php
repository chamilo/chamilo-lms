<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V210;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use JsonException;
use RuntimeException;

final class Version20260703210000 extends AbstractMigrationChamilo
{
    private const EXERCISE_RULE_FIELD_VARIABLE = 'final_exam_access_rule';
    private const USER_IDENTIFIER_FIELD_VARIABLE = 'fcdice_or_acadis_student_id';
    private const FINAL_EXAM_TITLE = 'Final Exam';
    private const SOURCE = 'ricky_legacy_final_exam_rule';

    /**
     * Legacy Ricky course timing values converted once into exercise configuration.
     * Runtime requests do not depend on these course codes after the migration.
     *
     * @var array<int|string, array{course_duration_minutes: int, final_exam_minutes: int}>
     */
    private const LEGACY_COURSE_RULES = [
        '2120' => ['course_duration_minutes' => 2400, 'final_exam_minutes' => 60],
        '2720' => ['course_duration_minutes' => 2400, 'final_exam_minutes' => 60],
        '2770' => ['course_duration_minutes' => 2400, 'final_exam_minutes' => 60],
        '1505' => ['course_duration_minutes' => 2400, 'final_exam_minutes' => 60],
        '1810' => ['course_duration_minutes' => 2400, 'final_exam_minutes' => 60],
        '2810' => ['course_duration_minutes' => 2400, 'final_exam_minutes' => 60],
        '2811' => ['course_duration_minutes' => 2400, 'final_exam_minutes' => 60],
        '2541' => ['course_duration_minutes' => 2400, 'final_exam_minutes' => 60],
        '1740' => ['course_duration_minutes' => 1920, 'final_exam_minutes' => 60],
        '17402021' => ['course_duration_minutes' => 1920, 'final_exam_minutes' => 60],
        '1510' => ['course_duration_minutes' => 1920, 'final_exam_minutes' => 60],
        '2521' => ['course_duration_minutes' => 1920, 'final_exam_minutes' => 60],
        '2706' => ['course_duration_minutes' => 1920, 'final_exam_minutes' => 60],
        '2741' => ['course_duration_minutes' => 720, 'final_exam_minutes' => 20],
        '27412021' => ['course_duration_minutes' => 720, 'final_exam_minutes' => 20],
        'NFPA' => ['course_duration_minutes' => 540, 'final_exam_minutes' => 120],
        '9641' => ['course_duration_minutes' => 2400, 'final_exam_minutes' => 60],
        '9516' => ['course_duration_minutes' => 2400, 'final_exam_minutes' => 60],
        '1540' => ['course_duration_minutes' => 2400, 'final_exam_minutes' => 60],
        '1302' => ['course_duration_minutes' => 1440, 'final_exam_minutes' => 60],
        '1301' => ['course_duration_minutes' => 2400, 'final_exam_minutes' => 60],
        '6742' => ['course_duration_minutes' => 2400, 'final_exam_minutes' => 60],
        '6741' => ['course_duration_minutes' => 2400, 'final_exam_minutes' => 60],
        'NFPA2018' => ['course_duration_minutes' => 540, 'final_exam_minutes' => 120],
        'COURSEDELIVERY' => ['course_duration_minutes' => 1920, 'final_exam_minutes' => 60],
        'COURSEDESIGN' => ['course_duration_minutes' => 720, 'final_exam_minutes' => 20],
        'CROWDMANAGERTRAINING' => ['course_duration_minutes' => 120, 'final_exam_minutes' => 60],
        '2111' => ['course_duration_minutes' => 2400, 'final_exam_minutes' => 60],
        'AERIALDRIVEROPERATOR' => ['course_duration_minutes' => 1920, 'final_exam_minutes' => 60],
        'NFPA2021' => ['course_duration_minutes' => 540, 'final_exam_minutes' => 120],
        'RN3842' => ['course_duration_minutes' => 480, 'final_exam_minutes' => 60],
        'NFPA2024' => ['course_duration_minutes' => 480, 'final_exam_minutes' => 240],
    ];


    /**
     * Legacy course-local exercise IDs used only to resolve ambiguous migrated
     * Final Exam resources. In Chamilo 1 these IDs were local to each course;
     * the migration matches them against the migrated resource display order
     * and never stores them in runtime configuration.
     *
     * @var array<string, int>
     */
    private const LEGACY_FINAL_EXAM_LOCAL_IDS = [
        '2120' => 7,
        '2770' => 6,
        '1510' => 4,
        '2706' => 4,
        'NFPA' => 7,
        '9641' => 29,
        '9516' => 4,
        '1302' => 30,
        '6742' => 65,
        'AERIALDRIVEROPERATOR' => 512,
    ];

    public function getDescription(): string
    {
        return 'Migrate Ricky final-exam rules and repair safely resolved final-exam references.';
    }

    public function up(Schema $schema): void
    {
        foreach (['course', 'c_lp', 'c_lp_item', 'c_quiz', 'resource_link', 'extra_field', 'extra_field_values'] as $tableName) {
            if (!$schema->hasTable($tableName)) {
                throw new RuntimeException("Required table '{$tableName}' is missing.");
            }
        }

        if (!$this->hasRickyUserIdentifierField()) {
            $this->getLogger()->info('Ricky final-exam rule migration skipped: required user identifier field not found.', [
                'field_variable' => self::USER_IDENTIFIER_FIELD_VARIABLE,
            ]);

            return;
        }

        $hasTracking = $schema->hasTable('track_e_exercises');
        $hasGradebookLinks = $schema->hasTable('gradebook_link');
        $fieldId = $this->getOrCreateExerciseRuleField();
        $removedGeneratedValues = $this->removePreviouslyGeneratedRules($fieldId);

        $configured = 0;
        $alreadyConfigured = 0;
        $conflictingExisting = 0;
        $missingCourse = 0;
        $missingFinalExamItem = 0;
        $ambiguousFinalExamItem = 0;
        $missingExerciseTarget = 0;
        $ambiguousExerciseTarget = 0;
        $repairedLearningPathItems = 0;
        $repairedAttemptRows = 0;

        foreach (self::LEGACY_COURSE_RULES as $courseCode => $legacyRule) {
            // PHP converts numeric-string array keys (for example, '2120') to integers.
            $courseCode = (string) $courseCode;

            $courseId = (int) $this->connection->fetchOne(
                'SELECT id FROM course WHERE code = :code LIMIT 1',
                ['code' => $courseCode]
            );

            if ($courseId <= 0) {
                ++$missingCourse;
                $this->getLogger()->warning('Final-exam rule skipped: course code was not found.', [
                    'course_code' => $courseCode,
                ]);

                continue;
            }

            $finalExamItems = $this->findFinalExamItems($courseId, $hasTracking);
            $finalExamItem = $this->selectFinalExamItem($finalExamItems);
            if (null === $finalExamItem) {
                if ([] === $finalExamItems) {
                    ++$missingFinalExamItem;
                } else {
                    ++$ambiguousFinalExamItem;
                }

                $this->getLogger()->warning('Final-exam rule skipped: learning-path item could not be resolved safely.', [
                    'course_id' => $courseId,
                    'course_code' => $courseCode,
                    'candidates' => \count($finalExamItems),
                ]);

                continue;
            }

            $exerciseCandidates = $this->findFinalExamExerciseCandidates($courseId, $hasGradebookLinks);
            $exercise = $this->selectFinalExamExercise(
                $courseCode,
                $courseId,
                $finalExamItem,
                $exerciseCandidates,
                $hasTracking
            );
            if (null === $exercise) {
                if ([] === $exerciseCandidates) {
                    ++$missingExerciseTarget;
                } else {
                    ++$ambiguousExerciseTarget;
                }

                $this->getLogger()->warning('Final-exam rule skipped: course exercise could not be resolved safely.', [
                    'course_id' => $courseId,
                    'course_code' => $courseCode,
                    'learning_path_id' => (int) $finalExamItem['learning_path_id'],
                    'learning_path_item_id' => (int) $finalExamItem['learning_path_item_id'],
                    'exercise_candidates' => \count($exerciseCandidates),
                ]);

                continue;
            }

            $exerciseId = (int) $exercise['exercise_id'];
            $referenceRepair = $this->repairFinalExamReferences(
                $courseId,
                $finalExamItem,
                $exerciseId,
                $hasTracking
            );
            $repairedLearningPathItems += $referenceRepair['learning_path_items'];
            $repairedAttemptRows += $referenceRepair['attempt_rows'];

            $courseDurationMinutes = $legacyRule['course_duration_minutes'];
            $finalExamMinutes = $legacyRule['final_exam_minutes'];
            $rule = [
                'course_duration_minutes' => $courseDurationMinutes,
                'final_exam_minutes' => $finalExamMinutes,
                'minimum_minutes_before_exam' => max(0, $courseDurationMinutes - $finalExamMinutes),
                'user_identifier_field_variable' => self::USER_IDENTIFIER_FIELD_VARIABLE,
                'allow_user_identifier_opt_out' => 'CROWDMANAGERTRAINING' === $courseCode,
                'user_identifier_opt_out_prompt' => 'CROWDMANAGERTRAINING' === $courseCode
                    ? 'Would you like your course completion submitted to the Florida Bureau of Fire Standards and Training?'
                    : '',
                'source' => self::SOURCE,
            ];
            $encodedRule = $this->encodeRule($rule);

            $existingValues = $this->connection->fetchAllAssociative(
                'SELECT id, field_value
                 FROM extra_field_values
                 WHERE field_id = :fieldId AND item_id = :exerciseId
                 ORDER BY id',
                ['fieldId' => $fieldId, 'exerciseId' => $exerciseId]
            );

            if ([] !== $existingValues) {
                if (1 === \count($existingValues) && $this->rulesMatch((string) $existingValues[0]['field_value'], $rule)) {
                    ++$alreadyConfigured;
                } else {
                    ++$conflictingExisting;
                    $this->getLogger()->warning('Final-exam rule skipped: existing exercise configuration was preserved.', [
                        'course_id' => $courseId,
                        'course_code' => $courseCode,
                        'learning_path_id' => (int) $finalExamItem['learning_path_id'],
                        'learning_path_item_id' => (int) $finalExamItem['learning_path_item_id'],
                        'exercise_id' => $exerciseId,
                        'existing_values' => \count($existingValues),
                    ]);
                }

                continue;
            }

            $now = date('Y-m-d H:i:s');
            $this->connection->insert('extra_field_values', [
                'field_id' => $fieldId,
                'field_value' => $encodedRule,
                'item_id' => $exerciseId,
                'created_at' => $now,
                'updated_at' => $now,
                'comment' => null,
                'asset_id' => null,
            ]);
            ++$configured;

            $this->getLogger()->info('Configured Ricky final-exam access rule.', [
                'course_id' => $courseId,
                'course_code' => $courseCode,
                'learning_path_id' => (int) $finalExamItem['learning_path_id'],
                'learning_path_item_id' => (int) $finalExamItem['learning_path_item_id'],
                'exercise_id' => $exerciseId,
                'selection_strategy' => (string) ($exercise['selection_strategy'] ?? 'unknown'),
                'required_minutes' => $rule['minimum_minutes_before_exam'],
            ]);
        }

        $this->getLogger()->info('Completed Ricky final-exam access rule migration.', [
            'legacy_rules' => \count(self::LEGACY_COURSE_RULES),
            'removed_previous_generated_values' => $removedGeneratedValues,
            'configured' => $configured,
            'already_configured' => $alreadyConfigured,
            'conflicting_existing' => $conflictingExisting,
            'missing_course' => $missingCourse,
            'missing_final_exam_item' => $missingFinalExamItem,
            'ambiguous_final_exam_item' => $ambiguousFinalExamItem,
            'missing_exercise_target' => $missingExerciseTarget,
            'ambiguous_exercise_target' => $ambiguousExerciseTarget,
            'repaired_learning_path_items' => $repairedLearningPathItems,
            'repaired_attempt_rows' => $repairedAttemptRows,
        ]);
    }

    public function down(Schema $schema): void
    {
        // Intentionally left empty.
        // Removing these values would disable migrated legal final-exam access controls.
    }

    private function hasRickyUserIdentifierField(): bool
    {
        return 1 === (int) $this->connection->fetchOne(
            'SELECT COUNT(1)
             FROM extra_field
             WHERE item_type = :itemType AND variable = :variable',
            [
                'itemType' => ExtraField::USER_FIELD_TYPE,
                'variable' => self::USER_IDENTIFIER_FIELD_VARIABLE,
            ]
        );
    }

    private function getOrCreateExerciseRuleField(): int
    {
        $fieldId = $this->connection->fetchOne(
            'SELECT id
             FROM extra_field
             WHERE item_type = :itemType AND variable = :variable
             LIMIT 1',
            [
                'itemType' => ExtraField::EXERCISE_FIELD_TYPE,
                'variable' => self::EXERCISE_RULE_FIELD_VARIABLE,
            ]
        );

        if (false !== $fieldId && (int) $fieldId > 0) {
            return (int) $fieldId;
        }

        $fieldOrder = (int) $this->connection->fetchOne(
            'SELECT COALESCE(MAX(field_order), 0) + 1 FROM extra_field WHERE item_type = :itemType',
            ['itemType' => ExtraField::EXERCISE_FIELD_TYPE]
        );
        $this->connection->insert('extra_field', [
            'item_type' => ExtraField::EXERCISE_FIELD_TYPE,
            'value_type' => ExtraField::FIELD_TYPE_TEXTAREA,
            'variable' => self::EXERCISE_RULE_FIELD_VARIABLE,
            'display_text' => 'Final exam access rule',
            'helper_text' => null,
            'default_value' => null,
            'field_order' => $fieldOrder,
            'visible_to_self' => 0,
            'visible_to_others' => 0,
            'changeable' => 0,
            'filter' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'auto_remove' => 0,
            'description' => 'JSON configuration for legal final-exam timing and user identifier requirements.',
        ]);

        return (int) $this->connection->lastInsertId();
    }

    private function removePreviouslyGeneratedRules(int $fieldId): int
    {
        $removed = 0;
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, field_value FROM extra_field_values WHERE field_id = :fieldId ORDER BY id',
            ['fieldId' => $fieldId]
        );

        foreach ($rows as $row) {
            try {
                $value = json_decode((string) $row['field_value'], true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                continue;
            }

            if (!\is_array($value) || self::SOURCE !== ($value['source'] ?? null)) {
                continue;
            }

            $removed += $this->connection->delete('extra_field_values', ['id' => (int) $row['id']]);
        }

        return $removed;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function findFinalExamItems(int $courseId, bool $hasTracking): array
    {
        $trackingCondition = $hasTracking
            ? ' OR EXISTS (
                    SELECT 1
                    FROM track_e_exercises tracked
                    WHERE tracked.c_id = :trackingCourseId
                      AND tracked.orig_lp_id = lp.iid
                      AND tracked.orig_lp_item_id = lpi.iid
               )'
            : '';

        $parameters = [
            'linkedCourseId' => $courseId,
            'finalExamTitle' => self::FINAL_EXAM_TITLE,
        ];
        if ($hasTracking) {
            $parameters['trackingCourseId'] = $courseId;
        }

        return $this->connection->fetchAllAssociative(
            "SELECT DISTINCT
                 lp.iid AS learning_path_id,
                 lp.title AS learning_path_title,
                 lpi.iid AS learning_path_item_id,
                 lpi.title AS learning_path_item_title,
                 lpi.path AS current_path,
                 lpi.display_order
             FROM c_lp lp
             INNER JOIN c_lp_item lpi
                 ON lpi.lp_id = lp.iid
                AND lpi.item_type = 'quiz'
             LEFT JOIN resource_link lp_link
                 ON lp_link.resource_node_id = lp.resource_node_id
                AND lp_link.c_id = :linkedCourseId
                AND lp_link.deleted_at IS NULL
                AND lp_link.session_id IS NULL
                AND lp_link.usergroup_id IS NULL
                AND lp_link.group_id IS NULL
                AND lp_link.user_id IS NULL
             WHERE (
                    LOWER(TRIM(lp.title)) = LOWER(:finalExamTitle)
                 OR LOWER(TRIM(lpi.title)) = LOWER(:finalExamTitle)
             )
               AND (lp_link.resource_node_id IS NOT NULL{$trackingCondition})
             ORDER BY lp.iid, lpi.display_order, lpi.iid",
            $parameters
        );
    }

    /**
     * @param list<array<string, mixed>> $items
     *
     * @return array<string, mixed>|null
     */
    private function selectFinalExamItem(array $items): ?array
    {
        $bothExact = array_values(array_filter(
            $items,
            static fn (array $item): bool => 0 === strcasecmp(
                self::FINAL_EXAM_TITLE,
                trim((string) $item['learning_path_title'])
            ) && 0 === strcasecmp(
                self::FINAL_EXAM_TITLE,
                trim((string) $item['learning_path_item_title'])
            )
        ));
        if (1 === \count($bothExact)) {
            return $bothExact[0];
        }

        $itemExact = array_values(array_filter(
            $items,
            static fn (array $item): bool => 0 === strcasecmp(
                self::FINAL_EXAM_TITLE,
                trim((string) $item['learning_path_item_title'])
            )
        ));
        if (1 === \count($itemExact)) {
            return $itemExact[0];
        }

        $learningPathExact = array_values(array_filter(
            $items,
            static fn (array $item): bool => 0 === strcasecmp(
                self::FINAL_EXAM_TITLE,
                trim((string) $item['learning_path_title'])
            )
        ));
        if (1 === \count($learningPathExact)) {
            return $learningPathExact[0];
        }

        return 1 === \count($items) ? $items[0] : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function findFinalExamExerciseCandidates(int $courseId, bool $hasGradebookLinks): array
    {
        $gradebookLinkCount = $hasGradebookLinks
            ? '(SELECT COUNT(*)
                 FROM gradebook_link gradebook
                 WHERE gradebook.c_id = :gradebookCourseId
                   AND gradebook.ref_id = q.iid)'
            : '0';
        $parameters = [
            'courseId' => $courseId,
            'finalExamTitle' => self::FINAL_EXAM_TITLE,
        ];
        if ($hasGradebookLinks) {
            $parameters['gradebookCourseId'] = $courseId;
        }

        return $this->connection->fetchAllAssociative(
            "SELECT DISTINCT
                 q.iid AS exercise_id,
                 q.title AS exercise_title,
                 q.max_attempt,
                 q.expired_time,
                 quiz_link.display_order,
                 {$gradebookLinkCount} AS gradebook_links
             FROM c_quiz q
             INNER JOIN resource_link quiz_link
                 ON quiz_link.resource_node_id = q.resource_node_id
                AND quiz_link.c_id = :courseId
                AND quiz_link.deleted_at IS NULL
                AND quiz_link.session_id IS NULL
                AND quiz_link.usergroup_id IS NULL
                AND quiz_link.group_id IS NULL
                AND quiz_link.user_id IS NULL
             WHERE LOWER(TRIM(q.title)) = LOWER(:finalExamTitle)
             ORDER BY q.iid",
            $parameters
        );
    }

    /**
     * @param array<string, mixed>       $finalExamItem
     * @param list<array<string, mixed>> $exerciseCandidates
     *
     * @return array<string, mixed>|null
     */
    private function selectFinalExamExercise(
        string $courseCode,
        int $courseId,
        array $finalExamItem,
        array $exerciseCandidates,
        bool $hasTracking
    ): ?array {
        if ([] === $exerciseCandidates) {
            return null;
        }

        $currentPath = trim((string) $finalExamItem['current_path']);
        if (ctype_digit($currentPath)) {
            $currentExerciseId = (int) $currentPath;
            $currentMatches = array_values(array_filter(
                $exerciseCandidates,
                static fn (array $candidate): bool => $currentExerciseId === (int) $candidate['exercise_id']
            ));
            if (1 === \count($currentMatches)) {
                return $this->withSelectionStrategy($currentMatches[0], 'current_path');
            }
        }

        if ($hasTracking) {
            $attemptTargets = $this->connection->fetchAllAssociative(
                'SELECT exe_exo_id AS exercise_id, COUNT(*) AS attempt_rows
                 FROM track_e_exercises
                 WHERE c_id = :courseId
                   AND orig_lp_id = :learningPathId
                   AND orig_lp_item_id = :learningPathItemId
                   AND exe_exo_id IS NOT NULL
                 GROUP BY exe_exo_id
                 ORDER BY attempt_rows DESC, exe_exo_id',
                [
                    'courseId' => $courseId,
                    'learningPathId' => (int) $finalExamItem['learning_path_id'],
                    'learningPathItemId' => (int) $finalExamItem['learning_path_item_id'],
                ]
            );
            $candidateIds = array_map(
                static fn (array $candidate): int => (int) $candidate['exercise_id'],
                $exerciseCandidates
            );
            $matchingAttemptTargets = array_values(array_filter(
                $attemptTargets,
                static fn (array $target): bool => \in_array((int) $target['exercise_id'], $candidateIds, true)
            ));

            if (1 === \count($matchingAttemptTargets)) {
                $targetId = (int) $matchingAttemptTargets[0]['exercise_id'];

                foreach ($exerciseCandidates as $candidate) {
                    if ($targetId === (int) $candidate['exercise_id']) {
                        return $this->withSelectionStrategy($candidate, 'tracking_target');
                    }
                }
            }
        }

        $legacyLocalExerciseId = self::LEGACY_FINAL_EXAM_LOCAL_IDS[$courseCode] ?? null;
        if (null !== $legacyLocalExerciseId) {
            $expectedDisplayOrder = $legacyLocalExerciseId - 1;
            $displayOrderMatches = array_values(array_filter(
                $exerciseCandidates,
                static fn (array $candidate): bool => $expectedDisplayOrder === (int) $candidate['display_order']
            ));
            if (1 === \count($displayOrderMatches)) {
                return $this->withSelectionStrategy($displayOrderMatches[0], 'legacy_display_order');
            }
        }

        $gradebookMatches = array_values(array_filter(
            $exerciseCandidates,
            static fn (array $candidate): bool => (int) $candidate['gradebook_links'] > 0
        ));
        if (1 === \count($gradebookMatches)) {
            return $this->withSelectionStrategy($gradebookMatches[0], 'unique_gradebook_link');
        }

        $operationalMatches = array_values(array_filter(
            $exerciseCandidates,
            static fn (array $candidate): bool => (int) $candidate['max_attempt'] > 0
                && (int) $candidate['expired_time'] > 0
        ));
        if (1 === \count($operationalMatches)) {
            return $this->withSelectionStrategy($operationalMatches[0], 'unique_operational_candidate');
        }

        return 1 === \count($exerciseCandidates)
            ? $this->withSelectionStrategy($exerciseCandidates[0], 'single_candidate')
            : null;
    }

    /**
     * @param array<string, mixed> $candidate
     *
     * @return array<string, mixed>
     */
    private function withSelectionStrategy(array $candidate, string $strategy): array
    {
        $candidate['selection_strategy'] = $strategy;

        return $candidate;
    }

    /**
     * @param array<string, mixed> $finalExamItem
     *
     * @return array{learning_path_items: int, attempt_rows: int}
     */
    private function repairFinalExamReferences(
        int $courseId,
        array $finalExamItem,
        int $exerciseId,
        bool $hasTracking
    ): array {
        $learningPathItemRepairs = 0;
        $currentPath = trim((string) $finalExamItem['current_path']);
        if ((string) $exerciseId !== $currentPath) {
            $learningPathItemRepairs = $this->connection->update(
                'c_lp_item',
                ['path' => (string) $exerciseId],
                ['iid' => (int) $finalExamItem['learning_path_item_id']]
            );

            $this->getLogger()->info('Repaired final-exam learning-path exercise reference.', [
                'course_id' => $courseId,
                'learning_path_id' => (int) $finalExamItem['learning_path_id'],
                'learning_path_item_id' => (int) $finalExamItem['learning_path_item_id'],
                'old_path' => $currentPath,
                'new_exercise_id' => $exerciseId,
            ]);
        }

        $attemptRepairs = 0;
        if ($hasTracking) {
            $attemptRepairs = $this->connection->executeStatement(
                'UPDATE track_e_exercises
                 SET exe_exo_id = :exerciseId
                 WHERE c_id = :courseId
                   AND orig_lp_id = :learningPathId
                   AND orig_lp_item_id = :learningPathItemId
                   AND (exe_exo_id IS NULL OR exe_exo_id <> :comparisonExerciseId)',
                [
                    'exerciseId' => $exerciseId,
                    'comparisonExerciseId' => $exerciseId,
                    'courseId' => $courseId,
                    'learningPathId' => (int) $finalExamItem['learning_path_id'],
                    'learningPathItemId' => (int) $finalExamItem['learning_path_item_id'],
                ]
            );

            if ($attemptRepairs > 0) {
                $this->getLogger()->info('Repaired migrated final-exam attempt references.', [
                    'course_id' => $courseId,
                    'learning_path_id' => (int) $finalExamItem['learning_path_id'],
                    'learning_path_item_id' => (int) $finalExamItem['learning_path_item_id'],
                    'exercise_id' => $exerciseId,
                    'rows' => $attemptRepairs,
                ]);
            }
        }

        return [
            'learning_path_items' => $learningPathItemRepairs,
            'attempt_rows' => $attemptRepairs,
        ];
    }

    /**
     * @param array<string, mixed> $rule
     */
    private function encodeRule(array $rule): string
    {
        try {
            return json_encode($rule, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Unable to encode a final-exam access rule.', 0, $exception);
        }
    }

    /**
     * @param array<string, mixed> $expectedRule
     */
    private function rulesMatch(string $existingValue, array $expectedRule): bool
    {
        try {
            $existingRule = json_decode($existingValue, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return false;
        }

        if (!\is_array($existingRule)) {
            return false;
        }

        foreach ($expectedRule as $key => $expectedValue) {
            if (!array_key_exists($key, $existingRule) || $existingRule[$key] !== $expectedValue) {
                return false;
            }
        }

        return true;
    }
}
