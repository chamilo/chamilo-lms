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
    public const string DELETE = 'DELETE';
    public const string VIEW = 'VIEW';
    public const string EDIT = 'EDIT';

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

        // Each row carries one participant's own mailbox state, so only that
        // participant may read, change or delete it — the rule the collection
        // extension already enforces. Checking message membership instead would
        // let any co-recipient (and the sender) act on another participant's row.
        $isOwnRow = $subject->getReceiver()->getId() === $user->getId();

        return match ($attribute) {
            self::VIEW, self::EDIT, self::DELETE => $isOwnRow,
            default => false,
        };
    }
}
