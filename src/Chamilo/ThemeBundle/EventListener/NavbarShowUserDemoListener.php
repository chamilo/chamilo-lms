<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ThemeBundle\EventListener;

use Chamilo\ThemeBundle\Event\ShowUserEvent;
use Chamilo\ThemeBundle\Model\UserModel;

/**
 * Class NavbarShowUserDemoListener.
 *
 * @package Chamilo\ThemeBundle\EventListener
 */
class NavbarShowUserDemoListener
{
    public function onShowUser(ShowUserEvent $event)
    {
        $user = new UserModel();
        $user->setAvatar('')->setIsOnline(true)->setMemberSince(new \DateTime())->setUsername('Demo User');
        $event->setUser($user);
    }
}
