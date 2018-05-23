<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * Class LegacyLoginListener.
 * File not needed the real listener is LegacyListener.
 *
 * @deprecated
 *
 * @package Chamilo\CoreBundle\EventListener
 */
class LegacyLoginListener implements EventSubscriberInterface
{
    /** @var ContainerInterface */
    protected $container;
    protected $tokenStorage;

    /**
     * LegacyLoginListener constructor.
     *
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

        $container = $this->container;
        $token = $this->tokenStorage->getToken();
        if ($token) {
            $isGranted = $container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY');
            if ($isGranted) {
            } else {
                if (isset($_SESSION) && isset($_SESSION['_user'])) {
                    if ($_SESSION['_user']['active'] == 1) {
                        $username = $_SESSION['_user']['username'];
                        $criteria = ['username' => $username];
                        /** @var User $user */
                        $user = $container->get('fos_user.user_manager')->findOneBy($criteria);
                        if ($user) {
                            $em = $container->get('doctrine');
                            /** @var User $completeUser */
                            $completeUser = $em->getRepository('ChamiloUserBundle:User')->findOneBy($criteria);
                            $user->setLanguage($completeUser->getLanguage());

                            $isAdminUser = $em->getRepository('ChamiloCoreBundle:Admin')->findOneBy(['userId' => $user->getId()]);
                            if ($isAdminUser) {
                                $user->setSuperAdmin(true);
                            }

                            $languages = ['german' => 'de', 'english' => 'en', 'spanish' => 'es', 'french' => 'fr'];
                            $locale = isset($languages[$user->getLanguage()]) ? $languages[$user->getLanguage()] : '';
                            if ($user && !empty($locale)) {
                                $user->setLocale($locale);
                                //$request->getSession()->set('_locale_user', $locale);
                                // if no explicit locale has been set on this request, use one from the session
                                $request->getSession()->set('_locale', $locale);
                                $request->setLocale($locale);
                            }

                            $token = new UsernamePasswordToken($user, null, 'admin', $user->getRoles());
                            $this->tokenStorage->setToken($token); //now the user is logged in

                            //now dispatch the login event
                            $event = new InteractiveLoginEvent($request, $token);
                            $container->get('event_dispatcher')->dispatch("security.interactive_login", $event);
                            $container->get('event_dispatcher')->addListener(
                                KernelEvents::RESPONSE,
                                [$this, 'redirectUser']
                            );
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
        return [
            // must be registered before the default Locale listener
            KernelEvents::REQUEST => [['onKernelRequest', 15]],
        ];
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function redirectUser(FilterResponseEvent $event)
    {
        $uri = $event->getRequest()->getUri();
        // on effectue la redirection
        $response = new RedirectResponse($uri);
        $event->setResponse($response);
    }
}
