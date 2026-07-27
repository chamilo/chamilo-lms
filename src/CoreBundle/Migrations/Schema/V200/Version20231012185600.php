<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;
use Symfony\Component\Uid\Uuid;
use Throwable;

use const ENT_HTML5;
use const ENT_QUOTES;

final class Version20231012185600 extends AbstractMigrationChamilo
{
    private const int QUIZ_BATCH_SIZE = 1000;
    private const int RESOURCE_NODE_TITLE_MAX_LENGTH = 255;

    public function getDescription(): string
    {
        return 'Migrate missing c_quiz resource nodes with resumable transactional DBAL batches';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        /** @var CQuizRepository $quizRepo */
        $quizRepo = $this->container->get(CQuizRepository::class);

        /** @var CourseRepository $courseRepo */
        $courseRepo = $this->container->get(CourseRepository::class);

        $resourceTypeId = (int) $quizRepo->getResourceType()->getId();
        $uuidIsBinary = $this->detectUuidIsBinary();
        $totalPending = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM c_quiz WHERE resource_node_id IS NULL'
        );

        if (0 === $totalPending) {
            $this->getLogger()->info('No missing quiz resource nodes were found.');

            return;
        }

        $courseIds = $this->connection->fetchFirstColumn(
            'SELECT DISTINCT c_id FROM c_quiz WHERE resource_node_id IS NULL ORDER BY c_id'
        );
        $processed = 0;
        $startedAt = microtime(true);

        $this->getLogger()->info('Missing quiz DBAL migration started.', [
            'pending' => $totalPending,
            'batch_size' => self::QUIZ_BATCH_SIZE,
        ]);

        foreach ($courseIds as $courseIdValue) {
            $courseId = (int) $courseIdValue;
            $course = $courseRepo->find($courseId);
            if (!$course instanceof Course) {
                throw new RuntimeException("Course {$courseId} was not found.");
            }

            $creatorId = $this->resolveCourseAdminId($course);
            $courseNode = $this->connection->fetchAssociative(
                'SELECT rn.id, rn.path, rn.level
                 FROM course c
                 INNER JOIN resource_node rn ON rn.id = c.resource_node_id
                 WHERE c.id = :courseId',
                ['courseId' => $courseId]
            );

            if (!$courseNode) {
                throw new RuntimeException("Course {$courseId} has no valid resource node.");
            }

            $courseNodeId = (int) $courseNode['id'];
            $coursePath = rtrim((string) $courseNode['path'], '/');
            $quizLevel = ((int) $courseNode['level']) + 1;
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
            $lastIid = 0;

            while (true) {
                $rows = $this->connection->fetchAllAssociative(
                    'SELECT iid, title
                     FROM c_quiz
                     WHERE c_id = :courseId
                       AND resource_node_id IS NULL
                       AND iid > :lastIid
                     ORDER BY iid
                     LIMIT '.self::QUIZ_BATCH_SIZE,
                    [
                        'courseId' => $courseId,
                        'lastIid' => $lastIid,
                    ]
                );

                if ([] === $rows) {
                    break;
                }

                $lastIid = (int) $rows[array_key_last($rows)]['iid'];
                $this->connection->beginTransaction();

                try {
                    foreach ($rows as $row) {
                        $quizId = (int) $row['iid'];
                        $title = $this->normalizeQuizTitle((string) ($row['title'] ?? ''), $quizId);
                        $slug = 'quiz-'.$quizId;
                        $now = gmdate('Y-m-d H:i:s');
                        $uuid = Uuid::v4();
                        $uuidValue = $uuidIsBinary ? $uuid->toBinary() : $uuid->toRfc4122();

                        $resourceNodeId = $this->insertResourceNode(
                            $title,
                            $slug,
                            $quizLevel,
                            $now,
                            $uuidValue,
                            $uuidIsBinary,
                            $resourceTypeId,
                            $creatorId,
                            $courseNodeId
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

                        $segment = preg_replace('/\s+/u', ' ', trim(str_replace(['/', '\\'], '-', $title)));
                        if (null === $segment || '' === $segment) {
                            $segment = $slug;
                        }

                        $this->connection->update(
                            'resource_node',
                            ['path' => $coursePath.'/'.$segment.'-'.$quizId.'-'.$resourceNodeId.'/'],
                            ['id' => $resourceNodeId]
                        );
                        $this->connection->update(
                            'c_quiz',
                            ['resource_node_id' => $resourceNodeId],
                            ['iid' => $quizId]
                        );

                        ++$displayOrder;
                        ++$processed;
                    }

                    $this->connection->commit();
                } catch (Throwable $e) {
                    if ($this->connection->isTransactionActive()) {
                        $this->connection->rollBack();
                    }

                    throw new RuntimeException("Missing quiz DBAL batch failed for course {$courseId}: {$e->getMessage()}", 0, $e);
                }

                $elapsed = max(1, (int) (microtime(true) - $startedAt));
                $rate = $processed / $elapsed;
                $remaining = max(0, $totalPending - $processed);

                $this->getLogger()->info('Missing quiz DBAL migration progress.', [
                    'processed' => $processed,
                    'total_pending' => $totalPending,
                    'percentage' => round(100 * $processed / $totalPending, 2),
                    'rows_per_second' => round($rate, 2),
                    'eta_seconds' => $rate > 0 ? (int) round($remaining / $rate) : null,
                    'course_id' => $courseId,
                    'last_iid' => $lastIid,
                ]);
            }

            $this->entityManager->clear();
        }

        $this->getLogger()->info('Missing quiz DBAL migration completed.', [
            'initial_pending' => $totalPending,
            'processed' => $processed,
            'remaining' => (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM c_quiz WHERE resource_node_id IS NULL'
            ),
            'elapsed_seconds' => (int) (microtime(true) - $startedAt),
        ]);
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
            throw new RuntimeException('Unable to resolve a quiz resource creator.');
        }

