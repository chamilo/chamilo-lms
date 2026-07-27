<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CourseBundle\Entity\CCourseDescription;
use Chamilo\CourseBundle\Repository\CCourseDescriptionRepository;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

final class Version20201215135838 extends AbstractMigrationChamilo
{
    private const int ORM_FLUSH_BATCH_SIZE = 100;

    public function getDescription(): string
    {
        return 'Migrate c_course_description';
    }

    /**
     * Course descriptions are committed in explicit ORM batches.
     * This makes the migration resumable and avoids losing hours of work if
     * the process is interrupted.
     */
    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $courseDescriptionRepo = $this->container->get(CCourseDescriptionRepository::class);
        $courseRepo = $this->container->get(CourseRepository::class);
        $userRepo = $this->container->get(UserRepository::class);

        $adminId = (int) $this->getAdmin()->getId();
        $courseIds = $this->connection->fetchFirstColumn('SELECT id FROM course ORDER BY id');

        foreach ($courseIds as $courseIdValue) {
            $courseId = (int) $courseIdValue;
            [$course, $admin] = $this->reloadCourseDescriptionContext($courseId, $adminId, $courseRepo, $userRepo);

            $itemIds = $this->connection->fetchFirstColumn(
                'SELECT iid FROM c_course_description WHERE c_id = :courseId AND resource_node_id IS NULL ORDER BY iid',
                ['courseId' => $courseId]
            );

            $processed = 0;

            foreach ($itemIds as $itemIdValue) {
                $id = (int) $itemIdValue;

                /** @var CCourseDescription $resource */
                $resource = $courseDescriptionRepo->find($id);
                if (!$resource instanceof CCourseDescription || $resource->hasResourceNode()) {
                    continue;
                }

                $result = $this->fixItemProperty(
                    'course_description',
                    $courseDescriptionRepo,
                    $course,
                    $admin,
                    $resource,
                    $course
                );

                if (false === $result) {
                    continue;
                }

                $this->entityManager->persist($resource);
                ++$processed;

                if (0 === $processed % self::ORM_FLUSH_BATCH_SIZE) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();

                    [$course, $admin] = $this->reloadCourseDescriptionContext(
                        $courseId,
                        $adminId,
                        $courseRepo,
                        $userRepo
                    );
                }
            }

            $this->entityManager->flush();
            $this->entityManager->clear();
        }
    }

    /**
     * @return array{0: Course, 1: User}
     */
    private function reloadCourseDescriptionContext(
        int $courseId,
        int $adminId,
        CourseRepository $courseRepo,
        UserRepository $userRepo
    ): array {
        $course = $courseRepo->find($courseId);
        $admin = $userRepo->find($adminId);

        if (!$course instanceof Course) {
            throw new RuntimeException("Course {$courseId} could not be reloaded.");
        }

        if (!$admin instanceof User) {
            throw new RuntimeException("Admin user {$adminId} could not be reloaded.");
        }

        return [$course, $admin];
    }
}
