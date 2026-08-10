<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\CourseInvitation;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseInvitation;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use CourseManager;
use RuntimeException;
use SessionManager;

/**
 * Shared subscribe step for both registration invitations and existing-user
 * login-and-join invitations. Keeps CourseManager / SessionManager usage in
 * one place so the two accept paths cannot drift.
 */
final class CourseInvitationSubscriptionService
{
    public function subscribe(User $user, CourseInvitation $invitation): void
    {
        $userId = (int) $user->getId();
        $session = $invitation->getSession();
        $course = $invitation->getCourse();

        if (null !== $session) {
            SessionManager::subscribeUsersToSession(
                (int) $session->getId(),
                [$userId],
                SESSION_VISIBLE_READ_ONLY,
                false,
            );

            return;
        }

        if (null !== $course) {
            CourseManager::subscribeUser(
                $userId,
                (int) $course->getId(),
                STUDENT,
                0,
                0,
                true,
                ['flash' => false, 'result' => true],
            );

            return;
        }

        throw new RuntimeException('The invitation has neither a course nor a session.');
    }

    public function isAlreadySubscribed(User $user, Course $course, ?Session $session): bool
    {
        if ($session instanceof Session) {
            foreach ($session->getUsers() as $subscription) {
                if ((int) $subscription->getUser()->getId() === (int) $user->getId()) {
                    return true;
                }
            }

            return false;
        }

        return $course->hasSubscriptionByUser($user);
    }

    public function isAlreadySubscribedToInvitation(User $user, CourseInvitation $invitation): bool
    {
        $course = $invitation->getCourse();
        if (!$course instanceof Course) {
            // Whole-session invitations still store the "sent from" course;
            // without any course we can only check the session membership.
            $session = $invitation->getSession();
            if (!$session instanceof Session) {
                return false;
            }

            foreach ($session->getUsers() as $subscription) {
                if ((int) $subscription->getUser()->getId() === (int) $user->getId()) {
                    return true;
                }
            }

            return false;
        }

        return $this->isAlreadySubscribed($user, $course, $invitation->getSession());
    }

    public function buildCourseHomeUrl(CourseInvitation $invitation): string
    {
        $course = $invitation->getCourse();
        $courseId = null !== $course ? (int) $course->getId() : 0;
        $sessionId = (int) ($invitation->getSession()?->getId() ?? 0);

        if ($courseId <= 0) {
            return api_get_path(WEB_PATH);
        }

        $query = http_build_query(array_filter([
            'cid' => $courseId,
            'sid' => $sessionId > 0 ? $sessionId : null,
        ]));

        return api_get_path(WEB_PATH).'course/'.$courseId.'/home'.($query ? '?'.$query : '');
    }
}
