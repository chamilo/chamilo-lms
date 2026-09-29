<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Single entry point for "may the current user edit content in this course".
 *
 * Port of the legacy api_is_allowed_to_edit(), with one deliberate deviation
 * documented in check().
 */
readonly class IsAllowedToEditHelper
{
    public function __construct(
        private SettingsManager $settingsManager,
        private Security $security,
        private RequestStack $requestStack,
        private CidReqHelper $cidReqHelper,
        private SessionVisibilityHelper $sessionVisibilityHelper,
        private StudentViewHelper $studentViewHelper,
        private UserHelper $userHelper,
        private ExtraFieldValuesRepository $extraFieldValuesRepository,
    ) {}

    /**
     * @param bool $checkStudentView pass false when the caller needs the raw permission, e.g. to let
     *                               a teacher preview unpublished content while in the student view
     */
    public function check(
        bool $tutor = false,
        bool $coach = false,
        bool $sessionCoach = false,
        bool $checkStudentView = true,
        ?Course $course = null,
        ?Session $session = null,
    ): bool {
        $user = $this->userHelper->getCurrent();

        if (null === $user) {
            return false;
        }

        // The entities the HTTP session carries come back detached, and the collections walked
        // below (coach and teacher subscriptions) need a live manager.
        $course ??= $this->cidReqHelper->getDoctrineCourseEntity();
        $session ??= $this->cidReqHelper->getDoctrineSessionEntity();

        $studentViewIsActive = $checkStudentView && $this->studentViewHelper->isActive();

        // isGranted() applies the role hierarchy, so ROLE_GLOBAL_ADMIN is covered here;
        // User::hasRole() would not have matched it.
        if ($this->security->isGranted('ROLE_ADMIN')
            || (
                $this->security->isGranted('ROLE_SESSION_MANAGER')
                && 'true' === $this->settingsManager->getSetting('session.session_admins_edit_courses_content', true)
            )
        ) {
            return !$studentViewIsActive;
        }

        if ($session instanceof Session && $course instanceof Course && $this->isCourseLockedInsideSessions($course)) {
            return false;
        }

        // Restricted to the current course on purpose: Session::hasCoach() also matches a coach
        // of any other course of the session, while the legacy api_is_coach() joins on c_id.
        // These two terms are what CourseAccessResolver grants
        // ROLE_CURRENT_COURSE_SESSION_TEACHER for.
        $isCoachAllowedToEdit = $session instanceof Session
            && !$studentViewIsActive
            && Session::READ_ONLY !== $this->sessionVisibilityHelper->getSessionVisibility($session, $user)
            && ($session->hasUserAsGeneralCoach($user) || $session->hasCourseCoachInCourse($user, $course));

        $coachMayEdit = $isCoachAllowedToEdit
            && 'true' === $this->settingsManager->getSetting('session.allow_coach_to_edit_course_session', true);

        // The entity term covers a CLOSED course, where CourseAccessResolver grants no contextual
        // role at all even though CourseVoter still lets the subscribed teacher in.
        $isCourseAdmin = $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
            || ($course instanceof Course && !$course->isHidden() && $course->hasUserAsTeacher($user));

        if (!$isCourseAdmin && $tutor) {
            $isCourseAdmin = $user->isCourseTutor($course, $session);
        }

        if (!$isCourseAdmin && $coach) {
            $isCourseAdmin = $coachMayEdit;
        }

        if (!$isCourseAdmin && $sessionCoach) {
            $isCourseAdmin = $isCoachAllowedToEdit;
        }

        // DELIBERATE DEVIATION from the legacy session branch, which discarded $isCourseAdmin and
        // returned the coach term alone. 1.11 could afford that because local.inc.php demoted the
        // base course teacher on session entry; CourseAccessResolver does not, so a base course
        // teacher keeps ROLE_CURRENT_COURSE_TEACHER inside a session. Reproducing the legacy
        // branch would deny every base course teacher inside a session, in every tool at once.
        //
        // $coachMayEdit is ORed regardless of the $coach flag, which is faithful: the legacy
        // session branch granted session coaches unconditionally when
        // allow_coach_to_edit_course_session was on. Outside a session it is always false, so the
        // flags behave as before.
        return ($isCourseAdmin || $coachMayEdit) && !$studentViewIsActive;
    }

    /**
     * Checks whether current user is allowed to create courses.
     */
    public function canCreateCourse(): bool
    {
        $user = $this->userHelper->getCurrent();

        if (null === $user) {
            return false;
        }

        // Literal role checks on purpose: isGranted() would let the hierarchy in, and
        // ROLE_HR implies ROLE_TEACHER, which would hand HR managers course creation the
        // moment allow_users_to_create_courses is turned on. Whether that is desirable is a
        // separate decision from this helper's editing rules.
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isTeacher()) {
            return 'true' === $this->settingsManager->getSetting('workflows.allow_users_to_create_courses', true);
        }

        // Written by LegacyListener, so it is missing outside a web request.
        return (bool) $this->requestStack->getSession()->get('is_allowedCreateCourse');
    }

    /**
     * Whether an administrator froze this course's content for every session that uses it.
     *
     * Public because tools whose own rules go beyond check() still have to respect the lock.
     */
    public function isCourseLockedInsideSessions(Course $course): bool
    {
        if ('true' !== $this->settingsManager->getSetting('session.session_courses_read_only_mode', true)) {
            return false;
        }

        $extraFieldValue = $this->extraFieldValuesRepository->getValueByVariableAndItem(
            'session_courses_read_only_mode',
            (int) $course->getId(),
            ExtraField::COURSE_FIELD_TYPE,
        );

        return $extraFieldValue instanceof ExtraFieldValues && !empty($extraFieldValue->getFieldValue());
    }
}
