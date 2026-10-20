<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Assignment;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Tells whether a user coaches an assignment in one given session.
 *
 * ResourceNodeVoter only lets session coaches edit resources linked to their own
 * session, so a base-course assignment, which every session of the course shows,
 * stays out of their reach. Following such an assignment is not editing it, so
 * the follow endpoints and the notifier accept a coach through this check instead.
 * It is scoped to one session and one course: the session must hold the course,
 * the assignment must be available in that session (base-course link or a link to
 * that very session), and the user must coach that course in it or be the
 * session's general coach.
 */
final class AssignmentSessionCoachAccess
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    /**
     * Same check from the ids of the request context (cid/sid). The entities are
     * loaded again, so a stale copy kept in the HTTP session is never trusted.
     */
    public function isCoachInSessionById(CStudentPublication $assignment, User $user, int $courseId, int $sessionId): bool
    {
        if ($courseId <= 0 || $sessionId <= 0) {
            return false;
        }

        $course = $this->entityManager->find(Course::class, $courseId);
        $session = $this->entityManager->find(Session::class, $sessionId);

        return $course instanceof Course
            && $session instanceof Session
            && $this->isCoachInSession($assignment, $user, $course, $session);
    }

    public function isCoachInSession(CStudentPublication $assignment, User $user, Course $course, Session $session): bool
    {
        if (!$session->hasCourse($course) || !$this->isAvailableInSession($assignment, $course, $session)) {
            return false;
        }

        return $session->hasCourseCoachInCourse($user, $course) || $session->hasUserAsGeneralCoach($user);
    }

    private function isAvailableInSession(CStudentPublication $assignment, Course $course, Session $session): bool
    {
        $resourceNode = $assignment->getResourceNode();
        if (null === $resourceNode) {
            return false;
        }

        foreach ($resourceNode->getResourceLinks() as $link) {
            // A link sent to one user only does not make the assignment part of the session.
            if (null !== $link->getUser() || $link->getCourse()?->getId() !== $course->getId()) {
                continue;
            }

            $linkSession = $link->getSession();
            if (null === $linkSession || $linkSession->getId() === $session->getId()) {
                return true;
            }
        }

        return false;
    }
}
