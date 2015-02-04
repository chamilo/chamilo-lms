<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\ThemeBundle\Event\ShowUserEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Class ShowUserListener
 * @package Chamilo\CoreBundle\EventListener
 */
class ShowUserListener
{
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param ShowUserEvent $event
     */
    public function onShowUser(ShowUserEvent $event)
    {
        $user = $this->getUser();
        if (!empty($user)) {
            $event->setUser($user);
        }
    }

    /**
     * @return mixed
     */
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
