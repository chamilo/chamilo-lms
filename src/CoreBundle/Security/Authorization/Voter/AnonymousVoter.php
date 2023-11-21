<?php
/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Chamilo\CoreBundle\Entity\User;

class AnonymousVoter extends Voter
{
    protected function supports(string $attribute, $subject): bool
    {
        return $attribute === 'ROLE_ANONYMOUS';
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return $user->getStatus() === User::ANONYMOUS;
    }
}
