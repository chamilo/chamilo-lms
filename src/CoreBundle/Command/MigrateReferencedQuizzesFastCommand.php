<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;
use Throwable;

use const ENT_HTML5;
use const ENT_QUOTES;

#[AsCommand(
    name: 'chamilo:migration:repair-referenced-quizzes',
    description: 'Creates draft resource nodes for referenced legacy quizzes that still have no resource node.',
)]
final class MigrateReferencedQuizzesFastCommand extends Command
{
    private const RESOURCE_NODE_TITLE_MAX_LENGTH = 255;

    public function __construct(
        private readonly Connection $connection,
        private readonly CQuizRepository $quizRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Shows the quizzes that would be repaired and rolls back all changes.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Repair referenced legacy quizzes');

        $dryRun = (bool) $input->getOption('dry-run');
        $hasQuestionRelations = $this->tableExists('c_quiz_rel_question');
        $hasAttempts = $this->tableExists('track_e_exercises');

        if (!$hasQuestionRelations && !$hasAttempts) {
            $io->error('Neither c_quiz_rel_question nor track_e_exercises is available. No safe usage signal can be evaluated.');

            return Command::FAILURE;
        }

        $resourceTypeId = (int) $this->quizRepository->getResourceType()->getId();
        if ($resourceTypeId <= 0) {
            $io->error('Quiz resource type could not be resolved.');

            return Command::FAILURE;
        }

        $resolution = $this->resolvePendingQuizzes($hasQuestionRelations, $hasAttempts);
        $quizzesByCourse = $resolution['by_course'];
        $this->printUnresolvedQuizzes($io, $resolution['unresolved']);

        if ([] === $quizzesByCourse) {
            $io->success('No safely resolvable referenced quizzes are pending.');
            $this->printSummary($io, $resolution);

            return Command::SUCCESS;
        }

        if ($dryRun) {
            $io->warning('Dry-run enabled. Every course transaction will be rolled back.');
        }

        $uuidIsBinary = $this->detectUuidIsBinary();
        $fallbackAdminId = $this->getFallbackAdminId();
        $processedCourses = 0;
        $processedQuizzes = 0;

        foreach ($quizzesByCourse as $courseId => $quizzes) {
            $courseId = (int) $courseId;
            $courseRow = $this->connection->fetchAssociative(
                'SELECT c.id, c.resource_node_id, rn.creator_id
                 FROM course c
                 LEFT JOIN resource_node rn ON rn.id = c.resource_node_id
                 WHERE c.id = :courseId',
                ['courseId' => $courseId]
            );

            if (!$courseRow || empty($courseRow['resource_node_id'])) {
                $io->warning("Course {$courseId} has no resource node. Skipping its referenced quizzes.");

                continue;
            }

            $courseNodeId = (int) $courseRow['resource_node_id'];
            $courseNode = $this->connection->fetchAssociative(
                'SELECT id, path, level FROM resource_node WHERE id = :nodeId',
                ['nodeId' => $courseNodeId]
            );

            if (!$courseNode) {
                $io->warning("Course {$courseId} resource node {$courseNodeId} was not found.");

                continue;
            }

            $creatorId = isset($courseRow['creator_id']) && (int) $courseRow['creator_id'] > 0
                ? (int) $courseRow['creator_id']
                : $fallbackAdminId;
            $coursePath = rtrim((string) ($courseNode['path'] ?? ''), '/');
            $quizLevel = ((int) ($courseNode['level'] ?? 0)) + 1;
            $displayOrder = (int) $this->connection->fetchOne(
                'SELECT COALESCE(MAX(display_order), -1) + 1
                 FROM resource_link
                 WHERE c_id = :courseId
                   AND resource_type_group = :resourceTypeId
                   AND session_id IS NULL
                   AND usergroup_id IS NULL
                   AND group_id IS NULL
                   AND user_id IS NULL',
                [
                    'courseId' => $courseId,
                    'resourceTypeId' => $resourceTypeId,
                ]
            );

            $io->section("Course {$courseId}: ".\count($quizzes).' referenced quizzes');
            $this->connection->beginTransaction();

            try {
                foreach ($quizzes as $quizRow) {
                    $quizId = (int) $quizRow['iid'];
                    $title = $this->normalizeQuizTitle((string) ($quizRow['title'] ?? ''), $quizId);
                    $slug = 'quiz-'.$quizId;
                    $now = gmdate('Y-m-d H:i:s');
                    $uuid = Uuid::v4();
                    $uuidValue = $uuidIsBinary ? $uuid->toBinary() : $uuid->toRfc4122();

                    $resourceNodeId = $this->insertResourceNode(
                        title: $title,
                        slug: $slug,
                        level: $quizLevel,
                        createdAt: $now,
                        updatedAt: $now,
                        uuid: $uuidValue,
                        uuidIsBinary: $uuidIsBinary,
                        resourceTypeId: $resourceTypeId,
                        creatorId: $creatorId,
                        parentId: $courseNodeId
                    );

                    $this->connection->insert('resource_link', [
                        'visibility' => ResourceLink::VISIBILITY_DRAFT,
                        'start_visibility_at' => null,
                        'end_visibility_at' => null,
                        'display_order' => $displayOrder,
                        'resource_type_group' => $resourceTypeId,
                        'deleted_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'resource_node_id' => $resourceNodeId,
                        'parent_id' => null,
                        'c_id' => $courseId,
                        'session_id' => null,
                        'usergroup_id' => null,
                        'group_id' => null,
                        'user_id' => null,
                    ]);

                    $segmentTitle = preg_replace('/\s+/u', ' ', trim(str_replace(['/', '\\'], '-', $title)));
                    if (null === $segmentTitle || '' === $segmentTitle) {
                        $segmentTitle = $slug;
                    }

                    $newPath = $coursePath.'/'.$segmentTitle.'-'.$quizId.'-'.$resourceNodeId.'/';
                    $this->connection->update('resource_node', ['path' => $newPath], ['id' => $resourceNodeId]);
                    $this->connection->update('c_quiz', ['resource_node_id' => $resourceNodeId], ['iid' => $quizId]);

                    $io->writeln(\sprintf(
                        '  - Quiz %d: questions=%d, attempts=%d, course_source=%s, node=%d, visibility=draft',
                        $quizId,
                        (int) ($quizRow['question_relations'] ?? 0),
                        (int) ($quizRow['attempt_rows'] ?? 0),
                        (string) ($quizRow['course_source'] ?? 'unknown'),
                        $resourceNodeId
                    ));

                    ++$displayOrder;
                    ++$processedQuizzes;
                }

                if ($dryRun) {
                    $this->connection->rollBack();
                    $io->note("Course {$courseId}: dry-run rolled back.");
                } else {
                    $this->connection->commit();
                }

                ++$processedCourses;
            } catch (Throwable $e) {
                if ($this->connection->isTransactionActive()) {
                    $this->connection->rollBack();
                }

                $io->error("Course {$courseId}: repair failed: {$e->getMessage()}");

                return Command::FAILURE;
            }
        }

        if ($dryRun) {
            $io->success("Dry-run completed. Courses={$processedCourses}, quizzes simulated={$processedQuizzes}.");
            $this->printSummary($io, $resolution);
        } else {
            $io->success("Repair completed. Courses={$processedCourses}, quizzes repaired={$processedQuizzes}.");
            $this->printSummary($io, $this->resolvePendingQuizzes($hasQuestionRelations, $hasAttempts));
        }

        return Command::SUCCESS;
    }

