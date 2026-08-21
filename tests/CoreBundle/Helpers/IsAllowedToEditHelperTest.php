<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CoreBundle\Helpers\SessionVisibilityHelper;
use Chamilo\CoreBundle\Helpers\StudentViewHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Repository\TrackECourseAccessRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * IsAllowedToEditHelper is the single answer to "may this user edit course content".
 *
 * Each case below pins a rule that costs something real when it breaks: a whole
 * tool going read-only for a teacher, a coach of one course reaching another
 * one's content, or the student view failing to hide edit actions. The most
 * important one is testBaseCourseTeacherMayEditInsideASession: the legacy
 * function returned the coach term alone inside a session, which was safe in
 * 1.11 only because entering a session demoted the base course teacher. It no
 * longer does, so reproducing that branch would deny every base course teacher
 * in every tool at once.
 */
final class IsAllowedToEditHelperTest extends TestCase
{
    public function testAnonymousUserMayNotEdit(): void
    {
        // Reachable: course description and other tools serve public courses to
        // visitors, and this used to dereference a null user.
        self::assertFalse($this->build(withUser: false)->check(course: $this->course()));
    }

    public function testPlatformAdminMayEdit(): void
    {
        // Modelled through isGranted('ROLE_ADMIN'), which is what makes
        // ROLE_GLOBAL_ADMIN work: User::hasRole() would not have matched it.
        self::assertTrue($this->build(roles: ['ROLE_ADMIN'])->check(course: $this->course()));
    }

    public function testPlatformAdminMayNotEditWhileInStudentView(): void
    {
        self::assertFalse(
            $this->build(roles: ['ROLE_ADMIN'], studentView: true)->check(course: $this->course())
        );
    }

    public function testStudentViewIsIgnoredWhenTheCallerAsksForTheRawPermission(): void
    {
        // What lets a teacher preview unpublished content from inside the student view.
        self::assertTrue(
            $this->build(roles: ['ROLE_ADMIN'], studentView: true)
                ->check(checkStudentView: false, course: $this->course())
        );
    }

    public function testSessionAdminMayEditOnlyWhenTheSettingAllowsIt(): void
    {
        self::assertFalse(
            $this->build(roles: ['ROLE_SESSION_MANAGER'])->check(course: $this->course())
        );

        self::assertTrue(
            $this->build(
                roles: ['ROLE_SESSION_MANAGER'],
                settings: ['session.session_admins_edit_courses_content' => 'true'],
            )->check(course: $this->course())
        );
    }

    public function testCourseTeacherMayEdit(): void
    {
        self::assertTrue(
            $this->build(roles: ['ROLE_CURRENT_COURSE_TEACHER'])->check(course: $this->course())
        );
    }

    public function testCourseTeacherMayNotEditWhileInStudentView(): void
    {
        self::assertFalse(
            $this->build(roles: ['ROLE_CURRENT_COURSE_TEACHER'], studentView: true)
                ->check(course: $this->course())
        );
    }

    public function testSubscribedTeacherOfAClosedCourseMayEdit(): void
    {
        // CourseAccessResolver grants no contextual role in a CLOSED course, yet
        // CourseVoter lets the subscribed teacher in. Without the entity term the
        // teacher would see the tool and be unable to edit anything in it.
        self::assertTrue(
            $this->build()->check(course: $this->course(hasUserAsTeacher: true))
        );
    }

    public function testTeacherOfAHiddenCourseMayNotEdit(): void
    {
        self::assertFalse(
            $this->build()->check(course: $this->course(hasUserAsTeacher: true, hidden: true))
        );
    }

    public function testBaseCourseTeacherMayEditInsideASession(): void
    {
        // The deliberate deviation from the legacy session branch. If this ever
        // returns false, every base course teacher loses editing in every tool
        // as soon as they enter a session.
        self::assertTrue(
            $this->build(roles: ['ROLE_CURRENT_COURSE_TEACHER'])
                ->check(course: $this->course(), session: $this->session())
        );
    }

    public function testSessionCoachOfTheCurrentCourseMayEdit(): void
    {
        self::assertTrue(
            $this->build()->check(
                course: $this->course(),
                session: $this->session(courseCoachHere: true),
            )
        );
    }

    public function testGeneralCoachOfTheSessionMayEdit(): void
    {
        self::assertTrue(
            $this->build()->check(course: $this->course(), session: $this->session(generalCoach: true))
        );
    }

    public function testCoachOfAnotherCourseOfTheSameSessionMayNotEdit(): void
    {
        // Session::hasCoach() would have matched this user, which is how a coach
        // of course B could edit course A. The legacy query joins on the course.
        self::assertFalse(
            $this->build()->check(
                course: $this->course(),
                session: $this->session(courseCoachHere: false, generalCoach: false),
            )
        );
    }

    public function testCoachMayNotEditWhenTheSettingIsOff(): void
    {
        self::assertFalse(
            $this->build(settings: ['session.allow_coach_to_edit_course_session' => 'false'])->check(
                course: $this->course(),
                session: $this->session(courseCoachHere: true),
            )
        );
    }

