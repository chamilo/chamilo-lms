<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ServiceHelper;

use Chamilo\CoreBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserHelper
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
    ) { }

    public function getCurrent(): ?User
    {
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            return null;
        }

        /** @var User|null $user */
        $user = $token->getUser();

        return $user instanceof UserInterface ? $user : null;
    }
}
