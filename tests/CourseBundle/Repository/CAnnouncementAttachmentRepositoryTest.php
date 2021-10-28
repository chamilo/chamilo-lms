<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CAnnouncement;
use Chamilo\CourseBundle\Entity\CAnnouncementAttachment;
use Chamilo\CourseBundle\Repository\CAnnouncementAttachmentRepository;
use Chamilo\CourseBundle\Repository\CAnnouncementRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CAnnouncementAttachmentRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();
        $repoAnnouncement = self::getContainer()->get(CAnnouncementRepository::class);
        $repoAttachment = self::getContainer()->get(CAnnouncementAttachmentRepository::class);
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $announcement = (new CAnnouncement())
            ->setTitle('item')
            ->setParent($course)
            ->setCreator($teacher)
        ;
        $this->assertHasNoEntityViolations($announcement);
        $em->persist($announcement);

        $attachment = (new CAnnouncementAttachment())
            ->setComment('comment')
            ->setFilename('image')
            ->setPath(uniqid('announce_', true))
            ->setAnnouncement($announcement)
            ->setSize(1)
            ->setCreator($teacher)
            ->setParent($announcement)
            ->addCourseLink($course)
        ;

        $this->assertHasNoEntityViolations($attachment);
        $em->persist($attachment);
        $em->flush();

        $this->assertNotNull($attachment->getComment());
        $this->assertSame(1, $attachment->getSize());
        $this->assertSame($attachment->getResourceIdentifier(), $attachment->getIid());

        $em->clear();

        /** @var CAnnouncement $announcement */
        $announcement = $repoAnnouncement->find($announcement->getIid());

        $this->assertSame(1, $announcement->getAttachments()->count());
        $this->assertSame(1, $repoAnnouncement->count([]));
        $this->assertSame(1, $repoAttachment->count([]));

        $course = $this->getCourse($course->getId());
        $courseRepo->delete($course);

        $this->assertSame(0, $repoAnnouncement->count([]));
        $this->assertSame(0, $repoAttachment->count([]));
        $this->assertSame(0, $courseRepo->count([]));
    }
}
