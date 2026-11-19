<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CAttendance;
use Chamilo\CourseBundle\Entity\CAttendanceCalendar;
use Chamilo\CourseBundle\Entity\CAttendanceSheet;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * A student may mark their own presence only where the course allows it. The course the
 * setting is read from is named in the request, so the dates being marked must belong to
 * that course and be visible to the student; otherwise the setting of one course would
 * open the attendance of any other.
 */
class AttendanceValidateSelfTest extends WebTestCase
{
    use ChamiloTestTrait;

    public function testOwnPresenceIsSavedInACourseThatAllowsIt(): void
    {
        [$client, $student, $own] = $this->setUpCourses();
        $calendar = $this->createCalendar($own['course'], $own['teacher']);

        $this->validateSelf($client, $student, $own['course'], true, [$calendar]);

        $this->assertResponseIsSuccessful();
        $this->assertSame(1, $this->findSheet($student, $calendar)?->getPresence());
    }

    public function testDateOfAnotherCourseCannotBeMarkedThroughTheSettingOfOwnCourse(): void
    {
        [$client, $student, $own, $foreign] = $this->setUpCourses();
        $ownCalendar = $this->createCalendar($own['course'], $own['teacher']);
        $foreignCalendar = $this->createCalendar($foreign['course'], $foreign['teacher']);

        // With or without the course in the URL, which the caller chooses.
        foreach ([true, false] as $withCidInUrl) {
            $this->validateSelf($client, $student, $own['course'], $withCidInUrl, [$ownCalendar, $foreignCalendar]);
            $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        }

        $this->assertNull($this->findSheet($student, $foreignCalendar));
        // Nothing of the request is kept, not even the dates that were valid.
        $this->assertNull($this->findSheet($student, $ownCalendar));
    }

    public function testDateOfACourseTheUserDoesNotBelongToCannotBeMarked(): void
    {
        [$client, , $own] = $this->setUpCourses();
        $calendar = $this->createCalendar($own['course'], $own['teacher']);
        $stranger = $this->createUser('validate_self_stranger');

        // Without the course in the URL, so that the course context listener does not stop the request first.
        $this->validateSelf($client, $stranger, $own['course'], false, [$calendar]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->assertNull($this->findSheet($stranger, $calendar));
    }

    public function testPresenceOtherThanPresentOrAbsentIsRejected(): void
    {
        [$client, $student, $own] = $this->setUpCourses();
        $calendar = $this->createCalendar($own['course'], $own['teacher']);

        // The attendance score adds up the presence values, so any other value would inflate the grade.
        $this->validateSelf($client, $student, $own['course'], true, [$calendar], 5);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertNull($this->findSheet($student, $calendar));
    }

    /**
     * @return array{0: KernelBrowser, 1: User, 2: array{course: Course, teacher: User}, 3: array{course: Course, teacher: User}}
     */
    private function setUpCourses(): array
    {
        $client = static::createClient();

        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $student = $this->createUser('validate_self_student');
        $ownTeacher = $this->createUser('validate_self_teacher_a');
        $foreignTeacher = $this->createUser('validate_self_teacher_b');

        $own = $this->createCourse('Validate self course A');
        $own->setVisibility(Course::REGISTERED);
        $own->addUserAsTeacher($ownTeacher);
        $own->addUserAsStudent($student);
        $courseRepo->update($own);

        // The foreign course does not allow students to mark their own presence.
        $foreign = $this->createCourse('Validate self course B');
        $foreign->setVisibility(Course::REGISTERED);
        $foreign->addUserAsTeacher($foreignTeacher);
        $courseRepo->update($foreign);

        $this->allowStudentsToValidateOwnAttendance($own);

        return [
            $client,
            $student,
            ['course' => $own, 'teacher' => $ownTeacher],
            ['course' => $foreign, 'teacher' => $foreignTeacher],
        ];
    }

    private function allowStudentsToValidateOwnAttendance(Course $course): void
    {
        $em = $this->getEntityManager();
        $setting = $em->getRepository(CCourseSetting::class)->findOneBy([
            'cId' => $course->getId(),
            'variable' => 'student_validate_own_attendance',
        ]) ?? (new CCourseSetting())
            ->setCId((int) $course->getId())
            ->setVariable('student_validate_own_attendance')
            ->setTitle('student_validate_own_attendance')
        ;
        $setting->setValue('1');
        $em->persist($setting);
        $em->flush();
    }

    private function createCalendar(Course $course, User $teacher): CAttendanceCalendar
    {
        $em = $this->getEntityManager();

        $attendance = (new CAttendance())
            ->setTitle('Validate self attendance '.$course->getCode())
            ->setLocked(0)
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $em->persist($attendance);
        $em->flush();

        $calendar = (new CAttendanceCalendar())
            ->setAttendance($attendance)
            ->setDateTime(new DateTime())
            ->setDoneAttendance(false)
            ->setBlocked(false)
        ;
        $em->persist($calendar);
        $em->flush();

        return $calendar;
    }

    /**
     * @param CAttendanceCalendar[] $calendars
     */
    private function validateSelf(KernelBrowser $client, User $user, Course $course, bool $withCidInUrl, array $calendars, int $presence = 1): void
    {
        $entries = array_map(
            static fn (CAttendanceCalendar $calendar): array => ['calendarId' => $calendar->getIid(), 'presence' => $presence],
            $calendars,
        );

        $client->loginUser($user);
        $client->request(
            'POST',
            '/attendance/validate-self'.($withCidInUrl ? '?cid='.$course->getId() : ''),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_SEC_FETCH_SITE' => 'same-origin'],
            json_encode(['courseId' => $course->getId(), 'entries' => $entries]),
        );
    }

    private function findSheet(User $user, CAttendanceCalendar $calendar): ?CAttendanceSheet
    {
        $em = $this->getEntityManager();
        $em->clear();

        return $em->getRepository(CAttendanceSheet::class)->findOneBy([
            'user' => $user->getId(),
            'attendanceCalendar' => $calendar->getIid(),
        ]);
    }
}
