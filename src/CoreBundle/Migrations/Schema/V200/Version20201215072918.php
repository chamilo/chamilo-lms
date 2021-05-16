<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Chamilo\CourseBundle\Entity\CCalendarEventAttachment;
use Chamilo\CourseBundle\Repository\CCalendarEventAttachmentRepository;
use Chamilo\CourseBundle\Repository\CCalendarEventRepository;
use Chamilo\CourseBundle\Repository\CGroupRepository;
use Chamilo\Kernel;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

final class Version20201215072918 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate c_calendar_event, calendar_event_attachment';
    }

    public function up(Schema $schema): void
    {
        $container = $this->getContainer();
        $doctrine = $container->get('doctrine');
        $em = $doctrine->getManager();
        /** @var Connection $connection */
        $connection = $em->getConnection();

        $eventRepo = $container->get(CCalendarEventRepository::class);
        $eventAttachmentRepo = $container->get(CCalendarEventAttachmentRepository::class);
        $courseRepo = $container->get(CourseRepository::class);

        /** @var Kernel $kernel */
        $kernel = $container->get('kernel');
        $rootPath = $kernel->getProjectDir();
        $admin = $this->getAdmin();

        $q = $em->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');
        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $courseId = $course->getId();
            $course = $courseRepo->find($courseId);

            $sql = "SELECT * FROM c_calendar_event WHERE c_id = {$courseId}
                    ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $events = $result->fetchAllAssociative();
            foreach ($events as $eventData) {
                $id = $eventData['iid'];
                /** @var CCalendarEvent $event */
                $event = $eventRepo->find($id);
                if ($event->hasResourceNode()) {
                    continue;
                }

                $sql = "SELECT * FROM c_item_property
                        WHERE tool = 'calendar_event' AND c_id = {$courseId} AND ref = {$id}";
                $result = $connection->executeQuery($sql);
                $items = $result->fetchAllAssociative();

                // For some reason this event doesnt have a c_item_property value,
                // then we added to the main course and assign the admin as the creator.
                if (empty($items)) {
                    $items[] = [
                        'visibility' => 1,
                        'insert_user_id' => $admin->getId(),
                        'to_group_id' => 0,
                        'session_id' => $eventData['session_id'],
                    ];
                    $this->fixItemProperty('calendar_event', $eventRepo, $course, $admin, $event, $course, $items);
                    $em->persist($event);
                    $em->flush();

                    continue;
                }

                // Assign parent.
                $parent = null;
                if (!empty($eventData['parent_event_id'])) {
                    $parent = $eventRepo->find($eventData['parent_event_id']);
                }
                if (null === $parent) {
                    $parent = $course;
                }

                if (false === $result) {
                    continue;
                }

                $this->fixItemProperty('calendar_event', $eventRepo, $course, $admin, $event, $parent);

                $em->persist($event);
                $em->flush();
            }

            $sql = "SELECT * FROM c_calendar_event_attachment WHERE c_id = {$courseId}
                    ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $attachments = $result->fetchAllAssociative();
            foreach ($attachments as $attachmentData) {
                $id = $attachmentData['iid'];
                $attachmentPath = $attachmentData['path'];
                $fileName = $attachmentData['filename'];
                /** @var CCalendarEventAttachment $attachment */
                $attachment = $eventAttachmentRepo->find($id);
                if ($attachment->hasResourceNode()) {
                    continue;
                }
                $parent = $attachment->getEvent();
                $result = $this->fixItemProperty(
                    'calendar_event_attachment',
                    $eventRepo,
                    $course,
                    $admin,
                    $attachment,
                    $parent
                );

                if (false === $result) {
                    continue;
                }

                $filePath = $rootPath.'/app/courses/'.$course->getDirectory().'/upload/calendar/'.$attachmentPath;
                $this->addLegacyFileToResource($filePath, $eventAttachmentRepo, $attachment, $id, $fileName);
                $em->persist($attachment);
                $em->flush();
            }
        }
    }

    public function down(Schema $schema): void
    {
    }
}
