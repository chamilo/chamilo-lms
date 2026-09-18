<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Push;

use Chamilo\CoreBundle\Entity\MobilePushInstallation;
use Chamilo\CoreBundle\Push\ApnsMobilePushProvider;
use Chamilo\CoreBundle\Push\MobilePushNotification;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class ApnsMobilePushProviderTest extends TestCase
{
    private ?string $keyFile = null;

    protected function tearDown(): void
    {
        foreach ([
            'CHAMILO_MOBILE_APNS_KEY_FILE',
            'CHAMILO_MOBILE_APNS_KEY_ID',
            'CHAMILO_MOBILE_APNS_TEAM_ID',
            'CHAMILO_MOBILE_APNS_BUNDLE_ID',
            'CHAMILO_MOBILE_APNS_ENVIRONMENT',
        ] as $name) {
            unset($_ENV[$name], $_SERVER[$name]);
        }

        if (null !== $this->keyFile && is_file($this->keyFile)) {
            unlink($this->keyFile);
        }

        parent::tearDown();
    }

    public function testSupportsOnlyIosInstallations(): void
    {
        $provider = new ApnsMobilePushProvider(
            $this->createMock(HttpClientInterface::class),
            $this->createMock(CacheInterface::class),
            $this->createMock(LoggerInterface::class),
        );

        self::assertTrue($provider->supports('ios'));
        self::assertFalse($provider->supports('android'));
    }

    public function testUsesMessageContentForTheApnsAlert(): void
    {
        $this->keyFile = tempnam(sys_get_temp_dir(), 'chamilo-apns-') ?: null;
        self::assertNotNull($this->keyFile);
        file_put_contents($this->keyFile, "-----BEGIN PRIVATE KEY-----\ntest\n-----END PRIVATE KEY-----");

        $_ENV['CHAMILO_MOBILE_APNS_KEY_FILE'] = $this->keyFile;
        $_ENV['CHAMILO_MOBILE_APNS_KEY_ID'] = '56AB2WJZ6N';
        $_ENV['CHAMILO_MOBILE_APNS_TEAM_ID'] = 'WX8U2C8TW6';
        $_ENV['CHAMILO_MOBILE_APNS_BUNDLE_ID'] = 'org.chamilo.mobile';
        $_ENV['CHAMILO_MOBILE_APNS_ENVIRONMENT'] = 'sandbox';

        $installation = $this->createMock(MobilePushInstallation::class);
        $installation->method('getToken')->willReturn('apns-device-token');
        $installation->method('getInstallationId')->willReturn('11111111-1111-4111-8111-111111111111');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->expects(self::once())
            ->method('request')
            ->with(
                'POST',
                'https://api.sandbox.push.apple.com/3/device/apns-device-token',
                self::callback(static function (array $options): bool {
                    $payload = $options['json'] ?? [];

                    return 'Project update' === ($payload['aps']['alert']['title'] ?? null)
                        && 'Ada Lovelace · Hello from Chamilo.' === ($payload['aps']['alert']['body'] ?? null)
                        && '42' === ($payload['messageId'] ?? null)
                        && '11111111-1111-4111-8111-111111111111' === ($payload['installationId'] ?? null)
                        && 'org.chamilo.mobile' === ($options['headers']['apns-topic'] ?? null);
                })
            )
            ->willReturn($response)
        ;

        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->willReturn('provider-token');

        $provider = new ApnsMobilePushProvider(
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
