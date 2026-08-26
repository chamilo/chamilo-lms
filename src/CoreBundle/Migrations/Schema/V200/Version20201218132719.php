<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Repository\CSurveyRepository;
use Doctrine\DBAL\Schema\Schema;

final class Version20201218132719 extends AbstractMigrationChamilo
{
    private const int SURVEY_BATCH_SIZE = 100;

    public function getDescription(): string
    {
        return 'Migrate c_survey using prefetched item properties and batched ORM writes';
    }

    public function up(Schema $schema): void
    {
        $surveyRepo = $this->container->get(CSurveyRepository::class);
        $courseRepo = $this->container->get(CourseRepository::class);

        // Previous resource migrations can leave a large Doctrine identity map.
        // Start this migration with a bounded ORM state.
        $this->releaseBatchState();

        $rows = $this->connection->fetchAllAssociative(
            'SELECT iid, c_id
             FROM c_survey
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

        unset($rows);

        $migrated = 0;
        $skipped = 0;

        foreach ($rowsByCourse as $courseId => $ids) {
            foreach (array_chunk($ids, self::SURVEY_BATCH_SIZE) as $idBatch) {
                $this->releaseBatchState();

                $course = $courseRepo->find($courseId);

                if (null === $course) {
                    $skipped += \count($idBatch);
                    $this->warnIf(true, "Course {$courseId} not found while migrating surveys.");

                    continue;
                }

                $admin = $this->getAdmin();

                $itemProperties = $this->fetchItemPropertiesMap(
                    'survey',
                    $courseId,
                    $idBatch
                );

                foreach ($idBatch as $id) {
                    /** @var CSurvey|null $resource */
                    $resource = $surveyRepo->find($id);

                    if (null === $resource || $resource->hasResourceNode()) {
                        continue;
                    }

                    if (false === $this->fixItemProperty(
                        'survey',
                        $surveyRepo,
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

                unset($itemProperties);
                $this->releaseBatchState();
            }
        }

        $this->getLogger()->info('Survey migration completed.', [
            'migrated' => $migrated,
            'skipped' => $skipped,
        ]);

        $this->releaseBatchState();
    }

    private function releaseBatchState(): void
    {
        $this->entityManager->clear();
        gc_collect_cycles();
    }
}
