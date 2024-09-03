<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Repository\CSurveyRepository;
use Doctrine\DBAL\Schema\Schema;

final class Version20201218132719 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate c_survey';
    }

    public function up(Schema $schema): void
    {
        $surveyRepo = $this->container->get(CSurveyRepository::class);
        $courseRepo = $this->container->get(CourseRepository::class);

        $q = $this->entityManager->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');

        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $courseId = $course->getId();
            $course = $courseRepo->find($courseId);

            $sql = "SELECT * FROM c_survey WHERE c_id = {$courseId} ORDER BY iid";
            $result = $this->connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            foreach ($items as $itemData) {
                $id = $itemData['iid'];

                /** @var CSurvey $resource */
                $resource = $surveyRepo->find($id);
                if ($resource->hasResourceNode()) {
                    continue;
                }

                $admin = $this->getAdmin();

                $result = $this->fixItemProperty(
                    'survey',
                    $surveyRepo,
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

            $this->entityManager->flush();
        }
    }
}
