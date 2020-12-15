<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CourseBundle\Entity\CCourseDescription;
use Chamilo\CourseBundle\Repository\CCourseDescriptionRepository;
use Chamilo\CourseBundle\Repository\CGroupRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

final class Version20201215135838 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate c_course_description';
    }

    public function up(Schema $schema): void
    {
        $container = $this->getContainer();
        $doctrine = $container->get('doctrine');
        $em = $doctrine->getManager();
        /** @var Connection $connection */
        $connection = $em->getConnection();

        $courseDescriptionRepo = $container->get(CCourseDescriptionRepository::class);
        $courseRepo = $container->get(CourseRepository::class);
        $sessionRepo = $container->get(SessionRepository::class);
        $groupRepo = $container->get(CGroupRepository::class);
        $userRepo = $container->get(UserRepository::class);

        $admin = $this->getAdmin();
        $q = $em->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');
        foreach ($q->toIterable() as $course) {
            $counter = 1;
            $courseId = $course->getId();
            $course = $courseRepo->find($courseId);

            $sql = "SELECT * FROM c_course_description WHERE c_id = $courseId
                    ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            foreach ($items as $itemData) {
                $id = $itemData['iid'];
                /** @var CCourseDescription $event */
                $resource = $courseDescriptionRepo->find($id);
                if ($resource->hasResourceNode()) {
                    continue;
                }
                $sql = "SELECT * FROM c_item_property
                        WHERE tool = 'course_description' AND c_id = $courseId AND ref = $id";
                $result = $connection->executeQuery($sql);
                $items = $result->fetchAllAssociative();

                // For some reason this event doesnt have a c_item_property value, then we added to the main course.
                if (empty($items)) {
                    continue;
                }
                $this->fixItemProperty($courseDescriptionRepo, $course, $admin, $resource, $course, $items);
                $em->persist($resource);
                $em->flush();
            }
        }
    }
}
