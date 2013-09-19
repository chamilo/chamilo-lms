<?php
namespace ChamiloLMS\Component\Auth;
/**
 * Class Role
 * @package ChamiloLMS\Component\Auth
 */

use Symfony\Component\Security\Core\Role\RoleInterface;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;

class Role implements RoleInterface
{
    private $user;

    /**
     * @param AdvancedUserInterface $user
     */
    public function __construct(AdvancedUserInterface $user)
    {
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getRole()
    {
        /*$globalPlatform = api_is_global_platform_admin($this->user->getUserId());
        if ($globalPlatform) {
            //return 'ROLE_GLOBAL_ADMIN';
        }*/

    }
}
