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

    /**
     * Whether the PDF export of these submissions should offer to include private comments.
     * The flag is true only when the user may see them and at least one is present.
     *
     * @param iterable<CStudentPublication> $submissions
     */
    public static function exportContainsVisibleTeacherOnlyComment(iterable $submissions, ?User $user): bool
    {
        foreach ($submissions as $submission) {
            if (!self::canSeeTeacherOnlyComments($submission, $user)) {
                continue;
            }
            foreach ($submission->getComments() as $comment) {
                if ($comment instanceof CStudentPublicationComment && $comment->isTeacherOnly()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Comments that belong in a PDF export of one submission.
     *
     * Teacher-only comments are included only when the caller asked for them and
     * the user is one of the submission's teachers. Learners never receive them,
     * even if the include parameter is set.
     *
     * @return list<CStudentPublicationComment>
     */
    public static function commentsForPdfExport(
        CStudentPublication $submission,
        ?User $user,
        bool $includeTeacherOnlyComments
    ): array {
        $comments = [];
        foreach ($submission->getComments() as $comment) {
            if (!$comment instanceof CStudentPublicationComment) {
                continue;
            }
            if (!$comment->isTeacherOnly()) {
                $comments[] = $comment;

                continue;
            }
            if ($includeTeacherOnlyComments && self::canSeeTeacherOnlyComments($submission, $user)) {
                $comments[] = $comment;
            }
        }

        return $comments;
    }
}
