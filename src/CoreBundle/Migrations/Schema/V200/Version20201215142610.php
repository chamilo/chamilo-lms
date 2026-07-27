<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizQuestionCategory;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\CourseBundle\Repository\CQuizQuestionCategoryRepository;
use Chamilo\CourseBundle\Repository\CQuizQuestionRepository;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;
use Symfony\Component\Uid\Uuid;
use Throwable;

use const ENT_HTML5;
use const ENT_QUOTES;

final class Version20201215142610 extends AbstractMigrationChamilo
{
    private const int ORM_FLUSH_BATCH_SIZE = 100;
    private const int SQL_QUESTION_BATCH_SIZE = 1000;
    private const int IMAGE_FLUSH_BATCH_SIZE = 20;
    private const int RESOURCE_NODE_TITLE_MAX_LENGTH = 255;

    public function getDescription(): string
    {
        return 'Migrate c_quiz, c_quiz_question_category, c_quiz_question';
    }

    /**
     * Questions are committed in explicit SQL batches.
     * This makes the migration resumable and avoids losing hours of work if one
     * later batch fails or the process is interrupted.
     */
    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $quizRepo = $this->container->get(CQuizRepository::class);
        $questionRepo = $this->container->get(CQuizQuestionRepository::class);
        $categoryRepo = $this->container->get(CQuizQuestionCategoryRepository::class);
        $documentRepo = $this->container->get(CDocumentRepository::class);
        $courseRepo = $this->container->get(CourseRepository::class);
        $userRepo = $this->container->get(UserRepository::class);

        $uuidIsBinary = $this->detectUuidIsBinary();
        $courseIds = $this->connection->fetchFirstColumn('SELECT id FROM course ORDER BY id');

