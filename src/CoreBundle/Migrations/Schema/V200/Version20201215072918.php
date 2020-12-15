<?php

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\AccessUrlRelCourse;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
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
        return 'Migrate agenda';
    }

    public function up(Schema $schema): void
    {
        $container = $this->getContainer();
        $doctrine = $container->get('doctrine');
        $em = $doctrine->getManager();
        /** @var Connection $connection */
        $connection = $em->getConnection();

        $urlRepo = $em->getRepository(AccessUrlRepository::class);
        $eventRepo = $container->get(CCalendarEventRepository::class);
        $eventAttachmentRepo = $container->get(CCalendarEventAttachmentRepository::class);
        $courseRepo = $container->get(CourseRepository::class);
        $sessionRepo = $container->get(SessionRepository::class);
        $groupRepo = $container->get(CGroupRepository::class);
        $userRepo = $container->get(UserRepository::class);

        /** @var Kernel $kernel */
        $kernel = $container->get('kernel');
        $rootPath = $kernel->getProjectDir();
        $admin = $this->getAdmin();
        $urls = $urlRepo->findAll();

        /** @var AccessUrl $url */
        foreach ($urls as $url) {
            $accessUrlRelCourses = $url->getCourses();
            /** @var AccessUrlRelCourse $accessUrlRelCourse */
            foreach ($accessUrlRelCourses as $accessUrlRelCourse) {
                $counter = 1;
                $course = $accessUrlRelCourse->getCourse();
                $courseId = $course->getId();
                $courseCode = $course->getCode();
                $course = $courseRepo->find($courseId);

                $sql = "SELECT * FROM c_calendar_event WHERE c_id = $courseId
                        ORDER BY iid";
                $result = $connection->executeQuery($sql);
                $events = $result->fetchAllAssociative();
                foreach ($events as $event) {
                    $id = $event['iid'];
                    /** @var CCalendarEvent $event */
                    $event = $eventRepo->find($id);
                    if ($event->hasResourceNode()) {
                        continue;
                    }
                    $sql = "SELECT * FROM c_item_property
                            WHERE tool = 'calendar_event' AND c_id = $courseId AND ref = $id";
                    $result = $connection->executeQuery($sql);
                    $items = $result->fetchAllAssociative();

                    // For some reason this document doesnt have a c_item_property value.
                    if (empty($items)) {
                        continue;
                    }
                    $parent = null;
                    if (!empty($event['parent_event_id'])) {
                        $parent = $eventRepo->find($event['parent_event_id']);
                    }
                    if (null === $parent) {
                        $parent = $course;
                    }
                    $this->fixItemProperty($eventRepo, $course, $admin, $event, $parent, $items);
                }

                $sql = "SELECT * FROM c_calendar_event_attachment WHERE c_id = $courseId
                        ORDER BY iid";
                $result = $connection->executeQuery($sql);
                $events = $result->fetchAllAssociative();
                foreach ($events as $event) {
                    $id = $event['iid'];
                    $attachmentPath = $event['filename'];
                    /** @var CCalendarEventAttachment $event */
                    $event = $eventAttachmentRepo->find($id);
                    if ($event->hasResourceNode()) {
                        continue;
                    }
                    $sql = "SELECT * FROM c_item_property
                            WHERE tool = 'calendar_event_attachment' AND c_id = $courseId AND ref = $id";
                    $result = $connection->executeQuery($sql);
                    $items = $result->fetchAllAssociative();
                    $this->fixItemProperty($eventAttachmentRepo, $event, $parent, $items);
                    $filePath = $rootPath.'/app/courses/'.$course->getDirectory().'/upload/calendar/'.$attachmentPath;
                    $this->addLegacyFileToResource($filePath, $eventAttachmentRepo, $event, $id);
                }
            }
        }
    }

    public function down(Schema $schema): void
    {
    }
}
