<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Update;

use Chamilo\CoreBundle\Service\Update\UpdateConfiguration;
use Chamilo\CoreBundle\Service\Update\UpdateManifestClient;
use Chamilo\CoreBundle\Service\Update\UpdatePackageDownloader;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

use const JSON_THROW_ON_ERROR;

final class UpdateRemoteSourceSecurityTest extends TestCase
{
    public function testProductionManifestRejectsExternalOriginBeforeRequest(): void
    {
        $httpClient = new MockHttpClient(static function (): MockResponse {
            self::fail('HTTP request should not be executed for an external manifest origin.');
        });
        $client = new UpdateManifestClient($httpClient, new UpdateConfiguration('prod'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('official update origin');

        $client->load('https://example.org/latest-stable.json');
    }

    public function testProductionPackageRejectsExternalOriginBeforeRequest(): void
    {
        $httpClient = new MockHttpClient(static function (): MockResponse {
            self::fail('HTTP request should not be executed for an external package origin.');
        });
        $downloader = new UpdatePackageDownloader($httpClient, sys_get_temp_dir(), new UpdateConfiguration('prod'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('official update origin');

        $downloader->download('https://example.org/chamilo-3.0.1.zip');
    }

    public function testOfficialManifestRequestDoesNotFollowRedirects(): void
    {
        $manifest = json_encode([
            'channel' => 'stable',
            'version' => '3.0.1',
            'released_at' => '2026-09-05T22:33:19+00:00',
            'package' => [
                'url' => 'https://updates.chamilo.org/assets/chamilo-3.0.1.zip',
                'sha256' => str_repeat('a', 64),
            ],
        ], JSON_THROW_ON_ERROR);

        $httpClient = new MockHttpClient(static function (string $method, string $url, array $options) use ($manifest): MockResponse {
            self::assertSame('GET', $method);
            self::assertSame('https://updates.chamilo.org/latest-stable.json', $url);
            self::assertSame(0, $options['max_redirects']);

            return new MockResponse($manifest);
        });
        $client = new UpdateManifestClient($httpClient, new UpdateConfiguration('prod'));

        $result = $client->load('https://updates.chamilo.org/latest-stable.json');

        self::assertSame('3.0.1', $result->getVersion());
    }

    public function testOfficialPackageRequestDoesNotFollowRedirects(): void
    {
        $targetDirectory = sys_get_temp_dir().'/chamilo-update-security-'.bin2hex(random_bytes(6));

        try {
            $httpClient = new MockHttpClient(static function (string $method, string $url, array $options): MockResponse {
                self::assertSame('GET', $method);
                self::assertSame('https://updates.chamilo.org/assets/chamilo-3.0.1.zip', $url);
                self::assertSame(0, $options['max_redirects']);

                return new MockResponse('package-content');
            });
            $downloader = new UpdatePackageDownloader($httpClient, sys_get_temp_dir(), new UpdateConfiguration('prod'));

            $packagePath = $downloader->download(
                'https://updates.chamilo.org/assets/chamilo-3.0.1.zip',
                $targetDirectory,
            );

            self::assertSame('package-content', file_get_contents($packagePath));
        } finally {
            if (is_dir($targetDirectory)) {
                foreach (glob($targetDirectory.'/*') ?: [] as $file) {
                    @unlink($file);
                }
                @rmdir($targetDirectory);
            }
        }
    }
}
