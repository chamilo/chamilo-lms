<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Applies SameSite=None to the session cookie when the
 * security.security_session_cookie_samesite_none setting is enabled.
 *
 * Must run before Symfony's session listener (priority 128) so that
 * ini_set takes effect before the session is started.
 */
class SessionCookieSameSiteSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly SettingsManager $settingsManager,
        private readonly ParameterBagInterface $parameterBag,
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $installed = $this->parameterBag->has('installed') && 1 === (int) $this->parameterBag->get('installed');
        if (!$installed) {
            return;
        }

        if ('true' !== $this->settingsManager->getSetting('security.security_session_cookie_samesite_none')) {
            return;
        }

        ini_set('session.cookie_samesite', 'None');
        ini_set('session.cookie_secure', '1');
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 150]],
        ];
    }
}
