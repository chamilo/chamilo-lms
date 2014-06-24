<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CoreBundle\EventListener;

use Avanzu\AdminThemeBundle\Event\ShowUserEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class ShowUserListener
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function onShowUser(ShowUserEvent $event)
    {
        $user = $this->getUser();
        if (!empty($user)) {
            $event->setUser($user);
        }
    }

    public function getUser()
    {
        /** @var  $security */
        $security = $this->container->get('security.context');
        $token = $security->getToken();

        if ($token) {
            $user = $token->getUser();
            if ($user) {
                return $user;
            }
        }
    }
}
