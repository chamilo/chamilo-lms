<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ServiceHelper;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
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

        $isSessionAdminAllowedToEdit = 'true' === $this->settingsManager->getSetting('session.session_admins_edit_courses_content');

        if ($user->isAdmin() || ($user->isSessionAdmin() && $isSessionAdminAllowedToEdit)) {
            if ($checkStudentView && $studentViewIsActive) {
                return false;
            }

            return true;
        }

        $session = $session ?: $this->cidReqHelper->getSessionEntity();
        $course = $course ?: $this->cidReqHelper->getCourseEntity();

        if ($session && $course && 'true' === $this->settingsManager->getSetting('session.session_courses_read_only_mode')) {
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
        $sessionVisibility = $session?->setAccessVisibilityByUser($user);
        $isCourseAdmin = $user->hasRole('ROLE_CURRENT_COURSE_TEACHER') || $user->hasRole('ROLE_CURRENT_COURSE_SESSION_TEACHER');

        if (!$isCourseAdmin && $tutor) {
            $isCourseAdmin = $user->isCourseTutor($course, $session);
        }

        if (!$isCourseAdmin && $coach) {
            if (Session::READ_ONLY === $sessionVisibility) {
                $isCoachAllowedToEdit = false;
            }

            if ('true' === $this->settingsManager->getSetting('session.allow_coach_to_edit_course_session')) {
                $isCourseAdmin = $isCoachAllowedToEdit;
            }
        }

        if (!$isCourseAdmin && $sessionCoach) {
            $isCourseAdmin = $isCoachAllowedToEdit;
        }

        if ('true' !== $this->settingsManager->getSetting('course.student_view_enabled')) {
            return $isCourseAdmin;
        }

        if ($session) {
            if (Session::READ_ONLY === $sessionVisibility) {
                $isCoachAllowedToEdit = false;
            }

            $isAllowed = 'true' === $this->settingsManager->getSetting('session.allow_coach_to_edit_course_session') && $isCoachAllowedToEdit;

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
}
