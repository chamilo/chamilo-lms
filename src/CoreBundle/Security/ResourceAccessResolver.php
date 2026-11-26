<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\Common\Collections\Criteria;

use const FILTER_VALIDATE_BOOLEAN;

/**
 * Answers who takes part in the courses a resource node is linked to, from the node's own links.
 *
 * Unlike the ROLE_CURRENT_COURSE_* roles, it does not depend on the course context of the request,
 * so it also serves requests that carry no cid (e.g. the files of a SCORM package).
 */
final class ResourceAccessResolver
{
    public function __construct(
        private readonly SettingsManager $settingsManager,
    ) {}

    /**
     * A course open to the world opens its published base-course content for reading only. A draft
     * or pending link, or one scoped to a session, group or single user, stays private.
     */
    public function isOpenToVisitors(ResourceLink $link): bool
    {
        $course = $link->getCourse();

        return $course instanceof Course
            && $course->isPublic()
            && ResourceLink::VISIBILITY_PUBLISHED === $link->getVisibility()
            && !$link->getSession() instanceof Session
            && !$link->getGroup() instanceof CGroup
            && null === $link->getUser();
    }

    /**
     * VISIBILITY_PUBLISHED means published inside its own course, never portal-wide: the course's
     * own visibility answers that. A course link therefore needs an open course or a real
     * subscription to it.
     */
    public function mayReadPublishedLink(ResourceLink $link, ResourceNode $resourceNode, ?User $user): bool
    {
        $linkCourse = $link->getCourse();
        if (!$linkCourse instanceof Course) {
            return true;
        }

        if ($linkCourse->isPublic()) {
            return true;
        }

        // An OPEN_PLATFORM course opens its contents to every registered user, unless the
        // administrator requires a subscription first. CourseVoter reads the same pair.
        if (Course::OPEN_PLATFORM === $linkCourse->getVisibility()
            && !filter_var(
                $this->settingsManager->getSetting('course.block_registered_users_access_to_open_course_contents', true),
                FILTER_VALIDATE_BOOLEAN,
            )
        ) {
            return true;
        }

        return $user instanceof User && $this->belongsToResourceCourse($resourceNode, $user);
    }

    /**
     * Whether the user takes part in one of the courses the resource is linked to,
     * as a real subscription rather than as a visitor of an open course.
     */
    public function belongsToResourceCourse(ResourceNode $resourceNode, User $user): bool
    {
        foreach ($resourceNode->getResourceLinks() as $link) {
            // Corrections and feedback are linked to the student they were sent to.
            $linkUser = $link->getUser();
            if ($linkUser instanceof User && $linkUser->getId() === $user->getId()) {
                return true;
            }

            $linkCourse = $link->getCourse();
            if (!$linkCourse instanceof Course) {
                continue;
            }

            $linkSession = $link->getSession();
            if ($linkSession instanceof Session) {
                if ($linkSession->hasUserAsGeneralCoach($user)
                    || $linkSession->hasCourseCoachInCourse($user, $linkCourse)
                    || $linkSession->hasUserInCourse($user, $linkCourse, Session::STUDENT)
                ) {
                    return true;
                }

                continue;
            }

            if ($linkCourse->hasUserAsTeacher($user)
                || $linkCourse->hasSubscriptionByUser($user)
                || $this->isSubscribedToCourseThroughSession($user, $linkCourse)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * The teaching half of belongsToResourceCourse(): the course the resource belongs to, not the
     * one the request carries, and without the student terms that method also accepts.
     */
    public function teachesResourceCourse(ResourceNode $resourceNode, User $user): bool
    {
        foreach ($resourceNode->getResourceLinks() as $link) {
            $linkCourse = $link->getCourse();
            if (!$linkCourse instanceof Course) {
                continue;
            }

            $linkSession = $link->getSession();
            if ($linkSession instanceof Session) {
                if ($linkSession->hasUserAsGeneralCoach($user)
                    || $linkSession->hasCourseCoachInCourse($user, $linkCourse)
                ) {
                    return true;
                }

                continue;
            }

            if ($linkCourse->hasUserAsTeacher($user)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Base-course content is shared with every session of the course, so a user enrolled in the
     * course only through a session (student or course coach, or general coach of a session
     * holding it) takes part in it too — the same terms CourseAccessResolver grants the session
     * context roles on.
     */
    private function isSubscribedToCourseThroughSession(User $user, Course $course): bool
    {
        $criteria = Criteria::create()->where(Criteria::expr()->eq('course', $course));
        if ($user->getSessionRelCourseRelUsers()->matching($criteria)->count() > 0) {
            return true;
        }

        foreach ($user->getSessionsAsGeneralCoach() as $session) {
            if ($session->hasCourse($course)) {
                return true;
            }
        }

        return false;
    }
}
