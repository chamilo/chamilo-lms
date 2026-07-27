<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CourseBundle\Entity\CGlossary;
use Chamilo\CourseBundle\Repository\CGlossaryRepository;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

final class Version20201216120654 extends AbstractMigrationChamilo
{
    private const int ORM_FLUSH_BATCH_SIZE = 100;

    public function getDescription(): string
    {
        return 'Migrate c_glossary';
    }

    /**
     * Glossary items are committed in explicit ORM batches.
     * This makes the migration resumable and avoids losing hours of work if
     * the process is interrupted.
     */
    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $glossaryRepo = $this->container->get(CGlossaryRepository::class);
        $courseRepo = $this->container->get(CourseRepository::class);
        $userRepo = $this->container->get(UserRepository::class);

        $adminId = (int) $this->getAdmin()->getId();
        $courseIds = $this->connection->fetchFirstColumn('SELECT id FROM course ORDER BY id');

        foreach ($courseIds as $courseIdValue) {
            $courseId = (int) $courseIdValue;
            [$course, $admin] = $this->reloadGlossaryContext($courseId, $adminId, $courseRepo, $userRepo);

            $glossaryIds = $this->connection->fetchFirstColumn(
                'SELECT iid FROM c_glossary WHERE c_id = :courseId AND resource_node_id IS NULL ORDER BY iid',
                ['courseId' => $courseId]
            );

            $processed = 0;

            foreach ($glossaryIds as $glossaryIdValue) {
                $id = (int) $glossaryIdValue;

                /** @var CGlossary $resource */
                $resource = $glossaryRepo->find($id);
                if (!$resource instanceof CGlossary || $resource->hasResourceNode()) {
                    continue;
                }

                $result = $this->fixItemProperty(
                    'glossary',
                    $glossaryRepo,
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

                    [$course, $admin] = $this->reloadGlossaryContext($courseId, $adminId, $courseRepo, $userRepo);
                }
            }

            $this->entityManager->flush();
            $this->entityManager->clear();
        }
    }

    /**
     * @return array{0: Course, 1: User}
     */
    private function reloadGlossaryContext(
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
