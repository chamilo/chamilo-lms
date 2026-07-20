<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\AccessUrlRelCourse;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\CourseBundle\Repository\CToolRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;
use Throwable;

final class Version20201212195011 extends AbstractMigrationChamilo
{
    private const TOOL_BATCH_SIZE = 200;
    private const COURSE_BATCH_SIZE = 50;

    public function getDescription(): string
    {
        return 'Migrate courses and c_tool with keyset batches and bounded ORM hydration';
    }

    public function up(Schema $schema): void
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = $this->container->get(CourseRepository::class);
        /** @var CToolRepository $toolRepo */
        $toolRepo = $this->container->get(CToolRepository::class);
        /** @var AccessUrlRepository $urlRepo */
        $urlRepo = $this->container->get(AccessUrlRepository::class);

        $adminId = (int) $this->getAdmin()->getId();
        if ($adminId <= 0) {
            throw new RuntimeException('Unable to resolve the migration administrator.');
        }

        $this->migrateCourseResourceNodes($adminId, $courseRepo, $urlRepo);
        $this->markSpecialCourses();
        $this->migrateCourseTools($adminId, $courseRepo, $toolRepo);
    }

    private function migrateCourseResourceNodes(
        int $adminId,
        CourseRepository $courseRepo,
        AccessUrlRepository $urlRepo
    ): void {
        $urlIds = array_map(
            'intval',
            $this->connection->fetchFirstColumn('SELECT id FROM access_url ORDER BY id')
        );
        $processed = 0;

        foreach ($urlIds as $urlId) {
            $url = $urlRepo->find($urlId);
            if (!$url instanceof AccessUrl) {
                continue;
            }

            $courseIds = [];
            /** @var AccessUrlRelCourse $relation */
            foreach ($url->getCourses() as $relation) {
                $course = $relation->getCourse();
                if ($course instanceof Course && null !== $course->getId()) {
                    $courseIds[] = (int) $course->getId();
                }
            }

            foreach (array_values(array_unique($courseIds)) as $courseId) {
                $course = $courseRepo->find($courseId);
                if (!$course instanceof Course || $course->hasResourceNode()) {
                    continue;
                }

                $url = $urlRepo->find($urlId);
                if (!$url instanceof AccessUrl) {
                    throw new RuntimeException("Access URL {$urlId} could not be reloaded.");
                }

                $admin = $this->entityManager->getReference(User::class, $adminId);
                $courseRepo->addResourceNode($course, $admin, $url);
                $this->entityManager->persist($course);
                ++$processed;

                if (0 === $processed % self::COURSE_BATCH_SIZE) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                }
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->getLogger()->info('Course resource-node migration completed.', [
            'migrated' => $processed,
        ]);
    }

    private function markSpecialCourses(): void
    {
        try {
            $affected = $this->connection->executeStatement(
                'UPDATE course course
                 INNER JOIN extra_field_values efv
                    ON efv.item_id = course.id
                   AND efv.field_value = :enabled
                 INNER JOIN extra_field ef
                    ON ef.id = efv.field_id
                   AND ef.item_type = :itemType
                   AND ef.variable = :variable
                 SET course.sticky = 1',
                [
                    'enabled' => '1',
                    'itemType' => ExtraField::COURSE_FIELD_TYPE,
                    'variable' => 'special_course',
                ]
            );

            $this->getLogger()->info('Special courses marked as sticky.', [
                'affected' => $affected,
            ]);
        } catch (Throwable $exception) {
            $this->getLogger()->warning('Special-course update could not be applied; continuing safely.', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function migrateCourseTools(
        int $adminId,
        CourseRepository $courseRepo,
        CToolRepository $toolRepo
    ): void {
        $toolMetadata = $this->entityManager->getClassMetadata(CTool::class);
        $toolIdField = (string) $toolMetadata->getSingleIdentifierFieldName();
        $total = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM c_tool WHERE resource_node_id IS NULL'
        );
        $lastIid = 0;
        $seen = 0;
        $migrated = 0;
        $startedAt = microtime(true);

        while (true) {
            $rows = $this->connection->fetchAllAssociative(
                'SELECT iid, c_id, session_id, visibility
                 FROM c_tool
                 WHERE resource_node_id IS NULL
                   AND iid > :lastIid
                 ORDER BY iid
                 LIMIT '.self::TOOL_BATCH_SIZE,
                ['lastIid' => $lastIid]
            );

            if ([] === $rows) {
                break;
            }

            $lastIid = (int) $rows[array_key_last($rows)]['iid'];
            $seen += \count($rows);

            $ids = array_map('intval', array_column($rows, 'iid'));
            $sessionIds = array_values(array_unique(array_filter(array_map(
                static fn (array $row): int => (int) ($row['session_id'] ?? 0),
                $rows
            ))));
            $validSessions = $this->loadValidSessionIds($sessionIds);

            /** @var CTool[] $tools */
            $tools = $toolRepo->findBy([$toolIdField => $ids]);
            $toolsById = [];
            foreach ($tools as $tool) {
                $identifier = $toolMetadata->getIdentifierValues($tool);
                $toolId = (int) ($identifier[$toolIdField] ?? 0);
                if ($toolId > 0) {
                    $toolsById[$toolId] = $tool;
                }
            }

            $courses = [];
            $admin = $this->entityManager->getReference(User::class, $adminId);

            foreach ($rows as $row) {
                $toolId = (int) $row['iid'];
                $courseId = (int) $row['c_id'];
                $tool = $toolsById[$toolId] ?? null;

                if (!$tool instanceof CTool || $tool->hasResourceNode() || $courseId <= 0) {
                    continue;
                }

                if (!isset($courses[$courseId])) {
                    $course = $courseRepo->find($courseId);
                    if (!$course instanceof Course || !$course->hasResourceNode()) {
                        $courses[$courseId] = null;
                    } else {
                        $courses[$courseId] = $course;
                    }
                }

                $course = $courses[$courseId];
                if (!$course instanceof Course) {
                    continue;
                }

                $sessionId = (int) ($row['session_id'] ?? 0);
                $session = $sessionId > 0 && isset($validSessions[$sessionId])
                    ? $this->entityManager->getReference(Session::class, $sessionId)
                    : null;

                $tool->setParent($course);
                $toolRepo->addResourceNode($tool, $admin, $course);
                $visibility = 1 === (int) ($row['visibility'] ?? 0)
                    ? ResourceLink::VISIBILITY_PUBLISHED
                    : ResourceLink::VISIBILITY_DRAFT;
                $tool->addCourseLink($course, $session, null, $visibility);
                $this->entityManager->persist($tool);
                ++$migrated;
            }

            $this->entityManager->flush();
            $this->entityManager->clear();

            $elapsed = max(1, (int) (microtime(true) - $startedAt));
            $rate = $seen / $elapsed;
            $this->getLogger()->info('Course-tool migration progress.', [
                'seen' => $seen,
                'migrated' => $migrated,
                'total' => $total,
                'last_iid' => $lastIid,
                'rows_per_second' => round($rate, 2),
                'eta_seconds' => $rate > 0 ? (int) round(max(0, $total - $seen) / $rate) : null,
            ]);
        }

        $this->getLogger()->info('Course-tool migration completed.', [
            'seen' => $seen,
            'migrated' => $migrated,
            'remaining' => (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM c_tool WHERE resource_node_id IS NULL'
            ),
        ]);
    }

    /**
     * @param int[] $sessionIds
     *
     * @return array<int, true>
     */
    private function loadValidSessionIds(array $sessionIds): array
    {
        if ([] === $sessionIds) {
            return [];
        }

        try {
            $rows = $this->connection->executeQuery(
                'SELECT id FROM session WHERE id IN (:ids)',
                ['ids' => $sessionIds],
                ['ids' => ArrayParameterType::INTEGER]
            )->fetchFirstColumn();
        } catch (Throwable) {
            return [];
        }

        $map = [];
        foreach ($rows as $sessionId) {
            $map[(int) $sessionId] = true;
        }

        return $map;
    }
}
