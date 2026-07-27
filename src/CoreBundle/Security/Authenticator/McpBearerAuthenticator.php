<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authenticator;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserApiKey;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\UserApiKeyRepository;
use Chamilo\CoreBundle\Service\Mcp\McpApiKeyManager;
use DateTime;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final class McpBearerAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private readonly UserApiKeyRepository $apiKeyRepository,
        private readonly UserRepository $userRepository,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly AccessUrlRepository $accessUrlRepository,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly RateLimiterFactory $mcpAuthenticationLimiter,
    ) {}

    public function supports(Request $request): ?bool
    {
        return '/mcp' === rtrim($request->getPathInfo(), '/')
            && str_starts_with((string) $request->headers->get('Authorization', ''), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $limiter = $this->mcpAuthenticationLimiter->create((string) $request->getClientIp());
        if (!$limiter->consume()->isAccepted()) {
            throw new CustomUserMessageAuthenticationException('Too many MCP authentication attempts.');
        }

        $authorization = (string) $request->headers->get('Authorization', '');
        $bearer = trim(substr($authorization, 7));
        if ('' === $bearer || mb_strlen($bearer) > 4096) {
            throw new CustomUserMessageAuthenticationException('Missing or invalid MCP bearer credential.');
        }

        if (McpApiKeyManager::isMcpKey($bearer)) {
            return $this->authenticateApiKey($request, $bearer);
        }

        return $this->authenticateJwt($request, $bearer);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(
            ['message' => $exception->getMessageKey()],
            Response::HTTP_UNAUTHORIZED,
        );
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new JsonResponse(
            ['message' => 'MCP authentication credentials are required.'],
            Response::HTTP_UNAUTHORIZED,
        );
    }

    private function authenticateApiKey(Request $request, string $plainKey): SelfValidatingPassport
    {
        $accessUrl = $this->resolveAccessUrl();
        $now = new DateTime();
        $hash = hash('sha256', $plainKey);
        $apiKey = $this->apiKeyRepository->findActiveByHashAndAccessUrl(
            $hash,
            (int) $accessUrl->getId(),
            McpApiKeyManager::SERVICE,
            $now,
        );

        if (!$apiKey instanceof UserApiKey || !hash_equals($apiKey->getApiKey(), $hash)) {
            throw new CustomUserMessageAuthenticationException('Invalid or revoked MCP API key.');
        }

        $user = $this->userRepository->find($apiKey->getUserId());
        $this->assertUserCanAuthenticate($user, $accessUrl);
        $this->apiKeyRepository->touchLastUsed($apiKey, $now);

        $request->attributes->set('_chamilo_mcp_auth_source', 'api_key');

        return new SelfValidatingPassport(
            new UserBadge((string) $user->getId(), static fn (): User => $user),
        );
    }

    private function authenticateJwt(Request $request, string $jwt): SelfValidatingPassport
    {
        try {
            $payload = $this->jwtManager->parse($jwt);
            $username = $payload['username'] ?? $payload['sub'] ?? null;
            if (!\is_string($username) || '' === trim($username)) {
                throw new Exception('JWT username claim is missing.');
            }
        } catch (Exception) {
            throw new CustomUserMessageAuthenticationException('Invalid or expired MCP JWT.');
        }

        $user = $this->userRepository->findOneBy(['username' => $username]);
        $accessUrl = $this->resolveAccessUrl();
        $this->assertUserCanAuthenticate($user, $accessUrl);

        $request->attributes->set('_chamilo_mcp_auth_source', 'jwt');

        return new SelfValidatingPassport(
            new UserBadge((string) $user->getId(), static fn (): User => $user),
        );
    }

    private function resolveAccessUrl(): AccessUrl
    {
        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (!$accessUrl instanceof AccessUrl || null === $accessUrl->getId()) {
            throw new CustomUserMessageAuthenticationException('The current Chamilo portal could not be resolved.');
        }

        return $accessUrl;
    }

    private function assertUserCanAuthenticate(?User $user, AccessUrl $accessUrl): void
    {
        if (!$user instanceof User || User::ACTIVE !== $user->getActive()) {
            throw new CustomUserMessageAuthenticationException('The MCP user account is inactive.');
        }

        $expirationDate = $user->getExpirationDate();
        if (null !== $expirationDate && $expirationDate <= new DateTime()) {
            throw new CustomUserMessageAuthenticationException('The MCP user account has expired.');
        }

        if (!$this->accessUrlRepository->isUrlActiveForUser($accessUrl, $user)) {
            throw new CustomUserMessageAuthenticationException('The MCP user is not active on this Chamilo portal.');
        }
    }
}
