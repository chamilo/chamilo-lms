<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

final class Version20230615213500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate c_lp to resource node position';
    }

    public function up(Schema $schema): void
    {
        $container = $this->getContainer();
        $doctrine = $container->get('doctrine');
        $em = $doctrine->getManager();
        /** @var Connection $connection */
        $connection = $em->getConnection();

        $lpRepo = $container->get(CLpRepository::class);
        $courseRepo = $container->get(CourseRepository::class);

        $q = $em->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');
        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $courseId = $course->getId();

            $sql = "SELECT * FROM c_lp WHERE c_id = {$courseId} ORDER BY display_order";
            $result = $connection->executeQuery($sql);
            $lps = $result->fetchAllAssociative();

            foreach ($lps as $lp) {
                $lpId = (int) $lp['iid'];
                $position = (int) $lp['display_order'];

                /** @var CLp $resource */
                $resource = $lpRepo->find($lpId);
                if ($resource->hasResourceNode()) {
                    $resourceNode = $resource->getResourceNode();
                    $resourceNodeId = $resourceNode->getId();

                    $item = $em->find(ResourceNode::class, $resourceNodeId);
                    if ($item) {
                        $item->setDisplayOrder($position);
                        $em->persist($item);
                        $em->flush();
                    }
                }
            }
        }
    }
}
