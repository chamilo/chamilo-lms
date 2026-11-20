<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Security;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Security\CourseAccessResolver;
use Chamilo\CoreBundle\Security\CourseStudentAccessChecker;
use Chamilo\CoreBundle\Settings\SettingsManager;
use PHPUnit\Framework\TestCase;

final class CourseStudentAccessCheckerTest extends TestCase
{
    public function testDisabledSettingPreservesCourseAccessEvenWhenStoredVisibilityIsZero(): void
    {
        [$course, $student] = $this->createSubscription(CourseRelUser::STUDENT, 0);
        $checker = $this->checker('false');

        self::assertFalse($checker->isEnabled());
        self::assertFalse($checker->isDisabled($student, $course));
    }

    public function testEnabledSettingBlocksOnlyDirectStudentSubscriptions(): void
    {
        [$course, $student] = $this->createSubscription(CourseRelUser::STUDENT, 0);
        $checker = $this->checker('true');

        self::assertTrue($checker->isEnabled());
        self::assertTrue($checker->isDisabled($student, $course));
        self::assertTrue($checker->isDisabled($student, $course, new Session()));
        self::assertFalse($checker->isDisabled(new User(), $course));

        $course->getSubscriptionByUser($student)->setVisibility(1);
        self::assertFalse($checker->isDisabled($student, $course));
    }

    public function testRealSessionEnrollmentRetainsIndependentCourseAccess(): void
    {
        [$course, $student] = $this->createSubscription(CourseRelUser::STUDENT, 0);
        $session = $this->createMock(Session::class);
        $session->method('hasCourse')->with($course)->willReturn(true);
        $session->method('hasUserInCourse')
            ->with($student, $course, Session::STUDENT)
            ->willReturn(true)
        ;

        $checker = $this->checker('true');
        self::assertFalse($checker->isDisabled($student, $course, $session));
    }

    public function testTeacherSubscriptionIsNeverDisabled(): void
    {
        [$course, $teacher] = $this->createSubscription(CourseRelUser::TEACHER, 0);

        self::assertFalse($this->checker('true')->isDisabled($teacher, $course));
    }

    public function testSuspendedPublicStudentReceivesNoContextRole(): void
    {
        [$course, $student] = $this->createSubscription(CourseRelUser::STUDENT, 0);
        $course->setVisibility(Course::OPEN_WORLD);
        $settings = $this->createMock(SettingsManager::class);
        $settings->method('getSetting')->willReturn('true');
        $checker = new CourseStudentAccessChecker($settings);
        $resolver = new CourseAccessResolver($settings, $checker);

        self::assertSame([], $resolver->resolveCourseRoles($student, $course));
        self::assertSame([], $resolver->resolveCourseRoles($student, $course, new Session()));
    }

    /**
     * @return array{Course, User}
     */
    private function createSubscription(int $status, int $visibility): array
    {
        $course = new Course();
        $user = new User();
        $course->addSubscriptionForUser($user, 0, null, $status);
        $course->getSubscriptionByUser($user)->setVisibility($visibility);

        return [$course, $user];
    }

    private function checker(string $value): CourseStudentAccessChecker
    {
        $settings = $this->createMock(SettingsManager::class);
        $settings->method('getSetting')->willReturn($value);

        return new CourseStudentAccessChecker($settings);
    }
}
