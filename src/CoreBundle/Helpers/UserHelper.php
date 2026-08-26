<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

readonly class UserHelper
{
    public function __construct(
        private Security $security,
    ) {}

    public function getCurrent(): ?User
    {
        /** @var User|null $user */
        $user = $this->security->getUser();

        return $user instanceof UserInterface ? $user : null;
    }

    /**
     * Whether the current user teaches the current course context, base course or session.
     *
     * Mirrors the legacy api_is_course_admin(). This is the role on its own: it says nothing
     * about whether the user may edit anything right now, since the student view and the
     * session rules do not enter into it. Use IsAllowedToEditHelper::check() for that, and
     * this one for read gates, which must keep letting a teacher see a tool they cannot
     * currently change.
     */
    public function isTeacherOfCurrentCourse(): bool
    {
        return $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
    }

    /**
     * Whether the current user belongs to the current course context, base course or session.
     *
     * Only the student roles are named because the role hierarchy has each teacher role imply
     * its student one, and ROLE_ADMIN imply all of them.
     */
    public function isMemberOfCurrentCourse(): bool
    {
        return $this->security->isGranted('ROLE_CURRENT_COURSE_STUDENT')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_STUDENT');
    }
}