        return (int) $admin->getId();
    }

    private function normalizeQuizTitle(string $title, int $quizId): string
    {
        $title = html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $title = preg_replace('/\s+/u', ' ', trim($title));

        if (null === $title || '' === $title) {
            return 'Quiz '.$quizId;
        }

        $title = str_replace(['/', '\\'], '-', $title);

        if (mb_strlen($title) > self::RESOURCE_NODE_TITLE_MAX_LENGTH) {
            $title = mb_substr($title, 0, self::RESOURCE_NODE_TITLE_MAX_LENGTH - 3).'...';
        }

        return $title;
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

            return \in_array($type, ['binary', 'varbinary'], true) || 16 === $column->getLength();
        } catch (Throwable) {
            return false;
        }
    }

    private function insertResourceNode(
        string $title,
        string $slug,
        int $level,
        string $now,
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
            'created_at' => $now,
            'updated_at' => $now,
            'public' => 0,
            'uuid' => $uuid,
            'resource_type_id' => $resourceTypeId,
            'resource_format_id' => null,
            'language_id' => null,
            'creator_id' => $creatorId,
            'parent_id' => $parentId,
        ];
        $types = $uuidIsBinary ? ['uuid' => ParameterType::BINARY] : [];

        if ($this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform) {
            return (int) $this->connection->fetchOne(
                'INSERT INTO resource_node (
                    title, slug, level, path, created_at, updated_at, public,
                    uuid, resource_type_id, resource_format_id, language_id,
                    creator_id, parent_id
                 ) VALUES (
                    :title, :slug, :level, :path, :created_at, :updated_at, :public,
                    :uuid, :resource_type_id, :resource_format_id, :language_id,
                    :creator_id, :parent_id
                 ) RETURNING id',
                $data,
                $types
            );
        }

        $this->connection->insert('resource_node', $data, $types);

        return (int) $this->connection->lastInsertId();
    }
}
