<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Chamilo\CourseBundle\Entity\CCalendarEventAttachment;
use Chamilo\CourseBundle\Repository\CCalendarEventAttachmentRepository;
use Chamilo\CourseBundle\Repository\CCalendarEventRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Datetime;

class CCalendarEventAttachmentRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreateEvent(): void
    {
        self::bootKernel();
        $user = $this->createUser('test');
        $course = $this->createCourse('new');

        $resourceNodeId = $user->getResourceNode()->getId();

        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(CCalendarEventRepository::class);
        $repoAttachment = self::getContainer()->get(CCalendarEventAttachmentRepository::class);

        // Current server local time (check your php.ini).
        $start = new Datetime('2040-06-30 11:00');
        $end = new Datetime('2040-06-30 15:00');

        // 1. Add event.
        $event = (new CCalendarEvent())
            ->setTitle('hello')
            ->setContent('content hello')
            ->setStartDate($start)
            ->setEndDate($end)
            ->setCreator($user)
            ->setParent($user)
            ->setParentResourceNode($resourceNodeId)
        ;
        $repo->create($event);

        $attachment = (new CCalendarEventAttachment())
            ->setFilename('file')
            ->setComment('test')
            ->setEvent($event)
            ->setParent($event)
            ->setCreator($user)
            ->addCourseLink($course)
        ;

        $this->assertHasNoEntityViolations($attachment);

        $repoAttachment->create($attachment);

        $file = $this->getUploadedFile();
        $repoAttachment->addFile($attachment, $file, '', true);

        /** @var CCalendarEvent $event */
        $event = $repo->find($event->getIid());
        //$this->assertSame(1, $event->getAttachments()->count());
    }
}
