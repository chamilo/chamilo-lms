<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CourseBundle\Repository\CAnnouncementRepository;
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
        $em = $this->getEntityManager();

        $linkCategoryRepo = $this->container->get(CLinkCategoryRepository::class);
        $linkRepo = $this->container->get(CLinkRepository::class);
        $groupCategoryRepo = $this->container->get(CGroupCategoryRepository::class);
        $glossaryRepo = $this->container->get(CGlossaryRepository::class);
        $announcementRepo = $this->container->get(CAnnouncementRepository::class);

        $this->updateResourceNodeDisplayOrder($linkCategoryRepo, 'c_link_category', $em, $schema);
        $this->updateResourceNodeDisplayOrder($linkRepo, 'c_link', $em, $schema);
        $this->updateResourceNodeDisplayOrder($groupCategoryRepo, 'c_group_category', $em, $schema);
        $this->updateResourceNodeDisplayOrder($glossaryRepo, 'c_glossary', $em, $schema);
        $this->updateResourceNodeDisplayOrder($announcementRepo, 'c_announcement', $em, $schema);
    }

    private function updateResourceNodeDisplayOrder($resourceRepo, $tableName, $em, Schema $schema): void
    {
        /** @var Connection $connection */
        $connection = $em->getConnection();

        $table = $schema->getTable($tableName);

        if (!$table->hasColumn('display_order')) {
            return;
        }

        $sql = "SELECT * FROM $tableName ORDER BY display_order";
        $result = $connection->executeQuery($sql);
        $resources = $result->fetchAllAssociative();

        foreach ($resources as $resourceData) {
            $resourceId = (int) $resourceData['iid'];
            $resourcePosition = (int) $resourceData['display_order'];

            /** @var AbstractResource $resource */
            $resource = $resourceRepo->find($resourceId);

            if (!$resource || !$resource->hasResourceNode()) {
                continue;
            }

            $resourceNode = $resource->getResourceNode();

            if ($resourceNode) {
                $course = $this->findCourse((int) $resourceData['c_id']);
                $session = $this->findSession((int) ($resourceData['session_id'] ?? 0));

                $link = $resourceNode->getResourceLinkByContext($course, $session);

                $link?->setDisplayOrder(
                    $resourcePosition > 0 ? $resourcePosition - 1 : 0
                );
            }
        }

        $em->flush();
    }
}