        foreach ($courseIds as $courseIdValue) {
            $courseId = (int) $courseIdValue;
            $course = $courseRepo->find($courseId);

            if (!$course instanceof Course) {
                continue;
            }

            $courseAdminId = $this->resolveCourseAdminId($course);

            $this->migrateQuizzes(
                $courseId,
                $courseAdminId,
                $quizRepo,
                $courseRepo,
                $userRepo
            );

            $quizResourceTypeId = (int) $quizRepo->getResourceType()->getId();
            $this->migrateReferencedQuizzesWithoutItemPropertyWithDbal(
                $courseId,
                $courseAdminId,
                $quizResourceTypeId,
                $uuidIsBinary
            );

            $this->migrateQuestionCategories(
                $courseId,
                $courseAdminId,
                $categoryRepo,
                $courseRepo,
                $userRepo
            );

            $questionResourceTypeId = $questionRepo->getResourceType()->getId();
            $this->entityManager->clear();

            $this->migrateQuestionsWithDbal(
                $courseId,
                $courseAdminId,
                $questionResourceTypeId,
                $uuidIsBinary
            );

            $this->migrateQuestionImages(
                $courseId,
                $questionRepo,
                $documentRepo
            );
        }
    }

    private function migrateQuizzes(
        int $courseId,
        int $courseAdminId,
        CQuizRepository $quizRepo,
        CourseRepository $courseRepo,
        UserRepository $userRepo
    ): void {
        [$course, $courseAdmin] = $this->reloadCourseContext(
            $courseId,
            $courseAdminId,
            $courseRepo,
            $userRepo
        );

        $resourceType = $quizRepo->getResourceType();
        $itemProperties = $this->loadItemPropertiesByRef($courseId, 'quiz');
        $quizIds = $this->connection->fetchFirstColumn(
            'SELECT iid
             FROM c_quiz
             WHERE c_id = :courseId
               AND resource_node_id IS NULL
             ORDER BY iid',
            ['courseId' => $courseId]
        );

        $processed = 0;

        foreach ($quizIds as $quizIdValue) {
            $quizId = (int) $quizIdValue;
            $quiz = $quizRepo->find($quizId);

            if (!$quiz instanceof CQuiz || $quiz->hasResourceNode()) {
                continue;
            }

            $result = $this->fixItemProperty(
                'quiz',
                $quizRepo,
                $course,
                $courseAdmin,
                $quiz,
                $course,
                $itemProperties[$quizId] ?? [],
                $resourceType
            );

            if (false === $result) {
                continue;
            }

            ++$processed;

            if (0 === $processed % self::ORM_FLUSH_BATCH_SIZE) {
                $this->entityManager->flush();
                $this->entityManager->clear();

                [$course, $courseAdmin] = $this->reloadCourseContext(
                    $courseId,
                    $courseAdminId,
                    $courseRepo,
                    $userRepo
                );
                $resourceType = $quizRepo->getResourceType();
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    /**
     * Some legacy quizzes have no c_item_property row but are still referenced by
     * questions or historical attempts. They receive a draft resource link so the
     * historical data remains reachable without publishing an unknown legacy state.
     */
    private function migrateReferencedQuizzesWithoutItemPropertyWithDbal(
        int $courseId,
        int $courseAdminId,
        int $resourceTypeId,
        bool $uuidIsBinary
    ): void {
        $hasQuestionRelations = $this->tableExists('c_quiz_rel_question');
        $hasAttempts = $this->tableExists('track_e_exercises');

        if (!$hasQuestionRelations && !$hasAttempts) {
            return;
        }

        $conditions = [];
        if ($hasQuestionRelations) {
            $conditions[] = 'EXISTS (SELECT 1 FROM c_quiz_rel_question r WHERE r.quiz_id = q.iid)';
        }
        if ($hasAttempts) {
            $conditions[] = 'EXISTS (SELECT 1 FROM track_e_exercises te WHERE te.exe_exo_id = q.iid)';
        }

        $usageCondition = implode(' OR ', $conditions);
        $questionCount = $hasQuestionRelations
            ? '(SELECT COUNT(*) FROM c_quiz_rel_question r WHERE r.quiz_id = q.iid)'
            : '0';
        $attemptCount = $hasAttempts
            ? '(SELECT COUNT(*) FROM track_e_exercises te WHERE te.exe_exo_id = q.iid)'
            : '0';

        $quizzes = $this->connection->fetchAllAssociative(
            "SELECT q.iid,
                    q.title,
                    {$questionCount} AS question_relations,
                    {$attemptCount} AS attempt_rows
             FROM c_quiz q
             WHERE q.c_id = :courseId
               AND q.resource_node_id IS NULL
               AND ({$usageCondition})
             ORDER BY q.iid",
            ['courseId' => $courseId]
        );

        if ([] === $quizzes) {
            return;
        }

        $courseRow = $this->connection->fetchAssociative(
            'SELECT id, resource_node_id FROM course WHERE id = :courseId',
            ['courseId' => $courseId]
        );

        if (!$courseRow || empty($courseRow['resource_node_id'])) {
            throw new RuntimeException("Course {$courseId} has no resource node.");
        }

        $courseNodeId = (int) $courseRow['resource_node_id'];
        $courseNode = $this->connection->fetchAssociative(
            'SELECT id, path, level FROM resource_node WHERE id = :nodeId',
            ['nodeId' => $courseNodeId]
        );

        if (!$courseNode) {
            throw new RuntimeException("Course {$courseId} resource node {$courseNodeId} was not found.");
        }

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

        $this->connection->beginTransaction();

        try {
            foreach ($quizzes as $quizRow) {
                $quizId = (int) $quizRow['iid'];
                $title = $this->normalizeQuizTitle((string) ($quizRow['title'] ?? ''), $quizId);
                $slug = 'quiz-'.$quizId;
                $now = $this->nowUtc();
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
                    creatorId: $courseAdminId,
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

                ++$displayOrder;

                $this->getLogger()->info('Repaired referenced quiz without legacy item property.', [
                    'course_id' => $courseId,
                    'quiz_iid' => $quizId,
                    'question_relations' => (int) ($quizRow['question_relations'] ?? 0),
                    'attempt_rows' => (int) ($quizRow['attempt_rows'] ?? 0),
                    'resource_node_id' => $resourceNodeId,
                    'visibility' => ResourceLink::VISIBILITY_DRAFT,
                ]);
            }

            $this->connection->commit();
        } catch (Throwable $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            throw new RuntimeException("Referenced quiz repair failed for course {$courseId}: {$e->getMessage()}", 0, $e);
        }
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

    private function tableExists(string $tableName): bool
    {
        try {
            return \in_array($tableName, $this->connection->createSchemaManager()->listTableNames(), true);
        } catch (Throwable) {
            return false;
        }
    }

    private function migrateQuestionCategories(
        int $courseId,
        int $courseAdminId,
        CQuizQuestionCategoryRepository $categoryRepo,
        CourseRepository $courseRepo,
        UserRepository $userRepo
    ): void {
        [$course, $courseAdmin] = $this->reloadCourseContext(
            $courseId,
            $courseAdminId,
            $courseRepo,
            $userRepo
        );

        $resourceType = $categoryRepo->getResourceType();
        $itemProperties = $this->loadItemPropertiesByRef($courseId, 'test_category');
        $categoryIds = $this->connection->fetchFirstColumn(
            'SELECT iid
             FROM c_quiz_question_category
             WHERE c_id = :courseId
               AND resource_node_id IS NULL
             ORDER BY iid',
            ['courseId' => $courseId]
        );

        $processed = 0;

        foreach ($categoryIds as $categoryIdValue) {
            $categoryId = (int) $categoryIdValue;
            $category = $categoryRepo->find($categoryId);

            if (!$category instanceof CQuizQuestionCategory || $category->hasResourceNode()) {
                continue;
            }

            $result = $this->fixItemProperty(
                'test_category',
                $categoryRepo,
                $course,
                $courseAdmin,
                $category,
                $course,
                $itemProperties[$categoryId] ?? [],
                $resourceType
            );

            if (false === $result) {
                continue;
            }

            ++$processed;

            if (0 === $processed % self::ORM_FLUSH_BATCH_SIZE) {
                $this->entityManager->flush();
                $this->entityManager->clear();

                [$course, $courseAdmin] = $this->reloadCourseContext(
                    $courseId,
                    $courseAdminId,
                    $courseRepo,
                    $userRepo
                );
                $resourceType = $categoryRepo->getResourceType();
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    private function migrateQuestionsWithDbal(
        int $courseId,
        int $courseAdminId,
        int $resourceTypeId,
        bool $uuidIsBinary
    ): void {
        $courseRow = $this->connection->fetchAssociative(
            'SELECT id, resource_node_id FROM course WHERE id = :courseId',
            ['courseId' => $courseId]
        );

        if (!$courseRow || empty($courseRow['resource_node_id'])) {
            throw new RuntimeException("Course {$courseId} has no resource node.");
        }

        $courseNodeId = (int) $courseRow['resource_node_id'];
        $courseNode = $this->connection->fetchAssociative(
            'SELECT id, path, level FROM resource_node WHERE id = :nodeId',
            ['nodeId' => $courseNodeId]
        );

        if (!$courseNode) {
            throw new RuntimeException("Course {$courseId} resource node {$courseNodeId} was not found.");
        }

        $coursePath = rtrim((string) ($courseNode['path'] ?? ''), '/');
        $questionLevel = ((int) ($courseNode['level'] ?? 0)) + 1;
        $totalQuestions = (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
             FROM c_quiz_question
             WHERE c_id = :courseId
               AND resource_node_id IS NULL',
            ['courseId' => $courseId]
        );

        if (0 === $totalQuestions) {
            return;
        }

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

        $lastQuestionId = 0;
        $processed = 0;
        $startedAt = microtime(true);

        $this->getLogger()->info('Starting fast DBAL quiz question migration.', [
            'course_id' => $courseId,
            'pending_questions' => $totalQuestions,
            'batch_size' => self::SQL_QUESTION_BATCH_SIZE,
        ]);

        while (true) {
            $questions = $this->connection->fetchAllAssociative(
                'SELECT iid, question
                 FROM c_quiz_question
                 WHERE c_id = :courseId
                   AND resource_node_id IS NULL
                   AND iid > :lastQuestionId
                 ORDER BY iid
                 LIMIT '.self::SQL_QUESTION_BATCH_SIZE,
                [
                    'courseId' => $courseId,
                    'lastQuestionId' => $lastQuestionId,
                ]
            );

            if ([] === $questions) {
                break;
            }

            $batchProcessed = 0;
            $this->connection->beginTransaction();

            try {
                foreach ($questions as $questionRow) {
                    $questionId = (int) $questionRow['iid'];
                    $title = $this->normalizeQuestionTitle(
                        (string) ($questionRow['question'] ?? ''),
                        $questionId
                    );
                    $slug = 'question-'.$questionId;
                    $now = $this->nowUtc();
                    $uuid = Uuid::v4();
                    $uuidValue = $uuidIsBinary ? $uuid->toBinary() : $uuid->toRfc4122();

                    $resourceNodeId = $this->insertResourceNode(
                        title: $title,
                        slug: $slug,
                        level: $questionLevel,
                        createdAt: $now,
                        updatedAt: $now,
                        uuid: $uuidValue,
                        uuidIsBinary: $uuidIsBinary,
                        resourceTypeId: $resourceTypeId,
                        creatorId: $courseAdminId,
                        parentId: $courseNodeId
                    );

                    $this->connection->insert('resource_link', [
                        'visibility' => ResourceLink::VISIBILITY_PUBLISHED,
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

                    $newPath = $coursePath.'/'.$segmentTitle.'-'.$questionId.'-'.$resourceNodeId.'/';

                    $this->connection->update(
                        'resource_node',
                        ['path' => $newPath],
                        ['id' => $resourceNodeId]
                    );
                    $this->connection->update(
                        'c_quiz_question',
                        ['resource_node_id' => $resourceNodeId],
                        ['iid' => $questionId]
                    );

                    ++$displayOrder;
                    ++$batchProcessed;
                    $lastQuestionId = $questionId;
                }

                $this->connection->commit();
            } catch (Throwable $e) {
                if ($this->connection->isTransactionActive()) {
                    $this->connection->rollBack();
                }

                throw new RuntimeException("Fast question migration failed for course {$courseId} near question {$lastQuestionId}: {$e->getMessage()}", 0, $e);
            }

            $processed += $batchProcessed;
            $elapsedSeconds = max(1, (int) (microtime(true) - $startedAt));
            $rate = $processed / $elapsedSeconds;
            $remaining = max(0, $totalQuestions - $processed);
            $etaSeconds = $rate > 0 ? (int) round($remaining / $rate) : null;

            $this->getLogger()->info('Fast DBAL quiz question migration progress.', [
                'course_id' => $courseId,
                'processed' => $processed,
                'total' => $totalQuestions,
                'percent' => round(($processed / $totalQuestions) * 100, 2),
                'last_question_iid' => $lastQuestionId,
                'elapsed_seconds' => $elapsedSeconds,
                'questions_per_second' => round($rate, 2),
                'eta_seconds' => $etaSeconds,
            ]);
        }

        $this->getLogger()->info('Completed fast DBAL quiz question migration.', [
            'course_id' => $courseId,
            'processed' => $processed,
            'elapsed_seconds' => (int) (microtime(true) - $startedAt),
        ]);
    }

    private function migrateQuestionImages(
        int $courseId,
        CQuizQuestionRepository $questionRepo,
        CDocumentRepository $documentRepo
    ): void {
        $imageRows = $this->connection->fetchAllAssociative(
            'SELECT q.iid, q.picture
             FROM c_quiz_question q
             LEFT JOIN resource_file rf ON rf.resource_node_id = q.resource_node_id
             WHERE q.c_id = :courseId
               AND q.resource_node_id IS NOT NULL
               AND q.picture IS NOT NULL
               AND q.picture <> \'\'
             GROUP BY q.iid, q.picture
             HAVING COUNT(rf.id) = 0
             ORDER BY q.iid',
            ['courseId' => $courseId]
        );

        $processed = 0;
        $migrated = 0;

        foreach ($imageRows as $imageRow) {
            $pictureId = (string) ($imageRow['picture'] ?? '');
            if ('' === $pictureId || !ctype_digit($pictureId)) {
                continue;
            }

            $questionId = (int) $imageRow['iid'];
            $question = $questionRepo->find($questionId);
            $document = $documentRepo->find((int) $pictureId);

            if (!$question instanceof CQuizQuestion
                || !$document instanceof CDocument
                || !$document->hasResourceNode()
                || !$document->getResourceNode()->hasResourceFile()
            ) {
                $this->getLogger()->warning('Question image source could not be migrated.', [
                    'course_id' => $courseId,
                    'question_iid' => $questionId,
                    'document_iid' => $pictureId,
                ]);

                continue;
            }

            $resourceFile = $document->getResourceNode()->getResourceFiles()->first();
            $contents = $documentRepo->getResourceFileContent($document);
            $originalName = $resourceFile->getOriginalName() ?: 'question-'.$questionId;
            $mimeType = $resourceFile->getMimeType() ?: 'application/octet-stream';

            $migratedResourceFile = $questionRepo->addFileFromString(
                $question,
                $originalName,
                $mimeType,
                $contents,
                false
            );

            if (null !== $migratedResourceFile) {
                $migratedResourceFile->setTitle(
                    mb_substr($originalName, 0, self::RESOURCE_NODE_TITLE_MAX_LENGTH)
                );
            }

            $this->entityManager->persist($question);
            ++$processed;
            ++$migrated;

            if (0 === $processed % self::IMAGE_FLUSH_BATCH_SIZE) {
                $this->entityManager->flush();
                $this->entityManager->clear();

                $this->getLogger()->info('Quiz question image migration progress.', [
                    'course_id' => $courseId,
                    'processed' => $processed,
                    'migrated' => $migrated,
                ]);
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    private function normalizeQuestionTitle(string $question, int $questionId): string
    {
        $title = html_entity_decode(
            strip_tags($question),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );
        $title = preg_replace('/\s+/u', ' ', trim($title));

        if (null === $title || '' === $title) {
            return 'question-'.$questionId;
        }

        $title = str_replace(['/', '\\'], '-', $title);

        if (mb_strlen($title) > self::RESOURCE_NODE_TITLE_MAX_LENGTH) {
            $title = mb_substr($title, 0, self::RESOURCE_NODE_TITLE_MAX_LENGTH - 3).'...';
        }

        return $title;
    }

    /**
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function loadItemPropertiesByRef(int $courseId, string $tool): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT ref, visibility, insert_user_id, session_id, to_group_id, lastedit_date
             FROM c_item_property
             WHERE c_id = :courseId
               AND tool = :tool',
            [
                'courseId' => $courseId,
                'tool' => $tool,
            ]
        );

        $itemsByRef = [];

        foreach ($rows as $row) {
            $itemsByRef[(int) $row['ref']][] = $row;
        }

        return $itemsByRef;
    }

    private function resolveCourseAdminId(Course $course): int
    {
        foreach ($course->getTeachersSubscriptions() as $courseRelUser) {
            $user = $courseRelUser->getUser();

            if ($user instanceof User && null !== $user->getId()) {
                return (int) $user->getId();
            }
        }

        $admin = $this->getAdmin();

        if (null === $admin->getId()) {
            throw new RuntimeException('Unable to resolve a course administrator.');
        }

        return (int) $admin->getId();
    }

    /**
     * @return array{0: Course, 1: User}
     */
    private function reloadCourseContext(
        int $courseId,
        int $courseAdminId,
        CourseRepository $courseRepo,
        UserRepository $userRepo
    ): array {
        $course = $courseRepo->find($courseId);
        $courseAdmin = $userRepo->find($courseAdminId);

        if (!$course instanceof Course) {
            throw new RuntimeException("Course {$courseId} could not be reloaded.");
        }

        if (!$courseAdmin instanceof User) {
            throw new RuntimeException("User {$courseAdminId} could not be reloaded.");
        }

        return [$course, $courseAdmin];
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

    private function nowUtc(): string
    {
        return gmdate('Y-m-d H:i:s');
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
