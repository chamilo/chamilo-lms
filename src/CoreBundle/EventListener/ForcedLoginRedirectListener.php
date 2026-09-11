<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Helpers\ForcedLoginRedirectHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Sends an anonymous visitor to the OAuth2 provider that declares force_redirect.
 *
 * AuthenticationEntryPoint covers the protected pages. This listener covers the public
 * ones the firewall lets through, which are the pages that show the login form.
 *
 * Priority -10 runs it after AnonymousUserSubscriber (0), so the anonymous account of a
 * public course is already on the token and keeps its access.
 */
final class ForcedLoginRedirectListener
{
    public function __construct(
        private readonly ForcedLoginRedirectHelper $forcedLoginRedirectHelper,
        private readonly TokenStorageInterface $tokenStorage,
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // Only a request without a session moves: redirecting a signed-in visitor,
        // anonymous account included, would break a working session.
        if ($this->tokenStorage->getToken()?->getUser() instanceof UserInterface) {
            return;
        }

        $url = $this->forcedLoginRedirectHelper->resolveRedirectUrl($event->getRequest());

        if (null === $url) {
            return;
        }

        $event->setResponse(new RedirectResponse($url));
    }
}
