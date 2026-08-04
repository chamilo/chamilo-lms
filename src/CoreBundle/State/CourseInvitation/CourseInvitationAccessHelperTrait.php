<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseInvitation;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

trait CourseInvitationAccessHelperTrait
{
    private function getCourse(Request $request, EntityManagerInterface $entityManager): Course
    {
        $courseId = $request->query->getInt('cid');
        if ($courseId <= 0) {
            throw new BadRequestHttpException('A valid course id is required.');
        }

        $course = $entityManager->getRepository(Course::class)->find($courseId);
        if (!$course instanceof Course) {
            throw new BadRequestHttpException('The requested course was not found.');
        }

        return $course;
    }

    private function getSession(Request $request, EntityManagerInterface $entityManager): ?Session
    {
        $sessionId = $request->query->getInt('sid');
        if ($sessionId <= 0) {
            return null;
        }

        $session = $entityManager->getRepository(Session::class)->find($sessionId);
        if (!$session instanceof Session) {
            throw new BadRequestHttpException('The requested session was not found.');
        }

        return $session;
    }

    private function assertSessionBelongsToCourse(?Session $session, Course $course): void
    {
        if (!$session instanceof Session || $session->hasCourse($course)) {
            return;
        }

        throw new BadRequestHttpException('The requested session does not contain the current course.');
    }

    /**
     * Chamilo cannot subscribe a user to "this course within this session"
     * as a distinct action — only to a base course or to a whole session.
     * So, unlike most other course-scoped contextual checks in this
     * codebase, a plain course-teacher/course-coach is deliberately NOT
     * enough to send a whole-session invitation: only a platform admin,
     * session admin, or the session's own general coach may do that,
     * because sending one subscribes the recipient to every course in the
     * session, not just the one whose Users tool is currently open.
     */
    private function canManageCourseInvitations(Security $security, Course $course, ?Session $session): bool
    {
        if ($security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $user = $security->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if (!$session instanceof Session) {
            return $course->hasUserAsTeacher($user) || $security->isGranted('ROLE_CURRENT_COURSE_TEACHER');
        }

        return $user->isSessionAdmin() || $session->hasUserAsGeneralCoach($user);
    }
}
