<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Repository\CGroupRepository;
use Chamilo\CourseBundle\Repository\CLpCategoryRepository;
use Chamilo\CourseBundle\Repository\CLpItemRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

final class Version20201216122012 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate c_lp, c_lp_category';
    }

    public function up(Schema $schema): void
    {
        $container = $this->getContainer();
        $doctrine = $container->get('doctrine');
        $em = $doctrine->getManager();
        /** @var Connection $connection */
        $connection = $em->getConnection();

        $lpCategoryRepo = $container->get(CLpCategoryRepository::class);
        $lpRepo = $container->get(CLpRepository::class);

        $courseRepo = $container->get(CourseRepository::class);
        $sessionRepo = $container->get(SessionRepository::class);
        $groupRepo = $container->get(CGroupRepository::class);
        $userRepo = $container->get(UserRepository::class);
        $lpItemRepo = $container->get(CLpItemRepository::class);

        $batchSize = self::BATCH_SIZE;
        $admin = $this->getAdmin();

        $q = $em->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');
        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $courseId = $course->getId();
            $course = $courseRepo->find($courseId);

            // c_lp_category.
            $sql = "SELECT * FROM c_lp_category WHERE c_id = {$courseId}
                    ORDER BY iid";
            $result = $connection->executeQuery($sql);
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

                $em->persist($resource);
                $em->flush();
            }

            $em->flush();
            $em->clear();

            // AND iid = 242 with error
            // c_lp.
            $sql = "SELECT * FROM c_lp WHERE c_id = {$courseId}
                    ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $lps = $result->fetchAllAssociative();
            $counter = 1;

            $course = $courseRepo->find($courseId);
            $admin = $this->getAdmin();

            foreach ($lps as $lp) {
                $lpId = (int) $lp['iid'];
                error_log("LP #$lpId");

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

                $em->persist($resource);
                //$em->flush();

                $rootItem = $lpItemRepo->getRootItem($lpId);

                if (null === $rootItem) {
                    continue;
                }

                $lpItem = new CLpItem();
                $lpItem
                    ->setTitle('root')
                    ->setPath('root')
                    ->setLp($resource)
                    ->setItemType('root');
                $em->persist($lpItem);

                // Migrate c_lp_item
                $sql = "SELECT * FROM c_lp_item WHERE lp_id = $lpId AND path <> 'root'
                        ORDER BY display_order";
                $resultItems = $connection->executeQuery($sql);
                $lpItems = $resultItems->fetchAllAssociative();
                $orderList = [];
                foreach ($lpItems as $item) {
                    var_dump($item);
                    $object = new \stdClass();
                    $object->id = $item['iid'];
                    $object->parent_id = (int) $item['parent_item_id'];
                    $orderList[] = $object;
                }

                \learnpath::sortItemByOrderList($lpItem, $orderList, false);

                if (0 === $counter % $batchSize) {
                    $em->flush();
                    $em->clear(); // Detaches all objects from Doctrine!
                }
                $counter++;
                $em->flush();
            }
        }
    }
}
