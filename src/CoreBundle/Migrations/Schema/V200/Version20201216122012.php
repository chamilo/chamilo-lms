<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Repository\CLpCategoryRepository;
use Chamilo\CourseBundle\Repository\CLpItemRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Doctrine\DBAL\Schema\Schema;

final class Version20201216122012 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate c_lp, c_lp_category to resource node';
    }

    public function up(Schema $schema): void
    {
        $lpCategoryRepo = $this->container->get(CLpCategoryRepository::class);
        $lpRepo = $this->container->get(CLpRepository::class);
        $courseRepo = $this->container->get(CourseRepository::class);
        $lpItemRepo = $this->container->get(CLpItemRepository::class);

        $admin = $this->getAdmin();

        $q = $this->entityManager->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');

        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $counter = 1;

            $courseId = $course->getId();
            $course = $courseRepo->find($courseId);

            // c_lp_category.
            $sql = "SELECT * FROM c_lp_category WHERE c_id = {$courseId}
                    ORDER BY iid";
            $result = $this->connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            foreach ($items as $itemData) {
                $id = $itemData['iid'];

                /** @var CLpCategory $resource */
                $resource = $lpCategoryRepo->find($id);
                if ($resource->hasResourceNode()) {
                    continue;
                }

                $result = $this->fixItemProperty(
                    'learnpath_category',
                    $lpCategoryRepo,
                    $course,
                    $admin,
                    $resource,
                    $course
                );

                if (false === $result) {
                    continue;
                }

                $this->entityManager->persist($resource);

                if ($counter % self::BATCH_SIZE === 0) {
                    $this->entityManager->flush();
                }

                $counter++;
            }

            $this->entityManager->flush();

            $counter = 1;

            $sql = "SELECT * FROM c_lp WHERE c_id = {$courseId}
                    ORDER BY iid";
            $result = $this->connection->executeQuery($sql);
            $lps = $result->fetchAllAssociative();

            $course = $courseRepo->find($courseId);
            $admin = $this->getAdmin();

            foreach ($lps as $lp) {
                $lpId = (int) $lp['iid'];

                /** @var CLp $resource */
                $resource = $lpRepo->find($lpId);
                if ($resource->hasResourceNode()) {
                    continue;
                }

                $result = $this->fixItemProperty(
                    'learnpath',
                    $lpRepo,
                    $course,
                    $admin,
                    $resource,
                    $course
                );

                if (false === $result) {
                    continue;
                }

                $this->entityManager->persist($resource);

                $rootItem = $lpItemRepo->getRootItem($lpId);

                if (null !== $rootItem) {
                    continue;
                }

                $rootItem = (new CLpItem())
                    ->setTitle('root')
                    ->setPath('root')
                    ->setLp($resource)
                    ->setItemType('root')
                ;
                $this->entityManager->persist($rootItem);

                if ($counter % self::BATCH_SIZE === 0) {
                    $this->entityManager->flush();
                }

                $counter++;
            }

            $this->entityManager->flush();
        }
    }
}
