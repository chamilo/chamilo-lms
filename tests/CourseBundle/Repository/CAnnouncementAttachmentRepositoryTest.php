<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

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
        self::bootKernel();

        $em = self::getContainer()->get('doctrine')->getManager();
        $repo = self::getContainer()->get(CAnnouncementRepository::class);
        $repoAttachment = self::getContainer()->get(CAnnouncementAttachmentRepository::class);

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

        $this->assertSame(1, $repo->count([]));
        $this->assertSame(1, $repoAttachment->count([]));
    }
}
