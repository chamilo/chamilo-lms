<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class UserVoter extends Voter
{
    public const CREATE = 'CREATE';
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    public function __construct(
        private Security $security
    ) {
    }

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

        return $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var User $currentUSer */
        $currentUSer = $token->getUser();

        if (!$currentUSer instanceof UserInterface) {
            return false;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        /** @var User $user */
        $user = $subject;

        if (self::VIEW === $attribute) {
            if ($currentUSer === $user) {
                return true;
            }

            if ($user->hasFriendWithRelationType($currentUSer, UserRelUser::USER_RELATION_TYPE_FRIEND)) {
                return true;
            }
        }

        return false;
    }
}
