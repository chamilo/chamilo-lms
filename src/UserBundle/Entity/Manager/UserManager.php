<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\UserBundle\Entity\Manager;

use Chamilo\UserBundle\Entity\User;
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
     * @return User
     */
    public function findUserByConfirmationToken($token)
    {
        return $this->findUserBy(['confirmationToken' => $token]);
    }

    /**
     * @param string $code
     * @return User
     */
    public function findUserByOfficialCode($code)
    {
        $criteria = ['officialCode' => $code ];
        return $this->findUserBy($criteria);
    }
}
