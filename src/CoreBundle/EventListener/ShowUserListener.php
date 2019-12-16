<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\ThemeBundle\Event\ShowUserEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ShowUserListener.
 */
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

    /**
     * @return mixed
     */
    public function getUser()
    {
        $security = $this->container->get('security.token_storage');
        $token = $security->getToken();

        if ($token) {
            $user = $token->getUser();
            if ($user) {
                return $user;
            }
        }
    }
}
