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
    private const int WIKI_BATCH_SIZE = 100;
    public function getDescription(): string
    {
        return 'Migrate c_wiki using prefetched item properties and batched ORM writes';
    }

    public function up(Schema $schema): void
    {
        $wikiRepo = $this->container->get(CWikiRepository::class);
        $courseRepo = $this->container->get(CourseRepository::class);

        $courseIds = $this->connection->fetchFirstColumn(
            'SELECT DISTINCT c_id
             FROM c_wiki
             WHERE resource_node_id IS NULL
               AND c_id > 0
             ORDER BY c_id'
        );

        $migrated = 0;
        $skipped = 0;

        foreach ($courseIds as $courseIdValue) {
            $courseId = (int) $courseIdValue;

            $courseExists = false !== $this->connection->fetchOne(
                'SELECT id FROM course WHERE id = :courseId',
                ['courseId' => $courseId]
            );

            if (!$courseExists) {
                $pending = (int) $this->connection->fetchOne(
                    'SELECT COUNT(*)
                     FROM c_wiki
                     WHERE c_id = :courseId
                       AND resource_node_id IS NULL',
                    ['courseId' => $courseId]
                );

                $skipped += $pending;

                $this->warnIf(
                    true,
                    "Course {$courseId} not found while migrating wiki resources."
                );

                continue;
            }

            $lastIid = 0;

            while (true) {
                $ids = $this->connection->fetchFirstColumn(
                    \sprintf(
                        'SELECT iid
                         FROM c_wiki
                         WHERE c_id = :courseId
                           AND resource_node_id IS NULL
                           AND iid > :lastIid
                         ORDER BY iid
                         LIMIT %d',
                        self::WIKI_BATCH_SIZE
                    ),
                    [
                        'courseId' => $courseId,
                        'lastIid' => $lastIid,
                    ]
                );

                if ([] === $ids) {
                    break;
                }

                $ids = array_map('intval', $ids);
                $lastIid = $ids[array_key_last($ids)];

                // Keep each ORM batch isolated. Large legacy databases can
                // otherwise accumulate a very large Doctrine identity map.
                $this->entityManager->clear();
                gc_collect_cycles();

                $course = $courseRepo->find($courseId);

                if (null === $course) {
                    $skipped += \count($ids);

                    continue;
                }

                $admin = $this->getAdmin();

                $itemProperties = $this->fetchItemPropertiesMap(
                    'wiki',
                    $courseId,
                    $ids
                );

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
                        $itemProperties[$id] ?? [],
                        synchronizeInverseCollections: false
                    )) {
                        ++$skipped;

                        continue;
                    }

                    ++$migrated;
                }

                $this->entityManager->flush();
                $this->entityManager->clear();
                gc_collect_cycles();

                unset($ids, $itemProperties);
            }
        }

        $this->entityManager->clear();
        gc_collect_cycles();

        $this->getLogger()->info('Wiki migration completed.', [
            'migrated' => $migrated,
            'skipped' => $skipped,
        ]);
    }
}
