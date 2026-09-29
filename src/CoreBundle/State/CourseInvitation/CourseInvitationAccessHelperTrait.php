<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseInvitation;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

trait CourseInvitationAccessHelperTrait
{
    /**
     * CidReqListener already resolved and validated the course, so a missing entity here
     * can only mean the request carried no course context at all.
     */
    private function getCourse(CidReqHelper $cidReqHelper): Course
    {
        return $cidReqHelper->getDoctrineCourseEntity()
            ?? throw new BadRequestHttpException('A valid course id is required.');
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
