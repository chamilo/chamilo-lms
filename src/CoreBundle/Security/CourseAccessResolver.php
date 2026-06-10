<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CGroup;

use const FILTER_VALIDATE_BOOLEAN;

/**
 * Resolves the contextual ROLE_CURRENT_COURSE_* roles a User has within a given
 * Course, optional course Session, and optional CGroup.
 *
 * Pure service: never mutates the User, the security token, or persists anything.
 * It mirrors the relationship logic embedded in CourseVoter / SessionVoter /
 * GroupVoter so that role assignment can happen in a dedicated request listener
 * instead of as a side effect inside Voters.
 */
final class CourseAccessResolver
{
    public function __construct(
        private readonly SettingsManager $settingsManager,
    ) {}

    /**
     * @return array<int, string> ROLE_CURRENT_COURSE_* role names the user holds in the given course/session
     */
    public function resolveCourseRoles(User $user, Course $course, ?Session $session = null): array
    {
        if ($course->isHidden()) {
            return [];
        }

        if ($course->isPublic()) {
            return $this->courseRolesForAccessibleCourse($user, $course);
        }

        if (Course::OPEN_PLATFORM === $course->getVisibility()
            && false === $this->isOpenCourseAccessBlockedForRegisteredUsers()
        ) {
            return $this->courseRolesForAccessibleCourse($user, $course);
        }

        if (null !== $session) {
            if ($session->hasUserAsGeneralCoach($user)
                || $session->hasCourseCoachInCourse($user, $course)
            ) {
                return [ResourceNodeVoter::ROLE_CURRENT_COURSE_SESSION_TEACHER];
            }

            if ($session->hasUserInCourse($user, $course, Session::STUDENT)) {
                return [ResourceNodeVoter::ROLE_CURRENT_COURSE_SESSION_STUDENT];
            }
        }

        if (Course::REGISTERED === $course->getVisibility()
            && $course->hasSubscriptionByUser($user)
        ) {
            return $this->courseRolesForAccessibleCourse($user, $course);
        }

        return [];
    }

    /**
     * @return array<int, string> ROLE_CURRENT_COURSE_GROUP_* role names the user holds in the given group
     */
    public function resolveGroupRoles(User $user, Course $course, CGroup $group): array
    {
        if ($course->isHidden()) {
            return [];
        }

        if (Course::REGISTERED === $course->getVisibility()
            && false === $course->hasSubscriptionByUser($user)
        ) {
            return [];
        }

        if ($course->hasUserAsTeacher($user) || $group->hasTutor($user)) {
            return [ResourceNodeVoter::ROLE_CURRENT_COURSE_GROUP_TEACHER];
        }

        if ($group->hasMember($user)) {
            return [ResourceNodeVoter::ROLE_CURRENT_COURSE_GROUP_STUDENT];
        }

        return [];
    }

    /**
     * @return array<int, string>
     */
    private function courseRolesForAccessibleCourse(User $user, Course $course): array
    {
        $roles = [ResourceNodeVoter::ROLE_CURRENT_COURSE_STUDENT];

        if ($course->hasUserAsTeacher($user)) {
            $roles[] = ResourceNodeVoter::ROLE_CURRENT_COURSE_TEACHER;
        }

        return $roles;
    }

    private function isOpenCourseAccessBlockedForRegisteredUsers(): bool
    {
        return filter_var(
            $this->settingsManager->getSetting('course.block_registered_users_access_to_open_course_contents', true),
            FILTER_VALIDATE_BOOLEAN,
        );
    }
}
