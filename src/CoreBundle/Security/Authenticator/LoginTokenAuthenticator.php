<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authenticator;

use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class LoginTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        protected readonly UserProviderInterface $userProvider,
        protected readonly RouterInterface $router,
        protected readonly JWTTokenManagerInterface $jwtManager,
    ) {}

    public function supports(Request $request): ?bool
    {
        return 'login_token_check' === $request->attributes->get('_route')
            && $request->headers->has('Authorization');
    }

    public function authenticate(Request $request): Passport
    {
        $authHeader = $request->headers->get('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            throw new AuthenticationException('Missing token.');
        }

        $jwt = substr($authHeader, 7);

        try {
            $payload = $this->jwtManager->parse($jwt);
            $username = $payload['username'] ?? $payload['sub'] ?? null;

            if (!$username) {
                throw new AuthenticationException('Token does not contain a username.');
            }
        } catch (Exception $e) {
            throw new AuthenticationException('Invalid JWT token: '.$e->getMessage());
        }

        return new SelfValidatingPassport(
            new UserBadge(
                $username,
                fn (string $username) => $this->userProvider->loadUserByIdentifier($username)
            )
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessage(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }
}
