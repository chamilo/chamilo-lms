<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CAttendance;
use Chamilo\CourseBundle\Entity\CAttendanceCalendar;
use Chamilo\CourseBundle\Entity\CAttendanceResult;
use Chamilo\CourseBundle\Entity\CAttendanceSheet;
use Chamilo\CourseBundle\Entity\CAttendanceSheetLog;
use Chamilo\CourseBundle\Repository\CAttendanceRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;

class CAttendanceRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();

        $courseRepo = self::getContainer()->get(CourseRepository::class);
        $repo = self::getContainer()->get(CAttendanceRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $item = (new CAttendance())
            ->setTitle('item')
            ->setResourceName('item')
            ->setDescription('desc')
            ->setLocked(1)
            ->setActive(1)
            ->setAttendanceQualifyMax(100)
            ->setAttendanceQualifyTitle('title')
            ->setAttendanceWeight(100.0)
            ->setParent($course)
            ->setCreator($teacher)
        ;
        $this->assertHasNoEntityViolations($item);
        $em->persist($item);
        $em->flush();

        $this->assertSame('item', (string) $item);
        $this->assertSame($item->getResourceIdentifier(), $item->getIid());
        $this->assertSame(1, $item->getActive());
        $this->assertSame('desc', $item->getDescription());
        $this->assertSame(100, $item->getAttendanceQualifyMax());
        $this->assertSame(100.0, $item->getAttendanceWeight());
        $this->assertSame(1, $item->getLocked());

        $this->assertSame(1, $repo->count([]));
        $courseRepo->delete($course);

        // Fixme Attendances are highly bound to courses and should be cascade-deleted with them
        // $this->assertSame(0, $repo->count([]));
        $this->assertSame(0, $courseRepo->count([]));
    }

    public function testCreateWithCalendar(): void
    {
        $em = $this->getEntityManager();

        $courseRepo = self::getContainer()->get(CourseRepository::class);
        $attendanceRepo = self::getContainer()->get(CAttendanceRepository::class);
        $calendarRepo = $em->getRepository(CAttendanceCalendar::class);
        $resultRepo = $em->getRepository(CAttendanceResult::class);
        $sheetRepo = $em->getRepository(CAttendanceSheet::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');
        $student = $this->createUser('student');

        $attendance = (new CAttendance())
            ->setTitle('item')
            ->setAttendanceWeight(100)
            ->setParent($course)
            ->setCreator($teacher)
        ;
        $em->persist($attendance);

        $calendar = (new CAttendanceCalendar())
            ->setAttendance($attendance)
            ->setDateTime(new DateTime())
            ->setDoneAttendance(true)
            ->setBlocked(false)
        ;
        $em->persist($calendar);

        $result = (new CAttendanceResult())
            ->setAttendance($attendance)
            ->setUser($student)
            ->setScore(100)
        ;
        $em->persist($result);

        $sheet = (new CAttendanceSheet())
            ->setUser($student)
            ->setAttendanceCalendar($calendar)
            ->setPresence(CAttendanceSheet::PRESENT)
            ->setSignature('image-blob-here')
        ;
        $em->persist($sheet);

        $log = (new CAttendanceSheetLog())
            ->setUser($student)
            ->setAttendance($attendance)
            ->setCalendarDateValue(new DateTime())
            ->setLasteditDate(new DateTime())
            ->setLasteditType('last')
        ;
        $em->persist($log);

        $em->flush();
        $em->clear();

        /** @var CAttendanceCalendar $calendar */
        $calendar = $calendarRepo->find($calendar->getIid());

        $this->assertNotNull($calendar->getDateTime());
        $this->assertNotNull($calendar->getDoneAttendance());
        $this->assertNotNull($calendar->getAttendance());
        $this->assertSame(1, $calendar->getSheets()->count());

        /** @var CAttendance $attendance */
        $attendance = $attendanceRepo->find($attendance->getIid());

        $course = $this->getCourse($course->getId());

        $this->assertSame(1, $attendance->getCalendars()->count());
        $this->assertSame(1, $attendance->getResults()->count());

        $this->assertSame($attendance->getResourceIdentifier(), $attendance->getIid());

        $this->assertSame(1, $attendanceRepo->count([]));
        $this->assertSame(1, $calendarRepo->count([]));
        $this->assertSame(1, $resultRepo->count([]));
        $this->assertSame(1, $sheetRepo->count([]));

        $courseRepo->delete($course);

        // Fixme Attendances are highly bound to the course and should be cascade-deleted with the course
        // $this->assertSame(1, $calendarRepo->count([]));
        // $this->assertSame(1, $attendanceRepo->count([]));
        // $this->assertSame(1, $sheetRepo->count([]));
    }
}