    public function testCourseTeacherStillEditsWhenTheCoachSettingIsOff(): void
    {
        // The coach setting must not gate the course teacher.
        self::assertTrue(
            $this->build(
                roles: ['ROLE_CURRENT_COURSE_TEACHER'],
                settings: ['session.allow_coach_to_edit_course_session' => 'false'],
            )->check(course: $this->course(), session: $this->session())
        );
    }

    public function testCoachMayNotEditAReadOnlySession(): void
    {
        self::assertFalse(
            $this->build()->check(
                course: $this->course(),
                session: $this->session(courseCoachHere: true, visibility: Session::READ_ONLY),
            )
        );
    }

    public function testCourseTeacherStillEditsAReadOnlySession(): void
    {
        self::assertTrue(
            $this->build(roles: ['ROLE_CURRENT_COURSE_TEACHER'])->check(
                course: $this->course(),
                session: $this->session(visibility: Session::READ_ONLY),
            )
        );
    }

    public function testLockedCourseContentDeniesEveryoneButAdmins(): void
    {
        // session_courses_read_only_mode plus the per-course extra field is how an
        // administrator freezes a course used by sessions.
        $settings = ['session.session_courses_read_only_mode' => 'true'];

        self::assertFalse(
            $this->build(roles: ['ROLE_CURRENT_COURSE_TEACHER'], settings: $settings, courseLocked: true)
                ->check(course: $this->course(), session: $this->session())
        );

        self::assertTrue(
            $this->build(roles: ['ROLE_ADMIN'], settings: $settings, courseLocked: true)
                ->check(course: $this->course(), session: $this->session())
        );
    }

    public function testLockOnlyAppliesInsideASession(): void
    {
        self::assertTrue(
            $this->build(
                roles: ['ROLE_CURRENT_COURSE_TEACHER'],
                settings: ['session.session_courses_read_only_mode' => 'true'],
                courseLocked: true,
            )->check(course: $this->course())
        );
    }

    /**
     * @param array<int, string>    $roles    roles isGranted() answers true for
     * @param array<string, string> $settings platform settings overriding the defaults
     */
    private function build(
        ?User $user = null,
        array $roles = [],
        bool $studentView = false,
        array $settings = [],
        bool $courseLocked = false,
        bool $withUser = true,
    ): IsAllowedToEditHelper {
        $user ??= $withUser ? $this->createMock(User::class) : null;

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);
        $security->method('isGranted')->willReturnCallback(
            fn (mixed $attribute): bool => \is_string($attribute) && \in_array($attribute, $roles, true)
        );

        $settings += [
            'session.session_admins_edit_courses_content' => 'false',
            'session.allow_coach_to_edit_course_session' => 'true',
            'session.session_courses_read_only_mode' => 'false',
            'session.session_coach_access_after_duration_end' => 'false',
        ];

        $settingsManager = $this->createMock(SettingsManager::class);
        $settingsManager->method('getSetting')->willReturnCallback(
            fn (string $variable): string => $settings[$variable] ?? ''
        );

        $studentViewHelper = $this->createMock(StudentViewHelper::class);
        $studentViewHelper->method('isActive')->willReturn($studentView);

        $extraFieldValues = $this->createMock(ExtraFieldValuesRepository::class);
        $extraFieldValues->method('getValueByVariableAndItem')->willReturn(
            $courseLocked ? $this->lockValue() : null
        );

        return new IsAllowedToEditHelper(
            $settingsManager,
            $security,
            new RequestStack(),
            $this->createMock(CidReqHelper::class),
            // Readonly, so it cannot be doubled; the real one falls back to
            // Session::setAccessVisibilityByUser(), which the session double answers.
            new SessionVisibilityHelper(
                $this->createMock(TrackECourseAccessRepository::class),
                $settingsManager
            ),
            $studentViewHelper,
            new UserHelper($security),
            $extraFieldValues,
        );
    }

    private function course(bool $hasUserAsTeacher = false, bool $hidden = false): Course
    {
        $course = $this->createMock(Course::class);
        $course->method('getId')->willReturn(1);
        $course->method('isHidden')->willReturn($hidden);
        $course->method('hasUserAsTeacher')->willReturn($hasUserAsTeacher);

        return $course;
    }

    private function session(
        bool $courseCoachHere = false,
        bool $generalCoach = false,
        int $visibility = Session::AVAILABLE,
    ): Session {
        $session = $this->createMock(Session::class);
        $session->method('getId')->willReturn(7);
        $session->method('getDuration')->willReturn(0);
        $session->method('hasCourseCoachInCourse')->willReturn($courseCoachHere);
        $session->method('hasUserAsGeneralCoach')->willReturn($generalCoach);
        $session->method('setAccessVisibilityByUser')->willReturn($visibility);

        return $session;
    }

    private function lockValue(): ExtraFieldValues
    {
        $value = $this->createMock(ExtraFieldValues::class);
        $value->method('getFieldValue')->willReturn('1');

        return $value;
    }
}
