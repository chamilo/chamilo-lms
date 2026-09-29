<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Push;

use Chamilo\CoreBundle\Entity\MobilePushInstallation;
use Chamilo\CoreBundle\Push\FcmV1MobilePushProvider;
use Chamilo\CoreBundle\Push\MobilePushNotification;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

use const JSON_THROW_ON_ERROR;

final class FcmV1MobilePushProviderTest extends TestCase
{
    private ?string $credentialsFile = null;

    protected function tearDown(): void
    {
        unset(
            $_ENV['CHAMILO_MOBILE_FCM_SERVICE_ACCOUNT'],
            $_SERVER['CHAMILO_MOBILE_FCM_SERVICE_ACCOUNT'],
        );

        if (null !== $this->credentialsFile && is_file($this->credentialsFile)) {
            unlink($this->credentialsFile);
        }

        parent::tearDown();
    }

    public function testUsesMessageContentAndChamiloNotificationIcon(): void
    {
        $this->credentialsFile = tempnam(sys_get_temp_dir(), 'chamilo-fcm-') ?: null;
        self::assertNotNull($this->credentialsFile);

        file_put_contents($this->credentialsFile, json_encode([
            'project_id' => 'chamilo-test1',
            'client_email' => 'push@example.test',
            'private_key' => "-----BEGIN PRIVATE KEY-----\ntest\n-----END PRIVATE KEY-----",
        ], JSON_THROW_ON_ERROR));
        $_ENV['CHAMILO_MOBILE_FCM_SERVICE_ACCOUNT'] = $this->credentialsFile;

        $installation = $this->createMock(MobilePushInstallation::class);
        $installation->method('getToken')->willReturn('device-token');
        $installation->method('getInstallationId')->willReturn('11111111-1111-4111-8111-111111111111');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->expects(self::once())
            ->method('request')
            ->with(
                'POST',
                'https://fcm.googleapis.com/v1/projects/chamilo-test1/messages:send',
                self::callback(static function (array $options): bool {
                    $message = $options['json']['message'] ?? [];

                    return 'oauth-token' === ($options['auth_bearer'] ?? null)
                        && 'device-token' === ($message['token'] ?? null)
                        && 'Project update' === ($message['notification']['title'] ?? null)
                        && 'Ada Lovelace · Hello from Chamilo.' === ($message['notification']['body'] ?? null)
                        && '42' === ($message['data']['messageId'] ?? null)
                        && '11111111-1111-4111-8111-111111111111' === ($message['data']['installationId'] ?? null)
                        && 'ic_stat_chamilo' === ($message['android']['notification']['icon'] ?? null);
                })
            )
            ->willReturn($response)
        ;

        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->willReturn('oauth-token');

        $provider = new FcmV1MobilePushProvider(
            $httpClient,
            $cache,
            $this->createMock(LoggerInterface::class),
        );
        $delivery = $provider->send(
            $installation,
            new MobilePushNotification(42, 'Project update', 'Ada Lovelace · Hello from Chamilo.'),
        );

        self::assertTrue($delivery->delivered);
        self::assertFalse($delivery->invalidToken);
    }
}
