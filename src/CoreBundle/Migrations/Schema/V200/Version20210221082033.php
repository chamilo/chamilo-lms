<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Doctrine\DBAL\Schema\Schema;

final class Version20210221082033 extends AbstractMigrationChamilo
{
    private const SELECT_BATCH_SIZE = 200;
    private const FILE_FLUSH_BATCH_SIZE = 25;

    public function getDescription(): string
    {
        return 'Migrate c_lp images with DBAL candidate batches and batched file flushes';
    }

    public function up(Schema $schema): void
    {
        /** @var CLpRepository $lpRepo */
        $lpRepo = $this->container->get(CLpRepository::class);

        $lastIid = 0;
        $seen = 0;
        $migrated = 0;
        $missing = 0;
        $pendingFlush = 0;

        while (true) {
            $rows = $this->connection->fetchAllAssociative(
                \sprintf(<<<'SQL'
SELECT
    lp.iid,
    lp.preview_image,
    lp.resource_node_id,
    course.directory
FROM c_lp lp
INNER JOIN course
    ON course.id = lp.c_id
WHERE lp.iid > :lastIid
  AND lp.resource_node_id IS NOT NULL
  AND lp.preview_image IS NOT NULL
  AND TRIM(lp.preview_image) <> ''
  AND NOT EXISTS (
      SELECT 1
      FROM resource_file rf
      WHERE rf.resource_node_id = lp.resource_node_id
        AND rf.original_name = lp.preview_image
  )
ORDER BY lp.iid
LIMIT %d
SQL, self::SELECT_BATCH_SIZE),
                ['lastIid' => $lastIid]
            );

            if ([] === $rows) {
                break;
            }

            $lastIid = (int) $rows[array_key_last($rows)]['iid'];

            foreach ($rows as $row) {
                ++$seen;
                $iid = (int) $row['iid'];
                $path = trim((string) $row['preview_image']);
                $filePath = $this->getUpdateRootPath().'/app/courses/'.(string) $row['directory'].'/upload/learning_path/images/'.$path;

                if (!$this->fileExists($filePath)) {
                    ++$missing;
                    $this->warnIf(true, "Learning path image {$iid} not found: {$filePath}");

                    continue;
                }

                $lp = $lpRepo->find($iid);
                if (!$lp instanceof CLp || !$lp->hasResourceNode()) {
                    continue;
                }

                if ($this->addLegacyFileToResource($filePath, $lpRepo, $lp, $iid, $path)) {
                    $this->entityManager->persist($lp);
                    ++$migrated;
                    ++$pendingFlush;
                }

                if ($pendingFlush >= self::FILE_FLUSH_BATCH_SIZE) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                    $pendingFlush = 0;
                }
            }

            $this->getLogger()->info('Learning path image migration progress.', [
                'seen' => $seen,
                'migrated' => $migrated,
                'missing' => $missing,
                'last_iid' => $lastIid,
            ]);
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }
}
