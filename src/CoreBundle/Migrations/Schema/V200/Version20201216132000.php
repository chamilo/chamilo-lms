<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Repository\CLpItemRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use learnpath;
use stdClass;

final class Version20201216132000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate c_lp_item to new order';
    }

    public function up(Schema $schema): void
    {
        $container = $this->getContainer();
        $doctrine = $container->get('doctrine');
        $em = $doctrine->getManager();
        /** @var Connection $connection */
        $connection = $em->getConnection();

        $lpItemRepo = $container->get(CLpItemRepository::class);

        $batchSize = self::BATCH_SIZE;
        //$q = $em->createQuery('SELECT lp FROM Chamilo\CourseBundle\Entity\CLp lp WHERE lp.iid = 263 ORDER BY lp.iid');
        $q = $em->createQuery('SELECT lp FROM Chamilo\CourseBundle\Entity\CLp lp');
        $counter = 1;
        /** @var CLp $lp */
        foreach ($q->toIterable() as $lp) {
            $lpId = $lp->getIid();
            error_log("LP #$lpId");

            /** @var CLp $resource */
            //$resource = $lpRepo->find($lpId);
            if (!$lp->hasResourceNode()) {
                error_log('no resource node');

                continue;
            }

            // Root item is created in the previous Migration.
            $rootItem = $lpItemRepo->getRootItem($lpId);

            if (null === $rootItem) {
                continue;
            }

            // Migrate c_lp_item
            $sql = "SELECT * FROM c_lp_item WHERE lp_id = $lpId AND path <> 'root'
                    ORDER BY display_order";
            $resultItems = $connection->executeQuery($sql);
            $lpItems = $resultItems->fetchAllAssociative();

            if (empty($lpItems)) {

                continue;
            }

            $orderList = [];
            foreach ($lpItems as $item) {
                $object = new stdClass();
                $object->id = $item['iid'];
                $object->parent_id = (int) $item['parent_item_id'];
                $orderList[] = $object;
            }

            learnpath::sortItemByOrderList($rootItem, $orderList, true);
            if (($counter % $batchSize) === 0) {
                $em->flush();
                $em->clear(); // Detaches all objects from Doctrine!
            }
            $counter++;
            $em->flush();
        }

        $em->flush();
        $em->clear();
    }
}
