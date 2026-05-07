<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class SessionCookieSameSiteSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly SettingsManager $settingsManager,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -2048],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!$request->isSecure()) {
            return;
        }

        if (!$this->isSameSiteNoneEnabled()) {
            return;
        }

        $sessionName = $request->hasSession()
            ? $request->getSession()->getName()
            : session_name();

        if ('' === $sessionName) {
            return;
        }

        $response = $event->getResponse();

        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() !== $sessionName) {
                continue;
            }

            $response->headers->setCookie(
                Cookie::create(
                    $cookie->getName(),
                    $cookie->getValue(),
                    $cookie->getExpiresTime(),
                    $cookie->getPath(),
                    $cookie->getDomain(),
                    true,
                    $cookie->isHttpOnly(),
                    $cookie->isRaw(),
                    Cookie::SAMESITE_NONE
                )
            );
        }
    }

    private function isSameSiteNoneEnabled(): bool
    {
        try {
            $value = $this->settingsManager->getSetting(
                'security.security_session_cookie_samesite_none',
                true
            );
        } catch (\Throwable) {
            return false;
        }

        if (true === $value || 1 === $value) {
            return true;
        }

        $normalized = strtolower(trim((string) $value));

        return 'true' === $normalized || '1' === $normalized;
    }
}
