<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Chamilo\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * Class LegacyLoginListener
 * @package Chamilo\CoreBundle\EventListener
 */
class LegacyLoginListener implements EventSubscriberInterface
{
    /** @var ContainerInterface */
    protected $container;
    protected $tokenStorage;

    /**
     * LegacyLoginListener constructor.
     * @param $container
     * @param TokenStorage $tokenStorage
     */
    public function __construct($container, $tokenStorage)
    {
        $this->container = $container;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (!$request->hasPreviousSession()) {

            return;
        }
        $token = $this->tokenStorage->getToken();
        if ($token) {
            $isGranted = $this->container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY');
            if (!$isGranted) {
                if (isset($_SESSION) && isset($_SESSION['_user'])) {
                    if ($_SESSION['_user']['active'] == 1) {
                        $username = $_SESSION['_user']['username'];
                        $criteria = ['username' => $username];
                        /** @var User $user */
                        $user = $this->container->get('sonata.user.user_manager')->findOneBy($criteria);
                        if ($user) {
                            /** @var User $completeUser */
                            $completeUser = $this->container->get('doctrine')->getRepository('ChamiloUserBundle:User')->findOneBy($criteria);
                            $user->setLanguage($completeUser->getLanguage());

                            $token = new UsernamePasswordToken($user, null, "main", $user->getRoles());

                            $this->tokenStorage->setToken($token); //now the user is logged in
                            //now dispatch the login event
                            $event = new InteractiveLoginEvent($request, $token);
                            $this->container->get("event_dispatcher")->dispatch("security.interactive_login", $event);
                        }
                    }
                }
            }
        }
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            // must be registered before the default Locale listener
            KernelEvents::REQUEST => array(array('onKernelRequest', 17)),
        );
    }
}
