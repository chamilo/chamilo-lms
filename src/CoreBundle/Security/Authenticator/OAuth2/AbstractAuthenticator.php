<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authenticator\OAuth2;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\ServiceHelper\AccessUrlHelper;
use Chamilo\CoreBundle\ServiceHelper\AuthenticationConfigHelper;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

abstract class AbstractAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    protected string $providerName = '';

    protected OAuth2ClientInterface $client;

    public function __construct(
        protected readonly ClientRegistry $clientRegistry,
        protected readonly RouterInterface $router,
        protected readonly UserRepository $userRepository,
        protected readonly AuthenticationConfigHelper $authenticationConfigHelper,
        protected readonly AccessUrlHelper $urlHelper,
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
        /** @var AccessToken $accessToken */
        $accessToken = $this->fetchAccessToken($this->client);

        $user = $this->userLoader($accessToken);

        return new SelfValidatingPassport(
            new UserBadge(
                $user->getUserIdentifier()
            ),
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $targetUrl = $this->router->generate('index');

        return new RedirectResponse($targetUrl);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessage(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * Find or create and save the new user.
     */
    abstract protected function userLoader(AccessToken $accessToken): User;
}
