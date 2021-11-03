<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CAnnouncement;
use Chamilo\CourseBundle\Repository\CAnnouncementRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;

class CAnnouncementRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(CAnnouncementRepository::class);
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $announcement = (new CAnnouncement())
            ->setTitle('item')
            ->setContent('content')
            ->setDisplayOrder(1)
            ->setEmailSent(false)
            ->setEndDate(new DateTime())
            ->setParent($course)
            ->setCreator($teacher)
        ;
        $this->assertHasNoEntityViolations($announcement);
        $em->persist($announcement);
        $em->flush();

        $this->assertSame('item', (string) $announcement);
        $this->assertFalse($announcement->getEmailSent());
        $this->assertNotNull($announcement->getEndDate());
        $this->assertSame($announcement->getResourceIdentifier(), $announcement->getIid());

        $this->assertSame(0, $announcement->getAttachments()->count());
        $this->assertSame(1, $repo->count([]));

        $courseRepo->delete($course);

        $this->assertSame(0, $courseRepo->count([]));
        $this->assertSame(0, $repo->count([]));
    }
}
