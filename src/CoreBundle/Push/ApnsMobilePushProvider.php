<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Push;

use Chamilo\CoreBundle\Entity\MobilePushInstallation;
use Firebase\JWT\JWT;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

#[AutoconfigureTag('chamilo.mobile_push_provider')]
final readonly class ApnsMobilePushProvider implements MobilePushProviderInterface
{
    private const string PRIVATE_KEY_FILE_ENV = 'CHAMILO_MOBILE_APNS_KEY_FILE';
    private const string KEY_ID_ENV = 'CHAMILO_MOBILE_APNS_KEY_ID';
    private const string TEAM_ID_ENV = 'CHAMILO_MOBILE_APNS_TEAM_ID';
    private const string BUNDLE_ID_ENV = 'CHAMILO_MOBILE_APNS_BUNDLE_ID';
    private const string ENVIRONMENT_ENV = 'CHAMILO_MOBILE_APNS_ENVIRONMENT';

    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
        private LoggerInterface $logger,
    ) {}

    public function isConfigured(): bool
    {
        $config = $this->getConfiguration();

        return '' !== $config['keyFile']
            && is_file($config['keyFile'])
            && is_readable($config['keyFile'])
            && 1 === preg_match('/^[A-Z0-9]{10}$/', $config['keyId'])
            && 1 === preg_match('/^[A-Z0-9]{10}$/', $config['teamId'])
            && '' !== $config['bundleId']
            && \in_array($config['environment'], ['sandbox', 'production'], true);
    }

    public function supports(string $platform): bool
    {
        return 'ios' === $platform;
    }

    public function send(
        MobilePushInstallation $installation,
        int $messageId
    ): MobilePushDelivery {
        try {
            $config = $this->getConfiguration();

            if (!$this->isConfigured()) {
                throw new RuntimeException('APNs mobile push provider is not configured.');
            }

            $providerToken = $this->getProviderToken($config);
            $endpoint = 'production' === $config['environment']
                ? 'https://api.push.apple.com'
                : 'https://api.sandbox.push.apple.com';

            $response = $this->httpClient->request(
                'POST',
                $endpoint.'/3/device/'.rawurlencode($installation->getToken()),
                [
                    'headers' => [
                        'authorization' => 'bearer '.$providerToken,
                        'apns-topic' => $config['bundleId'],
                        'apns-push-type' => 'alert',
                        'apns-priority' => '10',
                    ],
                    'json' => [
                        'aps' => [
                            'alert' => [
                                'title' => 'Chamilo',
                                'body' => 'You have a new message.',
                            ],
                            'sound' => 'default',
                        ],
                        'type' => 'message',
                        'messageId' => (string) $messageId,
                        'installationId' => $installation->getInstallationId(),
                    ],
                ]
            );

            $statusCode = $response->getStatusCode();

            if (200 === $statusCode) {
                return new MobilePushDelivery(true);
            }

            $payload = json_decode($response->getContent(false), true);
            $reason = \is_array($payload) ? (string) ($payload['reason'] ?? '') : '';
            $invalidToken = $this->isInvalidToken($statusCode, $reason);

            $this->logger->warning('APNs mobile push delivery failed.', [
                'status_code' => $statusCode,
                'reason' => $reason,
                'installation_id' => $installation->getInstallationId(),
                'invalid_token' => $invalidToken,
            ]);

            return new MobilePushDelivery(false, $invalidToken);
        } catch (Throwable $exception) {
            $this->logger->warning('APNs mobile push delivery raised an exception.', [
                'installation_id' => $installation->getInstallationId(),
                'exception' => $exception::class,
            ]);

            return new MobilePushDelivery(false);
        }
    }

    /**
     * @param array{keyFile: string, keyId: string, teamId: string, bundleId: string, environment: string} $config
     */
    private function getProviderToken(array $config): string
    {
        $cacheKey = 'chamilo.mobile.apns.'.hash('sha256', $config['teamId'].':'.$config['keyId']);

        return $this->cache->get(
            $cacheKey,
            function (ItemInterface $item) use ($config): string {
                $privateKey = file_get_contents($config['keyFile']);

                if (false === $privateKey || !str_contains($privateKey, 'BEGIN PRIVATE KEY')) {
                    throw new RuntimeException('The APNs provider private key is invalid.');
                }

                $item->expiresAfter(50 * 60);

                return JWT::encode(
                    [
                        'iss' => $config['teamId'],
                        'iat' => time(),
                    ],
                    $privateKey,
                    'ES256',
                    $config['keyId']
                );
            }
        );
    }

    /**
     * @return array{keyFile: string, keyId: string, teamId: string, bundleId: string, environment: string}
     */
    private function getConfiguration(): array
    {
        return [
            'keyFile' => $this->env(self::PRIVATE_KEY_FILE_ENV),
            'keyId' => $this->env(self::KEY_ID_ENV),
            'teamId' => $this->env(self::TEAM_ID_ENV),
            'bundleId' => $this->env(self::BUNDLE_ID_ENV),
            'environment' => strtolower($this->env(self::ENVIRONMENT_ENV)),
        ];
    }

    private function env(string $name): string
    {
        return trim((string) ($_SERVER[$name] ?? $_ENV[$name] ?? ''));
    }

    private function isInvalidToken(int $statusCode, string $reason): bool
    {
        if (410 === $statusCode) {
            return true;
        }

        return \in_array(
            $reason,
            ['BadDeviceToken', 'DeviceTokenNotForTopic', 'Unregistered'],
            true
        );
    }
}
