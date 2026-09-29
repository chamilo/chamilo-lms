<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\OAuthServer;

use Chamilo\CoreBundle\Entity\OAuthAccessToken;
use Chamilo\CoreBundle\Entity\OAuthAuthorizationCode;
use Chamilo\CoreBundle\Entity\OAuthClient;
use Chamilo\CoreBundle\Entity\OAuthRefreshToken;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Exception\OAuthServer\OAuthException;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\OAuthAccessTokenRepository;
use Chamilo\CoreBundle\Repository\OAuthAuthorizationCodeRepository;
use Chamilo\CoreBundle\Repository\OAuthRefreshTokenRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

/**
 * The /oauth/token and /oauth/revoke logic: PKCE verification, authorization
 * code exchange, refresh-token rotation, and reuse-detection family
 * revocation.
 */
final readonly class OAuthTokenService
{
    public const string ACCESS_TOKEN_PREFIX = 'chamilo_oauth_at_';
    public const string REFRESH_TOKEN_PREFIX = 'chamilo_oauth_rt_';
    public const int ACCESS_TOKEN_TTL_SECONDS = 3600;
    public const int REFRESH_TOKEN_TTL_SECONDS = 2_592_000;
    public const int REFRESH_TOKEN_ABSOLUTE_TTL_SECONDS = 7_776_000;

    public function __construct(
        private OAuthAuthorizationCodeRepository $codeRepository,
        private OAuthAccessTokenRepository $accessTokenRepository,
        private OAuthRefreshTokenRepository $refreshTokenRepository,
        private EntityManagerInterface $entityManager,
        private AccessUrlHelper $accessUrlHelper,
        private AccessUrlRepository $accessUrlRepository,
    ) {}

    public static function isAccessToken(string $plainToken): bool
    {
        return 1 === preg_match('/^'.preg_quote(self::ACCESS_TOKEN_PREFIX, '/').'[A-Za-z0-9_-]{43}$/', $plainToken);
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    public function exchangeAuthorizationCode(OAuthClient $client, array $params): array
    {
        $plainCode = (string) ($params['code'] ?? '');
        $redirectUri = (string) ($params['redirect_uri'] ?? '');
        $codeVerifier = (string) ($params['code_verifier'] ?? '');

        if ('' === $plainCode || '' === $redirectUri || '' === $codeVerifier) {
            throw OAuthException::invalidRequest('code, redirect_uri and code_verifier are required.');
        }

        $code = $this->codeRepository->findOneByHash(hash('sha256', $plainCode));
        if (!$code instanceof OAuthAuthorizationCode) {
            throw OAuthException::invalidGrant();
        }

        $now = new DateTime();

        if (!$this->codeRepository->consumeAtomically((int) $code->getId(), $now)) {
            // Already used: either a genuine replay, or two concurrent
            // exchange attempts raced. Either way, revoke anything this code
            // may have already produced (harmless no-op if nothing exists yet).
            $this->revokeFamily($code->getGrantId(), OAuthRefreshToken::REVOKED_REASON_REUSE_DETECTED, $now);

            throw OAuthException::invalidGrant();
        }

        if ($code->getExpiresAt() <= $now) {
            throw OAuthException::invalidGrant('The authorization code has expired.');
        }

        if ($code->getClient()->getId() !== $client->getId()) {
            throw OAuthException::invalidGrant();
        }

        if (!hash_equals($code->getRedirectUri(), $redirectUri)) {
            throw OAuthException::invalidGrant('redirect_uri does not match the one used to obtain this code.');
        }

        if (!$this->verifyPkce($codeVerifier, $code->getCodeChallenge())) {
            throw OAuthException::invalidGrant('PKCE verification failed.');
        }

        $user = $code->getUser();
        if (!$this->isUserActiveOnCurrentPortal($user)) {
            throw OAuthException::invalidGrant();
        }

        return $this->issueTokenPair(
            client: $client,
            user: $user,
            grantId: $code->getGrantId(),
            scope: $code->getScope(),
            resource: $code->getResource(),
            consentedAt: $code->getCreatedAt(),
            consentIp: $code->getConsentIp(),
            consentUserAgent: $code->getConsentUserAgent(),
            now: $now,
        );
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    public function refresh(OAuthClient $client, array $params): array
    {
        $plainRefreshToken = (string) ($params['refresh_token'] ?? '');
        if ('' === $plainRefreshToken) {
            throw OAuthException::invalidRequest('refresh_token is required.');
        }

        $refreshToken = $this->refreshTokenRepository->findOneByHash(hash('sha256', $plainRefreshToken));
        if (!$refreshToken instanceof OAuthRefreshToken) {
            throw OAuthException::invalidGrant();
        }

        $now = new DateTime();

        if (null !== $refreshToken->getRotatedAt() || null !== $refreshToken->getRevokedAt()) {
            // A rotated-away or already-revoked generation was presented
            // again: this is the refresh-token-reuse signal OAuth 2.1
            // rotation exists to catch. Kill the whole family.
            $this->revokeFamily($refreshToken->getGrantId(), OAuthRefreshToken::REVOKED_REASON_REUSE_DETECTED, $now);

            throw OAuthException::invalidGrant();
        }

        if ($refreshToken->getExpiresAt() <= $now || $refreshToken->getAbsoluteExpiresAt() <= $now) {
            throw OAuthException::invalidGrant('The refresh token has expired.');
        }

        if ($refreshToken->getClient()->getId() !== $client->getId()) {
            throw OAuthException::invalidGrant();
        }

        $user = $refreshToken->getUser();
        if (!$this->isUserActiveOnCurrentPortal($user)) {
            throw OAuthException::invalidGrant();
        }

        if (!$this->refreshTokenRepository->rotateAtomically((int) $refreshToken->getId(), $now)) {
            // Lost a race against another refresh attempt on the same token.
            $this->revokeFamily($refreshToken->getGrantId(), OAuthRefreshToken::REVOKED_REASON_REUSE_DETECTED, $now);

            throw OAuthException::invalidGrant();
        }

        return $this->issueTokenPair(
            client: $client,
            user: $user,
            grantId: $refreshToken->getGrantId(),
            scope: $refreshToken->getScope(),
            resource: $refreshToken->getResource(),
            consentedAt: $refreshToken->getConsentedAt(),
            consentIp: $refreshToken->getConsentIp(),
            consentUserAgent: $refreshToken->getConsentUserAgent(),
            now: $now,
            absoluteExpiresAt: $refreshToken->getAbsoluteExpiresAt(),
            replaces: $refreshToken,
        );
    }

    public function revoke(OAuthClient $client, string $plainToken, ?string $tokenTypeHint): void
    {
        $now = new DateTime();
        $hash = hash('sha256', $plainToken);

        $checkOrder = 'access_token' === $tokenTypeHint
            ? ['access', 'refresh']
            : ['refresh', 'access'];

        foreach ($checkOrder as $type) {
            if ('refresh' === $type) {
                $refreshToken = $this->refreshTokenRepository->findOneByHash($hash);
                if ($refreshToken instanceof OAuthRefreshToken && $refreshToken->getClient()->getId() === $client->getId()) {
                    $this->revokeFamily($refreshToken->getGrantId(), OAuthRefreshToken::REVOKED_REASON_CLIENT_REVOKED, $now);

                    return;
                }
            } else {
                $accessToken = $this->accessTokenRepository->findActiveByHash($hash, $now);
                if ($accessToken instanceof OAuthAccessToken && $accessToken->getClient()->getId() === $client->getId()) {
                    $this->revokeFamily($accessToken->getGrantId(), OAuthRefreshToken::REVOKED_REASON_CLIENT_REVOKED, $now);

                    return;
                }
            }
        }

        // RFC 7009 §2.2: unknown, foreign, or already-revoked tokens are not
        // an error — never disclose which is the case.
    }

    private function revokeFamily(string $grantId, string $reason, DateTime $now): void
    {
        $this->refreshTokenRepository->revokeFamily($grantId, $reason, $now);
        $this->accessTokenRepository->revokeByGrantId($grantId, $now);
    }

    private function verifyPkce(string $verifier, string $challenge): bool
    {
        if (1 !== preg_match('/^[A-Za-z0-9\-._~]{43,128}$/', $verifier)) {
            return false;
        }

        $computed = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        return hash_equals($challenge, $computed);
    }

    private function isUserActiveOnCurrentPortal(User $user): bool
    {
        if (User::ACTIVE !== $user->getActive()) {
            return false;
        }

        $expirationDate = $user->getExpirationDate();
        if (null !== $expirationDate && $expirationDate <= new DateTime()) {
            return false;
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (null === $accessUrl) {
            return false;
        }

        return $this->accessUrlRepository->isUrlActiveForUser($accessUrl, $user);
    }

    /**
     * @return array<string, mixed>
     */
    private function issueTokenPair(
        OAuthClient $client,
        User $user,
        string $grantId,
        ?string $scope,
        ?string $resource,
        DateTime $consentedAt,
        ?string $consentIp,
        ?string $consentUserAgent,
        DateTime $now,
        ?DateTime $absoluteExpiresAt = null,
        ?OAuthRefreshToken $replaces = null,
    ): array {
        $accessUrl = $this->accessUrlHelper->getCurrent();
        $accessUrlId = null !== $accessUrl ? (int) $accessUrl->getId() : null;

        $plainAccessToken = self::ACCESS_TOKEN_PREFIX.$this->randomSecret();
        $accessToken = (new OAuthAccessToken())
            ->setTokenHash(hash('sha256', $plainAccessToken))
            ->setTokenPrefix(mb_substr($plainAccessToken, 0, 24))
            ->setGrantId($grantId)
            ->setClient($client)
            ->setUser($user)
            ->setAccessUrlId($accessUrlId)
            ->setScope($scope)
            ->setResource($resource)
            ->setCreatedAt($now)
            ->setExpiresAt((clone $now)->modify('+'.self::ACCESS_TOKEN_TTL_SECONDS.' seconds'))
        ;
        $this->entityManager->persist($accessToken);

        $plainRefreshToken = self::REFRESH_TOKEN_PREFIX.$this->randomSecret();
        $newAbsoluteExpiresAt = $absoluteExpiresAt ?? (clone $consentedAt)->modify('+'.self::REFRESH_TOKEN_ABSOLUTE_TTL_SECONDS.' seconds');
        $slidingExpiresAt = (clone $now)->modify('+'.self::REFRESH_TOKEN_TTL_SECONDS.' seconds');
        $refreshExpiresAt = $slidingExpiresAt < $newAbsoluteExpiresAt ? $slidingExpiresAt : $newAbsoluteExpiresAt;

        $refreshToken = (new OAuthRefreshToken())
            ->setTokenHash(hash('sha256', $plainRefreshToken))
            ->setGrantId($grantId)
            ->setClient($client)
            ->setUser($user)
            ->setAccessUrlId($accessUrlId)
            ->setScope($scope)
            ->setResource($resource)
            ->setConsentedAt($consentedAt)
            ->setConsentIp($consentIp)
            ->setConsentUserAgent($consentUserAgent)
            ->setCreatedAt($now)
            ->setExpiresAt($refreshExpiresAt)
            ->setAbsoluteExpiresAt($newAbsoluteExpiresAt)
        ;
        $this->entityManager->persist($refreshToken);

        if (null !== $replaces) {
            $replaces->setReplacedBy($refreshToken);
        }

        $this->entityManager->flush();

        return [
            'access_token' => $plainAccessToken,
            'token_type' => 'Bearer',
            'expires_in' => self::ACCESS_TOKEN_TTL_SECONDS,
            'refresh_token' => $plainRefreshToken,
            'scope' => $scope ?? 'mcp',
        ];
    }

    private function randomSecret(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
