<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Traits;

use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * The two questions the exercise providers ask about the current user, which used to be
 * one method copied into 32 classes and therefore answered the same way in both cases.
 *
 * They are not the same question: the student view and the session rules must gate what a
 * teacher may change, but not what they may see.
 */
trait ExerciseAccessHelperTrait
{
    /**
     * The teacher role alone, with neither the student view nor the session rules applied.
     *
     * Read gates use this, because a teacher has to keep seeing the tool in a read-only
     * session, and so does the runtime when it decides whether this user may reach an
     * unpublished exercise: previewing one is not editing it.
     */
    private function isExerciseTeacher(Security $security): bool
    {
        return $security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
    }

    /**
     * Whether the current user may actually change exercises in this context.
     *
     * Delegates to the shared helper, so a teacher browsing in the student view, a coach of
     * a read-only session and a course frozen by session_courses_read_only_mode are all
     * denied here — none of which the plain role check used to notice.
     */
    private function canManageExercises(IsAllowedToEditHelper $isAllowedToEditHelper): bool
    {
        return $isAllowedToEditHelper->check(coach: true);
    }
}
