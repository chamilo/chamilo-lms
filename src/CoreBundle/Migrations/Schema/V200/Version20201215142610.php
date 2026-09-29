<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizQuestionCategory;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\CourseBundle\Repository\CQuizQuestionCategoryRepository;
use Chamilo\CourseBundle\Repository\CQuizQuestionRepository;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use DateTime;
use DateTimeZone;
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
    private const int SQL_QUIZ_BATCH_SIZE = 500;
    private const int SQL_QUESTION_BATCH_SIZE = 1000;
    private const int IMAGE_FLUSH_BATCH_SIZE = 20;
    private const int RESOURCE_NODE_TITLE_MAX_LENGTH = 255;
    private const string ITEM_PROPERTY_INDEX = 'idx_legacy_migration_item_property_tool_ref_course';
    private const string TRACK_EXERCISE_QUIZ_INDEX = 'idx_legacy_migration_track_exercise_course_quiz';

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
        $this->ensureItemPropertyMigrationIndex();
        $this->ensureTrackExerciseQuizMigrationIndex();

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

            $quizResourceTypeId = (int) $quizRepo->getResourceType()->getId();
            $this->entityManager->clear();

            $this->migrateQuizzesWithDbal(
                $courseId,
                $courseAdminId,
                $quizResourceTypeId,
                $uuidIsBinary
            );

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

    /**
     * Migrate quizzes without hydrating CQuiz/ResourceNode graphs.
     *
     * ResourceRepository::createNodeForResource() links every new node into the
     * managed parent/creator collections. On large courses that makes Doctrine
     * walk very large object graphs during flush and can exhaust memory even
     * when the EntityManager is cleared between batches.
     *
     * This follows the DBAL resource-node pattern already used below for quiz
     * questions and referenced quizzes, while preserving legacy item-property
     * creator, visibility, session/group context and deletion semantics.
     */
    private function migrateQuizzesWithDbal(
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
        $quizLevel = ((int) ($courseNode['level'] ?? 0)) + 1;
        $itemProperties = $this->loadItemPropertiesByRef($courseId, 'quiz');
        $quizRows = $this->connection->fetchAllAssociative(
            'SELECT iid, title
             FROM c_quiz
             WHERE c_id = :courseId
               AND resource_node_id IS NULL
             ORDER BY iid',
            ['courseId' => $courseId]
        );

        if ([] === $quizRows) {
            return;
        }

        $displayOrderByContext = [];
        $sessionExists = [];
        $groupExists = [];
        $userExists = [];

        foreach (array_chunk($quizRows, self::SQL_QUIZ_BATCH_SIZE) as $rowChunk) {
            $this->connection->beginTransaction();

            try {
                foreach ($rowChunk as $quizRow) {
                    $quizId = (int) $quizRow['iid'];
                    $quizItems = $itemProperties[$quizId] ?? [];

                    if ([] === $quizItems) {
                        // Preserve the legacy behavior: quizzes without
                        // c_item_property are skipped here. Referenced quizzes
                        // are repaired by migrateReferencedQuizzes... below.
                        $this->getLogger()->warning('Missing c_item_property for quiz; resource skipped.', [
                            'course_id' => $courseId,
                            'quiz_iid' => $quizId,
                        ]);

                        continue;
                    }

                    $creatorId = (int) ($quizItems[0]['insert_user_id'] ?? 0);
                    if (!$this->legacyUserExists($creatorId, $userExists)) {
                        $creatorId = $courseAdminId;
                    }

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
                        creatorId: $creatorId,
                        parentId: $courseNodeId
                    );

                    $segmentTitle = preg_replace('/\s+/u', ' ', trim(str_replace(['/', '\\'], '-', $title)));
                    if (null === $segmentTitle || '' === $segmentTitle) {
                        $segmentTitle = $slug;
                    }

                    $newPath = $coursePath.'/'.$segmentTitle.'-'.$quizId.'-'.$resourceNodeId.'/';

                    $this->connection->update(
                        'resource_node',
                        ['path' => $newPath],
                        ['id' => $resourceNodeId]
                    );
                    $this->connection->update(
                        'c_quiz',
                        ['resource_node_id' => $resourceNodeId],
                        ['iid' => $quizId]
                    );

                    $this->insertQuizResourceLinksWithDbal(
                        courseId: $courseId,
                        resourceNodeId: $resourceNodeId,
                        resourceTypeId: $resourceTypeId,
                        items: $quizItems,
                        displayOrderByContext: $displayOrderByContext,
                        sessionExists: $sessionExists,
                        groupExists: $groupExists
                    );
                }

                $this->connection->commit();
            } catch (Throwable $e) {
                if ($this->connection->isTransactionActive()) {
                    $this->connection->rollBack();
                }

                throw new RuntimeException("Fast quiz resource migration failed for course {$courseId}: {$e->getMessage()}", 0, $e);
            }
        }
    }

    /**
     * @param array<int, bool> $cache
     */
    private function legacyUserExists(int $userId, array &$cache): bool
    {
        if ($userId <= 0) {
            return false;
        }

        if (!\array_key_exists($userId, $cache)) {
            $cache[$userId] = (bool) $this->connection->fetchOne(
                'SELECT 1 FROM user WHERE id = :id LIMIT 1',
                ['id' => $userId]
            );
        }

        return $cache[$userId];
    }

    /**
     * Reproduces AbstractResource::addCourseLink() / fixItemProperty() semantics
     * without adding thousands of ResourceLink and ResourceRight entities to the
     * Doctrine UnitOfWork.
     *
     * @param array<int, array<string, mixed>> $items
     * @param array<string, int>               $displayOrderByContext
     * @param array<int, bool>                 $sessionExists
     * @param array<int, bool>                 $groupExists
     */
    private function insertQuizResourceLinksWithDbal(
        int $courseId,
        int $resourceNodeId,
        int $resourceTypeId,
        array $items,
        array &$displayOrderByContext,
        array &$sessionExists,
        array &$groupExists
    ): void {
        /** @var array<string, int> $linksByContext */
        $linksByContext = [];
        $editorMask = ResourceNodeVoter::getEditorMask();

        foreach ($items as $item) {
            $legacyVisibility = (int) ($item['visibility'] ?? 0);
            $visibility = 1 === $legacyVisibility
                ? ResourceLink::VISIBILITY_PUBLISHED
                : ResourceLink::VISIBILITY_DRAFT;

            $sessionId = $this->resolveExistingLegacyContextId(
                'session',
                Session::class,
                (int) ($item['session_id'] ?? 0),
                $sessionExists
            );
            $groupId = $this->resolveExistingLegacyContextId(
                'c_group',
                CGroup::class,
                (int) ($item['to_group_id'] ?? 0),
                $groupExists
            );

            $contextKey = ($sessionId ?? 0).':'.($groupId ?? 0);

            if (isset($linksByContext[$contextKey])) {
                // addCourseLink() keeps the first link for an identical
                // course/session/group context. Legacy visibility=2 still
                // marks that existing link as deleted.
                if (2 === $legacyVisibility) {
                    $this->connection->update(
                        'resource_link',
                        ['deleted_at' => $this->normalizeLegacyDeletedAt((string) ($item['lastedit_date'] ?? ''))],
                        ['id' => $linksByContext[$contextKey]]
                    );
                }

                continue;
            }

            $displayOrder = $this->nextQuizResourceLinkDisplayOrder(
                $courseId,
                $resourceTypeId,
                $sessionId,
                $groupId,
                $displayOrderByContext
            );
            $now = $this->nowUtc();
            $deletedAt = 2 === $legacyVisibility
                ? $this->normalizeLegacyDeletedAt((string) ($item['lastedit_date'] ?? ''))
                : null;

            $resourceLinkId = $this->insertResourceLink(
                [
                    'visibility' => $visibility,
                    'start_visibility_at' => null,
                    'end_visibility_at' => null,
                    'display_order' => $displayOrder,
                    'resource_type_group' => $resourceTypeId,
                    'deleted_at' => $deletedAt,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'resource_node_id' => $resourceNodeId,
                    'parent_id' => null,
                    'c_id' => $courseId,
                    'session_id' => $sessionId,
                    'usergroup_id' => null,
                    'group_id' => $groupId,
                    'user_id' => null,
                ]
            );

            $linksByContext[$contextKey] = $resourceLinkId;

            if (ResourceLink::VISIBILITY_PUBLISHED !== $visibility) {
                $this->connection->insert('resource_right', [
                    'resource_link_id' => $resourceLinkId,
                    'role' => ResourceNodeVoter::ROLE_CURRENT_COURSE_TEACHER,
                    'mask' => $editorMask,
                ]);
            }
        }
    }

    /**
     * @param array<int, bool> $cache
     */
    private function resolveExistingLegacyContextId(
        string $table,
        string $entityClass,
        int $id,
        array &$cache
    ): ?int {
        if ($id <= 0) {
            return null;
        }

        if (!\array_key_exists($id, $cache)) {
            try {
                // Keep the same fast lookup convention used by
                // AbstractMigrationChamilo::fixItemProperty().
                $cache[$id] = (bool) $this->connection->fetchOne(
                    "SELECT 1 FROM {$table} WHERE id = :id LIMIT 1",
                    ['id' => $id]
                );
            } catch (Throwable) {
                // Preserve fixItemProperty() fallback behavior when the legacy
                // fast table name does not match the current ORM mapping
                // (notably groups in current Chamilo).
                $cache[$id] = null !== $this->entityManager->find($entityClass, $id);
            }
        }

        return $cache[$id] ? $id : null;
    }

    /**
     * @param array<string, int> $displayOrderByContext
     */
    private function nextQuizResourceLinkDisplayOrder(
        int $courseId,
        int $resourceTypeId,
        ?int $sessionId,
        ?int $groupId,
        array &$displayOrderByContext
    ): int {
        $contextKey = ($sessionId ?? 0).':'.($groupId ?? 0);

        if (!isset($displayOrderByContext[$contextKey])) {
            $sql = 'SELECT COALESCE(MAX(display_order), -1) + 1
                    FROM resource_link
                    WHERE c_id = :courseId
                      AND resource_type_group = :resourceTypeId
                      AND usergroup_id IS NULL
                      AND user_id IS NULL';
            $parameters = [
                'courseId' => $courseId,
                'resourceTypeId' => $resourceTypeId,
            ];

            if (null === $sessionId) {
                $sql .= ' AND session_id IS NULL';
            } else {
                $sql .= ' AND session_id = :sessionId';
                $parameters['sessionId'] = $sessionId;
            }

            if (null === $groupId) {
                $sql .= ' AND group_id IS NULL';
            } else {
                $sql .= ' AND group_id = :groupId';
                $parameters['groupId'] = $groupId;
            }

            $displayOrderByContext[$contextKey] = (int) $this->connection->fetchOne(
                $sql,
                $parameters
            );
        }

        return $displayOrderByContext[$contextKey]++;
    }

    private function normalizeLegacyDeletedAt(string $lastEdit): string
    {
        if ('' === $lastEdit) {
            return $this->nowUtc();
        }

        return (new DateTime($lastEdit, new DateTimeZone('UTC')))
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d H:i:s')
        ;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function insertResourceLink(array $data): int
    {
        if ($this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform) {
            $sql = 'INSERT INTO resource_link (
                        visibility, start_visibility_at, end_visibility_at,
                        display_order, resource_type_group, deleted_at,
                        created_at, updated_at, resource_node_id, parent_id,
                        c_id, session_id, usergroup_id, group_id, user_id
                    ) VALUES (
                        :visibility, :start_visibility_at, :end_visibility_at,
                        :display_order, :resource_type_group, :deleted_at,
                        :created_at, :updated_at, :resource_node_id, :parent_id,
                        :c_id, :session_id, :usergroup_id, :group_id, :user_id
                    ) RETURNING id';

            return (int) $this->connection->fetchOne($sql, $data);
        }

        $this->connection->insert('resource_link', $data);

        return (int) $this->connection->lastInsertId();
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
            $conditions[] = 'EXISTS (SELECT 1 FROM track_e_exercises te WHERE te.c_id = q.c_id AND te.exe_exo_id = q.iid)';
        }

        $usageCondition = implode(' OR ', $conditions);
        $questionCount = $hasQuestionRelations
            ? '(SELECT COUNT(*) FROM c_quiz_rel_question r WHERE r.quiz_id = q.iid)'
            : '0';
        $attemptCount = $hasAttempts
            ? '(SELECT COUNT(*) FROM track_e_exercises te WHERE te.c_id = q.c_id AND te.exe_exo_id = q.iid)'
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

        foreach (array_chunk(array_map('intval', $categoryIds), self::ORM_FLUSH_BATCH_SIZE) as $idChunk) {
            $categoriesById = [];
            foreach ($categoryRepo->findBy(['iid' => $idChunk]) as $categoryEntity) {
                $categoriesById[$categoryEntity->getIid()] = $categoryEntity;
            }

            foreach ($idChunk as $categoryId) {
                $category = $categoriesById[$categoryId] ?? null;

                if (!$category instanceof CQuizQuestionCategory || $category->hasResourceNode()) {
                    continue;
                }

                $this->fixItemProperty(
                    'test_category',
                    $categoryRepo,
                    $course,
                    $courseAdmin,
                    $category,
                    $course,
                    $itemProperties[$categoryId] ?? [],
                    $resourceType,
                    false
                );
            }

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

        if ([] === $imageRows) {
            return;
        }

        $courseDirectory = (string) $this->connection->fetchOne(
            'SELECT directory FROM course WHERE id = :courseId',
            ['courseId' => $courseId]
        );
        $legacyDocumentRoot = '';
        if ('' !== $courseDirectory) {
            $legacyDocumentRoot = rtrim($this->getUpdateRootPath(), '/')
                .'/app/courses/'.$courseDirectory.'/document/';
        }

        $processed = 0;
        $migrated = 0;

        foreach (array_chunk($imageRows, self::IMAGE_FLUSH_BATCH_SIZE) as $rowChunk) {
            $questionIds = [];
            $pictureIds = [];
            foreach ($rowChunk as $imageRow) {
                $questionId = (int) ($imageRow['iid'] ?? 0);
                $picture = trim((string) ($imageRow['picture'] ?? ''));
                if ($questionId <= 0 || '' === $picture) {
                    continue;
                }

                $questionIds[] = $questionId;
                if (ctype_digit($picture)) {
                    $pictureIds[] = (int) $picture;
                }
            }

            $questionsById = [];
            foreach ($questionIds ? $questionRepo->findBy(['iid' => $questionIds]) : [] as $questionEntity) {
                $questionsById[$questionEntity->getIid()] = $questionEntity;
            }

            $documentsById = [];
            foreach ($pictureIds ? $documentRepo->findBy(['iid' => $pictureIds]) : [] as $documentEntity) {
                $documentsById[$documentEntity->getIid()] = $documentEntity;
            }

            foreach ($rowChunk as $imageRow) {
                $questionId = (int) ($imageRow['iid'] ?? 0);
                $picture = trim((string) ($imageRow['picture'] ?? ''));
                $question = $questionsById[$questionId] ?? null;

                if (!$question instanceof CQuizQuestion || '' === $picture) {
                    continue;
                }

                $migratedResourceFile = null;
                $documentId = ctype_digit($picture) ? (int) $picture : 0;
                $document = $documentId > 0 ? ($documentsById[$documentId] ?? null) : null;

                // Preferred path: reuse the file already migrated with c_document.
                if ($document instanceof CDocument
                    && $document->hasResourceNode()
                    && $document->getResourceNode()->hasResourceFile()
                ) {
                    try {
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
                    } catch (Throwable) {
                        // If migrated file metadata exists but its storage is not
                        // readable, fall back to the original Chamilo 1.x file.
                        $migratedResourceFile = null;
                    }
                }

                // Legacy fallback: Chamilo 1.x can store either the c_document IID
                // or directly the image filename in c_quiz_question.picture. The
                // physical hotspot image remains under document/images/. This also
                // preserves images when the document resource node exists but its
                // ResourceFile was not created during the earlier document migration.
                if (null === $migratedResourceFile && '' !== $legacyDocumentRoot) {
                    $legacyRelativePath = '';

                    if ($documentId > 0) {
                        $legacyRelativePath = (string) $this->connection->fetchOne(
                            'SELECT path
                             FROM c_document
                             WHERE iid = :documentId
                               AND c_id = :courseId',
                            [
                                'documentId' => $documentId,
                                'courseId' => $courseId,
                            ]
                        );
                    } elseif (basename($picture) === $picture) {
                        $legacyRelativePath = '/images/'.$picture;
                    }

                    $legacyRelativePath = ltrim($legacyRelativePath, '/');
                    if ('' !== $legacyRelativePath
                        && !str_contains($legacyRelativePath, '../')
                        && !str_contains($legacyRelativePath, '..\\')
                    ) {
                        $legacyFilePath = $legacyDocumentRoot.$legacyRelativePath;

                        if ($this->fileExists($legacyFilePath)) {
                            $originalName = basename($legacyFilePath);
                            $migratedResourceFile = $questionRepo->addFileFromPath(
                                $question,
                                $originalName,
                                $legacyFilePath,
                                false
                            );
                        }
                    }
                }

                if (null === $migratedResourceFile) {
                    $this->getLogger()->warning('Question image source could not be migrated.', [
                        'course_id' => $courseId,
                        'question_iid' => $questionId,
                        'document_iid' => $documentId > 0 ? $documentId : null,
                    ]);

                    continue;
                }

                $migratedResourceFile->setTitle(
                    mb_substr(
                        $migratedResourceFile->getOriginalName() ?: 'question-'.$questionId,
                        0,
                        self::RESOURCE_NODE_TITLE_MAX_LENGTH
                    )
                );

                $this->entityManager->persist($question);
                ++$processed;
                ++$migrated;
            }

            $this->entityManager->flush();
            $this->entityManager->clear();

            $this->getLogger()->info('Quiz question image migration progress.', [
                'course_id' => $courseId,
                'processed' => $processed,
                'migrated' => $migrated,
            ]);
        }
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
        // A scalar query avoids hydrating the full (potentially huge) course
        // roster just to find one teacher, unlike getTeachersSubscriptions().
        // Order by the course_rel_user row's own id (not user_id) to match the
        // subscription-order first-teacher semantics of the original unordered
        // getTeachersSubscriptions() iteration (Course::$users has no OrderBy).
        $teacherId = $this->connection->fetchOne(
            'SELECT user_id
             FROM course_rel_user
             WHERE c_id = :courseId AND status = :teacher
             ORDER BY id
             LIMIT 1',
            [
                'courseId' => (int) $course->getId(),
                'teacher' => CourseRelUser::TEACHER,
            ]
        );

        if (false !== $teacherId && null !== $teacherId) {
            return (int) $teacherId;
        }

        $admin = $this->getAdmin();

        if (null === $admin->getId()) {
            throw new RuntimeException('Unable to resolve a course administrator.');
        }

        return (int) $admin->getId();
    }

    private function ensureTrackExerciseQuizMigrationIndex(): void
    {
        try {
            $schemaManager = $this->connection->createSchemaManager();
            if (!\in_array('track_e_exercises', $schemaManager->listTableNames(), true)) {
                return;
            }

            foreach ($schemaManager->listTableIndexes('track_e_exercises') as $index) {
                $columns = array_map('strtolower', $index->getColumns());
                if (\count($columns) >= 2
                    && 'c_id' === $columns[0]
                    && 'exe_exo_id' === $columns[1]
                ) {
                    return;
                }
            }

            $this->getLogger()->notice('Creating migration index on exercise tracking quiz references.', [
                'index' => self::TRACK_EXERCISE_QUIZ_INDEX,
            ]);
            $this->connection->executeStatement(
                'CREATE INDEX '.self::TRACK_EXERCISE_QUIZ_INDEX.' ON track_e_exercises (c_id, exe_exo_id)'
            );
        } catch (Throwable $exception) {
            $this->getLogger()->warning('Could not create exercise tracking migration index; continuing safely.', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function ensureItemPropertyMigrationIndex(): void
    {
        try {
            $schemaManager = $this->connection->createSchemaManager();
            if (!\in_array('c_item_property', $schemaManager->listTableNames(), true)) {
                return;
            }

            foreach ($schemaManager->listTableIndexes('c_item_property') as $index) {
                if (self::ITEM_PROPERTY_INDEX === strtolower($index->getName())) {
                    return;
                }

                $columns = array_map('strtolower', $index->getColumns());
                if (\count($columns) >= 2
                    && 'tool' === $columns[0]
                    && 'ref' === $columns[1]
                ) {
                    return;
                }
            }

            $this->getLogger()->notice('Creating temporary migration index on c_item_property.', [
                'index' => self::ITEM_PROPERTY_INDEX,
            ]);
            $this->connection->executeStatement(
                'CREATE INDEX '.self::ITEM_PROPERTY_INDEX.' ON c_item_property (tool, ref, c_id)'
            );
        } catch (Throwable $exception) {
            $this->getLogger()->warning('Could not create c_item_property migration index; continuing safely.', [
                'error' => $exception->getMessage(),
            ]);
        }
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
