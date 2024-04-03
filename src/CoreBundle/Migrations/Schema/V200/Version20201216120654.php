<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CGlossary;
use Chamilo\CourseBundle\Repository\CGlossaryRepository;
use Doctrine\DBAL\Schema\Schema;

final class Version20201216120654 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate c_glossary';
    }

    public function up(Schema $schema): void
    {
        $em = $this->getEntityManager();

        $connection = $em->getConnection();

        $glossaryRepo = $this->container->get(CGlossaryRepository::class);
        $courseRepo = $this->container->get(CourseRepository::class);

        $admin = $this->getAdmin();

        $q = $em->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');

        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $courseId = $course->getId();
            $course = $courseRepo->find($courseId);

            // Glossary.
            $sql = "SELECT * FROM c_glossary WHERE c_id = {$courseId}
                    ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            foreach ($items as $itemData) {
                $id = $itemData['iid'];

                /** @var CGlossary $resource */
                $resource = $glossaryRepo->find($id);
                if ($resource->hasResourceNode()) {
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

                $em->persist($resource);
                $em->flush();
            }
        }
    }
}
