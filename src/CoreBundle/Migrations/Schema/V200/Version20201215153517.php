<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CAnnouncementAttachment;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Repository\CAnnouncementAttachmentRepository;
use Chamilo\CourseBundle\Repository\CAnnouncementRepository;
use Doctrine\DBAL\Schema\Schema;

final class Version20201215153517 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate c_announcement, c_announcement_attachment';
    }

    public function up(Schema $schema): void
    {
        $announcementRepo = $this->container->get(CAnnouncementRepository::class);
        $announcementAttachmentRepo = $this->container->get(CAnnouncementAttachmentRepository::class);
        $courseRepo = $this->container->get(CourseRepository::class);

        $kernel = $this->container->get('kernel');
        $rootPath = $kernel->getProjectDir();
        $admin = $this->getAdmin();

        $q = $this->entityManager->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');

        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $courseId = $course->getId();
            $course = $courseRepo->find($courseId);

            $sql = "SELECT * FROM c_announcement WHERE c_id = {$courseId}
                    ORDER BY iid";
            $result = $this->connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            foreach ($items as $itemData) {
                $id = $itemData['iid'];

                /** @var CQuiz $resource */
                $resource = $announcementRepo->find($id);
                if ($resource->hasResourceNode()) {
                    continue;
                }

                $result = $this->fixItemProperty(
                    'announcement',
                    $announcementRepo,
                    $course,
                    $admin,
                    $resource,
                    $course
                );

                if (false === $result) {
                    continue;
                }

                $this->entityManager->persist($resource);
                $this->entityManager->flush();
            }

            $sql = "SELECT * FROM c_announcement_attachment WHERE c_id = {$courseId}
                    ORDER BY iid";
            $result = $this->connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            foreach ($items as $itemData) {
                $id = $itemData['iid'];
                $path = $itemData['path'];
                $fileName = $itemData['filename'];

                /** @var CAnnouncementAttachment $resource */
                $resource = $announcementAttachmentRepo->find($id);
                if ($resource->hasResourceNode()) {
                    continue;
                }
                $result = $this->fixItemProperty(
                    'announcement_attachment',
                    $announcementAttachmentRepo,
                    $course,
                    $admin,
                    $resource,
                    $course
                );

                if (false === $result) {
                    continue;
                }

                $this->entityManager->persist($resource);
                $this->entityManager->flush();

                $filePath = $this->getUpdateRootPath().'/app/courses/'.$course->getDirectory().'/upload/announcements/'.$path;
                error_log('MIGRATIONS :: $filePath -- '.$filePath.' ...');
                $this->addLegacyFileToResource($filePath, $announcementAttachmentRepo, $resource, $id, $fileName);
                $this->entityManager->persist($resource);
                $this->entityManager->flush();
            }
        }
    }
}
