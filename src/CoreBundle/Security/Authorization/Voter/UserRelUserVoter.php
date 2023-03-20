<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class UserRelUserVoter extends Voter
{
    public const CREATE = 'CREATE';
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    private EntityManagerInterface $entityManager;
    private Security $security;

    public function __construct(
        EntityManagerInterface $entityManager,
        Security $security
    ) {
        $this->entityManager = $entityManager;
        $this->security = $security;
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

        // only vote on Post objects inside this voter
        return $subject instanceof UserRelUser;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        // Admins have access to everything.
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        /** @var UserRelUser $userRelUser */
        $userRelUser = $subject;

        switch ($attribute) {
            case self::CREATE:
                if ($userRelUser->getUser() === $user) {
                    return true;
                }

                break;
            case self::EDIT:
                if ($userRelUser->getUser() === $user) {
                    return true;
                }

                if ($userRelUser->getFriend() === $user &&
                    UserRelUser::USER_RELATION_TYPE_FRIEND_REQUEST === $userRelUser->getRelationType()
                ) {
                    return true;
                }

                break;
            case self::VIEW:
                return true;
            case self::DELETE:
                if ($userRelUser->getUser() === $user) {
                    return true;
                }

                if ($userRelUser->getFriend() === $user) {
                    return true;
                }

                break;
        }

        return false;
    }
}
