<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CourseCatalogueHelper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<'CREATE'|'VIEW', CourseRelUser>
 */
class CourseRelUserVoter extends Voter
{
    public const CREATE = 'CREATE';
    public const VIEW = 'VIEW';

    public function __construct(
        private readonly Security $security,
        private readonly CourseCatalogueHelper $courseCatalogueHelper,
    ) {}

    protected function supports(string $attribute, $subject): bool
    {
        return \in_array($attribute, [self::CREATE, self::VIEW], true) && $subject instanceof CourseRelUser;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::CREATE => $this->canCreate($subject, $user),
            self::VIEW => $this->canView($subject, $user),
        };
    }

    private function canCreate(CourseRelUser $subject, User $user): bool
    {
        // Admins may subscribe any user to any course.
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // Teachers may subscribe any user, but only to a course where they themselves teach.
        if ($this->security->isGranted('ROLE_TEACHER') && $subject->getCourse()->hasUserAsTeacher($user)) {
            return true;
        }

        // Any other user may only subscribe themselves, and only to a course listed in the public catalogue.
        return $subject->getUser() === $user
            && $this->courseCatalogueHelper->isCourseInPublicCatalogue($subject->getCourse());
    }

    private function canView(CourseRelUser $subject, User $user): bool
    {
        // Teachers and session managers (and admins, who inherit both) may view any subscription;
        // any other user only their own.
        if ($this->security->isGranted('ROLE_TEACHER') || $this->security->isGranted('ROLE_SESSION_MANAGER')) {
            return true;
        }

        return $subject->getUser() === $user;
    }
}
