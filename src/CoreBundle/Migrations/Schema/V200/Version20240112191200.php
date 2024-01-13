<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CourseBundle\Repository\CGlossaryRepository;
use Chamilo\CourseBundle\Repository\CGroupCategoryRepository;
use Chamilo\CourseBundle\Repository\CLinkCategoryRepository;
use Chamilo\CourseBundle\Repository\CLinkRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

final class Version20240112191200 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate tool display_order as resource order';
    }

    public function up(Schema $schema): void
    {
        $container = $this->getContainer();
        $doctrine = $container->get('doctrine');
        $em = $doctrine->getManager();

        /** @var Connection $connection */
        $connection = $em->getConnection();

        $linkCategoryRepo = $container->get(CLinkCategoryRepository::class);
        $linkRepo = $container->get(CLinkRepository::class);
        $groupCategoryRepo = $container->get(CGroupCategoryRepository::class);
        $glossaryRepo = $container->get(CGlossaryRepository::class);

        $this->updateResourceNodeDisplayOrder($linkCategoryRepo, 'c_link_category', $em);
        $this->updateResourceNodeDisplayOrder($linkRepo, 'c_link', $em);
        $this->updateResourceNodeDisplayOrder($groupCategoryRepo, 'c_group_category', $em);
        $this->updateResourceNodeDisplayOrder($glossaryRepo, 'c_glossary', $em);

    }

    private function updateResourceNodeDisplayOrder($resourceRepo, $tableName, $em) {

        /** @var Connection $connection */
        $connection = $em->getConnection();

        try {
            $testResult = $connection->executeQuery("SELECT display_order FROM $tableName LIMIT 1");
            $columnExists = true;
        } catch (\Exception $e) {
            $columnExists = false;
        }

        if ($columnExists) {
            $sql = "SELECT * FROM $tableName ORDER BY display_order";
            $result = $connection->executeQuery($sql);
            $resources = $result->fetchAllAssociative();

            foreach ($resources as $resourceData) {
                $resourceId = (int) $resourceData['iid'];
                $resourcePosition = (int) $resourceData['display_order'];

                $resource = $resourceRepo->find($resourceId);
                if ($resource && $resource->hasResourceNode()) {
                    $resourceNode = $resource->getResourceNode();
                    if ($resourceNode) {
                        $resourceNode->setDisplayOrder($resourcePosition);
                        $em->persist($resourceNode);
                    }
                }
            }

            $em->flush();
        }
    }
}
