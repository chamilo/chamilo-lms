<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CourseBundle\Repository\CGroupCategoryRepository;
use Chamilo\CourseBundle\Repository\CGroupRepository;
use Doctrine\DBAL\Schema\Schema;

final class Version20201212195112 extends AbstractMigrationChamilo
{
    private const int RESOURCE_BATCH_SIZE = 100;

    public function getDescription(): string
    {
        return 'Migrate c_group_info ';
    }

    public function up(Schema $schema): void
    {
        $courseRepo = $this->container->get(CourseRepository::class);
        $sessionRepo = $this->container->get(SessionRepository::class);
        $groupRepo = $this->container->get(CGroupRepository::class);
        $groupCategoryRepo = $this->container->get(CGroupCategoryRepository::class);

        $adminId = $this->getAdmin()->getId();
        $this->entityManager->clear();

        $migratedCategories = $this->migrateGroupCategories(
            $courseRepo,
            $groupRepo,
            $groupCategoryRepo,
            $adminId
        );
        $migratedGroups = $this->migrateGroups(
            $courseRepo,
            $sessionRepo,
            $groupRepo,
            $adminId
        );

        $this->getLogger()->info('Migrated legacy group resources.', [
            'categories' => $migratedCategories,
            'groups' => $migratedGroups,
        ]);
    }

    private function migrateGroupCategories(
        CourseRepository $courseRepo,
        CGroupRepository $groupRepo,
        CGroupCategoryRepository $groupCategoryRepo,
        int $adminId
    ): int {
        $lastIid = 0;
        $migrated = 0;

        while (true) {
            $rows = $this->connection->fetchAllAssociative(
                'SELECT iid, c_id
                 FROM c_group_category
                 WHERE iid > :lastIid
                   AND resource_node_id IS NULL
                 ORDER BY iid
                 LIMIT '.self::RESOURCE_BATCH_SIZE,
                ['lastIid' => $lastIid]
            );

            if ([] === $rows) {
                break;
            }

            $admin = $this->entityManager->getReference(User::class, $adminId);
            $resourceType = $groupRepo->getResourceType();

            foreach ($rows as $row) {
                $categoryId = (int) $row['iid'];
                $courseId = (int) $row['c_id'];
                $lastIid = $categoryId;

                if ($categoryId <= 0 || $courseId <= 0) {
                    continue;
                }

                $category = $groupCategoryRepo->find($categoryId);
                $course = $courseRepo->find($courseId);
                if (null === $category || null === $course || $category->hasResourceNode()) {
                    continue;
                }

                $category->setParent($course);

                // Bulk migration only needs owning sides. Avoid growing the admin resource-node
                // and course child collections for every legacy group category.
                $groupRepo->addResourceNode($category, $admin, $course, $resourceType, false);
                $category->addCourseLink($course, null, null, ResourceLink::VISIBILITY_PUBLISHED);
                $this->entityManager->persist($category);
                ++$migrated;
            }

            $this->entityManager->flush();
            $this->entityManager->clear();
        }

        return $migrated;
    }

    private function migrateGroups(
        CourseRepository $courseRepo,
        SessionRepository $sessionRepo,
        CGroupRepository $groupRepo,
        int $adminId
    ): int {
        $lastIid = 0;
        $migrated = 0;

        while (true) {
            $rows = $this->connection->fetchAllAssociative(
                'SELECT iid, c_id, session_id
                 FROM c_group_info
                 WHERE iid > :lastIid
                   AND resource_node_id IS NULL
                 ORDER BY iid
                 LIMIT '.self::RESOURCE_BATCH_SIZE,
                ['lastIid' => $lastIid]
            );

            if ([] === $rows) {
                break;
            }

            $admin = $this->entityManager->getReference(User::class, $adminId);
            $resourceType = $groupRepo->getResourceType();

            foreach ($rows as $row) {
                $groupId = (int) $row['iid'];
                $courseId = (int) $row['c_id'];
                $sessionId = (int) ($row['session_id'] ?? 0);
                $lastIid = $groupId;

                if ($groupId <= 0 || $courseId <= 0) {
                    continue;
                }

                $group = $groupRepo->find($groupId);
                $course = $courseRepo->find($courseId);
                if (null === $group || null === $course || $group->hasResourceNode()) {
                    continue;
                }

                $session = $sessionId > 0 ? $sessionRepo->find($sessionId) : null;
                $group->setParent($course);

                // As above, keep inverse collections uninitialized during bulk migration.
                $groupRepo->addResourceNode($group, $admin, $course, $resourceType, false);
                $visibility = $group->getStatus()
                    ? ResourceLink::VISIBILITY_PUBLISHED
                    : ResourceLink::VISIBILITY_PENDING;
                $group->addCourseLink($course, $session, null, $visibility);
                $this->entityManager->persist($group);
                ++$migrated;
            }

            $this->entityManager->flush();
            $this->entityManager->clear();
        }

        return $migrated;
    }
}
