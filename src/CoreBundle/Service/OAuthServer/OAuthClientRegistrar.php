<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\OAuthServer;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\OAuthClient;
use Chamilo\CoreBundle\Exception\OAuthServer\OAuthException;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

/**
 * Dynamic Client Registration (RFC 7591). Write path only — deliberately
 * separate from OAuthClientResolver so the token endpoint's read/auth path
 * never touches client creation.
 */
final readonly class OAuthClientRegistrar
{
    public const string CLIENT_ID_PREFIX = 'chamilo_oauth_client_';
    public const int MAX_REDIRECT_URIS = 5;
    private const int MAX_URI_LENGTH = 512;
    private const array ALLOWED_GRANT_TYPES = ['authorization_code', 'refresh_token'];
    private const array ALLOWED_RESPONSE_TYPES = ['code'];
    private const array ALLOWED_AUTH_METHODS = ['none', 'client_secret_post', 'client_secret_basic'];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private AccessUrlHelper $accessUrlHelper,
    ) {}

    /**
     * @param array<string, mixed> $metadata
     *
     * @return array<string, mixed>
     */
    public function register(array $metadata, string $clientIp): array
    {
        $redirectUris = $this->validateRedirectUris($metadata['redirect_uris'] ?? null);
        $grantTypes = $this->validateSubset($metadata['grant_types'] ?? null, self::ALLOWED_GRANT_TYPES, self::ALLOWED_GRANT_TYPES);
        $responseTypes = $this->validateSubset($metadata['response_types'] ?? null, self::ALLOWED_RESPONSE_TYPES, self::ALLOWED_RESPONSE_TYPES);
        $authMethod = $this->validateAuthMethod($metadata['token_endpoint_auth_method'] ?? 'none');

        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (!$accessUrl instanceof AccessUrl || null === $accessUrl->getId()) {
            throw new RuntimeException('The current access URL could not be resolved.');
        }

        $now = new DateTime();
        $clientId = self::CLIENT_ID_PREFIX.$this->randomToken();

        $client = (new OAuthClient())
            ->setClientId($clientId)
            ->setTokenEndpointAuthMethod($authMethod)
            ->setClientName($this->truncate($metadata['client_name'] ?? null, 255))
            ->setClientUri($this->truncate($metadata['client_uri'] ?? null, self::MAX_URI_LENGTH))
            ->setLogoUri($this->truncate($metadata['logo_uri'] ?? null, self::MAX_URI_LENGTH))
            ->setPolicyUri($this->truncate($metadata['policy_uri'] ?? null, self::MAX_URI_LENGTH))
            ->setTosUri($this->truncate($metadata['tos_uri'] ?? null, self::MAX_URI_LENGTH))
            ->setSoftwareId($this->truncate($metadata['software_id'] ?? null, 255))
            ->setSoftwareVersion($this->truncate($metadata['software_version'] ?? null, 64))
            ->setRedirectUris($redirectUris)
            ->setGrantTypes($grantTypes)
            ->setResponseTypes($responseTypes)
            ->setScope('mcp')
            ->setAccessUrlId((int) $accessUrl->getId())
            ->setRegistrationIp($clientIp)
            ->setCreatedAt($now)
        ;

        $plainSecret = null;
        if ('none' !== $authMethod) {
            $plainSecret = $this->randomToken();
            $client
                ->setClientSecretHash(hash('sha256', $plainSecret))
                ->setClientSecretPrefix(mb_substr($plainSecret, 0, 12))
            ;
        }

        $this->entityManager->persist($client);
        $this->entityManager->flush();

        $response = [
            'client_id' => $clientId,
            'client_id_issued_at' => $now->getTimestamp(),
            'redirect_uris' => $redirectUris,
            'grant_types' => $grantTypes,
            'response_types' => $responseTypes,
            'token_endpoint_auth_method' => $authMethod,
            'scope' => 'mcp',
        ];

        foreach (['client_name', 'client_uri', 'logo_uri', 'policy_uri', 'tos_uri', 'software_id', 'software_version'] as $field) {
            if (isset($metadata[$field])) {
                $response[$field] = $metadata[$field];
            }
        }

        if (null !== $plainSecret) {
            // Returned once, at registration time only — there is no client
            // configuration endpoint (RFC 7592) to retrieve it again later.
            $response['client_secret'] = $plainSecret;
            $response['client_secret_expires_at'] = 0;
        }

        return $response;
    }

    /**
     * @return array<int, string>
     */
    private function validateRedirectUris(mixed $value): array
    {
        if (!\is_array($value) || [] === $value) {
            throw OAuthException::invalidClientMetadata('redirect_uris is required and must be a non-empty array.');
        }

        if (\count($value) > self::MAX_REDIRECT_URIS) {
            throw OAuthException::invalidClientMetadata(\sprintf('A maximum of %d redirect_uris is allowed.', self::MAX_REDIRECT_URIS));
        }

        $result = [];
        foreach ($value as $uri) {
            if (!\is_string($uri) || '' === $uri || mb_strlen($uri) > self::MAX_URI_LENGTH) {
                throw OAuthException::invalidRedirectUri('Each redirect_uri must be a non-empty string.');
            }

            $parts = parse_url($uri);
            if (false === $parts || !isset($parts['scheme'], $parts['host'])) {
                throw OAuthException::invalidRedirectUri(\sprintf('"%s" is not a valid absolute URI.', $uri));
            }

            if (isset($parts['fragment'])) {
                throw OAuthException::invalidRedirectUri('redirect_uri must not contain a fragment.');
            }

            $isHttps = 'https' === $parts['scheme'];
            $isLoopback = 'http' === $parts['scheme']
                && \in_array($parts['host'], ['localhost', '127.0.0.1', '::1'], true);

            if (!$isHttps && !$isLoopback) {
                throw OAuthException::invalidRedirectUri('redirect_uri must use https, or http restricted to localhost/127.0.0.1/[::1].');
            }

            $result[] = $uri;
        }

        return $result;
    }

    /**
     * @param array<int, string> $allowed
     * @param array<int, string> $default
     *
     * @return array<int, string>
     */
    private function validateSubset(mixed $value, array $allowed, array $default): array
    {
        if (null === $value) {
            return $default;
        }

        if (!\is_array($value) || [] === $value) {
            throw OAuthException::invalidClientMetadata('Invalid metadata array.');
        }

        foreach ($value as $item) {
            if (!\is_string($item) || !\in_array($item, $allowed, true)) {
                throw OAuthException::invalidClientMetadata(\sprintf('"%s" is not supported.', (string) $item));
            }
        }

        return array_values(array_unique($value));
    }

    private function validateAuthMethod(mixed $value): string
    {
        if (!\is_string($value) || !\in_array($value, self::ALLOWED_AUTH_METHODS, true)) {
            throw OAuthException::invalidClientMetadata('Unsupported token_endpoint_auth_method.');
        }

        return $value;
    }

    private function truncate(mixed $value, int $maxLength): ?string
    {
        if (!\is_string($value) || '' === $value) {
            return null;
        }

        return mb_substr($value, 0, $maxLength);
    }

    private function randomToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
