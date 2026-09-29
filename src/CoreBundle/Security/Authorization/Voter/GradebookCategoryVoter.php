<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Grants write access to a gradebook category based on the course and session
 * the category itself belongs to, not on the ones carried by the request.
 *
 * @extends Voter<'EDIT'|'DELETE', GradebookCategory>
 */
class GradebookCategoryVoter extends Voter
{
    public const string EDIT = 'EDIT';
    public const string DELETE = 'DELETE';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {}

    protected function supports(string $attribute, $subject): bool
    {
        $allowed = [
            self::EDIT,
            self::DELETE,
        ];

        return $subject instanceof GradebookCategory && \in_array($attribute, $allowed, true);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        /** @var GradebookCategory $category */
        $category = $subject;
        $course = $category->getCourse();

        // Teacher of the base course the category belongs to.
        if ($course->hasUserAsTeacher($user)) {
            return true;
        }

        // Coach of the session the category belongs to.
        $session = $category->getSession();

        return null !== $session
            && ($session->hasUserAsGeneralCoach($user) || $session->hasCourseCoachInCourse($user, $course));
    }
}
