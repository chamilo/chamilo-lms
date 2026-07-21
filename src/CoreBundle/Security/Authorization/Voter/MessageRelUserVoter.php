<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\MessageRelUser;
use Chamilo\CoreBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends Voter<'DELETE'|'VIEW'|'EDIT', MessageRelUser>
 */
class MessageRelUserVoter extends Voter
{
    public const DELETE = 'DELETE';
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::DELETE, self::VIEW, self::EDIT])
            && $subject instanceof MessageRelUser;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        if ($this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        \assert($user instanceof User);
        \assert($subject instanceof MessageRelUser);

        $message = $subject->getMessage();
        $isReceiver = $message->hasUserReceiver($user);

        return match ($attribute) {
            self::VIEW, self::EDIT, self::DELETE => $isReceiver,
            default => false,
        };
    }
}
