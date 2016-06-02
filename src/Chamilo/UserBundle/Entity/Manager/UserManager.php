<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\UserBundle\Entity\Manager;

use Sonata\UserBundle\Entity\UserManager as BaseUserManager;

/**
 * Class UserManager
 *
 * @package Chamilo\UserBundle\Entity\Manager
 */
class UserManager extends BaseUserManager
{
    /**
     * Finds a user either by confirmation token
     *
     * @param string $token
     *
     * @return UserInterface
     */
    public function findUserByConfirmationToken($token)
    {
        return $this->findUserBy(array('confirmationToken' => $token));
    }
}
