<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\MessageTag;
use Chamilo\CoreBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends Voter<'CREATE'|'VIEW'|'EDIT'|'DELETE', MessageTag>
 */
class MessageTagVoter extends Voter
{
    public const string CREATE = 'CREATE';
    public const string VIEW = 'VIEW';
    public const string EDIT = 'EDIT';
    public const string DELETE = 'DELETE';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager
    ) {}

    protected function supports(string $attribute, $subject): bool
    {
        $options = [
            self::CREATE,
            self::VIEW,
            self::EDIT,
            self::DELETE,
        ];

        // if the attribute isn't one we support, return false
        if (!\in_array($attribute, $options, true)) {
            return false;
        }

        // only vote on Post objects inside this voter
        return $subject instanceof MessageTag;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        /** @var User $user */
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        if ($this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        /** @var MessageTag $messageTag */
        $messageTag = $subject;

        switch ($attribute) {
            case self::CREATE:
            case self::VIEW:
            case self::EDIT:
            case self::DELETE:
                if ($messageTag->getUser() === $user) {
                    return true;
                }

                break;
        }

        return false;
    }
}
