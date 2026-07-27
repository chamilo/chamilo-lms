<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Push;

use Chamilo\CoreBundle\Entity\MobilePushInstallation;
use Firebase\JWT\JWT;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

use const FILTER_VALIDATE_EMAIL;

final readonly class FcmV1MobilePushProvider implements MobilePushProviderInterface
{
    private const string CREDENTIALS_ENV = 'CHAMILO_MOBILE_FCM_SERVICE_ACCOUNT';
    private const string OAUTH_TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const string OAUTH_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
        private LoggerInterface $logger,
    ) {}

    public function isConfigured(): bool
    {
        $path = $this->getCredentialsPath();

        return '' !== $path && is_file($path) && is_readable($path);
    }

    public function supports(string $platform): bool
    {
        return 'android' === $platform;
    }

    public function send(
        MobilePushInstallation $installation,
        int $messageId
    ): MobilePushDelivery {
        try {
            $credentials = $this->loadCredentials();
            $accessToken = $this->getAccessToken($credentials);
            $projectId = $credentials['project_id'];

            $response = $this->httpClient->request(
                'POST',
                'https://fcm.googleapis.com/v1/projects/'.rawurlencode($projectId).'/messages:send',
                [
                    'auth_bearer' => $accessToken,
                    'json' => [
                        'message' => [
                            'token' => $installation->getToken(),
                            'notification' => [
                                'title' => 'Chamilo',
                                'body' => 'You have a new message.',
                            ],
                            'data' => [
                                'type' => 'message',
                                'messageId' => (string) $messageId,
                                'installationId' => $installation->getInstallationId(),
                            ],
                            'android' => [
                                'priority' => 'high',
                                'notification' => [
                                    'sound' => 'default',
                                ],
                            ],
                        ],
                    ],
                ]
            );

            $statusCode = $response->getStatusCode();

            if ($statusCode >= 200 && $statusCode < 300) {
                return new MobilePushDelivery(true);
            }

            $payload = $response->toArray(false);
            $invalidToken = $this->isUnregisteredToken($payload);

            $this->logger->warning('FCM mobile push delivery failed.', [
                'status_code' => $statusCode,
                'installation_id' => $installation->getInstallationId(),
                'invalid_token' => $invalidToken,
            ]);

            return new MobilePushDelivery(false, $invalidToken);
        } catch (Throwable $exception) {
            $this->logger->warning('FCM mobile push delivery raised an exception.', [
                'installation_id' => $installation->getInstallationId(),
                'exception' => $exception::class,
            ]);

            return new MobilePushDelivery(false);
        }
    }

    /**
     * @return array{project_id: string, client_email: string, private_key: string}
     */
    private function loadCredentials(): array
    {
        $path = $this->getCredentialsPath();
        $json = '' !== $path ? file_get_contents($path) : false;
        $credentials = false !== $json ? json_decode($json, true) : null;

        if (!\is_array($credentials)) {
            throw new RuntimeException('The FCM service account file is invalid.');
        }

        $projectId = trim((string) ($credentials['project_id'] ?? ''));
        $clientEmail = trim((string) ($credentials['client_email'] ?? ''));
        $privateKey = (string) ($credentials['private_key'] ?? '');

        if (
            !preg_match('/^[a-z0-9][a-z0-9-]{3,60}[a-z0-9]$/', $projectId)
            || false === filter_var($clientEmail, FILTER_VALIDATE_EMAIL)
            || !str_contains($privateKey, 'BEGIN PRIVATE KEY')
        ) {
            throw new RuntimeException('The FCM service account is incomplete.');
        }

        return [
            'project_id' => $projectId,
            'client_email' => $clientEmail,
            'private_key' => $privateKey,
        ];
    }

    /**
     * @param array{project_id: string, client_email: string, private_key: string} $credentials
     */
    private function getAccessToken(array $credentials): string
    {
        $cacheKey = 'chamilo.mobile.fcm.'.hash('sha256', $credentials['client_email']);

        return $this->cache->get(
            $cacheKey,
            function (ItemInterface $item) use ($credentials): string {
                $now = time();
                $assertion = JWT::encode(
                    [
                        'iss' => $credentials['client_email'],
                        'sub' => $credentials['client_email'],
                        'aud' => self::OAUTH_TOKEN_URL,
                        'scope' => self::OAUTH_SCOPE,
                        'iat' => $now,
                        'exp' => $now + 3600,
                    ],
                    $credentials['private_key'],
                    'RS256'
                );

                $response = $this->httpClient->request('POST', self::OAUTH_TOKEN_URL, [
                    'body' => [
                        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                        'assertion' => $assertion,
                    ],
                ]);
                $payload = $response->toArray();
                $accessToken = $payload['access_token'] ?? null;
                $expiresIn = (int) ($payload['expires_in'] ?? 3600);

                if (!\is_string($accessToken) || '' === $accessToken) {
                    throw new RuntimeException('FCM OAuth did not return an access token.');
                }

                $item->expiresAfter(max(60, $expiresIn - 120));

                return $accessToken;
            }
        );
    }

    private function getCredentialsPath(): string
    {
        return trim((string) ($_SERVER[self::CREDENTIALS_ENV] ?? $_ENV[self::CREDENTIALS_ENV] ?? ''));
    }

    private function isUnregisteredToken(array $payload): bool
    {
        if ('UNREGISTERED' === ($payload['error']['status'] ?? null)) {
            return true;
        }

        foreach ($payload['error']['details'] ?? [] as $detail) {
            if (\is_array($detail) && 'UNREGISTERED' === ($detail['errorCode'] ?? null)) {
                return true;
            }
        }

        return false;
    }
}
