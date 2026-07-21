<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

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

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

#[AsCommand(
    name: 'chamilo:migration:migrate-ricky-final-exam-access',
    description: 'Migrate Ricky final-exam access rules and repair verified legacy references.'
)]
final class MigrateRickyFinalExamAccessCommand extends Command
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
     * the command matches them against the migrated resource display order
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

    public function __construct(
        private readonly Connection $connection
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Runs every validation and write inside a transaction that is rolled back.'
            )
            ->addOption(
                'course-code',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Limits processing to one or more Ricky course codes.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Migrate Ricky final-exam access rules');

        $dryRun = (bool) $input->getOption('dry-run');
        $requestedCodes = array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): string => trim((string) $value),
            (array) $input->getOption('course-code')
        ))));

        $knownCodes = array_map('strval', array_keys(self::LEGACY_COURSE_RULES));
        $unknownCodes = array_values(array_diff($requestedCodes, $knownCodes));
        if ([] !== $unknownCodes) {
            $io->error('Unknown Ricky course code(s): '.implode(', ', $unknownCodes));

            return Command::INVALID;
        }

        if ($dryRun) {
            $io->warning('Dry-run enabled. All database changes will be rolled back.');
        }

        $this->connection->beginTransaction();

        try {
            $summary = $this->migrate($io, $requestedCodes);

            if ($dryRun) {
                $this->connection->rollBack();
            } else {
                $this->connection->commit();
            }
        } catch (Throwable $exception) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success($dryRun ? 'Dry-run completed.' : 'Ricky final-exam migration completed.');
        $io->definitionList(
            ['Selected legacy rules' => $summary['legacy_rules']],
            ['Configured rules' => $summary['configured']],
            ['Already configured' => $summary['already_configured']],
            ['Conflicting existing rules preserved' => $summary['conflicting_existing']],
            ['Missing courses' => $summary['missing_course']],
            ['Missing final-exam items' => $summary['missing_final_exam_item']],
            ['Ambiguous final-exam items' => $summary['ambiguous_final_exam_item']],
            ['Missing exercise targets' => $summary['missing_exercise_target']],
            ['Ambiguous exercise targets' => $summary['ambiguous_exercise_target']],
            ['Learning-path references repaired' => $summary['repaired_learning_path_items']],
            ['Tracking rows repaired' => $summary['repaired_attempt_rows']]
        );

        return Command::SUCCESS;
    }

    /**
     * @param list<string> $requestedCodes
     *
     * @return array<string, int>
     */
    private function migrate(SymfonyStyle $io, array $requestedCodes): array
    {
        $tableNames = array_map(
            static fn (string $tableName): string => strtolower($tableName),
            $this->connection->createSchemaManager()->listTableNames()
        );

        foreach (['course', 'c_lp', 'c_lp_item', 'c_quiz', 'resource_link', 'extra_field', 'extra_field_values'] as $tableName) {
            if (!\in_array(strtolower($tableName), $tableNames, true)) {
                throw new RuntimeException("Required table '{$tableName}' is missing.");
            }
        }

        if (!$this->hasRickyUserIdentifierField()) {
            throw new RuntimeException(\sprintf('Required Ricky user identifier field "%s" was not found.', self::USER_IDENTIFIER_FIELD_VARIABLE));
        }

        $hasTracking = \in_array('track_e_exercises', $tableNames, true);
        $hasGradebookLinks = \in_array('gradebook_link', $tableNames, true);
        $fieldId = $this->getOrCreateExerciseRuleField();

        $summary = [
            'legacy_rules' => 0,
            'configured' => 0,
            'already_configured' => 0,
            'conflicting_existing' => 0,
            'missing_course' => 0,
            'missing_final_exam_item' => 0,
            'ambiguous_final_exam_item' => 0,
            'missing_exercise_target' => 0,
            'ambiguous_exercise_target' => 0,
            'repaired_learning_path_items' => 0,
            'repaired_attempt_rows' => 0,
        ];

        foreach (self::LEGACY_COURSE_RULES as $courseCode => $legacyRule) {
            // PHP converts numeric-string array keys (for example, '2120') to integers.
            $courseCode = (string) $courseCode;

            if ([] !== $requestedCodes && !\in_array($courseCode, $requestedCodes, true)) {
                continue;
            }

            ++$summary['legacy_rules'];

            $courseId = (int) $this->connection->fetchOne(
                'SELECT id FROM course WHERE code = :code LIMIT 1',
                ['code' => $courseCode]
            );

            if ($courseId <= 0) {
                ++$summary['missing_course'];
                $this->writeWarning($io, 'Final-exam rule skipped: course code was not found.', [
                    'course_code' => $courseCode,
                ]);

                continue;
            }

            $finalExamItems = $this->findFinalExamItems($courseId, $hasTracking);
            $finalExamItem = $this->selectFinalExamItem($finalExamItems);
            if (null === $finalExamItem) {
                if ([] === $finalExamItems) {
                    ++$summary['missing_final_exam_item'];
                } else {
                    ++$summary['ambiguous_final_exam_item'];
                }

                $this->writeWarning($io, 'Final-exam rule skipped: learning-path item could not be resolved safely.', [
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
                    ++$summary['missing_exercise_target'];
                } else {
                    ++$summary['ambiguous_exercise_target'];
                }

                $this->writeWarning($io, 'Final-exam rule skipped: course exercise could not be resolved safely.', [
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
                $io,
                $courseId,
                $finalExamItem,
                $exerciseId,
                $hasTracking
            );
            $summary['repaired_learning_path_items'] += $referenceRepair['learning_path_items'];
            $summary['repaired_attempt_rows'] += $referenceRepair['attempt_rows'];

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
                    ++$summary['already_configured'];
                } else {
                    ++$summary['conflicting_existing'];
                    $this->writeWarning($io, 'Final-exam rule skipped: existing exercise configuration was preserved.', [
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
            ++$summary['configured'];

            $this->writeInfo($io, 'Configured Ricky final-exam access rule.', [
                'course_id' => $courseId,
                'course_code' => $courseCode,
                'learning_path_id' => (int) $finalExamItem['learning_path_id'],
                'learning_path_item_id' => (int) $finalExamItem['learning_path_item_id'],
                'exercise_id' => $exerciseId,
                'selection_strategy' => (string) ($exercise['selection_strategy'] ?? 'unknown'),
                'required_minutes' => $rule['minimum_minutes_before_exam'],
            ]);
        }

        return $summary;
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
        SymfonyStyle $io,
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

            $this->writeInfo($io, 'Repaired final-exam learning-path exercise reference.', [
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
                $this->writeInfo($io, 'Repaired migrated final-exam attempt references.', [
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
            if (!\array_key_exists($key, $existingRule) || $existingRule[$key] !== $expectedValue) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function writeInfo(SymfonyStyle $io, string $message, array $context): void
    {
        $io->writeln(\sprintf('<info>%s</info> %s', $message, $this->formatContext($context)));
    }

    /**
     * @param array<string, mixed> $context
     */
    private function writeWarning(SymfonyStyle $io, string $message, array $context): void
    {
        $io->writeln(\sprintf('<comment>%s</comment> %s', $message, $this->formatContext($context)));
    }

    /**
     * @param array<string, mixed> $context
     */
    private function formatContext(array $context): string
    {
        $encoded = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return false === $encoded ? '{}' : $encoded;
    }
}
