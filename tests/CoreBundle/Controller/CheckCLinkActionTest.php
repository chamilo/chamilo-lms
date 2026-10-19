<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Controller\Api\CheckCLinkAction;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

use const CURLOPT_FOLLOWLOCATION;
use const CURLOPT_NOPROXY;
use const CURLOPT_PROXY;
use const CURLOPT_RESOLVE;
use const CURLOPT_URL;

final class CheckCLinkActionTest extends TestCase
{
    /**
     * DNS rebinding must not allow internal targets through the first validation step.
     */
    public function testRejectsPrivateAndInvalidTargets(): void
    {
        foreach ([
            'http://127.0.0.1/',
            'http://10.1.2.3/',
            'http://169.254.169.254/latest/meta-data/',
            'http://100.64.1.1/',
            'http://[::1]/',
            'http://[::ffff:127.0.0.1]/',
            'http://localhost/',
            'https://user:password@example.com/',
            'file:///etc/passwd',
            'http://8.8.8.8:0/',
            'http://exämple.com/',
            'http://example.com\\\@127.0.0.1/',
        ] as $url) {
            self::assertNull($this->validate($url), $url);
        }
    }

    public function testAcceptsPublicIpLiteralsWithoutAnotherDnsLookup(): void
    {
        self::assertSame(
            ['url' => 'https://8.8.8.8:8443/test', 'host' => '8.8.8.8', 'port' => 8443, 'ip' => '8.8.8.8', 'literal' => true],
            $this->validate('https://8.8.8.8:8443/test'),
        );

        self::assertSame(
            ['url' => 'https://[2606:4700:4700::1111]/', 'host' => '2606:4700:4700::1111', 'port' => 443, 'ip' => '2606:4700:4700::1111', 'literal' => true],
            $this->validate('https://[2606:4700:4700::1111]/'),
        );
    }

    /**
     * The original hostname must be retained for Host/SNI and TLS verification,
     * but cURL must connect to the checked IP, never resolve the name again.
     */
    public function testPinsHostnameAndPortAndDisablesProxies(): void
    {
        if (!\extension_loaded('curl')) {
            self::markTestSkipped('Requires ext-curl.');
        }

        $options = $this->curlOptions([
            'url' => 'https://example.com:8443/path',
            'host' => 'example.com',
            'port' => 8443,
            'ip' => '8.8.8.8',
            'literal' => false,
        ]);

        self::assertSame('https://example.com:8443/path', $options[CURLOPT_URL]);
        self::assertSame(['example.com:8443:8.8.8.8'], $options[CURLOPT_RESOLVE]);
        self::assertFalse($options[CURLOPT_FOLLOWLOCATION]);
        self::assertSame('', $options[CURLOPT_PROXY]);
        self::assertSame('*', $options[CURLOPT_NOPROXY]);
    }

    public function testPinsIpv6DnsAnswersWithCurlBracketSyntax(): void
    {
        if (!\extension_loaded('curl')) {
            self::markTestSkipped('Requires ext-curl.');
        }

        $options = $this->curlOptions([
            'url' => 'https://example.com/',
            'host' => 'example.com',
            'port' => 443,
            'ip' => '2606:4700:4700::1111',
            'literal' => false,
        ]);

        self::assertSame(['example.com:443:[2606:4700:4700::1111]'], $options[CURLOPT_RESOLVE]);
    }

    private function validate(string $url): ?array
    {
        $method = new ReflectionMethod(CheckCLinkAction::class, 'normalizeAndValidateUrl');

        return $method->invoke(new CheckCLinkAction(), $url);
    }

    private function curlOptions(array $target): array
    {
        $method = new ReflectionMethod(CheckCLinkAction::class, 'createCurlOptions');

        return $method->invoke(new CheckCLinkAction(), $target);
    }
}
