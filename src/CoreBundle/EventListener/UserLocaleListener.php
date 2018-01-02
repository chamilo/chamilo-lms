<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\SettingsBundle\Manager\SettingsManager;
use Chamilo\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * Class UserLocaleListener
 *
 * Stores the locale of the user in the session after the
 * login. This can be used by the LocaleListener afterwards.
 *
 * Priority order: platform -> user
 * Priority order: platform -> user -> course
 *
 * @package Chamilo\CoreBundle\EventListener
 */
class UserLocaleListener
{
    /**
     * @var Session
     */
    private $session;

    /**
     * UserLocaleListener constructor.
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Set locale when user enters the platform
     * @param InteractiveLoginEvent $event
     */
    public function onInteractiveLogin(InteractiveLoginEvent $event)
    {
        $languages = ['german' => 'de', 'english' => 'en', 'spanish' => 'es', 'french' => 'fr'];

        /** @var User $user */
        $token = $event->getAuthenticationToken();

        if ($token) {
            $user = $token->getUser();
            $locale = isset($languages[$user->getLanguage()]) ? $languages[$user->getLanguage()] : '';
            if ($user && !empty($locale)) {
                $this->session->set('_locale', $locale);
            }
        }
    }
}
