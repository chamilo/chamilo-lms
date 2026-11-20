<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;

use const FILTER_VALIDATE_BOOLEAN;

/**
 * Checks direct-course access separately from platform user state and session membership.
 */
final readonly class CourseStudentAccessChecker
{
    public function __construct(
        private SettingsManager $settingsManager
    ) {}

    public function isEnabled(): bool
    {
        return filter_var(
            $this->settingsManager->getSetting('workflows.teacher_defined_student_access_to_course'),
            FILTER_VALIDATE_BOOLEAN,
        );
    }

    public function isDisabled(User $user, Course $course, ?Session $session = null): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $subscription = $course->getSubscriptionByUser($user);
        if (!$subscription instanceof CourseRelUser
            || CourseRelUser::STUDENT !== $subscription->getStatus()
            || 0 !== $subscription->getVisibility()
        ) {
            return false;
        }

        // A sid parameter alone must not bypass the suspended direct subscription.
        return !$session instanceof Session
            || !$session->hasCourse($course)
            || !(
                $session->hasUserInCourse($user, $course, Session::STUDENT)
                || $session->hasUserAsGeneralCoach($user)
                || $session->hasCourseCoachInCourse($user, $course)
            );
    }
}
