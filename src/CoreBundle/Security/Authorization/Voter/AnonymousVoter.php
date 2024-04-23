<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<'ROLE_ANONYMOUS', User>
 */
class AnonymousVoter extends Voter
{
    protected function supports(string $attribute, $subject): bool
    {
        return 'ROLE_ANONYMOUS' === $attribute;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return User::ANONYMOUS === $user->getStatus();
    }
}
