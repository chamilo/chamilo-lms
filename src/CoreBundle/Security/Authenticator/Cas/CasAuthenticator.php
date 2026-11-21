<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authenticator\Cas;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AuthenticationConfigHelper;
use Chamilo\CoreBundle\Helpers\ForcedLoginRedirectHelper;
use Chamilo\CoreBundle\Security\Badge\CasBadge;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class CasAuthenticator extends AbstractAuthenticator implements InteractiveAuthenticatorInterface
{
    public function __construct(
        private readonly CasConfiguration $configuration,
        private readonly CasLoginFlow $flow,
        private readonly CasTicketValidator $validator,
        private readonly CasUserResolver $users,
        private readonly AuthenticationConfigHelper $authConfig,
        private readonly ForcedLoginRedirectHelper $forcedRedirect,
        private readonly TokenStorageInterface $tokens,
        private readonly UrlGeneratorInterface $router,
    ) {}

    public function supports(Request $request): ?bool
    {
        if ('chamilo.cas_check' !== $request->attributes->get('_route') || !$request->isMethod('GET')) {
            return false;
        }
        $current = $this->tokens->getToken()?->getUser();

        return !$current instanceof User || User::ANONYMOUS === $current->getStatus();
    }

    public function authenticate(Request $request): Passport
    {
        $portal = $this->configuration->resolvePortal($request);
        $config = null !== $portal ? $this->configuration->get($portal) : null;
        $forced = $this->forcedRedirect->isEscapeActive($request) ? null : $this->authConfig->getForcedLoginMethod($portal);
        if (null === $config || !\in_array($forced, [null, 'cas'], true)) {
            throw new CustomUserMessageAuthenticationException('Authentication failed.');
        }
        $current = $this->tokens->getToken()?->getUser();
        if ($current instanceof User && User::ANONYMOUS !== $current->getStatus()) {
            throw new CustomUserMessageAuthenticationException('Authentication failed.');
        }
        $service = $this->flow->consume($request, $portal, $config);
        $ticket = $request->query->all()['ticket'] ?? null;
        if (!\is_string($ticket)) {
            throw new CustomUserMessageAuthenticationException('Authentication failed.');
        }
        $identity = $this->validator->validate($config, $service, $ticket);
        $user = $this->users->resolve($identity, $portal, $config);
        $passport = new SelfValidatingPassport(new UserBadge($user->getUserIdentifier(), static fn (): User => $user), [new CasBadge()]);
        $request->attributes->set('_chamilo_cas_logout', ['portal' => $portal->getId(), 'base' => $config['base'], 'user' => $user->getId()]);

        return $passport;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $token->setAttribute(CasLoginFlow::LOGOUT_KEY, $request->attributes->get('_chamilo_cas_logout'));

        return $this->redirect($this->router->generate('index'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if ($request->hasSession()) {
            $request->getSession()->remove(CasLoginFlow::SESSION_KEY);
            $session = $request->getSession();
            if ($session instanceof FlashBagAwareSessionInterface) {
                $session->getFlashBag()->add('error', 'Authentication failed.');
            }
        }

        return $this->redirect($this->router->generate('login', [ForcedLoginRedirectHelper::ESCAPE_PARAMETER => 1]));
    }

    public function isInteractive(): bool
    {
        return true;
    }

    private function redirect(string $url): RedirectResponse
    {
        return new RedirectResponse($url, Response::HTTP_SEE_OTHER, ['Cache-Control' => 'no-store', 'Referrer-Policy' => 'no-referrer']);
    }
}
