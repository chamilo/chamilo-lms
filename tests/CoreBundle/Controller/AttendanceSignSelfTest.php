<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CAttendance;
use Chamilo\CourseBundle\Entity\CAttendanceCalendar;
use Chamilo\CourseBundle\Entity\CAttendanceSheet;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * A student signs their own attendance sheet only where the platform allows signatures and
 * the date is still open. The teacher's view draws the stored value as an image, so only an
 * image produced by the signature pad is accepted: a URL would make the teacher's browser
 * contact a server chosen by the student.
 */
class AttendanceSignSelfTest extends WebTestCase
{
    use ChamiloTestTrait;

    private const string SIGNATURE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    public function testStudentSignsOwnSheet(): void
    {
        [$client, $student, $calendar] = $this->setUpSheet();

        $this->signSelf($client, $student, $calendar, self::SIGNATURE);

        $this->assertResponseIsSuccessful();
        $this->assertSame(self::SIGNATURE, $this->findSheet($student, $calendar)->getSignature());
    }

    public function testSignatureIsRejectedWhenThePlatformDoesNotAllowSignatures(): void
    {
        [$client, $student, $calendar] = $this->setUpSheet(signaturesEnabled: false);

        $this->signSelf($client, $student, $calendar, self::SIGNATURE);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->assertNull($this->findSheet($student, $calendar)->getSignature());
    }

    public function testBlockedDateCannotBeSigned(): void
    {
        [$client, $student, $calendar] = $this->setUpSheet(blocked: true);

        $this->signSelf($client, $student, $calendar, self::SIGNATURE);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->assertNull($this->findSheet($student, $calendar)->getSignature());
    }

    public function testSignatureThatIsNotAPngImageIsRejected(): void
    {
        [$client, $student, $calendar] = $this->setUpSheet();

        foreach (['https://example.com/pixel.png', 'data:image/svg+xml;base64,PHN2Zz4=', 'data:image/png;base64,'.str_repeat('A', 65536)] as $signature) {
            $this->signSelf($client, $student, $calendar, $signature);
            $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        }

        $this->assertNull($this->findSheet($student, $calendar)->getSignature());
    }

    /**
     * @return array{0: KernelBrowser, 1: User, 2: CAttendanceCalendar}
     */
    private function setUpSheet(bool $signaturesEnabled = true, bool $blocked = false): array
    {
        $client = static::createClient();
        $em = $this->getEntityManager();

        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        self::getContainer()->get(SettingsManager::class)
            ->updateSetting('attendance.enable_sign_attendance_sheet', $signaturesEnabled ? 'true' : 'false')
        ;

        $student = $this->createUser('sign_self_student');
        $teacher = $this->createUser('sign_self_teacher');

        $course = $this->createCourse('Sign self course');
        $course->setVisibility(Course::REGISTERED);
        $course->addUserAsTeacher($teacher);
        $course->addUserAsStudent($student);
        $courseRepo->update($course);

        $attendance = (new CAttendance())
            ->setTitle('Sign self attendance')
            ->setLocked(0)
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $em->persist($attendance);

        $calendar = (new CAttendanceCalendar())
            ->setAttendance($attendance)
            ->setDateTime(new DateTime())
            ->setDoneAttendance(true)
            ->setBlocked($blocked)
        ;
        $em->persist($calendar);

        // The teacher marked the student present: the student only adds the signature.
        $sheet = (new CAttendanceSheet())
            ->setUser($student)
            ->setAttendanceCalendar($calendar)
            ->setPresence(1)
            ->setSignature(null)
        ;
        $em->persist($sheet);
        $em->flush();

        return [$client, $student, $calendar];
    }

    private function signSelf(KernelBrowser $client, User $user, CAttendanceCalendar $calendar, string $signature): void
    {
        $client->loginUser($user);
        $client->request(
            'POST',
            '/attendance/sign-self',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_SEC_FETCH_SITE' => 'same-origin'],
            json_encode(['calendarId' => $calendar->getIid(), 'signature' => $signature]),
        );
    }

    private function findSheet(User $user, CAttendanceCalendar $calendar): CAttendanceSheet
    {
        $em = $this->getEntityManager();
        $em->clear();

        return $em->getRepository(CAttendanceSheet::class)->findOneBy([
            'user' => $user->getId(),
            'attendanceCalendar' => $calendar->getIid(),
        ]);
    }
}
