<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Chamilo\CourseBundle\Entity\CCalendarEventAttachment;
use Chamilo\CourseBundle\Repository\CCalendarEventAttachmentRepository;
use Chamilo\CourseBundle\Repository\CCalendarEventRepository;
use Doctrine\DBAL\Schema\Schema;

final class Version20201215072920 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate c_calendar_event, calendar_event_attachment and update agenda_reminder';
    }

    public function up(Schema $schema): void
    {
        $eventRepo = $this->container->get(CCalendarEventRepository::class);
        $eventAttachmentRepo = $this->container->get(CCalendarEventAttachmentRepository::class);
        $courseRepo = $this->container->get(CourseRepository::class);

        $kernel = $this->container->get('kernel');
        $rootPath = $kernel->getProjectDir();
        $admin = $this->getAdmin();
        $oldNewEventMap = [];

        $q = $this->entityManager->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');

        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $courseId = $course->getId();
            $course = $courseRepo->find($courseId);

            $sql = "SELECT * FROM c_calendar_event WHERE c_id = {$courseId}
                    ORDER BY iid";
            $result = $this->connection->executeQuery($sql);
            $events = $result->fetchAllAssociative();
            foreach ($events as $eventData) {
                $id = $eventData['iid'];
                $oldEventId = $id;

                /** @var CCalendarEvent $event */
                $event = $eventRepo->find($id);
                if ($event->hasResourceNode()) {
                    continue;
                }

                $sql = "SELECT * FROM c_item_property
                        WHERE tool = 'calendar_event' AND c_id = {$courseId} AND ref = {$id}";
                $result = $this->connection->executeQuery($sql);
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
                    $this->entityManager->persist($event);
                    $this->entityManager->flush();

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

                $this->entityManager->persist($event);
                $this->entityManager->flush();

                $oldNewEventMap[$oldEventId] = $event;
            }

            $sql = "SELECT * FROM c_calendar_event_attachment WHERE c_id = {$courseId}
                    ORDER BY iid";
            $result = $this->connection->executeQuery($sql);
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
                error_log('MIGRATIONS :: $filePath -- '.$filePath.' ...');
                $this->addLegacyFileToResource($filePath, $eventAttachmentRepo, $attachment, $id, $fileName);
                $this->entityManager->persist($attachment);
                $this->entityManager->flush();
            }
        }

        if ($schema->hasTable('agenda_reminder')) {
            $tblAgendaReminder = $schema->getTable('agenda_reminder');

            if ($tblAgendaReminder->hasColumn('type')) {
                $this->updateAgendaReminders($oldNewEventMap);
            }
        }
    }

    /**
     * @param array<int, CCalendarEvent> $oldNewEventMap
     */
    private function updateAgendaReminders(array $oldNewEventMap): void
    {
        $result = $this->connection->executeQuery("SELECT * FROM agenda_reminder WHERE type = 'course'");

        while (($reminder = $result->fetchAssociative()) !== false) {
            $oldEventId = $reminder['event_id'];
            if (\array_key_exists($oldEventId, $oldNewEventMap)) {
                $newEvent = $oldNewEventMap[$oldEventId];
                $this->addSql(
                    \sprintf(
                        'UPDATE agenda_reminder SET event_id = %d WHERE id = %d',
                        $newEvent->getIid(),
                        $reminder['id']
                    )
                );
            }
        }
    }

    public function down(Schema $schema): void {}
}
