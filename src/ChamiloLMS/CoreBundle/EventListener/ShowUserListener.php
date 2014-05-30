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
        $event->setUser($user);
    }

    public function getUser()
    {
        $security = $this->container->get('security.context');
        $token = $security->getToken();


        // $user = $this->getUser();
        return $token->getUser();
    }
}
