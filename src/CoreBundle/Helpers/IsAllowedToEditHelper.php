<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\TrackECourseAccessRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use ExtraFieldValue;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class IsAllowedToEditHelper
{
    public function __construct(
        private SettingsManager $settingsManager,
        private Security $security,
        private RequestStack $requestStack,
        private CidReqHelper $cidReqHelper,
        private TrackECourseAccessRepository $trackECourseAccessRepository,
    ) {}

    public function check(
        bool $tutor = false,
        bool $coach = false,
        bool $sessionCoach = false,
        bool $checkStudentView = true,
        ?Course $course = null,
        ?Session $session = null,
    ): bool {
        /** @var User $user */
        $user = $this->security->getUser();

        $studentViewIsActive = 'studentview' === $this->requestStack->getSession()->get('studentview');

        $isSessionAdminAllowedToEdit = 'true' === $this->settingsManager->getSetting('session.session_admins_edit_courses_content', true);

        if ($user->isAdmin() || ($user->isSessionAdmin() && $isSessionAdminAllowedToEdit)) {
            if ($checkStudentView && $studentViewIsActive) {
                return false;
            }

            return true;
        }

        $session = $session ?: $this->cidReqHelper->getSessionEntity();
        $course = $course ?: $this->cidReqHelper->getCourseEntity();

        if ($session && $course && 'true' === $this->settingsManager->getSetting('session.session_courses_read_only_mode', true)) {
            $lockExrafieldField = (new ExtraFieldValue('course'))
                ->get_values_by_handler_and_field_variable(
                    $course->getId(),
                    'session_courses_read_only_mode'
                )
            ;

            if (!empty($lockExrafieldField['value'])) {
                return false;
            }
        }

        $isCoachAllowedToEdit = $session?->hasCoach($user) && !$studentViewIsActive;

        // Read the setting to determine if coaches should always have access after duration ends.
        // Use the repository directly to avoid stale Doctrine lazy collection on User entity.
        $coachAccessAfterDurationEnd = 'true' === $this->settingsManager->getSetting('session.session_coach_access_after_duration_end', true);
        $sessionVisibility = $session
            ? $this->getSessionVisibility($session, $user, $coachAccessAfterDurationEnd)
            : null;

        $isCourseAdmin = $user->hasRole('ROLE_CURRENT_COURSE_TEACHER') || $user->hasRole('ROLE_CURRENT_COURSE_SESSION_TEACHER');

        if (!$isCourseAdmin && $tutor) {
            $isCourseAdmin = $user->isCourseTutor($course, $session);
        }

        if (!$isCourseAdmin && $coach) {
            if (Session::READ_ONLY === $sessionVisibility) {
                $isCoachAllowedToEdit = false;
            }

            if ('true' === $this->settingsManager->getSetting('session.allow_coach_to_edit_course_session', true)) {
                $isCourseAdmin = $isCoachAllowedToEdit;
            }
        }

        if (!$isCourseAdmin && $sessionCoach) {
            $isCourseAdmin = $isCoachAllowedToEdit;
        }

        if ('true' !== $this->settingsManager->getSetting('course.student_view_enabled', true)) {
            return $isCourseAdmin;
        }

        if ($session) {
            if (Session::READ_ONLY === $sessionVisibility) {
                $isCoachAllowedToEdit = false;
            }

            $isAllowed = 'true' === $this->settingsManager->getSetting('session.allow_coach_to_edit_course_session', true) && $isCoachAllowedToEdit;

            if ($checkStudentView) {
                $isAllowed = $isAllowed && !$studentViewIsActive;
            }
        } elseif ($checkStudentView) {
            $isAllowed = $isCourseAdmin && !$studentViewIsActive;
        } else {
            $isAllowed = $isCourseAdmin;
        }

        return $isAllowed;
    }

    /**
     * Calculate session visibility using a fresh DB query for duration-based sessions,
     * falling back to the standard method for date-based sessions.
     */
    private function getSessionVisibility(Session $session, User $user, bool $coachAccessAfterDurationEnd): int
    {
        // Only use the repository path for duration-based sessions without fixed dates.
        if ($session->getDuration() > 0 && !$session->getAccessStartDate() && !$session->getAccessEndDate()) {
            // If setting is enabled, coaches always have access regardless of duration end.
            if ($coachAccessAfterDurationEnd && $session->hasCoach($user)) {
                return Session::AVAILABLE;
            }

            $duration = $session->getDuration() * 24 * 60 * 60;

            // Use repository directly to avoid stale Doctrine lazy collection.
            $firstAccess = $this->trackECourseAccessRepository->findFirstAccessByUserAndSession(
                $user,
                $session->getId()
            );

            // If no previous access exists, session is still available.
            if (!$firstAccess) {
                return Session::AVAILABLE;
            }

            $userSessionSubscription = $user->getSubscriptionToSession($session);
            $userDuration = $userSessionSubscription
                ? $userSessionSubscription->getDuration() * 24 * 60 * 60
                : 0;

            $firstAccessTimestamp = $firstAccess->getLoginCourseDate()->getTimestamp();
            $totalDuration = $firstAccessTimestamp + $duration + $userDuration;

            return $totalDuration > time() ? Session::AVAILABLE : $session->getVisibility();
        }

        // Fall back to standard method for date-based sessions.
        return $session->setAccessVisibilityByUser($user, true, $coachAccessAfterDurationEnd);
    }

    /**
     * Checks whether current user is allowed to create courses.
     */
    public function canCreateCourse(): bool
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isTeacher()) {
            return 'true' === $this->settingsManager->getSetting('workflows.allow_users_to_create_courses', true);
        }

        return $this->requestStack->getSession()->get('is_allowedCreateCourse');
    }
}
