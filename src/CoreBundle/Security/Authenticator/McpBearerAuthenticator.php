<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authenticator;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\OAuthAccessToken;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserApiKey;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\OAuthAccessTokenRepository;
use Chamilo\CoreBundle\Repository\UserApiKeyRepository;
use Chamilo\CoreBundle\Service\Mcp\McpAccessPolicy;
use Chamilo\CoreBundle\Service\Mcp\McpApiKeyManager;
use Chamilo\CoreBundle\Service\OAuthServer\OAuthMetadataService;
use Chamilo\CoreBundle\Service\OAuthServer\OAuthTokenService;
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
        private readonly OAuthMetadataService $oauthMetadata,
        private readonly OAuthAccessTokenRepository $oauthAccessTokenRepository,
        private readonly McpAccessPolicy $mcpAccessPolicy,
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

        if (OAuthTokenService::isAccessToken($bearer)) {
            return $this->authenticateOAuthToken($request, $bearer);
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
        $response = new JsonResponse(
            ['message' => $exception->getMessageKey()],
            Response::HTTP_UNAUTHORIZED,
        );
        $response->headers->set(
            'WWW-Authenticate',
            $this->buildWwwAuthenticateHeader('invalid_token', $exception->getMessageKey()),
        );

        return $response;
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        $response = new JsonResponse(
            ['message' => 'MCP authentication credentials are required.'],
            Response::HTTP_UNAUTHORIZED,
        );
        $response->headers->set('WWW-Authenticate', $this->buildWwwAuthenticateHeader());

        return $response;
    }

    /**
     * Advertises where an OAuth client can discover this resource server's
     * Authorization Server (RFC 9728 §5.1 / MCP authorization spec). Without
     * this header, a bare 401 gives an OAuth client nothing to discover the
     * new /oauth/* endpoints with.
     */
    private function buildWwwAuthenticateHeader(?string $error = null, ?string $errorDescription = null): string
    {
        $parts = [
            'realm="Chamilo MCP"',
            'resource_metadata="'.$this->oauthMetadata->getResourceMetadataUrl('mcp').'"',
        ];

        if (null !== $error) {
            $parts[] = 'error="'.$error.'"';
        }

        if (null !== $errorDescription) {
            $parts[] = 'error_description="'.str_replace('"', "'", $errorDescription).'"';
        }

        return 'Bearer '.implode(', ', $parts);
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

    /**
     * Accepts an access token issued by the generic OAuth Authorization
     * Server (see Chamilo\CoreBundle\Service\OAuthServer\*) — the first
     * resource server built on top of it. isAccessToken()'s prefix regex is
     * disjoint from McpApiKeyManager::isMcpKey()'s, so the two schemes never
     * collide; keep this branch ordered before isMcpKey() regardless, so the
     * precedence stays explicit rather than accidental.
     */
    private function authenticateOAuthToken(Request $request, string $plainToken): SelfValidatingPassport
    {
        $accessUrl = $this->resolveAccessUrl();
        $now = new DateTime();
        $hash = hash('sha256', $plainToken);
        $token = $this->oauthAccessTokenRepository->findActiveByHash($hash, $now);

        if (!$token instanceof OAuthAccessToken || !hash_equals($token->getTokenHash(), $hash)) {
            throw new CustomUserMessageAuthenticationException('Invalid or revoked MCP OAuth access token.');
        }

        if ($token->getAccessUrlId() !== (int) $accessUrl->getId()) {
            // Same message as an outright invalid token: a cross-portal
            // token must not be distinguishable from a bogus one.
            throw new CustomUserMessageAuthenticationException('Invalid or revoked MCP OAuth access token.');
        }

        $expectedResource = rtrim($this->oauthMetadata->getResourceIdentifier('mcp'), '/');
        $tokenResource = rtrim((string) $token->getResource(), '/');
        if ('' === $tokenResource || !hash_equals($expectedResource, $tokenResource)) {
            throw new CustomUserMessageAuthenticationException('Invalid or revoked MCP OAuth access token.');
        }

        if (!$token->getClient()->isActiveAt($now)) {
            throw new CustomUserMessageAuthenticationException('Invalid or revoked MCP OAuth access token.');
        }

        $user = $token->getUser();
        $this->assertUserCanAuthenticate($user, $accessUrl);
        $this->oauthAccessTokenRepository->touchLastUsed($token, $now);

        $request->attributes->set('_chamilo_mcp_auth_source', 'oauth');

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

        if (!$this->mcpAccessPolicy->canUse($user)) {
            throw new CustomUserMessageAuthenticationException('MCP access is disabled or not allowed for this user role.');
        }
    }
}
