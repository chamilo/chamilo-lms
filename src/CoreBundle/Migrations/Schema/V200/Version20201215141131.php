<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CourseBundle\Entity\CLink;
use Chamilo\CourseBundle\Entity\CLinkCategory;
use Chamilo\CourseBundle\Repository\CGroupRepository;
use Chamilo\CourseBundle\Repository\CLinkCategoryRepository;
use Chamilo\CourseBundle\Repository\CLinkRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

final class Version20201215141131 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate c_link_category, c_link';
    }

    public function up(Schema $schema): void
    {
        $container = $this->getContainer();
        $doctrine = $container->get('doctrine');
        $em = $doctrine->getManager();
        /** @var Connection $connection */
        $connection = $em->getConnection();

        $linkRepo = $container->get(CLinkRepository::class);
        $linkCategoryRepo = $container->get(CLinkCategoryRepository::class);
        $courseRepo = $container->get(CourseRepository::class);
        $sessionRepo = $container->get(SessionRepository::class);
        $groupRepo = $container->get(CGroupRepository::class);
        $userRepo = $container->get(UserRepository::class);

        $admin = $this->getAdmin();

        $q = $em->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');
        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $counter = 1;
            $courseId = $course->getId();
            $course = $courseRepo->find($courseId);

            $sql = "SELECT * FROM c_link_category WHERE c_id = $courseId
                    ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            foreach ($items as $itemData) {
                $id = $itemData['iid'];
                /** @var CLinkCategory $event */
                $resource = $linkCategoryRepo->find($id);
                if ($resource->hasResourceNode()) {
                    continue;
                }
                $result = $this->fixItemProperty(
                    'link_category',
                    $linkCategoryRepo,
                    $course,
                    $admin,
                    $resource,
                    $course
                );

                if (false === $result) {
                    continue;
                }
                $em->persist($resource);
                $em->flush();
            }

            $sql = "SELECT * FROM c_link WHERE c_id = $courseId
                    ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            foreach ($items as $itemData) {
                $id = $itemData['iid'];
                $categoryId = $itemData['category_id'];
                /** @var CLink $event */
                $resource = $linkRepo->find($id);
                if ($resource->hasResourceNode()) {
                    continue;
                }
                $parent = $course;

                if (!empty($categoryId)) {
                    $category = $linkCategoryRepo->find($categoryId);
                    if (null !== $category) {
                        $parent = $category;
                    }
                }

                $result = $this->fixItemProperty(
                    'link',
                    $linkRepo,
                    $course,
                    $admin,
                    $resource,
                    $parent
                );

                if (false === $result) {
                    continue;
                }
                $em->persist($resource);
                $em->flush();
            }
        }
    }
}
