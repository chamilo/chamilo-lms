<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\SettingsBundle\Manager\SettingsManager;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * Class UserLocaleListener.
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
    /** @var SessionInterface */
    private $session;
    /** @var SettingsManager */
    private $settings;

    /**
     * UserLocaleListener constructor.
     *
     * @param SessionInterface $session
     * @param SettingsManager  $settings
     */
    public function __construct(SessionInterface $session, SettingsManager $settings)
    {
        $this->session = $session;
        $this->settings = $settings;
    }

    /**
     * Set locale when user enters the platform.
     *
     * @param InteractiveLoginEvent $event
     */
    public function onInteractiveLogin(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();
        if (null !== $user->getLocale()) {
            $this->session->set('_locale', $user->getLocale());
            $this->session->set('_locale_user', $user->getLocale());
        }
    }
}