    /**
     * @return array{
     *     by_course: array<int, array<int, array<string, mixed>>>,
     *     unresolved: array<int, array{quiz_id: int, reason: string}>,
     *     referenced_pending: int,
     *     resolved_pending: int,
     *     unused_pending: int
     * }
     */
    private function resolvePendingQuizzes(bool $hasQuestionRelations, bool $hasAttempts): array
    {
        $questionJoin = $hasQuestionRelations
            ? 'LEFT JOIN (
                    SELECT quiz_id, COUNT(*) AS question_relations
                    FROM c_quiz_rel_question
                    WHERE quiz_id IS NOT NULL
                    GROUP BY quiz_id
               ) question_usage ON question_usage.quiz_id = q.iid'
            : '';
        $questionSelect = $hasQuestionRelations
            ? 'COALESCE(question_usage.question_relations, 0)'
            : '0';

        $attemptJoin = $hasAttempts
            ? 'LEFT JOIN (
                    SELECT
                        exe_exo_id AS quiz_id,
                        COUNT(*) AS attempt_rows,
                        COUNT(DISTINCT c_id) AS attempt_course_count,
                        MIN(c_id) AS attempt_course_id
                    FROM track_e_exercises
                    WHERE exe_exo_id IS NOT NULL
                    GROUP BY exe_exo_id
               ) attempt_usage ON attempt_usage.quiz_id = q.iid'
            : '';
        $attemptRowsSelect = $hasAttempts ? 'COALESCE(attempt_usage.attempt_rows, 0)' : '0';
        $attemptCourseCountSelect = $hasAttempts ? 'COALESCE(attempt_usage.attempt_course_count, 0)' : '0';
        $attemptCourseIdSelect = $hasAttempts ? 'attempt_usage.attempt_course_id' : 'NULL';

        $hasCategoryCourse = $this->tableHasColumn('c_quiz_category', 'c_id');
        $categoryJoin = $hasCategoryCourse
            ? 'LEFT JOIN c_quiz_category quiz_category ON quiz_category.id = q.quiz_category_id'
            : '';
        $categoryCourseSelect = $hasCategoryCourse ? 'quiz_category.c_id' : 'NULL';

        $rows = $this->connection->fetchAllAssociative(
            "SELECT
                q.iid,
                q.title,
                {$questionSelect} AS question_relations,
                {$attemptRowsSelect} AS attempt_rows,
                {$attemptCourseCountSelect} AS attempt_course_count,
                {$attemptCourseIdSelect} AS attempt_course_id,
                {$categoryCourseSelect} AS category_course_id
             FROM c_quiz q
             {$questionJoin}
             {$attemptJoin}
             {$categoryJoin}
             WHERE q.resource_node_id IS NULL
             ORDER BY q.iid"
        );

        $byCourse = [];
        $unresolved = [];
        $referencedPending = 0;
        $resolvedPending = 0;
        $unusedPending = 0;

        foreach ($rows as $row) {
            $quizId = (int) $row['iid'];
            $questionRelations = (int) ($row['question_relations'] ?? 0);
            $attemptRows = (int) ($row['attempt_rows'] ?? 0);

            if (0 === $questionRelations && 0 === $attemptRows) {
                ++$unusedPending;

                continue;
            }

            ++$referencedPending;
            $attemptCourseCount = (int) ($row['attempt_course_count'] ?? 0);
            $attemptCourseId = (int) ($row['attempt_course_id'] ?? 0);
            $categoryCourseId = (int) ($row['category_course_id'] ?? 0);
            $courseId = 0;
            $courseSource = '';
            $reason = null;

            if ($attemptCourseCount > 1) {
                $reason = 'historical attempts reference multiple courses';
            } elseif (1 === $attemptCourseCount && $attemptCourseId > 0) {
                $courseId = $attemptCourseId;
                $courseSource = 'attempts';
            }

            if (null === $reason && $categoryCourseId > 0) {
                if ($courseId > 0 && $courseId !== $categoryCourseId) {
                    $reason = 'attempt course and quiz category course disagree';
                } elseif (0 === $courseId) {
                    $courseId = $categoryCourseId;
                    $courseSource = 'category';
                }
            }

            if (null === $reason && $courseId <= 0) {
                $reason = 'no safe course context remains after c_quiz.c_id removal';
            }

            if (null !== $reason) {
                $unresolved[] = ['quiz_id' => $quizId, 'reason' => $reason];

                continue;
            }

            $row['course_id'] = $courseId;
            $row['course_source'] = $courseSource;
            $byCourse[$courseId][] = $row;
            ++$resolvedPending;
        }

        ksort($byCourse);

        return [
            'by_course' => $byCourse,
            'unresolved' => $unresolved,
            'referenced_pending' => $referencedPending,
            'resolved_pending' => $resolvedPending,
            'unused_pending' => $unusedPending,
        ];
    }

    /**
     * @param array<int, array{quiz_id: int, reason: string}> $unresolved
     */
    private function printUnresolvedQuizzes(SymfonyStyle $io, array $unresolved): void
    {
        if ([] === $unresolved) {
            return;
        }

        $io->warning(\count($unresolved).' referenced quizzes were preserved because their course context is not safe to infer.');
        foreach (\array_slice($unresolved, 0, 20) as $row) {
            $io->writeln(\sprintf('  - Quiz %d: %s', $row['quiz_id'], $row['reason']));
        }
        if (\count($unresolved) > 20) {
            $io->writeln('  - Additional unresolved quizzes omitted from console output.');
        }
    }

    /**
     * @param array<string, mixed> $resolution
     */
    private function printSummary(SymfonyStyle $io, array $resolution): void
    {
        $io->definitionList(
            ['Referenced quizzes still pending' => (int) $resolution['referenced_pending']],
            ['Safely resolvable referenced quizzes' => (int) $resolution['resolved_pending']],
            ['Referenced quizzes with unresolved course context' => \count($resolution['unresolved'])],
            ['Unused legacy quizzes preserved without resources' => (int) $resolution['unused_pending']]
        );
    }

    private function normalizeQuizTitle(string $title, int $quizId): string
    {
        $normalized = html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalized = preg_replace('/\s+/u', ' ', trim($normalized));

        if (null === $normalized || '' === $normalized) {
            return 'Quiz '.$quizId;
        }

        $normalized = str_replace(['/', '\\'], '-', $normalized);

        if (mb_strlen($normalized) > self::RESOURCE_NODE_TITLE_MAX_LENGTH) {
            $normalized = mb_substr($normalized, 0, self::RESOURCE_NODE_TITLE_MAX_LENGTH - 3).'...';
        }

        return $normalized;
    }

    private function getFallbackAdminId(): int
    {
        $id = $this->connection->fetchOne(
            'SELECT id FROM user WHERE roles LIKE :role ORDER BY id LIMIT 1',
            ['role' => '%ROLE_ADMIN%']
        );

        return $id ? (int) $id : 1;
    }

    private function detectUuidIsBinary(): bool
    {
        try {
            $table = $this->connection->createSchemaManager()->introspectTable('resource_node');
            if (!$table->hasColumn('uuid')) {
                return false;
            }

            $column = $table->getColumn('uuid');
            $type = $column->getType()->getName();
            $length = $column->getLength();

            return \in_array($type, ['binary', 'varbinary'], true) || 16 === $length;
        } catch (Throwable) {
            return false;
        }
    }

    private function tableExists(string $tableName): bool
    {
        try {
            return \in_array($tableName, $this->connection->createSchemaManager()->listTableNames(), true);
        } catch (Throwable) {
            return false;
        }
    }

    private function tableHasColumn(string $tableName, string $columnName): bool
    {
        try {
            $schemaManager = $this->connection->createSchemaManager();
            if (!\in_array($tableName, $schemaManager->listTableNames(), true)) {
                return false;
            }

            return $schemaManager->introspectTable($tableName)->hasColumn($columnName);
        } catch (Throwable) {
            return false;
        }
    }

    private function insertResourceNode(
        string $title,
        string $slug,
        int $level,
        string $createdAt,
        string $updatedAt,
        string $uuid,
        bool $uuidIsBinary,
        int $resourceTypeId,
        int $creatorId,
        int $parentId
    ): int {
        $data = [
            'title' => $title,
            'slug' => $slug,
            'level' => $level,
            'path' => null,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
            'public' => 0,
            'uuid' => $uuid,
            'resource_type_id' => $resourceTypeId,
            'resource_format_id' => null,
            'language_id' => null,
            'creator_id' => $creatorId,
            'parent_id' => $parentId,
        ];

        $types = [];
        if ($uuidIsBinary) {
            $types['uuid'] = ParameterType::BINARY;
        }

        if ($this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform) {
            $sql = 'INSERT INTO resource_node (
                        title, slug, level, path, created_at, updated_at, public,
                        uuid, resource_type_id, resource_format_id, language_id,
                        creator_id, parent_id
                    ) VALUES (
                        :title, :slug, :level, :path, :created_at, :updated_at, :public,
                        :uuid, :resource_type_id, :resource_format_id, :language_id,
                        :creator_id, :parent_id
                    ) RETURNING id';

            return (int) $this->connection->fetchOne($sql, $data, $types);
        }

        $this->connection->insert('resource_node', $data, $types);

        return (int) $this->connection->lastInsertId();
    }
}
