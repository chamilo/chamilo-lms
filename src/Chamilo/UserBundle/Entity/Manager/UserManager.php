<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\UserBundle\Entity\Manager;

use Chamilo\UserBundle\Entity\User;
use Sonata\UserBundle\Entity\UserManager as BaseUserManager;

/**
 * Class UserManager.
 */
class UserManager extends BaseUserManager
{
    /**
     * Finds a user either by confirmation token.
     *
     * @param string $token
     *
     * @return User
     */
    public function findUserByConfirmationToken($token)
    {
        return $this->findUserBy(['confirmationToken' => $token]);
    }
}
