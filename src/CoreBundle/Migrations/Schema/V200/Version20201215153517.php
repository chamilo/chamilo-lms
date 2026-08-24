<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CourseBundle\Entity\CAnnouncementAttachment;
use Chamilo\CourseBundle\Repository\CAnnouncementAttachmentRepository;
use Chamilo\CourseBundle\Repository\CAnnouncementRepository;
use Doctrine\DBAL\Schema\Schema;

final class Version20201215153517 extends AbstractMigrationChamilo
{
    private const int ANNOUNCEMENT_BATCH_SIZE = 250;
    private const int ATTACHMENT_BATCH_SIZE = 50;

    public function getDescription(): string
    {
        return 'Migrate c_announcement, c_announcement_attachment';
    }

    public function up(Schema $schema): void
    {
        $announcementRepo = $this->container->get(CAnnouncementRepository::class);
        $announcementAttachmentRepo = $this->container->get(CAnnouncementAttachmentRepository::class);

        // Previous large migrations can leave a sizeable Doctrine identity map
        // in the same migrations process. Start this migration from a bounded
        // ORM state and keep clearing it between explicit batches.
        $this->releaseBatchState();

        $courseIds = $this->connection->fetchFirstColumn(
            'SELECT id FROM course ORDER BY id'
        );

        foreach ($courseIds as $courseIdValue) {
            $courseId = (int) $courseIdValue;

            $announcementIds = $this->connection->fetchFirstColumn(
                'SELECT iid
                   FROM c_announcement
                  WHERE c_id = :courseId
                  ORDER BY iid',
                ['courseId' => $courseId]
            );

            foreach (array_chunk($announcementIds, self::ANNOUNCEMENT_BATCH_SIZE) as $idBatch) {
                $this->releaseBatchState();

                $course = $this->entityManager->find(Course::class, $courseId);

                if (!$course instanceof Course) {
                    continue 2;
                }

                $admin = $this->getAdmin();

                foreach ($idBatch as $idValue) {
                    $resource = $announcementRepo->find((int) $idValue);

                    if (null === $resource || $resource->hasResourceNode()) {
                        continue;
                    }

                    $fixed = $this->fixItemProperty(
                        'announcement',
                        $announcementRepo,
                        $course,
                        $admin,
                        $resource,
                        $course,
                        synchronizeInverseCollections: false
                    );

                    if (false === $fixed) {
                        continue;
                    }

                    $this->entityManager->persist($resource);
                }

                $this->entityManager->flush();
            }

            unset($announcementIds);

            $attachmentRows = $this->connection->fetchAllAssociative(
                'SELECT iid, path, filename
                   FROM c_announcement_attachment
                  WHERE c_id = :courseId
                  ORDER BY iid',
                ['courseId' => $courseId]
            );

            foreach (array_chunk($attachmentRows, self::ATTACHMENT_BATCH_SIZE) as $rowBatch) {
                $this->releaseBatchState();

                $course = $this->entityManager->find(Course::class, $courseId);

                if (!$course instanceof Course) {
                    continue 2;
                }

                $admin = $this->getAdmin();
                $attachmentBatch = [];

                foreach ($rowBatch as $itemData) {
                    $id = (int) $itemData['iid'];

                    /** @var CAnnouncementAttachment|null $resource */
                    $resource = $announcementAttachmentRepo->find($id);

                    if (null === $resource || $resource->hasResourceNode()) {
                        continue;
                    }

                    $fixed = $this->fixItemProperty(
                        'announcement_attachment',
                        $announcementAttachmentRepo,
                        $course,
                        $admin,
                        $resource,
                        $course,
                        synchronizeInverseCollections: false
                    );

                    if (false === $fixed) {
                        continue;
                    }

                    $this->entityManager->persist($resource);

                    $attachmentBatch[] = [
                        'resource' => $resource,
                        'id' => $id,
                        'fileName' => (string) $itemData['filename'],
                        'filePath' => $this->getUpdateRootPath()
                            .'/app/courses/'
                            .$course->getDirectory()
                            .'/upload/announcements/'
                            .$itemData['path'],
                    ];
                }

                if ([] !== $attachmentBatch) {
                    $this->flushAttachmentBatch(
                        $attachmentBatch,
                        $announcementAttachmentRepo
                    );
                }
            }

            unset($attachmentRows);

            $this->releaseBatchState();
        }

        $this->releaseBatchState();
    }

    /**
     * Resource nodes must be flushed before files are attached. The second
     * flush persists all files from the batch instead of flushing per file.
     */
    private function flushAttachmentBatch(
        array &$attachmentBatch,
        CAnnouncementAttachmentRepository $announcementAttachmentRepo
    ): void {
        $this->entityManager->flush();

        foreach ($attachmentBatch as $item) {
            error_log('MIGRATIONS :: $filePath -- '.$item['filePath'].' ...');

            $this->addLegacyFileToResource(
                $item['filePath'],
                $announcementAttachmentRepo,
                $item['resource'],
                $item['id'],
                $item['fileName']
            );

            $this->entityManager->persist($item['resource']);
        }

        $this->entityManager->flush();
        $attachmentBatch = [];
    }

    private function releaseBatchState(): void
    {
        $this->entityManager->clear();
        gc_collect_cycles();
    }
}
