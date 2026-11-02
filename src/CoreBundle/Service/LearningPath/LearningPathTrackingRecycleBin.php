<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\LearningPath;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Service\Exercise\ExerciseAttemptRecycleBin;
use Chamilo\CoreBundle\Service\Exercise\ExerciseAttemptRecycleSnapshotCodec;
use Chamilo\CoreBundle\Service\Gradebook\GradebookLinkManager;
use Chamilo\CoreBundle\State\Gradebook\GradebookLinkResourceResolver;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Transactional snapshots of learning path reporting resets.
 * The caller owns the reset transaction; no live progress is overwritten on restore.
 */
final readonly class LearningPathTrackingRecycleBin
{
    private const array SNAPSHOT_TABLES = [
        'c_lp_view',
        'c_lp_item_view',
        'c_lp_iv_interaction',
        'c_lp_iv_objective',
        'c_lp_iv_comment',
        'track_e_access_complete',
    ];

    private const array METADATA_TABLES = ['__exercise_links', '__exercise_archives'];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ExerciseAttemptRecycleSnapshotCodec $codec,
        private ExerciseAttemptRecycleBin $exerciseRecycleBin,
        private CQuizRepository $quizRepository,
        private GradebookLinkManager $gradebookLinkManager,
    ) {}

    /**
     * Called inside the existing LP reporting reset transaction, before any destructive writes.
     *
     * @param list<int> $userIds
     */
    public function captureBeforeReset(
        int $lpId,
        Course $course,
        ?Session $session,
        array $userIds,
        bool $deleteExerciseAttempts,
        bool $deleteAccessCompletion,
        int $actorId,
    ): void {
        $connection = $this->entityManager->getConnection();
        $this->assertTables($connection);
        $this->assertDependencies($connection);
        $courseId = (int) $course->getId();
        $sessionId = $session?->getId();

        foreach ($userIds as $userId) {
            $context = ['lp' => $lpId, 'course' => $courseId, 'user' => $userId];
            $sessionWhere = null === $sessionId ? 'session_id IS NULL' : 'session_id = :session';
            if (null !== $sessionId) {
                $context['session'] = $sessionId;
            }
            $tables = [];
            $tables['c_lp_view'] = $connection->fetchAllAssociative(
                'SELECT * FROM c_lp_view WHERE lp_id = :lp AND c_id = :course AND user_id = :user AND '.$sessionWhere.' FOR UPDATE',
                $context,
            );
            $viewIds = array_map(static fn (array $row): int => (int) $row['iid'], $tables['c_lp_view']);
            $tables['c_lp_item_view'] = $this->rowsByIds($connection, 'c_lp_item_view', 'lp_view_id', $viewIds);
            $itemIds = array_map(static fn (array $row): int => (int) $row['iid'], $tables['c_lp_item_view']);
            foreach (['c_lp_iv_interaction', 'c_lp_iv_objective', 'c_lp_iv_comment'] as $table) {
                $tables[$table] = $connection->createSchemaManager()->tablesExist([$table])
                    ? $this->rowsByIds($connection, $table, 'lp_iv_id', $itemIds)
                    : [];
            }
            $tables['track_e_access_complete'] = $deleteAccessCompletion ? $connection->fetchAllAssociative(
                'SELECT * FROM track_e_access_complete WHERE tool = :tool AND tool_id = :lp AND c_id = :course AND session_id = :session AND user_id = :user FOR UPDATE',
                ['tool' => 'learnpath', 'lp' => $lpId, 'course' => $courseId, 'session' => $sessionId ?? 0, 'user' => $userId],
            ) : [];

            $exerciseContext = ['lp' => $lpId, 'course' => $courseId, 'user' => $userId];
            if (null !== $sessionId) {
                $exerciseContext['session'] = $sessionId;
            }
            $linkedAttempts = $connection->fetchAllAssociative(
                'SELECT exe_id, exe_exo_id, orig_lp_id, orig_lp_item_id, orig_lp_item_view_id FROM track_e_exercises '
                .'WHERE orig_lp_id = :lp AND c_id = :course AND exe_user_id = :user AND '.$sessionWhere.' FOR UPDATE',
                $exerciseContext,
            );
            $tables['__exercise_links'] = $deleteExerciseAttempts ? [] : $linkedAttempts;
            $tables['__exercise_archives'] = [];

            if ($deleteExerciseAttempts) {
                foreach ($linkedAttempts as $attemptRow) {
                    $quizId = (int) ($attemptRow['exe_exo_id'] ?? 0);
                    if ($quizId <= 0 || null === $this->quizRepository->findInCourseContext($quizId, $course, $session)) {
                        throw new ConflictHttpException('An exercise in this learning path is unavailable in the current context.');
                    }
                    $this->assertQuizNotLocked($course, $session, $quizId);
                    $attemptId = (int) $attemptRow['exe_id'];
                    $attempt = $this->entityManager->getRepository(TrackEExercise::class)->find($attemptId);
                    if (!$attempt instanceof TrackEExercise) {
                        throw new ConflictHttpException('A learning path exercise attempt is missing.');
                    }
                    $this->exerciseRecycleBin->archiveAndDelete([$attempt], $actorId);
                    $archive = $connection->fetchAssociative(
                        'SELECT id, exercise_id, user_id, course_id, session_id FROM exercise_attempt_recycle_bin '
                        .'WHERE attempt_id = :id AND restored_at IS NULL ORDER BY id DESC LIMIT 1',
                        ['id' => $attemptId],
                    );
                    if (false === $archive || (int) $archive['exercise_id'] !== $quizId || (int) $archive['user_id'] !== $userId
                        || (int) $archive['course_id'] !== $courseId
                        || (null === $sessionId ? null !== $archive['session_id'] : $sessionId !== (int) $archive['session_id'])
                    ) {
                        throw new RuntimeException('An exercise archive could not be linked to the learning path reset.');
                    }
                    $tables['__exercise_archives'][] = [
                        'archive_id' => (string) $archive['id'],
                        'exercise_id' => (string) $quizId,
                        'attempt_id' => (string) $attemptId,
                    ];
                }
            }

            if (0 === array_sum(array_map('count', $tables))) {
                continue;
            }
            $payload = $this->codec->encode($tables);
            $connection->insert('learning_path_tracking_recycle_bin', [
                'lp_id' => $lpId,
                'course_id' => $courseId,
                'session_id' => $sessionId,
                'user_id' => $userId,
                'deleted_by' => $actorId,
                'deleted_at' => date('Y-m-d H:i:s'),
                'snapshot_sha256' => hash('sha256', $payload),
                'snapshot' => $payload,
            ]);
        }
    }

    /**
     * @param list<int> $itemViewIds
     */
    public function deleteComments(array $itemViewIds): void
    {
        if ([] === $itemViewIds || !$this->entityManager->getConnection()->createSchemaManager()->tablesExist(['c_lp_iv_comment'])) {
            return;
        }
        $this->entityManager->getConnection()->executeStatement(
            'DELETE FROM c_lp_iv_comment WHERE lp_iv_id IN (:ids)',
            ['ids' => $itemViewIds],
            ['ids' => ArrayParameterType::INTEGER],
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForLearningPath(int $lpId, int $courseId, ?int $sessionId, array $allowedUserIds): array
    {
        if ([] === $allowedUserIds) {
            return [];
        }
        $connection = $this->entityManager->getConnection();
        $this->assertTables($connection);

        return $connection->executeQuery(
            'SELECT id, user_id, deleted_by, deleted_at FROM learning_path_tracking_recycle_bin '
            .'WHERE lp_id = :lp AND course_id = :course AND '
            .(null === $sessionId ? 'session_id IS NULL' : 'session_id = :session')
            .' AND user_id IN (:users) AND restored_at IS NULL ORDER BY deleted_at DESC, id DESC LIMIT 200',
            null === $sessionId
                ? ['lp' => $lpId, 'course' => $courseId, 'users' => $allowedUserIds]
                : ['lp' => $lpId, 'course' => $courseId, 'session' => $sessionId, 'users' => $allowedUserIds],
            ['users' => ArrayParameterType::INTEGER],
        )->fetchAllAssociative();
    }

    public function getArchiveUserId(int $archiveId, int $lpId, int $courseId, ?int $sessionId): int
    {
        $row = $this->entityManager->getConnection()->fetchAssociative(
            'SELECT user_id, session_id FROM learning_path_tracking_recycle_bin '
            .'WHERE id = :id AND lp_id = :lp AND course_id = :course AND restored_at IS NULL',
            ['id' => $archiveId, 'lp' => $lpId, 'course' => $courseId],
        );
        if (false === $row || (null === $sessionId ? null !== $row['session_id'] : $sessionId !== (int) $row['session_id'])) {
            throw new NotFoundHttpException('Learning path recycle record not found.');
        }

        return (int) $row['user_id'];
    }

    public function restore(int $archiveId, int $lpId, Course $course, ?Session $session, int $actorId): void
    {
        $connection = $this->entityManager->getConnection();
        $this->assertTables($connection);
        $this->assertDependencies($connection);
        $courseId = (int) $course->getId();
        $sessionId = $session?->getId();

        $connection->transactional(function (Connection $connection) use ($archiveId, $lpId, $course, $session, $courseId, $sessionId, $actorId): void {
            $row = $connection->fetchAssociative(
                'SELECT * FROM learning_path_tracking_recycle_bin WHERE id = :id AND lp_id = :lp AND course_id = :course FOR UPDATE',
                ['id' => $archiveId, 'lp' => $lpId, 'course' => $courseId],
            );
            if (false === $row || null !== $row['restored_at']
                || (null === $sessionId ? null !== $row['session_id'] : $sessionId !== (int) $row['session_id'])
            ) {
                throw new NotFoundHttpException('Learning path recycle record not found in this context.');
            }
            if (!hash_equals((string) $row['snapshot_sha256'], hash('sha256', (string) $row['snapshot']))) {
                throw new ConflictHttpException('The learning path archive failed its integrity check.');
            }
            $tables = $this->codec->decode((string) $row['snapshot']);
            if ([] !== array_diff(array_keys($tables), [...self::SNAPSHOT_TABLES, ...self::METADATA_TABLES])
                || !isset($tables['c_lp_view'], $tables['c_lp_item_view'], $tables['__exercise_links'], $tables['__exercise_archives'])
            ) {
                throw new ConflictHttpException('Invalid learning path archive structure.');
            }
            $userId = (int) $row['user_id'];
            $params = ['lp' => $lpId, 'course' => $courseId, 'user' => $userId];
            $sessionWhere = null === $sessionId ? 'session_id IS NULL' : 'session_id = :session';
            if (null !== $sessionId) {
                $params['session'] = $sessionId;
            }
            $newProgress = $connection->fetchOne(
                'SELECT iid FROM c_lp_view WHERE lp_id = :lp AND c_id = :course AND user_id = :user AND '.$sessionWhere.' LIMIT 1',
                $params,
            );
            if (false !== $newProgress) {
                throw new ConflictHttpException('The learner has new progress. Restore must not overwrite it.');
            }
            $viewIds = [];
            foreach ($tables['c_lp_view'] as $view) {
                if ((int) ($view['lp_id'] ?? 0) !== $lpId || (int) ($view['c_id'] ?? 0) !== $courseId
                    || (int) ($view['user_id'] ?? 0) !== $userId
                    || (null === $sessionId ? null !== ($view['session_id'] ?? null) : (int) ($view['session_id'] ?? 0) !== $sessionId)
                ) {
                    throw new ConflictHttpException('The learning path archive context is inconsistent.');
                }
                $viewIds[] = (int) $view['iid'];
            }
            $itemIds = [];
            foreach ($tables['c_lp_item_view'] as $item) {
                if (!\in_array((int) ($item['lp_view_id'] ?? 0), $viewIds, true)) {
                    throw new ConflictHttpException('The learning path item belongs to a different view.');
                }
                $itemIds[] = (int) $item['iid'];
            }
            foreach (['c_lp_iv_interaction', 'c_lp_iv_objective', 'c_lp_iv_comment'] as $table) {
                foreach ($tables[$table] ?? [] as $record) {
                    if (!\in_array((int) ($record['lp_iv_id'] ?? 0), $itemIds, true)) {
                        throw new ConflictHttpException('The SCORM record belongs to a different item view.');
                    }
                }
            }
            foreach ($tables['track_e_access_complete'] ?? [] as $record) {
                if ('learnpath' !== ($record['tool'] ?? null) || (int) ($record['tool_id'] ?? 0) !== $lpId
                    || (int) ($record['c_id'] ?? 0) !== $courseId || (int) ($record['user_id'] ?? 0) !== $userId
                    || (int) ($record['session_id'] ?? -1) !== ($sessionId ?? 0)
                ) {
                    throw new ConflictHttpException('An access-completion record has an invalid context.');
                }
            }

            foreach (self::SNAPSHOT_TABLES as $table) {
                foreach ($tables[$table] ?? [] as $record) {
                    $connection->insert($table, $record);
                }
            }

            foreach ($tables['__exercise_links'] as $link) {
                if (!\in_array((int) ($link['orig_lp_item_view_id'] ?? 0), $itemIds, true)
                    && 0 !== (int) ($link['orig_lp_item_view_id'] ?? 0)
                ) {
                    throw new ConflictHttpException('The original exercise attempt belongs to a different learning path view.');
                }
                $attemptId = (int) ($link['exe_id'] ?? 0);
                if ($attemptId <= 0 || (int) ($link['orig_lp_id'] ?? 0) !== $lpId) {
                    throw new ConflictHttpException('Invalid detached exercise attempt.');
                }
                $updated = $connection->executeStatement(
                    'UPDATE track_e_exercises SET orig_lp_id = :lp, orig_lp_item_id = :item, orig_lp_item_view_id = :view '
                    .'WHERE exe_id = :attempt AND c_id = :course AND exe_user_id = :user AND '
                    .(null === $sessionId ? 'session_id IS NULL' : 'session_id = :session')
                    .' AND orig_lp_id = 0 AND orig_lp_item_id = 0 AND orig_lp_item_view_id = 0',
                    null === $sessionId ? [
                        'lp' => $lpId, 'item' => (int) $link['orig_lp_item_id'], 'view' => (int) $link['orig_lp_item_view_id'],
                        'attempt' => $attemptId, 'course' => $courseId, 'user' => $userId,
                    ] : [
                        'lp' => $lpId, 'item' => (int) $link['orig_lp_item_id'], 'view' => (int) $link['orig_lp_item_view_id'],
                        'attempt' => $attemptId, 'course' => $courseId, 'user' => $userId, 'session' => $sessionId,
                    ],
                );
                if (1 !== $updated) {
                    throw new ConflictHttpException('A detached exercise attempt changed after the reset.');
                }
            }

            foreach ($tables['__exercise_archives'] as $archive) {
                $exerciseId = (int) ($archive['exercise_id'] ?? 0);
                $attemptId = (int) ($archive['attempt_id'] ?? 0);
                $exerciseArchiveId = (int) ($archive['archive_id'] ?? 0);
                if ($exerciseArchiveId <= 0 || $attemptId <= 0 || $exerciseId <= 0
                    || null === $this->quizRepository->findInCourseContext($exerciseId, $course, $session)
                ) {
                    throw new ConflictHttpException('An archived exercise is no longer available in this context.');
                }
                $this->assertQuizNotLocked($course, $session, $exerciseId);
                $archiveRow = $connection->fetchAssociative(
                    'SELECT attempt_id, user_id, course_id, session_id, exercise_id, restored_at '
                    .'FROM exercise_attempt_recycle_bin WHERE id = :id FOR UPDATE',
                    ['id' => $exerciseArchiveId],
                );
                if (false === $archiveRow || null !== $archiveRow['restored_at']
                    || (int) $archiveRow['attempt_id'] !== $attemptId || (int) $archiveRow['user_id'] !== $userId
                    || (int) $archiveRow['course_id'] !== $courseId || (int) $archiveRow['exercise_id'] !== $exerciseId
                    || (null === $sessionId ? null !== $archiveRow['session_id'] : $sessionId !== (int) $archiveRow['session_id'])
                ) {
                    throw new ConflictHttpException('An associated exercise attempt was already restored or changed.');
                }
                $this->exerciseRecycleBin->restore($exerciseArchiveId, $courseId, $sessionId, $exerciseId, $actorId, $archiveId);
            }

            $connection->update('learning_path_tracking_recycle_bin', [
                'restored_by' => $actorId,
                'restored_at' => date('Y-m-d H:i:s'),
            ], ['id' => $archiveId]);
        });
    }

    /**
     * @param list<int> $ids
     *
     * @return list<array<string, mixed>>
     */
    private function rowsByIds(Connection $connection, string $table, string $column, array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        return $connection->executeQuery(
            'SELECT * FROM '.$table.' WHERE '.$column.' IN (:ids) FOR UPDATE',
            ['ids' => $ids],
            ['ids' => ArrayParameterType::INTEGER],
        )->fetchAllAssociative();
    }

    private function assertTables(Connection $connection): void
    {
        foreach (['learning_path_tracking_recycle_bin', 'c_lp_view', 'c_lp_item_view', 'c_lp_iv_interaction', 'c_lp_iv_objective', 'track_e_exercises', 'exercise_attempt_recycle_bin'] as $table) {
            if (!$connection->createSchemaManager()->tablesExist([$table])) {
                throw new RuntimeException('Required tracking recycle table is missing: '.$table);
            }
        }
    }

    private function assertDependencies(Connection $connection): void
    {
        foreach ($connection->fetchAllAssociative(
            'SELECT TABLE_NAME, REFERENCED_TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE '
            ."WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IN ('c_lp_view', 'c_lp_item_view')",
        ) as $row) {
            if ('c_lp_view' === $row['REFERENCED_TABLE_NAME'] && 'c_lp_item_view' === $row['TABLE_NAME']) {
                continue;
            }

            throw new RuntimeException('An unhandled learning path tracking dependency prevents safe recycling: '.$row['TABLE_NAME']);
        }
    }

    private function assertQuizNotLocked(Course $course, ?Session $session, int $quizId): void
    {
        if ($this->gradebookLinkManager->isResourceLocked($course, $session, GradebookLinkResourceResolver::LINK_EXERCISE, $quizId)) {
            throw new ConflictHttpException('A linked exercise is locked by gradebook.');
        }
    }
}
