<?php
namespace ChamiloLMS\Component\Auth;
/**
 * Class Role
 * @package ChamiloLMS\Component\Auth
 */

use Symfony\Component\Security\Core\Role\RoleInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class Role implements RoleInterface
{
    public function __construct(UserInterface $user)
    {
        $this->user = $user;
    }

    public function getRole()
    {
        //return 'ROLE_' . strtoupper($this->user->getUsername());
    }
}
