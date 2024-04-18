<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CCourseDescription;
use Chamilo\CourseBundle\Repository\CCourseDescriptionRepository;
use Doctrine\DBAL\Schema\Schema;

final class Version20201215135838 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate c_course_description';
    }

    public function up(Schema $schema): void
    {
        $courseDescriptionRepo = $this->container->get(CCourseDescriptionRepository::class);
        $courseRepo = $this->container->get(CourseRepository::class);

        $admin = $this->getAdmin();
        $q = $this->entityManager->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');

        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $counter = 1;
            $courseId = $course->getId();
            $course = $courseRepo->find($courseId);

            $sql = "SELECT * FROM c_course_description WHERE c_id = {$courseId}
                    ORDER BY iid";
            $result = $this->connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            foreach ($items as $itemData) {
                $id = $itemData['iid'];

                /** @var CCourseDescription $resource */
                $resource = $courseDescriptionRepo->find($id);
                if ($resource->hasResourceNode()) {
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
                $this->entityManager->flush();
            }
        }
    }
}
