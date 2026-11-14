<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationComment;

/**
 * Who may read and write the teacher-only (private) comments of an assignment submission.
 *
 * The teachers of a submission are the people who correct it: the teachers and tutors of the
 * course it was sent in, and the general and course coaches of its session. They are looked up
 * on the submission's own resource links, never on the course or session of the request, so a
 * teacher of one course cannot read the private notes of another one by changing the cid.
 */
final class StudentPublicationCommentVisibilityHelper
{
    public static function canSeeTeacherOnlyComments(CStudentPublication $submission, ?User $user): bool
    {
        if (!$user instanceof User || null === $user->getId()) {
            return false;
        }

        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        $resourceNode = $submission->getResourceNode();
        if (null === $resourceNode) {
            return false;
        }

        foreach ($resourceNode->getResourceLinks() as $link) {
            if (!$link instanceof ResourceLink) {
                continue;
            }

            $course = $link->getCourse();
            if (!$course instanceof Course) {
                continue;
            }

            if ($course->hasUserAsTeacher($user) || true === $course->getSubscriptionByUser($user)?->isTutor()) {
                return true;
            }

            $session = $link->getSession();
            if ($session instanceof Session
                && ($session->hasCourseCoachInCourse($user, $course) || $session->hasUserAsGeneralCoach($user))
            ) {
                return true;
            }
        }

        return false;
    }

    public static function canSeeComment(CStudentPublicationComment $comment, ?User $user): bool
    {
        return !$comment->isTeacherOnly()
            || self::canSeeTeacherOnlyComments($comment->getPublication(), $user);
    }
}
