<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Room;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CAttendance;
use Chamilo\CourseBundle\Entity\CThematic;
use Chamilo\CourseBundle\Entity\CThematicAdvance;
use Chamilo\CourseBundle\Repository\CAttendanceRepository;
use Chamilo\CourseBundle\Repository\CThematicAdvanceRepository;
use Chamilo\CourseBundle\Repository\CThematicRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;

class CThematicAdvanceRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();

        $courseRepo = self::getContainer()->get(CourseRepository::class);
        $thematicRepo = self::getContainer()->get(CThematicRepository::class);
        $advanceRepo = self::getContainer()->get(CThematicAdvanceRepository::class);
        $attendanceRepo = self::getContainer()->get(CAttendanceRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $attendance = (new CAttendance())
            ->setName('item')
            ->setAttendanceWeight(100)
            ->setParent($course)
            ->setCreator($teacher)
        ;
        $em->persist($attendance);

        $thematic = (new CThematic())
            ->setTitle('thematic')
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $em->persist($thematic);

        $room = (new Room())
            ->setTitle('title')
            ->setDescription('desc')
            ->setGeolocation('')
            ->setIp('127.0.0.1')
            ->setIpMask('24')
        ;
        $em->persist($room);

        $advance = (new CThematicAdvance())
            ->setStartDate(new DateTime())
            ->setContent('content')
            ->setAttendance($attendance)
            ->setDoneAdvance(true)
            ->setDuration(1)
            ->setThematic($thematic)
            ->setRoom($room)
        ;
        $this->assertHasNoEntityViolations($advance);
        $em->persist($advance);
        $em->flush();
        $em->clear();

        /** @var CThematic $thematic */
        $thematic = $thematicRepo->find($thematic->getIid());

        $this->assertNotNull($advance->getRoom());
        $this->assertNotNull($advance->getThematic());

        $this->assertSame(1, $thematic->getAdvances()->count());
        $this->assertSame(0, $thematic->getPlans()->count());

        $this->assertSame(1, $thematicRepo->count([]));
        $this->assertSame(1, $attendanceRepo->count([]));
        $this->assertSame(1, $advanceRepo->count([]));

        $advance = $advanceRepo->find($advance->getIid());
        $advanceRepo->delete($advance);

        $this->assertSame(1, $courseRepo->count([]));
        $this->assertSame(1, $thematicRepo->count([]));
        $this->assertSame(1, $attendanceRepo->count([]));
        $this->assertSame(0, $advanceRepo->count([]));
    }

    public function testCreateDeleteCourse(): void
    {
        $em = $this->getEntityManager();

        $courseRepo = self::getContainer()->get(CourseRepository::class);
        $thematicRepo = self::getContainer()->get(CThematicRepository::class);
        $advanceRepo = self::getContainer()->get(CThematicAdvanceRepository::class);
        $attendanceRepo = self::getContainer()->get(CAttendanceRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $attendance = (new CAttendance())
            ->setName('item')
            ->setAttendanceWeight(100)
            ->setParent($course)
            ->setCreator($teacher)
        ;
        $em->persist($attendance);

        $thematic = (new CThematic())
            ->setTitle('thematic')
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $em->persist($thematic);

        $room = (new Room())
            ->setTitle('title')
            ->setDescription('desc')
            ->setGeolocation('')
            ->setIp('127.0.0.1')
            ->setIpMask('24')
        ;
        $em->persist($room);

        $advance = (new CThematicAdvance())
            ->setStartDate(new DateTime())
            ->setContent('content')
            ->setAttendance($attendance)
            ->setDoneAdvance(true)
            ->setDuration(1)
            ->setThematic($thematic)
            ->setRoom($room)
        ;
        $this->assertHasNoEntityViolations($advance);
        $em->persist($advance);
        $em->flush();
        $em->clear();

        /** @var CThematic $thematic */
        $thematic = $thematicRepo->find($thematic->getIid());

        $this->assertNotNull($advance->getRoom());
        $this->assertNotNull($advance->getThematic());

        $this->assertSame(1, $thematic->getAdvances()->count());
        $this->assertSame(0, $thematic->getPlans()->count());

        $this->assertSame(1, $courseRepo->count([]));
        $this->assertSame(1, $thematicRepo->count([]));
        $this->assertSame(1, $attendanceRepo->count([]));
        $this->assertSame(1, $advanceRepo->count([]));

        $course = $this->getCourse($course->getId());
        $courseRepo->delete($course);

        $this->assertSame(0, $courseRepo->count([]));
        $this->assertSame(0, $thematicRepo->count([]));
        $this->assertSame(0, $attendanceRepo->count([]));
        $this->assertSame(0, $advanceRepo->count([]));
    }
}
