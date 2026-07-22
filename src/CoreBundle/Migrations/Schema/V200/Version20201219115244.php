<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CWiki;
use Chamilo\CourseBundle\Repository\CWikiRepository;
use Doctrine\DBAL\Schema\Schema;

final class Version20201219115244 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate c_wiki using prefetched item properties and batched ORM writes';
    }

    public function up(Schema $schema): void
    {
        $wikiRepo = $this->container->get(CWikiRepository::class);
        $courseRepo = $this->container->get(CourseRepository::class);
        $admin = $this->getAdmin();

        $rows = $this->connection->fetchAllAssociative(
            'SELECT iid, c_id
             FROM c_wiki
             WHERE resource_node_id IS NULL
             ORDER BY c_id, iid'
        );

        $rowsByCourse = [];
        foreach ($rows as $row) {
            $courseId = (int) ($row['c_id'] ?? 0);
            if ($courseId > 0) {
                $rowsByCourse[$courseId][] = (int) $row['iid'];
            }
        }

        $migrated = 0;
        $skipped = 0;

        foreach ($rowsByCourse as $courseId => $ids) {
            $course = $courseRepo->find($courseId);
            if (null === $course) {
                $skipped += \count($ids);
                $this->warnIf(true, "Course {$courseId} not found while migrating wiki resources.");

                continue;
            }

            $itemProperties = $this->fetchItemPropertiesMap('wiki', $courseId, $ids);

            foreach ($ids as $id) {
                /** @var CWiki|null $resource */
                $resource = $wikiRepo->find($id);
                if (null === $resource || $resource->hasResourceNode()) {
                    continue;
                }

                if (false === $this->fixItemProperty(
                    'wiki',
                    $wikiRepo,
                    $course,
                    $admin,
                    $resource,
                    $course,
                    $itemProperties[$id] ?? []
                )) {
                    ++$skipped;

                    continue;
                }

                ++$migrated;
            }

            $this->entityManager->flush();
        }

        $this->getLogger()->info('Wiki migration completed.', [
            'migrated' => $migrated,
            'skipped' => $skipped,
        ]);
    }
}
