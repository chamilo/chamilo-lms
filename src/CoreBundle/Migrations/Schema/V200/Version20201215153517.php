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
    private const ANNOUNCEMENT_BATCH_SIZE = 250;
    private const ATTACHMENT_BATCH_SIZE = 50;

    public function getDescription(): string
    {
        return 'Migrate c_announcement, c_announcement_attachment';
    }

    public function up(Schema $schema): void
    {
        $announcementRepo = $this->container->get(CAnnouncementRepository::class);
        $announcementAttachmentRepo = $this->container->get(CAnnouncementAttachmentRepository::class);
        $admin = $this->getAdmin();

        $pendingAnnouncements = 0;
        $attachmentBatch = [];

        $query = $this->entityManager->createQuery(
            'SELECT c FROM Chamilo\CoreBundle\Entity\Course c'
        );

        /** @var Course $course */
        foreach ($query->toIterable() as $course) {
            $courseId = $course->getId();

            $result = $this->connection->executeQuery(
                'SELECT iid
                   FROM c_announcement
                  WHERE c_id = :courseId
                  ORDER BY iid',
                ['courseId' => $courseId]
            );

            while (false !== ($itemData = $result->fetchAssociative())) {
                $resource = $announcementRepo->find((int) $itemData['iid']);

                if (null === $resource || $resource->hasResourceNode()) {
                    continue;
                }

                $fixed = $this->fixItemProperty(
                    'announcement',
                    $announcementRepo,
                    $course,
                    $admin,
                    $resource,
                    $course
                );

                if (false === $fixed) {
                    continue;
                }

                $this->entityManager->persist($resource);
                ++$pendingAnnouncements;

                if ($pendingAnnouncements >= self::ANNOUNCEMENT_BATCH_SIZE) {
                    $this->entityManager->flush();
                    $pendingAnnouncements = 0;
                }
            }

            $result = $this->connection->executeQuery(
                'SELECT iid, path, filename
                   FROM c_announcement_attachment
                  WHERE c_id = :courseId
                  ORDER BY iid',
                ['courseId' => $courseId]
            );

            while (false !== ($itemData = $result->fetchAssociative())) {
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
                    $course
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

                if (\count($attachmentBatch) >= self::ATTACHMENT_BATCH_SIZE) {
                    $this->flushAttachmentBatch(
                        $attachmentBatch,
                        $announcementAttachmentRepo
                    );
                }
            }
        }

        if ($pendingAnnouncements > 0) {
            $this->entityManager->flush();
        }

        if ([] !== $attachmentBatch) {
            $this->flushAttachmentBatch(
                $attachmentBatch,
                $announcementAttachmentRepo
            );
        }
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
}
