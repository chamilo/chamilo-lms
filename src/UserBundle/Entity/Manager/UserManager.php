<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\UserBundle\Entity\Manager;

use Chamilo\UserBundle\Entity\User;
use Sonata\UserBundle\Entity\UserManager as BaseUserManager;

/**
 * Class UserManager.
 *
 * @package Chamilo\UserBundle\Entity\Manager
 */
class UserManager extends BaseUserManager
{
    /**
     * @param string $code
     *
     * @return User
     */
    public function findUserByOfficialCode($code)
    {
        $criteria = ['officialCode' => $code];

        return $this->findUserBy($criteria);
    }
}
