<?php
/**
 * NavbarShowUserListener.php
 * avanzu-admin
 * Date: 23.02.14
 */

namespace Chamilo\AdminThemeBundle\EventListener;


use Chamilo\AdminThemeBundle\Event\ShowUserEvent;
use Chamilo\AdminThemeBundle\Model\UserModel;

class NavbarShowUserDemoListener {

    public function onShowUser(ShowUserEvent $event) {

        $user = new UserModel();
        $user->setAvatar('')->setIsOnline(true)->setMemberSince(new \DateTime())->setUsername('Demo User');

        $event->setUser($user);
    }

}
