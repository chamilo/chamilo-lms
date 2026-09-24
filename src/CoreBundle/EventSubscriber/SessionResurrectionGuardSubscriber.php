<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * Keeps a logout from being undone by a request that was still running when it happened.
 *
 * Many requests release the session lock early ($session->save()), e.g.
 * ApiSessionUnlockSubscriber and /platform-config/list. Any later session access, at the
 * latest the firewall's ContextListener storing the token on the response, restarts the
 * session. If a concurrent /logout destroyed it in the meantime, PHP's strict mode starts
 * a brand-new session id, and the token written into it silently logs the browser back in
 * through that response's Set-Cookie.
 *
 * Outside a login (which migrates the session on purpose), the session id can only change
 * during a request that way. This runs just before ContextListener (priority 0) and, in
 * that case, drops the token so nothing authenticated is written into the new session.
 */
class SessionResurrectionGuardSubscriber implements EventSubscriberInterface
{
    private const string LOGGED_IN_ATTRIBUTE = '_chamilo_logged_in_this_request';

    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
            KernelEvents::RESPONSE => [['onKernelResponse', 8]],
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $event->getRequest()->attributes->set(self::LOGGED_IN_ATTRIBUTE, true);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$request->hasSession()
            || !$request->hasPreviousSession()
            || $request->attributes->get(self::LOGGED_IN_ATTRIBUTE)
        ) {
            return;
        }

        $session = $request->getSession();

        // Restart a released session here rather than in ContextListener, so a destroyed
        // one shows up as a new id before the token is written into it.
        if (!$session->isStarted()) {
            $session->start();
        }

        if ($session->getId() !== $request->cookies->get($session->getName())) {
            $this->tokenStorage->setToken(null);
        }
    }
}
