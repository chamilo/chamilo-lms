<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authenticator;

use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class WebserviceApiKeyAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function supports(Request $request): ?bool
    {
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return false;
        }

        return '' !== $this->getApiKeyFromRequest($request);
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $apiKey = $this->getApiKeyFromRequest($request);

        if ('' === $apiKey) {
            throw new CustomUserMessageAuthenticationException('Missing API key.');
        }

        $request->attributes->set('_chamilo_webservice_api_key', true);

        return new SelfValidatingPassport(
            new UserBadge($apiKey, function () use ($apiKey): User {
                $user = $this->entityManager
                    ->getRepository(User::class)
                    ->findOneBy(['apiToken' => $apiKey])
                ;

                if (!$user instanceof User) {
                    throw new CustomUserMessageAuthenticationException('Invalid API key.');
                }

                if (method_exists($user, 'isActive') && !$user->isActive()) {
                    throw new CustomUserMessageAuthenticationException('Inactive user.');
                }

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, $token, string $firewallName): ?Response
    {
        $request->attributes->set('_chamilo_webservice_api_key', true);

        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(
            ['message' => $exception->getMessageKey()],
            Response::HTTP_UNAUTHORIZED
        );
    }

    private function getApiKeyFromRequest(Request $request): string
    {
        /*
         * Do not read Authorization: Bearer here.
         * The API firewall already uses JWT and Bearer tokens.
         */
        return trim((string) $request->headers->get('X-Chamilo-Api-Key', ''));
    }
}
