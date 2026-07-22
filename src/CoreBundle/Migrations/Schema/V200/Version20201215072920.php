<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Chamilo\CourseBundle\Entity\CCalendarEventAttachment;
use Chamilo\CourseBundle\Repository\CCalendarEventAttachmentRepository;
use Chamilo\CourseBundle\Repository\CCalendarEventRepository;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

final class Version20201215072920 extends AbstractMigrationChamilo
{
    private const ORM_FLUSH_BATCH_SIZE = 100;

    public function getDescription(): string
    {
        return 'Migrate c_calendar_event, calendar_event_attachment and update agenda_reminder';
    }

    /**
     * Calendar events/attachments are committed in explicit ORM batches.
     * This makes the migration resumable and avoids losing hours of work if
     * the process is interrupted.
     */
    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $eventRepo = $this->container->get(CCalendarEventRepository::class);
        $eventAttachmentRepo = $this->container->get(CCalendarEventAttachmentRepository::class);
        $courseRepo = $this->container->get(CourseRepository::class);
        $userRepo = $this->container->get(UserRepository::class);

        $adminId = (int) $this->getAdmin()->getId();

        /** @var array<int, int> $oldNewEventIdMap */
        $oldNewEventIdMap = [];

        $courseIds = $this->connection->fetchFirstColumn(
            'SELECT DISTINCT c_id FROM c_calendar_event WHERE resource_node_id IS NULL
             UNION
             SELECT DISTINCT c_id FROM c_calendar_event_attachment WHERE resource_node_id IS NULL
             ORDER BY c_id'
        );

        foreach ($courseIds as $courseIdValue) {
            $courseId = (int) $courseIdValue;
            [$course, $admin] = $this->reloadCalendarContext($courseId, $adminId, $courseRepo, $userRepo);

            $events = $this->connection->fetchAllAssociative(
                'SELECT iid, session_id, parent_event_id
                 FROM c_calendar_event
                 WHERE c_id = :courseId AND resource_node_id IS NULL
                 ORDER BY iid',
                ['courseId' => $courseId]
            );

            $processed = 0;

            foreach ($events as $eventData) {
                $id = (int) $eventData['iid'];
                $oldEventId = $id;

                /** @var CCalendarEvent|null $event */
                $event = $eventRepo->find($id);
                if (!$event instanceof CCalendarEvent || $event->hasResourceNode()) {
                    continue;
                }

                $items = $this->connection->fetchAllAssociative(
                    "SELECT * FROM c_item_property
                     WHERE tool = 'calendar_event' AND c_id = :courseId AND ref = :id",
                    ['courseId' => $courseId, 'id' => $id]
                );

                // For some reason this event doesnt have a c_item_property value,
                // then we added to the main course and assign the admin as the creator.
                if (empty($items)) {
                    $items[] = [
                        'visibility' => 1,
                        'insert_user_id' => $adminId,
                        'to_group_id' => 0,
                        'session_id' => $eventData['session_id'],
                    ];
                    $this->fixItemProperty('calendar_event', $eventRepo, $course, $admin, $event, $course, $items);
                } else {
                    // Assign parent.
                    $parent = null;
                    if (!empty($eventData['parent_event_id'])) {
                        $parent = $eventRepo->find((int) $eventData['parent_event_id']);
                    }
                    $parent ??= $course;

                    $this->fixItemProperty('calendar_event', $eventRepo, $course, $admin, $event, $parent);
                }

                $this->entityManager->persist($event);
                $oldNewEventIdMap[$oldEventId] = (int) $event->getIid();
                ++$processed;

                if (0 === $processed % self::ORM_FLUSH_BATCH_SIZE) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();

                    [$course, $admin] = $this->reloadCalendarContext($courseId, $adminId, $courseRepo, $userRepo);
                }
            }

            $this->entityManager->flush();
            $this->entityManager->clear();
            [$course, $admin] = $this->reloadCalendarContext($courseId, $adminId, $courseRepo, $userRepo);

            $attachments = $this->connection->fetchAllAssociative(
                'SELECT iid, path, filename
                 FROM c_calendar_event_attachment
                 WHERE c_id = :courseId AND resource_node_id IS NULL
                 ORDER BY iid',
                ['courseId' => $courseId]
            );

            $processed = 0;

            foreach ($attachments as $attachmentData) {
                $id = (int) $attachmentData['iid'];

                /** @var CCalendarEventAttachment|null $attachment */
                $attachment = $eventAttachmentRepo->find($id);
                if (!$attachment instanceof CCalendarEventAttachment || $attachment->hasResourceNode()) {
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

                $filePath = $this->getUpdateRootPath().'/app/courses/'.$course->getDirectory().'/upload/calendar/'.$attachmentData['path'];
                error_log('MIGRATIONS :: $filePath -- '.$filePath.' ...');
                $this->addLegacyFileToResource(
                    $filePath,
                    $eventAttachmentRepo,
                    $attachment,
                    $id,
                    (string) $attachmentData['filename']
                );
                $this->entityManager->persist($attachment);
                ++$processed;

                if (0 === $processed % self::ORM_FLUSH_BATCH_SIZE) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();

                    [$course, $admin] = $this->reloadCalendarContext($courseId, $adminId, $courseRepo, $userRepo);
                }
            }

            $this->entityManager->flush();
            $this->entityManager->clear();
        }

        if ($schema->hasTable('agenda_reminder')) {
            $tblAgendaReminder = $schema->getTable('agenda_reminder');

            if ($tblAgendaReminder->hasColumn('type')) {
                $this->updateAgendaReminders($oldNewEventIdMap);
            }
        }
    }

    /**
     * @param array<int, int> $oldNewEventIdMap
     */
    private function updateAgendaReminders(array $oldNewEventIdMap): void
    {
        $result = $this->connection->executeQuery("SELECT * FROM agenda_reminder WHERE type = 'course'");

        while (($reminder = $result->fetchAssociative()) !== false) {
            $oldEventId = $reminder['event_id'];
            if (\array_key_exists($oldEventId, $oldNewEventIdMap)) {
                $this->addSql(
                    \sprintf(
                        'UPDATE agenda_reminder SET event_id = %d WHERE id = %d',
                        $oldNewEventIdMap[$oldEventId],
                        $reminder['id']
                    )
                );
            }
        }
    }

    /**
     * @return array{0: Course, 1: User}
     */
    private function reloadCalendarContext(
        int $courseId,
        int $adminId,
        CourseRepository $courseRepo,
        UserRepository $userRepo
    ): array {
        $course = $courseRepo->find($courseId);
        $admin = $userRepo->find($adminId);

        if (!$course instanceof Course) {
            throw new RuntimeException("Course {$courseId} could not be reloaded.");
        }

        if (!$admin instanceof User) {
            throw new RuntimeException("Admin user {$adminId} could not be reloaded.");
        }

        return [$course, $admin];
    }

    public function down(Schema $schema): void {}
}
