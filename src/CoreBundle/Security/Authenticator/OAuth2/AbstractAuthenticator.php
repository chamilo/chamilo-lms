<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authenticator\OAuth2;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\AuthenticationConfigHelper;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use KnpU\OAuth2ClientBundle\Security\Exception\InvalidStateAuthenticationException;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

abstract class AbstractAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    private const STATE_RETRY_COOKIE_LIFETIME = 300;

    protected string $providerName = '';

    protected OAuth2ClientInterface $client;

    public function __construct(
        protected readonly ClientRegistry $clientRegistry,
        protected readonly RouterInterface $router,
        protected readonly UserRepository $userRepository,
        protected readonly AuthenticationConfigHelper $authenticationConfigHelper,
        protected readonly AccessUrlHelper $accessUrlHelper,
        protected readonly EntityManagerInterface $entityManager,
        protected readonly LoggerInterface $logger,
    ) {
        $this->client = $this->clientRegistry->getClient($this->providerName);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        $targetUrl = $this->router->generate('login');

        return new RedirectResponse($targetUrl);
    }

    abstract public function supports(Request $request): ?bool;

    public function authenticate(Request $request): Passport
    {
        $accessToken = $this->fetchAccessToken($this->client);

        $user = $this->userLoader($accessToken);

        $passport = new SelfValidatingPassport(
            new UserBadge(
                $user->getUserIdentifier()
            ),
        );

        if ($customBadge = $this->getCustomBadge()) {
            $passport->addBadge($customBadge);
        }

        return $passport;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $response = new RedirectResponse($this->router->generate('index'));

        if ($request->cookies->has($this->getStateRetryCookieName())) {
            $response->headers->clearCookie($this->getStateRetryCookieName());
        }

        return $response;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if ($exception instanceof InvalidStateAuthenticationException
            && ($retryResponse = $this->createStateRetryResponse($request, $exception))
        ) {
            return $retryResponse;
        }

        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        if ($request->hasSession()) {
            $request->getSession()->getFlashBag()->add('error', $message);
        }

        $response = new RedirectResponse($this->router->generate('index'));

        if ($request->cookies->has($this->getStateRetryCookieName())) {
            $response->headers->clearCookie($this->getStateRetryCookieName());
        }

        return $response;
    }

    /**
     * On invalid OAuth2 state (session lost between the redirect and the callback), restart
     * the auth flow transparently. The retry marker lives in its own short-lived cookie — not
     * in the session, whose loss is precisely what triggers this failure — so a second
     * consecutive failure always falls through to the normal error path instead of looping.
     */
    private function createStateRetryResponse(Request $request, AuthenticationException $exception): ?RedirectResponse
    {
        $cookieName = $this->getStateRetryCookieName();

        // No session cookie in the callback request means the browser is not persisting
        // cookies at all: the OAuth2 state can never survive the round trip, so retrying
        // would only bounce against the provider indefinitely.
        if (!$request->hasSession()
            || !$request->cookies->has($request->getSession()->getName())
            || $request->cookies->has($cookieName)
        ) {
            return null;
        }

        // The start route rejects disabled (403) or unconfigured (500) providers for the
        // current access URL; show the normal error instead of redirecting into that.
        try {
            $providerParams = $this->authenticationConfigHelper->getOAuthProviderConfig($this->providerName);
        } catch (InvalidArgumentException) {
            return null;
        }

        if (!($providerParams['enabled'] ?? false)) {
            return null;
        }

        $this->logger->warning(
            'Invalid OAuth2 state for provider "{provider}": {error}. Restarting the auth flow (single retry).',
            [
                'provider' => $this->providerName,
                'error' => strtr($exception->getMessageKey(), $exception->getMessageData()),
            ]
        );

        $response = new RedirectResponse(
            $this->router->generate('chamilo.oauth2_'.$this->providerName.'_start')
        );
        $response->headers->setCookie(
            Cookie::create($cookieName)
                ->withValue('1')
                ->withExpires(time() + self::STATE_RETRY_COOKIE_LIFETIME)
                ->withSecure($request->isSecure())
                ->withSameSite(Cookie::SAMESITE_LAX)
        );

        return $response;
    }

    private function getStateRetryCookieName(): string
    {
        return 'oauth2_state_retry_'.$this->providerName;
    }

    /**
     * Find or create and save the new user.
     */
    abstract protected function userLoader(AccessToken $accessToken): User;

    abstract protected function getCustomBadge(): ?BadgeInterface;
}
