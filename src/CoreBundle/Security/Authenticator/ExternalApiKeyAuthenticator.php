<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authenticator;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserApiKey;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\UserApiKeyRepository;
use Chamilo\CoreBundle\Service\ExternalApi\ExternalApiKeyManager;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTime;
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

/**
 * Accepts an "external" API key (Chamilo\CoreBundle\Service\ExternalApi\ExternalApiKeyManager)
 * as a Bearer credential on the general /api (API Platform) firewall, resolving it straight to
 * the Chamilo user it was issued for. Sibling of McpBearerAuthenticator, registered as an
 * *additional* authenticator alongside the api firewall's JWT authenticator — it only engages
 * when the bearer token matches this key format. The firewall's JWT authenticator is
 * StrictJwtAuthenticator, not Lexik's own default, specifically so it doesn't also try (and
 * fail) to parse these tokens as a JWT — see that class's docblock for why that distinction
 * matters here.
 */
final class ExternalApiKeyAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly UserApiKeyRepository $apiKeyRepository,
        private readonly UserRepository $userRepository,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly AccessUrlRepository $accessUrlRepository,
        private readonly SettingsManager $settingsManager,
        private readonly RateLimiterFactory $externalApiAuthenticationLimiter,
    ) {}

    public function supports(Request $request): ?bool
    {
        $authorization = (string) $request->headers->get('Authorization', '');
        if (!str_starts_with($authorization, 'Bearer ')) {
            return false;
        }

        return ExternalApiKeyManager::isExternalKey(trim(substr($authorization, 7)));
    }

    public function authenticate(Request $request): Passport
    {
        $limiter = $this->externalApiAuthenticationLimiter->create((string) $request->getClientIp());
        if (!$limiter->consume()->isAccepted()) {
            throw new CustomUserMessageAuthenticationException('Too many authentication attempts.');
        }

        if ('true' !== $this->settingsManager->getSetting('security.external_api_enabled', true)) {
            throw new CustomUserMessageAuthenticationException('External API key authentication is disabled on this portal.');
        }

        $authorization = (string) $request->headers->get('Authorization', '');
        $plainKey = trim(substr($authorization, 7));

        $accessUrl = $this->resolveAccessUrl();
        $now = new DateTime();
        $hash = hash('sha256', $plainKey);
        $apiKey = $this->apiKeyRepository->findActiveByHashAndAccessUrl(
            $hash,
            (int) $accessUrl->getId(),
            ExternalApiKeyManager::SERVICE,
            $now,
        );

        if (!$apiKey instanceof UserApiKey || !hash_equals($apiKey->getApiKey(), $hash)) {
            throw new CustomUserMessageAuthenticationException('Invalid or revoked external API key.');
        }

        $user = $this->userRepository->find($apiKey->getUserId());
        $this->assertUserCanAuthenticate($user, $accessUrl);
        $this->apiKeyRepository->touchLastUsed($apiKey, $now);

        return new SelfValidatingPassport(
            new UserBadge((string) $user->getId(), static fn (): User => $user),
        );
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
            throw new CustomUserMessageAuthenticationException('The external API key user account is inactive.');
        }

        $expirationDate = $user->getExpirationDate();
        if (null !== $expirationDate && $expirationDate <= new DateTime()) {
            throw new CustomUserMessageAuthenticationException('The external API key user account has expired.');
        }

        if (!$this->accessUrlRepository->isUrlActiveForUser($accessUrl, $user)) {
            throw new CustomUserMessageAuthenticationException('The external API key user is not active on this Chamilo portal.');
        }
    }
}
