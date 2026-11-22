<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Security\Authenticator\Cas\CasConfiguration;
use Chamilo\CoreBundle\Security\Authenticator\Cas\CasLoginFlow;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Throwable;

final readonly class CasLogoutListener
{
    public function __construct(
        private CasConfiguration $configuration
    ) {}

    public function __invoke(LogoutEvent $event): void
    {
        $request = $event->getRequest();
        $token = $event->getToken();
        if ($token instanceof SwitchUserToken) {
            $token = $token->getOriginalToken();
        }
        $marker = $token?->hasAttribute(CasLoginFlow::LOGOUT_KEY) ? $token->getAttribute(CasLoginFlow::LOGOUT_KEY) : null;
        $user = $token?->getUser();
        if (!\is_array($marker) || !$user instanceof User || $marker['user'] !== $user->getId()) {
            return;
        }

        try {
            $portal = $this->configuration->resolvePortal($request);
            $config = null !== $portal ? $this->configuration->get($portal) : null;
            if (null !== $config && $marker['portal'] === $portal->getId() && $marker['base'] === $config['base']) {
                // The normal Symfony logout listeners still invalidate the local session.
                $event->setResponse(new RedirectResponse($config['base'].'/logout', 303, ['Cache-Control' => 'no-store', 'Referrer-Policy' => 'no-referrer']));
            }
        } catch (Throwable) {
            // An unavailable or changed CAS configuration must not prevent local logout.
        }
    }
}
